<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Outgoing mail, and the failure it makes invisible.
 *
 * `MAIL_MAILER=log` is the shipped default. It writes every message into
 * `storage/logs/laravel.log` and reports success to the application, which
 * means an enquiry the sales desk is never told about looks exactly like one
 * that was delivered. On a development machine that is convenient; on the
 * server it is the company losing leads and not knowing.
 *
 * There is nothing the application can do to fix it for itself — the
 * credentials are somebody's to buy and type in. So what is held here is that
 * it cannot stay hidden: the dashboard says so while it is the case, and one
 * command sends a real message and reports what the mail server said.
 */
class MailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_says_so_while_mail_is_going_to_the_log(): void
    {
        config(['mail.default' => 'log']);

        $this->actingAs($this->makeAdmin())
            ->get('/admin')
            ->assertOk()
            ->assertSee(__('admin.alerts.mail_is_logged'));
    }

    public function test_the_banner_goes_away_once_mail_is_configured(): void
    {
        config(['mail.default' => 'smtp']);

        $this->actingAs($this->makeAdmin())
            ->get('/admin')
            ->assertOk()
            ->assertDontSee(__('admin.alerts.mail_is_logged'));
    }

    /**
     * The same treatment for the other channel: switched off, or on with no
     * number, sends nothing and says nothing.
     */
    public function test_the_dashboard_says_so_while_the_sms_channel_is_silent(): void
    {
        // One account for both requests: signing in as a *different* user
        // mid-test invalidates the session, which reads as a logged-out 302.
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee(__('admin.alerts.sms_is_silent'));

        \App\Support\Sms::store([
            'sms.enabled' => '1',
            'sms.provider' => 'console',
            'sms.sales_mobile' => '09121234567',
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertDontSee(__('admin.alerts.sms_is_silent'));
    }

    /** The command sends for real, and says which mailer it used. */
    public function test_the_test_command_sends_a_message(): void
    {
        Mail::fake();

        $this->artisan('mail:test', ['to' => 'someone@example.com'])
            ->expectsOutputToContain('someone@example.com')
            ->assertSuccessful();

        Mail::assertSent(\App\Mail\TestMessage::class,
            fn ($mail) => $mail->hasTo('someone@example.com'));
    }

    /**
     * On a `log` mailer it warns instead of claiming a delivery, because
     * "sent" there means "written to a file on this machine".
     */
    public function test_the_command_does_not_claim_a_delivery_on_the_log_mailer(): void
    {
        config(['mail.default' => 'log']);
        Mail::fake();

        $this->artisan('mail:test', ['to' => 'someone@example.com'])
            ->expectsOutputToContain('nothing leaves this machine')
            ->assertSuccessful();
    }

    /**
     * A refused connection is the common failure, and the message the server
     * gave is the only useful thing about it. It is printed verbatim rather
     * than summarised, and the command exits non-zero so a deploy script can
     * tell.
     */
    public function test_a_failure_is_reported_verbatim_and_fails(): void
    {
        config(['mail.default' => 'smtp']);

        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('535 Authentication failed'));

        $this->artisan('mail:test', ['to' => 'someone@example.com'])
            ->expectsOutputToContain('535 Authentication failed')
            ->assertFailed();
    }

    /**
     * The From address never falls back to a placeholder.
     *
     * A live server ran with `MAIL_FROM_ADDRESS="hello@example.com"` — the
     * value straight out of Laravel's skeleton — and nothing complained,
     * because nothing was being sent yet. With SMTP configured it would have
     * been sending as a domain the account may not send as, which fails SPF and
     * DKIM alignment at the far end: the mail leaves, and lands in spam.
     */
    public function test_the_from_address_falls_back_to_the_company_not_a_placeholder(): void
    {
        // The config file rather than the resolved value: what `.env` says is
        // per-machine, and the fallback is the part this repository controls.
        $source = (string) file_get_contents(config_path('mail.php'));

        preg_match("/'address' => .*/", $source, $line);

        $this->assertNotEmpty($line, 'The from address is not where this test expects it.');
        $this->assertStringNotContainsString('example.com', $line[0]);
        $this->assertStringContainsString("env('SITE_EMAIL', 'info@artaleca.com')", $line[0]);
    }

    /** And where `.env` does carry a placeholder, the command says so. */
    public function test_the_command_warns_about_a_foreign_from_address(): void
    {
        config([
            'app.url' => 'https://artaleca.com',
            'mail.from.address' => 'hello@example.com',
            'mail.default' => 'smtp',
        ]);

        Mail::fake();

        $this->artisan('mail:test', ['to' => 'someone@example.com'])
            ->expectsOutputToContain('does not belong to this site')
            ->assertSuccessful();
    }

    public function test_the_command_is_quiet_about_an_address_at_the_site(): void
    {
        config([
            'app.url' => 'https://www.artaleca.com',
            'mail.from.address' => 'info@artaleca.com',
            'mail.default' => 'smtp',
        ]);

        Mail::fake();

        $this->artisan('mail:test', ['to' => 'someone@example.com'])
            ->doesntExpectOutputToContain('does not belong to this site')
            ->assertSuccessful();
    }

    /** Reading the panel's own notices is an administrator's business. */
    public function test_an_editor_does_not_see_the_configuration_notices(): void
    {
        $this->actingAs($this->makeAdmin(User::ROLE_EDITOR))
            ->get('/admin')
            ->assertOk()
            ->assertDontSee(__('admin.alerts.mail_is_logged'));
    }
}
