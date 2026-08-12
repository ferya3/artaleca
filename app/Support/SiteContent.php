<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Lang;

/**
 * Every piece of page copy, editable without a deploy.
 *
 * The text still lives in the translation files — those stay the source of
 * truth and the fallback. What the admin writes is an *override*, stored under
 * `content.<group>.<key>`, and read in front of the file. Two things fall out
 * of that arrangement, and both are the reason for it:
 *
 *  - nothing breaks on the day this ships. A key with no override behaves
 *    exactly as it did before, so the screen can be filled in gradually rather
 *    than needing every string pasted in before the site works.
 *  - a new string added to a translation file appears on the screen by itself.
 *    The schema is read from the files, so it cannot drift from what the pages
 *    actually render.
 *
 * `form` and `validation` are deliberately absent. Their strings are wired into
 * FormRequest attributes and message keys, where an editor changing a label
 * would change what a validation error says about a field — a different kind of
 * edit with a different blast radius.
 */
final class SiteContent
{
    /**
     * The translation files exposed for editing, in the order an editor would
     * look for them: the pages first, then the things that appear on all of
     * them.
     *
     * @var list<string>
     */
    public const GROUPS = [
        'home', 'about', 'product', 'applications', 'projects', 'representatives',
        'articles', 'downloads', 'gallery', 'partners', 'faq', 'contact', 'search',
        'legal', 'seo', 'nav', 'common',
    ];

    public static function isGroup(string $group): bool
    {
        return in_array($group, self::GROUPS, true);
    }

    /**
     * The keys in a group, flattened to dot notation, with the value the
     * translation file gives them in the default language.
     *
     * @return array<string, string>
     */
    public static function keys(string $group): array
    {
        if (! self::isGroup($group)) {
            return [];
        }

        $lines = Lang::get($group, [], Locales::default());

        return is_array($lines) ? self::flatten($lines) : [];
    }

    /**
     * The text a page should render: the override if there is one, the
     * translation file otherwise.
     *
     * @param  array<string, string|int>  $replace
     */
    public static function get(string $key, array $replace = []): string
    {
        /*
         * Read the map directly rather than through `Setting::get`, which falls
         * back between languages: an Arabic page with no Arabic override would
         * have been handed the Persian one. That is the right behaviour for a
         * photograph — a shared picture beats none — and exactly wrong for
         * text, where the fallback has to be the shipped Arabic translation.
         */
        $stored = Setting::map()['content.'.$key] ?? null;
        $override = is_array($stored) ? ($stored[Locales::current()] ?? null) : $stored;

        if (! is_string($override) || $override === '') {
            return (string) __($key, $replace);
        }

        // `__()` does the placeholder substitution for the file; an override has
        // to be given the same treatment or `:count` would reach the page raw.
        foreach ($replace as $name => $value) {
            $override = str_replace([':'.$name, ':'.ucfirst((string) $name)], (string) $value, $override);
        }

        return $override;
    }

    /** How many keys in this group an editor has actually overridden. */
    public static function overriddenCount(string $group): int
    {
        $prefix = 'content.'.$group.'.';

        return count(array_filter(
            Setting::map(),
            fn (mixed $value, string $key): bool => str_starts_with($key, $prefix) && filled($value),
            ARRAY_FILTER_USE_BOTH,
        ));
    }

    /**
     * A textarea for anything long enough to want one.
     *
     * Measured against the default text rather than declared per key: a
     * hand-kept list of which fields are long would be wrong the first time
     * someone rewrote a sentence.
     */
    public static function isLong(string $default): bool
    {
        return mb_strlen($default) > 90;
    }

    /**
     * @param  array<string, mixed>  $lines
     * @return array<string, string>
     */
    private static function flatten(array $lines, string $prefix = ''): array
    {
        $flat = [];

        foreach ($lines as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $flat += self::flatten($value, $path);
            } elseif (is_string($value)) {
                $flat[$path] = $value;
            }
        }

        return $flat;
    }
}
