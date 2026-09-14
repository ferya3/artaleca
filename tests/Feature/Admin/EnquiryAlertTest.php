<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Jobs\SendSmsAlert;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Models\User;
use App\Support\Mobile;
use App\Support\Sms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * A new enquiry, on the sales manager's phone.
 *
 * The email has always gone out, and has always arrived whenever somebody next
 * opened their inbox. What these tests hold is the second channel: that it is
 * configurable without touching a file, that the panel password is not sitting
 * in the database in the clear, and — the part that matters most — that an SMS
 * panel which is down, out of credit or misconfigured never costs the company
 * the enquiry it was meant to announce.
 */
class EnquiryAlertTest extends TestCase
{
    use RefreshDatabase;

    /** A configured panel: credentials, a sender line, and a number to ring. */
    private function configurePanel(array $overrides = []): void
    {
        Sms::store(array_merge([
            'sms.enabled' => '1',
            'sms.provider' => 'kavenegar',
            'sms.api_key' => 'test-api-key',
            'sms.sender' => '10004321',
            'sms.sales_mobile' => '09121234567',
        ], $overrides));
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

    private function submitQuote(): \Illuminate\Testing\TestResponse
    {
        return $this->post('/fa/quote', [
            'name' => 'علی رضایی',
            'email' => 'ali@example.com',
            // Required on this form: a quote is settled in a call, and this is
            // the number the alert below carries.
            'phone' => '09121234567',
            // Also required: a price is calculated from a grade and a volume.
            'product_id' => $this->makeProduct()->id,
            'message' => 'استعلام قیمت',
            'quantity' => '۵۰۰ متر مکعب',
            'consent' => '1',
            'started_at' => $this->humanFormToken(),
        ]);
    }

    // ── The number ──────────────────────────────────────────────────────

    /**
     * However it is typed, it reaches the panel in the one shape a panel takes.
     *
     * This is the field the company changes themselves, so it has to accept
     * what a person actually types — Persian digits, a country code, spaces.
     */
    public function test_the_sales_number_is_normalised_however_it_is_typed(): void
    {
        foreach (['09121234567', '۰۹۱۲۱۲۳۴۵۶۷', '+989121234567', '00989121234567', '0912 123 4567'] as $typed) {
            $this->assertSame('09121234567', Mobile::normalize($typed), "Failed on: {$typed}");
        }

        // And what is not a mobile number is refused rather than guessed at.
        foreach (['0212233445', '0912123456', 'not a number', ''] as $rubbish) {
            $this->assertNull(Mobile::normalize($rubbish), "Should have refused: {$rubbish}");
        }
    }

    public function test_the_number_is_stored_normalised_from_the_panel(): void
    {
        $this->actingAs($this->makeAdmin())->put('/admin/sms', [
            'provider' => 'console',
            'custom_method' => 'GET',
            'sales_mobile' => '۰۹۱۲ ۱۲۳ ۴۵۶۷',
            'enabled' => '1',
        ])->assertRedirect();

        $this->assertSame('09121234567', Sms::salesMobile());
    }

    public function test_a_number_that_is_not_a_mobile_is_refused(): void
    {
        $this->actingAs($this->makeAdmin())->put('/admin/sms', [
            'provider' => 'console',
            'custom_method' => 'GET',
            'sales_mobile' => '021 8888 0011',
        ])->assertSessionHasErrors('sales_mobile');
    }

    // ── The credential ──────────────────────────────────────────────────

    /** The database is the thing that gets dumped, copied and restored. */
    public function test_the_panel_password_is_not_stored_in_the_clear(): void
    {
        Sms::store(['sms.password' => 'secret-password']);

        $stored = Setting::map()['sms.password'];

        $this->assertIsString($stored);
        $this->assertStringNotContainsString('secret-password', $stored);
        $this->assertSame('secret-password', Sms::settings()['sms.password']);
    }

    /** A database restored under a different APP_KEY must not break the panel. */
    public function test_an_undecryptable_credential_reads_as_absent(): void
    {
        Setting::put('sms.password', 'not-actually-encrypted', 'sms', translatable: false);

        $this->assertSame('', Sms::settings()['sms.password']);
    }

    /** Blank means "keep the one you have", not "delete it". */
    public function test_saving_without_a_password_keeps_the_saved_one(): void
    {
        $this->configurePanel(['sms.provider' => 'melipayamak', 'sms.username' => 'arta', 'sms.password' => 'secret']);

        $this->actingAs($this->makeAdmin())->put('/admin/sms', [
            'provider' => 'melipayamak',
            'username' => 'arta',
            'password' => '',
            'custom_method' => 'GET',
            'sales_mobile' => '09121234567',
            'enabled' => '1',
        ])->assertRedirect();

        $this->assertSame('secret', Sms::settings()['sms.password']);
    }

    public function test_the_credentials_can_be_cleared_deliberately(): void
    {
        $this->configurePanel();

        $this->actingAs($this->makeAdmin())->delete('/admin/sms')->assertRedirect();

        $this->assertSame('', Sms::settings()['sms.api_key']);
        $this->assertFalse(Sms::enabled());
    }

    // ── The alert ───────────────────────────────────────────────────────

    public function test_submitting_the_quote_form_queues_an_alert(): void
    {
        Queue::fake();
        Mail::fake();

        $this->submitQuote();

        Queue::assertPushed(SendSmsAlert::class);
    }

    /**
     * What the message carries, and what it deliberately does not.
     *
     * A Persian SMS is 70 characters a part and every part is charged, so this
     * is the part someone acts on — who, and the number to call back — not a
     * copy of the enquiry. The panel and the email have the rest.
     */
    public function test_the_message_carries_what_the_manager_acts_on(): void
    {
        $this->configurePanel();

        Http::fake(['*' => Http::response(['return' => ['status' => 200]])]);

        (new SendSmsAlert($this->enquiry()->id))->handle();

        Http::assertSent(function ($request) {
            $text = (string) ($request['message'] ?? '');

            foreach (['استعلام قیمت جدید', 'علی رضایی', 'ساختمانی پارس', '09121234567', '۵۰۰ متر مکعب'] as $needle) {
                if (! str_contains($text, $needle)) {
                    return false;
                }
            }

            return ($request['receptor'] ?? null) === '09121234567'
                && str_contains($request->url(), 'api.kavenegar.com');
        });
    }

    public function test_the_message_stays_short_enough_to_be_worth_sending(): void
    {
        $text = SendSmsAlert::text($this->enquiry([
            'name' => str_repeat('ن', 120),
            'company' => str_repeat('ش', 120),
            'quantity' => str_repeat('۹', 80),
        ]));

        // Four parts of a Persian SMS is the ceiling this trimming is for; the
        // real thing is two.
        $this->assertLessThan(280, mb_strlen($text));
    }

    public function test_nothing_is_sent_while_the_channel_is_off(): void
    {
        $this->configurePanel(['sms.enabled' => '0']);
        Http::fake();

        (new SendSmsAlert($this->enquiry()->id))->handle();

        Http::assertNothingSent();
    }

    /** No number, no message — and no error either. */
    public function test_nothing_is_sent_without_a_sales_number(): void
    {
        $this->configurePanel(['sms.sales_mobile' => '']);
        Http::fake();

        (new SendSmsAlert($this->enquiry()->id))->handle();

        Http::assertNothingSent();
    }

    /**
     * An unconfigured panel is not a temporary failure, so it must not go
     * round the retry loop: there is nothing to retry against.
     */
    public function test_an_unconfigured_panel_is_not_retried(): void
    {
        Sms::store([
            'sms.enabled' => '1',
            'sms.provider' => 'kavenegar',
            'sms.sales_mobile' => '09121234567',
        ]);

        Http::fake();

        // No throw: a throw is what the queue reads as "try again".
        (new SendSmsAlert($this->enquiry()->id))->handle();

        Http::assertNothingSent();
    }

    /** A deleted enquiry is not an error; the queue may simply be behind. */
    public function test_an_enquiry_that_no_longer_exists_is_not_an_error(): void
    {
        $this->configurePanel();
        Http::fake();

        (new SendSmsAlert(99999))->handle();

        Http::assertNothingSent();
    }

    /** The whole point: a broken panel must never break the form. */
    public function test_a_failing_panel_never_costs_the_enquiry(): void
    {
        $this->configurePanel();
        Mail::fake();

        Http::fake(fn () => throw new ConnectionException('No route to host'));

        $this->submitQuote()->assertRedirect();

        $this->assertSame(1, ContactMessage::count());
    }

    // ── The panel screen ────────────────────────────────────────────────

    public function test_the_screen_is_reachable_and_never_echoes_a_credential(): void
    {
        $this->configurePanel(['sms.api_key' => 'super-secret-key']);

        $this->actingAs($this->makeAdmin())
            ->get('/admin/sms')
            ->assertOk()
            ->assertSee(__('admin.sms.title'))
            ->assertDontSee('super-secret-key');
    }

    /**
     * The failure this screen exists to surface: the server cannot reach the
     * panel at all. Finding that out on the first real enquiry is the bad
     * outcome, which is why the test button sends for real and reports back.
     */
    public function test_the_test_button_reports_an_unreachable_panel(): void
    {
        $this->configurePanel();

        Http::fake(fn () => throw new ConnectionException('No route to host'));

        $this->actingAs($this->makeAdmin())
            ->post('/admin/sms/test', ['mobile' => '09121234567'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_the_test_button_confirms_a_working_panel(): void
    {
        $this->configurePanel();

        Http::fake(['*' => Http::response(['return' => ['status' => 200]])]);

        $this->actingAs($this->makeAdmin())
            ->post('/admin/sms/test', ['mobile' => '09121234567'])
            ->assertRedirect()
            ->assertSessionHas('status', __('admin.sms.errors.sent'));
    }

    /** An unconfigured panel says so rather than reporting a send failure. */
    public function test_the_test_button_names_the_missing_setting(): void
    {
        Sms::store(['sms.enabled' => '1', 'sms.provider' => 'kavenegar']);

        $this->actingAs($this->makeAdmin())
            ->post('/admin/sms/test', ['mobile' => '09121234567'])
            ->assertRedirect()
            ->assertSessionHas('error', __('admin.sms.errors.no_api_key'));
    }

    public function test_an_editor_cannot_reach_the_sms_settings(): void
    {
        $this->actingAs($this->makeAdmin(User::ROLE_EDITOR))
            ->get('/admin/sms')
            ->assertForbidden();
    }

    // ── The custom panel, and the hole it would otherwise open ──────────

    /**
     * A settings field that becomes an outbound request is an SSRF, and the one
     * address worth naming in a test is the cloud metadata service: on a rented
     * server it hands out credentials to anything that can reach it.
     */
    public function test_a_custom_url_cannot_point_inside_the_server(): void
    {
        Sms::store([
            'sms.enabled' => '1',
            'sms.provider' => 'custom',
            'sms.custom_url' => 'http://169.254.169.254/latest/meta-data/',
            'sms.custom_method' => 'GET',
            'sms.sales_mobile' => '09121234567',
        ]);

        Http::fake();

        [$status] = Sms::send('09121234567', 'test');

        $this->assertSame(Sms::STATUS_FAILED, $status);
        Http::assertNothingSent();
    }

    public function test_a_custom_url_must_be_http(): void
    {
        $this->actingAs($this->makeAdmin())->put('/admin/sms', [
            'provider' => 'custom',
            'custom_url' => 'file:///etc/passwd',
            'custom_method' => 'GET',
        ])->assertSessionHasErrors('custom_url');
    }
}
