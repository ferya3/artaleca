<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Jobs\SendBaleAlert;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Models\User;
use App\Support\Bale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * A new enquiry, on the sales desk's phone.
 *
 * The email has always gone out, and has always arrived whenever somebody next
 * opened their inbox. What these tests hold is the second channel: that it is
 * configurable without touching a file, that the credential is not sitting in
 * the database in the clear, and — the part that matters most — that a
 * messenger which is down, blocked or misconfigured never costs the company
 * the enquiry it was meant to announce.
 */
class EnquiryAlertTest extends TestCase
{
    use RefreshDatabase;

    private function configureBale(): void
    {
        Bale::storeToken('123456:test-token');
        Setting::put('notifications.bale_chat_id', '-100200300', 'notifications', translatable: false);
        Setting::put('notifications.bale_enabled', true, 'notifications', translatable: false);
    }

    private function enquiry(array $overrides = []): ContactMessage
    {
        return ContactMessage::create(array_merge([
            'type' => 'quote',
            'name' => 'علی رضایی',
            'company' => 'ساختمانی پارس',
            'phone' => '09121234567',
            'email' => 'ali@example.com',
            'message' => 'برای پروژه‌ای در کرج نیاز داریم.',
            'quantity' => '۵۰۰ متر مکعب',
            'locale' => 'fa',
        ], $overrides));
    }

    // ── The credential ──────────────────────────────────────────────────

    /** The database is the thing that gets dumped, copied and restored. */
    public function test_the_token_is_not_stored_in_the_clear(): void
    {
        Bale::storeToken('123456:secret-token');

        $stored = Setting::map()['notifications.bale_token'];

        $this->assertIsString($stored);
        $this->assertStringNotContainsString('secret-token', $stored);
        $this->assertSame('123456:secret-token', Bale::token());
    }

    /** A database restored under a different APP_KEY must not break the panel. */
    public function test_an_undecryptable_token_reads_as_absent(): void
    {
        Setting::put('notifications.bale_token', 'not-actually-encrypted', 'notifications', translatable: false);

        $this->assertSame('', Bale::token());
        $this->assertFalse(Bale::configured());
        $this->assertFalse(Bale::enabled());
    }

    public function test_it_stays_off_until_all_three_pieces_are_present(): void
    {
        $this->assertFalse(Bale::enabled());

        Bale::storeToken('123456:test-token');
        $this->assertFalse(Bale::enabled(), 'A token alone is not a destination.');

        Setting::put('notifications.bale_chat_id', '-100200300', 'notifications', translatable: false);
        $this->assertFalse(Bale::enabled(), 'Configured is not the same as switched on.');

        Setting::put('notifications.bale_enabled', true, 'notifications', translatable: false);
        $this->assertTrue(Bale::enabled());
    }

    // ── The alert ───────────────────────────────────────────────────────

    public function test_submitting_the_quote_form_queues_an_alert(): void
    {
        Queue::fake();
        Mail::fake();

        $this->post('/fa/quote', [
            'name' => 'علی رضایی',
            'email' => 'ali@example.com',
            'message' => 'استعلام قیمت',
            'quantity' => '۵۰۰ متر مکعب',
            'consent' => '1',
            'started_at' => $this->humanFormToken(),
        ]);

        Queue::assertPushed(SendBaleAlert::class);
    }

    public function test_the_message_carries_what_the_desk_needs_to_act_on(): void
    {
        $this->configureBale();

        Http::fake(['*' => Http::response(['ok' => true, 'result' => []])]);

        (new SendBaleAlert($this->enquiry()->id))->handle();

        Http::assertSent(function ($request) {
            $text = $request['text'] ?? '';

            foreach (['علی رضایی', 'ساختمانی پارس', '09121234567', 'ali@example.com', '۵۰۰ متر مکعب'] as $needle) {
                if (! str_contains($text, $needle)) {
                    return false;
                }
            }

            return str_contains($request->url(), '/bot123456:test-token/sendMessage')
                && ($request['chat_id'] ?? null) === '-100200300';
        });
    }

    /** The alert links back to the record rather than repeating all of it. */
    public function test_the_message_links_to_the_enquiry_in_the_panel(): void
    {
        $this->configureBale();
        Http::fake(['*' => Http::response(['ok' => true])]);

        $enquiry = $this->enquiry();
        (new SendBaleAlert($enquiry->id))->handle();

        Http::assertSent(fn ($request) => str_contains(
            $request['text'] ?? '',
            route('admin.enquiries.show', $enquiry),
        ));
    }

