<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\HtmlString;

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
     * Returns an `HtmlString` — and only then — when the override contains line
     * breaks. Blade's `{{ }}` hands anything `Htmlable` straight through, so a
     * paragraph typed with Enter in the admin arrives on the page with its
     * breaks intact, while every other string stays an ordinary string and
     * keeps being escaped. The escaping is done here first, so the only markup
     * that survives is the markup this method wrote.
     *
     * @param  array<string, string|int>  $replace
     */
    public static function get(string $key, array $replace = []): string|HtmlString
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

        return str_contains($override, "\n") ? self::asLines($override) : $override;
    }

    /**
     * Turn a typed block of text into the paragraph it looks like.
     *
     * A line opening with `-` or `*` becomes a bullet, because that is what an
     * editor types when they mean one. It stays a `•` and a line break rather
     * than becoming a `<ul>`: these strings are rendered inside `<p>` elements
     * all over the site, and a list inside a paragraph is invalid HTML the
     * browser fixes by closing the paragraph early — which would break the
     * layout around it far more visibly than the missing list semantics.
     */
    private static function asLines(string $text): HtmlString
    {
        /*
         * The `u` flag is load-bearing. Without it `\R` matches raw bytes, and
         * one of the bytes it matches is 0x85 — which is the second half of
         * "م" in UTF-8. The split then landed inside the character and ate it:
         * "خط دوم" came back as "خط دو" with a stray break after it.
         */
        $lines = preg_split('/\R/u', trim($text)) ?: [];

        $parts = [];

        foreach ($lines as $line) {
            $line = trim($line);
            $bullet = preg_match('/^[-*•]\s*(.+)$/u', $line, $matches) === 1;

            $parts[] = [
                'bullet' => $bullet,
                'html' => $bullet
                    ? '<span class="copy-bullet">'.e($matches[1]).'</span>'
                    : e($line),
            ];
        }

        /*
         * A bullet is its own block, so it needs no `<br>` on either side —
         * emitting one anyway left a blank line above and below every list.
         * Two ordinary lines still get a break between them, which is the only
         * thing that separates them.
         */
        $html = '';

        foreach ($parts as $index => $part) {
            $previous = $parts[$index - 1] ?? null;

            if ($previous !== null && ! $part['bullet'] && ! $previous['bullet']) {
                $html .= '<br>';
            }

            $html .= $part['html'];
        }

        return new HtmlString($html);
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
