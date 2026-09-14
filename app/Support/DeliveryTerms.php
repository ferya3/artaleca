<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;

/**
 * The delivery terms a buyer can pick on the quote form.
 *
 * The select used to be five bare Incoterm codes — EXW, FOB, CFR, CIF, DAP —
 * written into the Blade template. Two problems with that, and the second is
 * the one that costs enquiries: they were not editable without a deploy, and
 * to almost every buyer they mean nothing. A contractor ordering thirty cubic
 * metres for a roof screed has no reason to know what "FOB" is, and a field
 * whose options are unreadable is a field that gets left alone or guessed at.
 *
 * So each code now carries a sentence in the buyer's own language, and the
 * whole list lives in Panel → Settings → Delivery terms as one line per term:
 *
 *     EXW | تحویل درب کارخانه؛ بارگیری و حمل با خریدار
 *
 * The code on the left is what gets stored on the enquiry and what the sales
 * desk and the freight forwarder read — the standard is the standard, and a
 * quote written against "درب کارخانه" is not a quote anybody can act on
 * internationally. The sentence on the right is only ever shown to the buyer.
 */
final class DeliveryTerms
{
    public const SETTING = 'delivery.terms';

    /**
     * A paste cannot turn the select into a thousand rows, and a code is a
     * trade abbreviation rather than a sentence.
     */
    private const MAX_TERMS = 20;

    private const MAX_CODE = 12;

    /**
     * What the select shows: code => the text the buyer reads.
     *
     * @return array<string, string>
     */
    public static function options(?string $locale = null): array
    {
        $terms = self::terms($locale);

        $options = [];

        foreach ($terms as $code => $label) {
            // The code stays visible next to the explanation. A buyer's
            // forwarder asks for it by name, and hiding it would trade one
            // unreadable field for one that cannot be quoted against.
            $options[$code] = $label === $code ? $code : "{$label} ({$code})";
        }

        return $options;
    }

    /**
     * The codes the form will accept.
     *
     * Read from the same place the select is built from, so a term an editor
     * removes stops validating and one they add starts — without that, the
     * panel would offer an option the request rejects.
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::terms());
    }

    /**
     * One code, spelled out — for the enquiry screen, where somebody reading a
     * three-letter code needs to know what was actually agreed.
     */
    public static function explain(?string $code, ?string $locale = null): ?string
    {
        $code = trim((string) $code);

        if ($code === '') {
            return null;
        }

        $label = self::terms($locale)[strtoupper($code)] ?? null;

        return $label === null || $label === $code ? null : $label;
    }

    /**
     * The shipped list as the panel's textarea would hold it.
     *
     * Shown as the field's placeholder, so an empty box reads as "still the
     * shipped list" rather than as a list nobody has configured — the same
     * thing the contact fields do.
     */
    public static function defaultText(?string $locale = null): string
    {
        $lines = [];

        foreach (self::shipped($locale) as $code => $label) {
            $lines[] = "{$code} | {$label}";
        }

        return implode("\n", $lines);
    }

    /**
     * code => label, from the setting if an editor has written one and from the
     * language files otherwise.
     *
     * @return array<string, string>
     */
    private static function terms(?string $locale = null): array
    {
        $written = self::parse(self::written($locale));

        return $written === [] ? self::shipped($locale) : $written;
    }

    /**
     * The editor's list for one language, and only that language.
     *
     * This deliberately does not go through `Setting::get`, whose per-locale
     * fallback would hand an English buyer the Persian list. That fallback is
     * right for a headline or an address — one language's words beat a blank —
     * but wrong here: the shipped list exists in all three languages, so an
     * English box left empty has something better to fall back to than Persian
     * sentences an English speaker cannot read.
     */
    private static function written(?string $locale = null): ?string
    {
        $value = Setting::map()[self::SETTING] ?? null;

        if (is_string($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return null;
        }

        $value = $value[$locale ?? Locales::current()] ?? null;

        return is_string($value) ? $value : null;
    }

    /** @return array<string, string> */
    private static function shipped(?string $locale = null): array
    {
        $terms = __('form.delivery_terms_options', [], $locale ?? Locales::current());

        if (! is_array($terms)) {
            return [];
        }

        /** @var array<string, string> $terms */
        return $terms;
    }

    /**
     * One term per line, `CODE | explanation`.
     *
     * A line with no `|` is a bare code and becomes its own label, because
     * "EXW" on its own is exactly what the field used to offer and an editor
     * mid-edit should not see the list collapse. Anything with no usable code
     * is dropped rather than rendered as an empty option.
     *
     * @return array<string, string>
     */
    private static function parse(mixed $text): array
    {
        if (! is_string($text) || trim($text) === '') {
            return [];
        }

        $terms = [];

        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
            [$code, $label] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');

            // Uppercase A–Z and digits only: it is stored on the enquiry, sent
            // in an email and read by a forwarder, so it cannot carry Persian
            // digits, spaces or punctuation that arrived by paste.
            $code = mb_substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($code)) ?? '', 0, self::MAX_CODE);

            if ($code === '' || isset($terms[$code])) {
                continue;
            }

            $terms[$code] = $label === '' ? $code : $label;

            if (count($terms) >= self::MAX_TERMS) {
                break;
            }
        }

        return $terms;
    }
}
