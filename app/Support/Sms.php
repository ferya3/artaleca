<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * The SMS panel: six providers behind one `send()`.
 *
 * Ported from the nobatdehi project so the same panel, the same credentials and
 * the same settings screen work here too. Each provider's quirks are left
 * exactly as they were — the reseller domains the afe panel answers on, and its
 * habit of reporting success as a bare message id rather than a status code,
 * were learned the hard way and are not worth relearning.
 *
 * Two decisions carried over deliberately:
 *
 * - **The settings live in the database, not `.env`.** Changing a provider or a
 *   password should not need SSH and a service restart. Credentials are
 *   encrypted at rest, so a database dump is not a set of working credentials.
 * - **Nothing here throws.** A panel that is down, out of credit or
 *   misconfigured must never turn into an error on a visitor's form. Every call
 *   returns `[status, detail]`, and the detail is what the panel screen shows
 *   and what the log records.
 */
final class Sms
{
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DISABLED = 'disabled';

    /** The two settings that are credentials, and are stored encrypted. */
    public const SECRETS = ['sms.password', 'sms.api_key'];

    /** @return array<string, string> provider key => the label the panel shows */
    public static function providers(): array
    {
        return [
            'console' => __('admin.sms.providers.console'),
            'afe' => __('admin.sms.providers.afe'),
            'kavenegar' => __('admin.sms.providers.kavenegar'),
            'smsir' => __('admin.sms.providers.smsir'),
            'melipayamak' => __('admin.sms.providers.melipayamak'),
            'custom' => __('admin.sms.providers.custom'),
        ];
    }

    /**
     * Every setting, with the credentials decrypted.
     *
     * A value that cannot be decrypted comes back empty rather than throwing,
     * which is what a database restored under a different `APP_KEY` looks like.
     * The screen then reads as "not configured" — the truth — instead of every
     * page in the panel dying on a decrypt error.
     *
     * @return array<string, string>
     */
    public static function settings(): array
    {
        $keys = [
            'sms.enabled', 'sms.provider', 'sms.sender', 'sms.username', 'sms.password',
            'sms.api_key', 'sms.afe_domain', 'sms.custom_url', 'sms.custom_method',
            'sms.sales_mobile',
        ];

        $values = [];

        foreach ($keys as $key) {
            $stored = Setting::get($key);
            $value = is_string($stored) ? $stored : (is_bool($stored) ? ($stored ? '1' : '0') : '');

            if (in_array($key, self::SECRETS, true) && $value !== '') {
                try {
                    $value = Crypt::decryptString($value);
                } catch (Throwable) {
                    $value = '';
                }
            }

            $values[$key] = $value;
        }

        return $values;
    }

    /**
     * Save a set of settings.
     *
     * A blank credential means "leave it alone", not "delete it": the form
     * never sends a saved secret back to the browser, so treating blank as a
     * deletion would wipe the password every time somebody changed the sender
     * line. Clearing one is a separate, explicit action.
     *
     * @param  array<string, string|bool|null>  $values
     */
    public static function store(array $values): void
    {
        foreach ($values as $key => $value) {
            if (in_array($key, self::SECRETS, true)) {
                if (blank($value)) {
                    continue;
                }

                $value = Crypt::encryptString((string) $value);
            }

            Setting::put($key, $value, 'sms', translatable: false);
        }
    }

    public static function enabled(): bool
    {
        return (self::settings()['sms.enabled'] ?? '') === '1';
    }

    /** The sales manager's number, normalised, or empty if none is set. */
    public static function salesMobile(): string
    {
        return Mobile::normalize(self::settings()['sms.sales_mobile'] ?? '') ?? '';
    }

