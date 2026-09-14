<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The message `mail:test` sends.
 *
 * Plain text, and a Mailable rather than `Mail::raw`: text because the point is
 * to prove the transport rather than to render anything, and a class because
 * `Mail::raw` is a no-op under `Mail::fake()` and therefore cannot be asserted.
 * A diagnostic nobody can test is a diagnostic nobody should trust.
 */
class TestMessage extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ARTA LECA — test message ('.now()->format('Y-m-d H:i').')',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.test',
            with: [
                'sentAt' => now()->toDateTimeString(),
                'mailer' => (string) config('mail.default'),
                'from' => (string) config('mail.from.address'),
            ],
        );
    }
}
