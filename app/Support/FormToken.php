<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Signed render-time token for public forms.
 *
 * The value is `<unix-timestamp>.<hmac>`, so the server can tell how long the
 * form was on screen without trusting the client and without a session write.
 * Paired with the honeypot field it stops the overwhelming majority of bot
 * submissions before they ever reach validation.
 */
final class FormToken
{
    public static function issue(): string
    {
        $timestamp = (string) now()->getTimestamp();

        return $timestamp.'.'.hash_hmac('sha256', $timestamp, (string) config('app.key'));
    }
}