    /**
     * Can these settings send at all? If not, why not.
     *
     * `send()` checks the same things, but this is separate so the queue can
     * tell "the panel is not set up" (retrying is pointless) from "the panel
     * did not answer" (retrying is the whole idea).
     *
     * @param  array<string, string>|null  $settings
     */
    public static function configurationError(?array $settings = null): ?string
    {
        $s = $settings ?? self::settings();
        $provider = ($s['sms.provider'] ?? '') ?: 'console';
        $has = fn (string $key): bool => trim((string) ($s[$key] ?? '')) !== '';

        return match ($provider) {
            'console' => null,
            'afe', 'melipayamak' => match (true) {
                ! $has('sms.username') || ! $has('sms.password') => __('admin.sms.errors.no_login'),
                $provider === 'afe' && ! $has('sms.sender') => __('admin.sms.errors.no_sender'),
                default => null,
            },
            'kavenegar', 'smsir' => $has('sms.api_key') ? null : __('admin.sms.errors.no_api_key'),
            'custom' => $has('sms.custom_url') ? null : __('admin.sms.errors.no_custom_url'),
            default => __('admin.sms.errors.unknown_provider', ['provider' => $provider]),
        };
    }

    /**
     * Send one message.
     *
     * @param  array<string, string>|null  $settings
     * @return array{0: string, 1: string} [status, detail]
     */
    public static function send(string $mobile, string $text, ?array $settings = null): array
    {
        $s = $settings ?? self::settings();

        if (($s['sms.enabled'] ?? '') !== '1') {
            return [self::STATUS_DISABLED, __('admin.sms.errors.disabled')];
        }

        $mobile = Mobile::normalize($mobile) ?? '';

        if ($mobile === '') {
            return [self::STATUS_FAILED, __('admin.sms.errors.no_mobile')];
        }

        $provider = ($s['sms.provider'] ?? '') ?: 'console';

        try {
            return match ($provider) {
                'console' => self::console($mobile, $text),
                'afe' => self::afe($s, $mobile, $text),
                'kavenegar' => self::kavenegar($s, $mobile, $text),
                'smsir' => self::smsir($s, $mobile, $text),
                'melipayamak' => self::melipayamak($s, $mobile, $text),
                'custom' => self::custom($s, $mobile, $text),
                default => [self::STATUS_FAILED, __('admin.sms.errors.unknown_provider', ['provider' => $provider])],
            };
        } catch (Throwable $e) {
            return [self::STATUS_FAILED, mb_substr($e->getMessage(), 0, 150)];
        }
    }

    /**
     * The test provider: writes the message to a log instead of sending it.
     *
     * This is what the site ships with, so a fresh install announces enquiries
     * into a file rather than failing against a panel nobody has configured
     * yet.
     */
    private static function console(string $mobile, string $text): array
    {
        @file_put_contents(
            storage_path('logs/sms-test.log'),
            '['.date('Y-m-d H:i:s')."] → {$mobile}:\n{$text}\n----\n",
            FILE_APPEND,
        );

        return [self::STATUS_SENT, __('admin.sms.errors.console_sent')];
    }

    // ── Asr Fara Ertebat and its resellers (afe.ir, wide.ir, …) ─────────

    /**
     * The URLs to try, in order.
     *
     * A reseller panel answers on its own domain and not always on the one the
     * contract names, and which of them works is not something an operator can
     * be expected to know. So a domain typed into the settings is tried first
     * and the two the provider itself publishes follow.
     *
     * @param  array<string, string>  $s
     * @return list<string>
     */
    private static function afeCandidates(array $s): array
    {
        $list = [];
        $domain = trim((string) ($s['sms.afe_domain'] ?? ''));

        if ($domain !== '') {
            $bare = preg_replace('#^www\.#i', '', (string) preg_replace('#/.*$#', '', $domain));
            $list[] = preg_match('#^https?://#i', $domain) ? $domain : 'https://www.'.$bare.'/Url/SendSMS.aspx';
        }

        $list[] = 'https://www.afe.ir/Url/SendSMS.aspx';
        $list[] = 'https://www.wide.ir/Url/SendSMS.aspx';

        return array_values(array_unique($list));
    }

    /** The panel answers with a status line and then some HTML. */
    private static function afeStatusText(string $body): string
    {
        $body = trim($body);
        $at = strpos($body, '<');

        return trim($at === false ? $body : substr($body, 0, $at));
    }

