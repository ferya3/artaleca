<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Enquiry alerts on Bale, the Iranian messenger.
 *
 * The sales desk already gets an email, which arrives when someone next opens
 * their inbox. An enquiry is worth a phone buzzing, and Bale is the channel
 * that costs nothing per message and needs no VPN inside Iran — its bot API is
 * shaped like Telegram's, so this is one HTTPS call and no SDK.
 *
 * Three things are deliberate here:
 *
 * - **The token is stored encrypted.** It is a credential with the same power
 *   as the bot itself, and the database is the thing that gets dumped, copied
 *   between servers and restored on a laptop. Encrypted, a stray dump is not a
 *   leaked bot.
 * - **Nothing here throws.** A messenger that is down, blocked or misconfigured
 *   must never turn into an error on the visitor's form or a failed job that
 *   needs a human. Every call reports a boolean and logs the detail.
 * - **The base URL is configuration, not a constant.** Bale's API host is the
 *   one part of this that is outside our control and outside our ability to
 *   test from here; if it moves, it moves in `.env` rather than in a release.
 */
final class Bale
{
    public static function enabled(): bool
    {
        return (bool) Setting::get('notifications.bale_enabled')
            && filled(self::token())
            && filled(self::chatId());
    }

    /** Whether a token has been saved, without revealing it. */
    public static function configured(): bool
    {
        return filled(self::token());
    }

    public static function chatId(): string
    {
        $value = Setting::get('notifications.bale_chat_id');

        return is_string($value) ? trim($value) : '';
    }

    /**
     * The bot token, decrypted.
     *
     * Returns empty rather than throwing on a token that cannot be decrypted —
     * which is what a database restored under a different `APP_KEY` looks like.
     * The panel then reads as "not configured", which is the truth, instead of
     * every page in the admin dying on a decrypt error.
     */
    public static function token(): string
    {
        $stored = Setting::get('notifications.bale_token');

        if (! is_string($stored) || $stored === '') {
            return '';
        }

        try {
            return Crypt::decryptString($stored);
        } catch (Throwable) {
            return '';
        }
    }

    public static function storeToken(string $token): void
    {
        Setting::put(
            'notifications.bale_token',
            $token === '' ? null : Crypt::encryptString($token),
            'notifications',
            translatable: false,
        );
    }

    /**
     * Send a message. Returns whether Bale accepted it.
     *
     * `HTML` parse mode rather than Markdown: an enquiry carries a company name
     * and a free-text message, and Markdown's underscores and asterisks turn up
     * in ordinary prose far more often than angle brackets do — which are
     * escaped here anyway.
     */
    public static function send(string $text, ?string $chatId = null): bool
    {
        $chatId = $chatId ?: self::chatId();

        if (blank(self::token()) || blank($chatId)) {
            return false;
        }

        $response = self::call('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);

        return (bool) ($response['ok'] ?? false);
    }

    /**
     * The chats the bot can currently see.
     *
     * Finding a chat id by hand means reading raw JSON, which is exactly the
     * step that makes people give up. The panel calls this and offers a list to
     * pick from instead.
     *
     * @return array<int, array{id: string, title: string}>
     */
    public static function chats(): array
    {
        $response = self::call('getUpdates', ['limit' => 50], 'get');

        $chats = [];

        foreach ($response['result'] ?? [] as $update) {
            $chat = $update['message']['chat']
                ?? $update['edited_message']['chat']
                ?? $update['channel_post']['chat']
                ?? null;

            if (! is_array($chat) || ! isset($chat['id'])) {
                continue;
            }

            $chats[(string) $chat['id']] = [
                'id' => (string) $chat['id'],
                'title' => (string) ($chat['title']
                    ?? trim(($chat['first_name'] ?? '').' '.($chat['last_name'] ?? ''))
                    ?: ($chat['username'] ?? $chat['id'])),
            ];
        }

        return array_values($chats);
    }

    /** Does the token work at all? Used by the panel's test button. */
    public static function me(): ?array
    {
        $response = self::call('getMe', [], 'get');

        return ($response['ok'] ?? false) ? ($response['result'] ?? null) : null;
    }

    /**
     * One HTTPS call, and never an exception.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private static function call(string $method, array $payload = [], string $verb = 'post'): array
    {
        $token = self::token();

        if ($token === '') {
            return [];
        }

        $url = rtrim((string) config('services.bale.base_url'), '/').'/bot'.$token.'/'.$method;

        try {
            $response = self::client()->{$verb}($url, $payload);

            $body = $response->json();

            if (! is_array($body)) {
                return [];
            }

            if (! ($body['ok'] ?? false)) {
                logger()->warning('Bale rejected a call', [
                    'method' => $method,
                    'status' => $response->status(),
                    // The description, never the payload: the payload is the
                    // enquiry, and an enquiry does not belong in a log file.
                    'description' => $body['description'] ?? null,
                ]);
            }

            return $body;
        } catch (Throwable $e) {
            logger()->warning('Bale call failed', [
                'method' => $method,
                'exception' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private static function client(): PendingRequest
    {
        return Http::timeout((int) config('services.bale.timeout', 8))
            ->connectTimeout(5)
            ->acceptJson();
    }
}
