<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;

/**
 * A site image resolved down to the two files a page can actually use.
 *
 * A stored image is a map of language to theme:
 *
 *     ['fa' => ['light' => '/…', 'dark' => '/…'], 'en' => [...]]
 *
 * Language is resolved by `Setting::get`, which every translatable value
 * already goes through. Theme cannot be: the page does not know which theme it
 * will be shown in — the visitor's operating system or their toggle decides
 * that after the HTML has left. So both files come back and the template hands
 * the choice to CSS.
 */
final class SiteImage
{
    /**
     * @return array{light: ?string, dark: ?string}
     */
    public static function get(string $key, ?string $locale = null): array
    {
        $value = Setting::get($key, null, $locale);

        /*
         * Two older shapes still have to work, because they are what is in the
         * database on a site that has been running:
         *
         *  - a plain string, from before images were per-language, and
         *  - a language map of plain strings, from before they were per-theme.
         *
         * `Setting::get` has already unwrapped the second one, so both arrive
         * here as a string, and in both cases that one file stood for
         * everything — which is exactly what it goes on doing.
         */
        if (is_string($value)) {
            return ['light' => $value, 'dark' => $value];
        }

        if (! is_array($value)) {
            return ['light' => null, 'dark' => null];
        }

        $light = self::path($value['light'] ?? null);
        $dark = self::path($value['dark'] ?? null);

        /*
         * Night falls back to day, never the reverse.
         *
         * Most images here are photographs, which need one file and no second
         * thought. The second slot exists for artwork with a background of its
         * own — an infographic lettered on white — and only that artwork should
         * cost the editor a second upload or the visitor a second file.
         */
        return ['light' => $light, 'dark' => $dark ?? $light];
    }

    /** True when the two themes genuinely need different files. */
    public static function isThemed(string $key, ?string $locale = null): bool
    {
        $image = self::get($key, $locale);

        return $image['dark'] !== null && $image['dark'] !== $image['light'];
    }

    private static function path(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