    /**
     * Success is either the words, or a bare number.
     *
     * The number is the message id, which the panel returns instead of a status
     * code on a successful send — so "is this all digits" is the test, however
     * odd that looks.
     */
    private static function afeIsSuccess(string $body): bool
    {
        if (stripos($body, 'send successfully') !== false) {
            return true;
        }

        $status = self::afeStatusText($body);

        return $status !== '' && ctype_digit(str_replace([',', ' '], '', $status));
    }

    /** @param array<string, string> $s */
    private static function afe(array $s, string $mobile, string $text): array
    {
        if (trim((string) ($s['sms.username'] ?? '')) === '' || trim((string) ($s['sms.password'] ?? '')) === '') {
            return [self::STATUS_FAILED, __('admin.sms.errors.no_login')];
        }

        if (trim((string) ($s['sms.sender'] ?? '')) === '') {
            return [self::STATUS_FAILED, __('admin.sms.errors.no_sender')];
        }

        $query = [
            'Username' => trim((string) $s['sms.username']),
            'Password' => trim((string) $s['sms.password']),
            'Number' => trim((string) $s['sms.sender']),
            'Mobile' => $mobile,
            'SMS' => $text,
        ];

        foreach (self::afeCandidates($s) as $url) {
            try {
                $response = SafeHttp::get($url, $query);
            } catch (Throwable) {
                continue;
            }

            $body = (string) $response->body();

            if (self::afeIsSuccess($body)) {
                return [self::STATUS_SENT, __('admin.sms.errors.sent_with_reply', [
                    'reply' => mb_substr(self::afeStatusText($body), 0, 80),
                ])];
            }

            $status = self::afeStatusText($body);

            if ($status !== '') {
                return [self::STATUS_FAILED, mb_substr($status, 0, 160)];
            }
        }

        return [self::STATUS_FAILED, __('admin.sms.errors.no_reply')];
    }

    /**
     * Try each candidate domain and report what each one said.
     *
     * The diagnostic that turns "it does not work" into "your panel is on
     * wide.ir, not afe.ir". It stops at the first success so the test message
     * is not sent twice.
     *
     * @param  array<string, string>  $s
     * @return list<array{url: string, host: string, code: int, ok: bool, body: string}>
     */
    public static function probe(array $s, string $mobile): array
    {
        $query = [
            'Username' => trim((string) ($s['sms.username'] ?? '')),
            'Password' => trim((string) ($s['sms.password'] ?? '')),
            'Number' => trim((string) ($s['sms.sender'] ?? '')),
            'Mobile' => Mobile::normalize($mobile) ?? '',
            'SMS' => __('admin.sms.test_message'),
        ];

        $results = [];

        foreach (self::afeCandidates($s) as $url) {
            $row = [
                // The password never reaches the screen, even in a diagnostic.
                'url' => $url.'?'.preg_replace('/(Password=)[^&]*/i', '$1******', http_build_query($query)),
                'host' => parse_url($url, PHP_URL_HOST) ?: $url,
                'code' => 0,
                'ok' => false,
                'body' => '',
            ];

            try {
                $response = SafeHttp::get($url, $query);
                $row['code'] = $response->status();
                $row['body'] = trim((string) $response->body());
                $row['ok'] = self::afeIsSuccess($row['body']);
            } catch (Throwable $e) {
                $row['body'] = mb_substr($e->getMessage(), 0, 150);
            }

            $results[] = $row;

            if ($row['ok']) {
                break;
            }
        }

        return $results;
    }

    // ── The rest ───────────────────────────────────────────────────────

    /** @param array<string, string> $s */
    private static function kavenegar(array $s, string $mobile, string $text): array
    {
        $key = trim((string) ($s['sms.api_key'] ?? ''));

        if ($key === '') {
            return [self::STATUS_FAILED, __('admin.sms.errors.no_api_key')];
        }

        $params = ['receptor' => $mobile, 'message' => $text];

        if (trim((string) ($s['sms.sender'] ?? '')) !== '') {
            $params['sender'] = trim((string) $s['sms.sender']);
        }

        $url = 'https://api.kavenegar.com/v1/'.rawurlencode($key).'/sms/send.json';
        $data = SafeHttp::get($url, $params)->json() ?: [];

        return ($data['return']['status'] ?? 0) === 200
            ? [self::STATUS_SENT, __('admin.sms.errors.sent')]
            : [self::STATUS_FAILED, 'Kavenegar: '.($data['return']['message'] ?? '—')];
    }

