<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Laravel's HTTP client with the SSRF guard already on it.
 *
 * Three rules, and each closes a way around the check in `Ip`:
 *
 *  - only http and https, so `file://` and friends cannot be typed into a
 *    settings field;
 *  - the host is resolved and checked before the request, and the connection is
 *    then pinned to the address that was checked — otherwise a hostname can
 *    answer publicly for the check and privately for the fetch;
 *  - redirects are not followed, since a 302 to `127.0.0.1` would otherwise
 *    walk straight past both.
 *
 * Ported from the nobatdehi project along with the SMS panel it protects.
 */
final class SafeHttp
{
    /**
     * @return list<string> the addresses to pin the connection to
     */
    public static function assertSafeUrl(string $url): array
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if ($scheme !== 'http' && $scheme !== 'https') {
            throw new RuntimeException('Only http and https addresses are allowed.');
        }

        return Ip::assertHostPublic((string) parse_url($url, PHP_URL_HOST));
    }

    /** @param array<int, mixed> $curl */
    public static function client(int $timeout = 15, array $curl = []): PendingRequest
    {
        $request = Http::timeout($timeout)
            ->connectTimeout(5)
            ->withoutRedirecting()
            ->withOptions(['protocols' => ['http', 'https']]);

        return $curl === [] ? $request : $request->withOptions(['curl' => $curl]);
    }

    /** @param array<string, mixed> $query */
    public static function get(string $url, array $query = [], int $timeout = 15)
    {
        $addresses = self::assertSafeUrl($url);
        $client = self::client($timeout, self::pin($url, $addresses));

        /*
         * Guzzle's `query` option *replaces* the URL's own query string rather
         * than adding to it, so passing an empty array would strip the
         * `?to=…&text=…` a custom panel's URL carries and send it nothing.
         */
        return $query === [] ? $client->get($url) : $client->get($url, $query);
    }

    /** @param array<string, mixed> $options */
    public static function post(string $url, array $options = [], int $timeout = 15)
    {
        $addresses = self::assertSafeUrl($url);
        $request = self::client($timeout, self::pin($url, $addresses));

        if (! empty($options['headers'])) {
            $request = $request->withHeaders($options['headers']);
        }

        if (array_key_exists('json', $options)) {
            return $request->asJson()->post($url, $options['json']);
        }

        if (array_key_exists('form', $options)) {
            return $request->asForm()->post($url, $options['form']);
        }

        // A string body has to travel exactly as given, which the default
        // (JSON) body format would re-encode.
        $body = $options['body'] ?? [];

        return is_string($body)
            ? $request->withBody($body, $options['content_type'] ?? 'application/x-www-form-urlencoded')->post($url)
            : $request->asForm()->post($url, $body);
    }

    /**
     * Pin the connection to the address that was actually checked, so cURL does
     * not resolve the name a second time.
     *
     * @param  list<string>  $addresses
     * @return array<int, mixed>
     */
    private static function pin(string $url, array $addresses): array
    {
        if ($addresses === []) {
            return [];
        }

        $host = trim((string) parse_url($url, PHP_URL_HOST), '[]');
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $port = (int) (parse_url($url, PHP_URL_PORT) ?: ($scheme === 'https' ? 443 : 80));

        return [CURLOPT_RESOLVE => ["{$host}:{$port}:{$addresses[0]}"]];
    }
}
