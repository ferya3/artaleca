<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\App;

/**
 * Thin read-only facade over `config('site.locales')`.
 *
 * Blade and the routing layer both need to reason about locales; keeping the
 * lookups here means the shape of the config array is only known in one place.
 */
final class Locales
{
    /**
     * Where a visitor's own language choice is remembered.
     *
     * A year, because the answer does not change: someone who reads Persian in
     * March still reads Persian in December.
     */
    public const COOKIE = 'site_locale';

    public const COOKIE_DAYS = 365;

    /** @return array<string, array{name:string,native:string,dir:string,hreflang:string,flag:string}> */
    public static function all(): array
    {
        return config('site.locales');
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function default(): string
    {
        return config('site.default_locale', 'fa');
    }

    public static function supports(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, self::all());
    }

    public static function current(): string
    {
        $locale = App::getLocale();

        return self::supports($locale) ? $locale : self::default();
    }

    /** @return array{name:string,native:string,dir:string,hreflang:string,flag:string} */
    public static function meta(?string $locale = null): array
    {
        $locale = $locale ?? self::current();

        return self::all()[$locale] ?? self::all()[self::default()];
    }

    public static function direction(?string $locale = null): string
    {
        return self::meta($locale)['dir'];
    }

    public static function isRtl(?string $locale = null): bool
    {
        return self::direction($locale) === 'rtl';
    }

    public static function hreflang(?string $locale = null): string
    {
        return self::meta($locale)['hreflang'];
    }

    /**
     * Locales other than the given one, for the language switcher.
     *
     * @return array<string, array{name:string,native:string,dir:string,hreflang:string,flag:string}>
     */
    public static function others(?string $locale = null): array
    {
        $locale = $locale ?? self::current();

        return array_diff_key(self::all(), [$locale => null]);
    }

    /**
     * Countries whose visitors are served Arabic.
     *
     * Not "every country where Arabic is spoken" — the list is the export
     * markets this plant actually ships to, which is what the Arabic pages were
     * written for.
     *
     * @var list<string>
     */
    private const ARABIC_COUNTRIES = [
        'AE', 'SA', 'IQ', 'KW', 'QA', 'OM', 'BH', 'JO', 'SY', 'LB',
        'EG', 'LY', 'YE', 'SD', 'DZ', 'MA', 'TN', 'MR',
    ];

    /**
     * The language a country should be shown, or null when the country says
     * nothing useful.
     *
     * Null rather than the default is the point: it lets the caller fall back
     * to `Accept-Language`, which is a better signal than a guess. Everywhere
     * outside Iran and the Arab export markets is deliberately *not* mapped —
     * a German visitor is not "not Iranian, therefore English" by geography,
     * they are English because that is the language the site has for them, and
     * their browser can say so more precisely than their address can.
     */
    public static function forCountry(?string $country): ?string
    {
        if (blank($country)) {
            return null;
        }

        $country = strtoupper($country);

        if ($country === 'IR') {
            return 'fa';
        }

        if (in_array($country, self::ARABIC_COUNTRIES, true)) {
            return self::supports('ar') ? 'ar' : null;
        }

        /*
         * Everything else, including `Geo::ELSEWHERE`, gets English. That is
         * the answer the site has for a visitor it cannot place, and it is what
         * makes a VPN do the thing it is expected to do here — move the visitor
         * out of Iran, and the site into English.
         */
        return 'en';
    }

    /**
     * Best supported locale for an incoming Accept-Language header.
     */
    public static function negotiate(?string $header): string
    {
        if (blank($header)) {
            return self::default();
        }

        $ranked = [];

        foreach (explode(',', $header) as $part) {
            $bits = explode(';q=', trim($part));
            $tag = strtolower(trim($bits[0]));
            $quality = isset($bits[1]) ? (float) $bits[1] : 1.0;

            if ($tag === '') {
                continue;
            }

            $primary = explode('-', $tag)[0];

            if (self::supports($primary)) {
                $ranked[$primary] = max($ranked[$primary] ?? 0, $quality);
            }
        }

        if ($ranked === []) {
            return self::default();
        }

        arsort($ranked);

        return array_key_first($ranked);
    }
}
