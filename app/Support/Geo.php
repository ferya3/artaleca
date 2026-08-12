<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Which country a request came from.
 *
 * PHP cannot get this from an IP address on its own — an address is just a
 * number until something maps it to an allocation — so there are two sources
 * here and they are tried in order of how much they can be trusted.
 *
 *  1. **A header from the edge.** Cloudflare sets `CF-IPCountry`; nginx with
 *     the GeoIP2 module can set `X-Geo-Country`. Whoever put that in front of
 *     the app already did the lookup properly and keeps the database current,
 *     so if the header is there it wins. It is read only when config names it,
 *     because an unnamed header is just something the visitor typed.
 *  2. **A list of Iran's allocated ranges**, synced from RIPE by
 *     `php artisan geo:sync` and stored on disk. That answers the only question
 *     this site actually asks — is this visitor in Iran — without a database, a
 *     licence key, or a per-request call to somebody else's API.
 *
 * Neither is present on a bare install, and that is deliberate: with no source
 * the answer is "unknown", the caller falls back to `Accept-Language`, and
 * nothing about the site changes. A geolocation feature that half-works is
 * worse than one that is plainly off.
 */
final class Geo
{
    public const PREFIX_FILE = 'geo/ir-prefixes.txt';

    /**
     * "Somewhere the synced list does not cover."
     *
     * The prefix list holds one country, so it can answer "Iran" or "not Iran"
     * but never "Germany". Returning null for the second case would be wrong in
     * a way that defeats the whole feature: null means *no source*, and the
     * caller then falls back to `Accept-Language` — which a VPN does not
     * change, so a visitor on a VPN would keep getting Persian. `ZZ` is the ISO
     * 3166 user-assigned code for exactly this, and it maps to English.
     */
    public const ELSEWHERE = 'ZZ';

    private const CACHE_KEY = 'geo.ir-prefixes';

    /** Two-letter country code, or null when nothing can say. */
    public static function country(Request $request): ?string
    {
        $header = self::fromHeader($request);

        if ($header !== null) {
            return $header;
        }

        // No list, no answer — and the caller falls back to the browser.
        if (self::prefixes() === []) {
            return null;
        }

        $ip = $request->ip();

        if ($ip === null) {
            return null;
        }

        return self::isIranian($ip) ? 'IR' : self::ELSEWHERE;
    }

    /**
     * A country header, read only when one has been named in config.
     *
     * Not gated on `isFromTrustedProxy()`, which would look like a check and be
     * none: proxies are trusted at `*` so the app can see a real client address
     * behind nginx, which makes every request "trusted" and every header
     * forgeable. Naming the header in config is the operator stating that an
     * edge they control sets it — and anything not named is ignored, so a
     * visitor sending `CF-IPCountry: IR` to a server with no Cloudflare in
     * front of it changes nothing.
     */
    private static function fromHeader(Request $request): ?string
    {
        $name = config('site.geo.header');

        if (blank($name)) {
            return null;
        }

        $value = strtoupper(trim((string) $request->header($name)));

        // Cloudflare sends XX for anonymised addresses and T1 for Tor.
        if (preg_match('/^[A-Z]{2}$/', $value) !== 1 || in_array($value, ['XX', 'T1'], true)) {
            return null;
        }

        return $value;
    }

    /** Whether an address falls inside a synced Iranian allocation. */
    public static function isIranian(string $ip): bool
    {
        $prefixes = self::prefixes();

        if ($prefixes === []) {
            return false;
        }

        $packed = @inet_pton($ip);

        if ($packed === false) {
            return false;
        }

        foreach ($prefixes as [$network, $bits]) {
            if (strlen($network) === strlen($packed) && self::matches($packed, $network, $bits)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compare the first `$bits` bits of two packed addresses.
     *
     * Byte-wise on the packed form rather than arithmetic on integers: an IPv6
     * address does not fit in one, and doing it this way makes v4 and v6 the
     * same code path.
     */
    private static function matches(string $address, string $network, int $bits): bool
    {
        $whole = intdiv($bits, 8);
        $remainder = $bits % 8;

        if ($whole > 0 && strncmp($address, $network, $whole) !== 0) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = 0xFF << (8 - $remainder) & 0xFF;

        return (ord($address[$whole]) & $mask) === (ord($network[$whole]) & $mask);
    }

    /**
     * The synced prefixes, as packed network plus bit length.
     *
     * Parsed once and cached: the file is a few thousand lines, and re-reading
     * it on every request to the site root would be the most expensive thing
     * that request does.
     *
     * @return list<array{0:string,1:int}>
     */
    private static function prefixes(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $path = storage_path('app/'.self::PREFIX_FILE);

            if (! File::exists($path)) {
                return [];
            }

            $parsed = [];

            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '/')) {
                    continue;
                }

                [$network, $bits] = explode('/', $line, 2);
                $packed = @inet_pton($network);

                if ($packed === false || ! ctype_digit($bits)) {
                    continue;
                }

                $parsed[] = [$packed, (int) $bits];
            }

            return $parsed;
        });
    }

    /** Called by the sync command once it has written a new file. */
    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