    public function test_nothing_is_sent_while_the_channel_is_off(): void
    {
        Http::fake();

        (new SendBaleAlert($this->enquiry()->id))->handle();

        Http::assertNothingSent();
    }

    /** A deleted enquiry is not an error; the queue may simply be behind. */
    public function test_an_enquiry_that_no_longer_exists_is_not_an_error(): void
    {
        $this->configureBale();
        Http::fake();

        (new SendBaleAlert(99999))->handle();

        Http::assertNothingSent();
    }

    /** The whole point: a broken messenger must never break the form. */
    public function test_a_failing_messenger_never_costs_the_enquiry(): void
    {
        $this->configureBale();
        Mail::fake();

        Http::fake(['*' => Http::response(['ok' => false, 'description' => 'chat not found'], 400)]);

        $response = $this->post('/fa/quote', [
            'name' => 'علی رضایی',
            'email' => 'ali@example.com',
            'message' => 'استعلام قیمت',
            'quantity' => '۵۰۰ متر مکعب',
            'consent' => '1',
            'started_at' => $this->humanFormToken(),
        ]);

        $response->assertRedirect();
        $this->assertSame(1, ContactMessage::count());
    }

    // ── The panel ───────────────────────────────────────────────────────

    public function test_the_screen_is_reachable_and_never_echoes_the_token(): void
    {
        Bale::storeToken('123456:secret-token');

        $this->actingAs($this->makeAdmin())
            ->get('/admin/notifications')
            ->assertOk()
            ->assertSee(__('admin.notifications.title'))
            ->assertDontSee('secret-token');
    }

    /** Blank means "keep the one you have", not "delete it". */
    public function test_saving_without_a_token_keeps_the_saved_one(): void
    {
        $this->configureBale();

        $this->actingAs($this->makeAdmin())
            ->put('/admin/notifications', ['bale_chat_id' => '-999', 'bale_enabled' => '1'])
            ->assertRedirect();

        $this->assertSame('123456:test-token', Bale::token());
        $this->assertSame('-999', Bale::chatId());
    }

    public function test_the_token_can_be_cleared_deliberately(): void
    {
        $this->configureBale();

        $this->actingAs($this->makeAdmin())
            ->delete('/admin/notifications')
            ->assertRedirect();

        $this->assertFalse(Bale::configured());
        $this->assertFalse(Bale::enabled());
    }

    public function test_the_chat_finder_lists_the_groups_the_bot_can_see(): void
    {
        $this->configureBale();

        Http::fake(['*' => Http::response(['ok' => true, 'result' => [
            ['message' => ['chat' => ['id' => -100200300, 'title' => 'استعلام‌های آرتالکا']]],
            ['message' => ['chat' => ['id' => -100200300, 'title' => 'استعلام‌های آرتالکا']]],
            ['message' => ['chat' => ['id' => 55, 'first_name' => 'علی']]],
        ]])]);

        $this->actingAs($this->makeAdmin())
            ->post('/admin/notifications/chats')
            ->assertRedirect()
            ->assertSessionHas('chats', fn (array $chats) => count($chats) === 2
                && $chats[0]['title'] === 'استعلام‌های آرتالکا');
    }

    /**
     * The failure this screen exists to surface: the server cannot reach Bale
     * at all. Finding that out on the first real enquiry is the bad outcome.
     */
    public function test_the_test_button_reports_an_unreachable_service(): void
    {
        $this->configureBale();

        Http::fake(fn () => throw new ConnectionException('No route to host'));

        $this->actingAs($this->makeAdmin())
            ->post('/admin/notifications/test')
            ->assertRedirect()
            ->assertSessionHas('error', __('admin.notifications.unreachable'));
    }

    public function test_the_test_button_confirms_a_working_path(): void
    {
        $this->configureBale();

        Http::fake(['*' => Http::response(['ok' => true, 'result' => ['username' => 'ArtaLecaBot']])]);

        $this->actingAs($this->makeAdmin())
            ->post('/admin/notifications/test')
            ->assertRedirect()
            ->assertSessionHas('status', __('admin.notifications.test_sent'));
    }

    public function test_an_editor_cannot_reach_the_notification_settings(): void
    {
        $this->actingAs($this->makeAdmin(User::ROLE_EDITOR))
            ->get('/admin/notifications')
            ->assertForbidden();
    }
}
