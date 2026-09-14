<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\TestMessage;
use App\Support\Contact;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Send one real email and say exactly what happened.
 *
 *     php artisan mail:test
 *     php artisan mail:test someone@gmail.com
 *
 * Outgoing mail is the one part of this site that cannot be checked by looking
 * at it. `MAIL_MAILER=log` — the shipped default, and what a fresh server still
 * has — writes every message into `storage/logs/laravel.log` and reports
 * success to the application, so an enquiry notification that nobody receives
 * looks identical to one that arrived. This command is the difference.
 *
 * It prints the configuration it is about to use first, because the usual
 * failure is not a refused login: it is a cached config, a `log` mailer nobody
 * changed, or a From address at a domain the SMTP account is not allowed to
 * send as.
 */
class MailTest extends Command
{
    protected $signature = 'mail:test
                            {to? : Where to send it; defaults to the sales address in the panel}';

    protected $description = 'Send a test email through the configured mailer';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');
        $to = (string) ($this->argument('to') ?: Contact::value('sales_email') ?: config('mail.from.address'));

        $this->newLine();
        $this->line('  Mailer      '.$mailer);

        if ($mailer === 'smtp') {
            $smtp = config('mail.mailers.smtp');

            $this->line('  Host        '.($smtp['host'] ?? '—').':'.($smtp['port'] ?? '—'));
            $this->line('  Encryption  '.($smtp['scheme'] ?? $smtp['encryption'] ?? '—'));
            $this->line('  Username    '.($smtp['username'] ?: '— (none, which most providers refuse)'));
        }

        $this->line('  From        '.config('mail.from.address').' ('.config('mail.from.name').')');
        $this->line('  To          '.$to);
        $this->newLine();

        if ($mailer === 'log') {
            /*
             * Not an error: it is the default, and it is a perfectly good way to
             * develop. It is only a problem on the server, where it means the
             * sales desk is not being told about enquiries — so say so plainly
             * rather than reporting a successful send.
             */
            $this->warn('  The mailer is `log`, so nothing leaves this machine.');
            $this->line('  The message below went to storage/logs/laravel.log instead.');
            $this->line('  Set MAIL_MAILER=smtp and the MAIL_* credentials in .env to send for real,');
            $this->line('  then run `php artisan config:clear` — a cached config ignores .env entirely.');
            $this->newLine();
        }

        if (blank($to)) {
            $this->error('  No address to send to, and none configured in the panel.');

            return self::FAILURE;
        }

        try {
            Mail::to($to)->send(new TestMessage);
        } catch (Throwable $e) {
            /*
             * Verbatim, and not summarised. "Connection could not be
             * established" and "535 Authentication failed" send whoever is
             * holding the terminal to two completely different places, and a
             * tidied-up message loses the difference.
             */
            $this->error('  Not sent.');
            $this->newLine();
            $this->line('  '.$e->getMessage());
            $this->newLine();

            return self::FAILURE;
        }

        $this->info($mailer === 'log'
            ? '  Written to the log.'
            : '  Handed to the mail server without an error.');

        if ($mailer !== 'log') {
            $this->newLine();
            $this->line('  That is only half the answer: the server accepted it. Check the inbox,');
            $this->line('  and the spam folder — if it landed there, the SPF, DKIM and DMARC records');
            $this->line('  are what to look at, not this application.');
        }

        $this->newLine();

        return self::SUCCESS;
    }
}
