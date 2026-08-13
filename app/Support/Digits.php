<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Persian digits for the Persian pages.
 *
 * Numbers reach the page from three directions — typed into the admin panel,
 * seeded into the database, and printed by templates (`number_format`, page
 * numbers, the year in the footer) — so there is no single point in the
 * application where they could all be formatted. There is, however, a single
 * point where they are all *rendered*: the HTML on its way out. That is where
 * this runs, from LocaliseDigits.
 *
 * Only what a reader sees is converted. Attributes are left alone, which keeps
 * `tel:` links dialable, `datetime=""` machine-readable, `value=""` submittable
 * and every URL intact; `<script>` is skipped, so the JSON-LD Google reads
 * keeps the Western digits the schema.org spec requires; `<textarea>` is
 * skipped so text on its way back to a form is never rewritten under the person
 * editing it.
 */
final class Digits
{
    /** ASCII digit → Extended Arabic-Indic (the Persian set). */
    private const MAP = [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
    ];

    /** Elements whose text content is data, not prose. */
    private const OPAQUE = 'script|style|textarea';

    /**
     * Opt-out for the odd run of text that is a code rather than a number —
     * a standard designation such as EN 13055-1, say. Put it on any element
     * and the digits inside it, and inside anything nested in it, are left
     * alone: `<span data-latin-digits>EN 13055-1</span>`.
     */
    private const OPT_OUT = 'data-latin-digits';

    /**
     * Convert the digits in a fragment of plain text.
     *
     * Persian orthography groups thousands with U+066C and marks decimals with
     * U+066B, but only where the character really is a separator: a comma or a
     * full stop with digits on both sides. One that merely follows a number is
     * punctuation and stays as it is, so "سال ۲۰۲۶." does not end in a decimal
     * point.
     */
    public static function text(string $text): string
    {
        $converted = preg_replace_callback(
            '/&\#?[0-9a-z]++;|[0-9][0-9,.]*+/i',
            static fn (array $m): string => str_starts_with($m[0], '&')
                ? $m[0]                       // an HTML entity is markup, not a number
                : self::number($m[0]),
            $text,
        );

        return $converted ?? $text;
    }

    /**
     * Convert the digits in a whole HTML document, leaving the markup alone.
     *
     * Splitting on tags rather than parsing: a DOM parse would rewrite the
     * document on the way back out — reordering attributes, closing tags the
     * templates deliberately leave open, and stripping the nonces off the
     * inline scripts. Here every tag is passed through byte for byte and only
     * the text between them is touched.
     */
    public static function html(string $html): string
    {
        $parts = preg_split('/(<[^>]*+>)/', $html, flags: PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return $html;
        }

        $out = '';

        // The element currently being skipped, and how deep we are inside
        // elements of the same name — so a <span data-latin-digits> wrapping
        // other spans ends where it really ends.
        $skipping = null;
        $depth = 0;

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if ($part[0] !== '<') {
                $out .= $skipping === null ? self::text($part) : $part;

                continue;
            }

            $out .= $part;

            if (preg_match('#^<(/?)\s*([a-z][a-z0-9-]*)#i', $part, $tag) !== 1) {
                continue;   // a comment, a doctype, or a stray "<"
            }

            $name = strtolower($tag[2]);
            $closing = $tag[1] === '/';

            if ($skipping !== null) {
                if ($name !== $skipping) {
                    continue;
                }

                $depth += $closing ? -1 : 1;

                if ($depth <= 0) {
                    $skipping = null;
                }

                continue;
            }

            if ($closing || str_ends_with(rtrim($part, '>'), '/')) {
                continue;
            }

            if (preg_match('#^(?:'.self::OPAQUE.')$#', $name) === 1
                || stripos($part, self::OPT_OUT) !== false) {
                $skipping = $name;
                $depth = 1;
            }
        }

        return $out;
    }

    private static function number(string $number): string
    {
        $number = (string) preg_replace('/(?<=[0-9]),(?=[0-9])/', '٬', $number);
        $number = (string) preg_replace('/(?<=[0-9])\.(?=[0-9])/', '٫', $number);

        return strtr($number, self::MAP);
    }
}