    /** @param array<string, string> $s */
    private static function smsir(array $s, string $mobile, string $text): array
    {
        $key = trim((string) ($s['sms.api_key'] ?? ''));

        if ($key === '') {
            return [self::STATUS_FAILED, __('admin.sms.errors.no_api_key')];
        }

        $data = SafeHttp::post('https://api.sms.ir/v1/send/bulk', [
            'headers' => ['x-api-key' => $key, 'Accept' => 'application/json'],
            'json' => [
                'lineNumber' => trim((string) ($s['sms.sender'] ?? '')),
                'messageText' => $text,
                'mobiles' => [$mobile],
            ],
        ])->json() ?: [];

        return ($data['status'] ?? 0) === 1
            ? [self::STATUS_SENT, __('admin.sms.errors.sent')]
            : [self::STATUS_FAILED, 'SMS.ir: '.($data['message'] ?? '—')];
    }

    /** @param array<string, string> $s */
    private static function melipayamak(array $s, string $mobile, string $text): array
    {
        $username = trim((string) ($s['sms.username'] ?? ''));
        $password = trim((string) ($s['sms.password'] ?? ''));

        if ($username === '' || $password === '') {
            return [self::STATUS_FAILED, __('admin.sms.errors.no_login')];
        }

        $data = SafeHttp::post('https://rest.payamak-panel.com/api/SendSMS/SendSMS', [
            'headers' => ['Accept' => 'application/json'],
            'json' => [
                'username' => $username,
                'password' => $password,
                'to' => $mobile,
                'from' => trim((string) ($s['sms.sender'] ?? '')),
                'text' => $text,
            ],
        ])->json() ?: [];

        return ($data['RetStatus'] ?? 0) === 1
            ? [self::STATUS_SENT, __('admin.sms.errors.sent')]
            : [self::STATUS_FAILED, 'Melipayamak: '.($data['StrRetStatus'] ?? '—')];
    }

    /**
     * Any other panel, by URL template.
     *
     * The escape hatch: a provider this code has never heard of is a URL with
     * `{to}` and `{text}` in it. Every value is URL-encoded on the way in, and
     * the host still goes through the SSRF guard like everything else.
     *
     * @param  array<string, string>  $s
     */
    private static function custom(array $s, string $mobile, string $text): array
    {
        $template = trim((string) ($s['sms.custom_url'] ?? ''));

        if ($template === '') {
            return [self::STATUS_FAILED, __('admin.sms.errors.no_custom_url')];
        }

        $values = [
            '{to}' => rawurlencode($mobile),
            '{text}' => rawurlencode($text),
            '{from}' => rawurlencode(trim((string) ($s['sms.sender'] ?? ''))),
            '{username}' => rawurlencode(trim((string) ($s['sms.username'] ?? ''))),
            '{password}' => rawurlencode(trim((string) ($s['sms.password'] ?? ''))),
            '{apikey}' => rawurlencode(trim((string) ($s['sms.api_key'] ?? ''))),
        ];

        $method = strtoupper((string) ($s['sms.custom_method'] ?? 'GET'));

        if ($method === 'POST' && str_contains($template, '?')) {
            [$base, $query] = explode('?', $template, 2);
            $response = SafeHttp::post($base, ['body' => strtr($query, $values)]);
        } else {
            $response = SafeHttp::get(strtr($template, $values));
        }

        $code = $response->status();
        $body = trim((string) $response->body());

        return $code >= 200 && $code < 300
            ? [self::STATUS_SENT, __('admin.sms.errors.sent_with_code', [
                'code' => $code,
                'reply' => mb_substr($body, 0, 150) ?: '—',
            ])]
            : [self::STATUS_FAILED, __('admin.sms.errors.bad_code', [
                'code' => $code,
                'reply' => mb_substr($body, 0, 150),
            ])];
    }
}
