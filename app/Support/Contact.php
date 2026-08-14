<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;

/**
 * Where the company is and how to reach it.
 *
 * These details started life in `config/site.php`, which meant the only way to
 * correct a phone number or move an office was to edit a file on the server and
 * deploy. They are settings now, and the config array is the fallback: nothing
 * had to be filled in for the site to keep working, and a field left blank in
 * the panel still shows the shipped value rather than an empty line.
 *
 * Every reader goes through here rather than through `config('site.contact')`,
 * so the page, the footer and the structured data can never disagree about
 * which address is current.
 */
final class Contact
{
    /** A plain, language-independent value: a phone number, an email address. */
    public static function value(string $field): string
    {
        $stored = Setting::get('contact.'.$field);

        return filled($stored) && is_string($stored)
            ? $stored
            : (string) (config('site.contact.'.self::CONFIG[$field]) ?? '');
    }

    /** A value written once per language: an address, the opening hours. */
    public static function lines(string $field): string
    {
        $stored = Setting::get('contact.'.$field);

        if (filled($stored) && is_string($stored)) {
            return $stored;
        }

        $fallback = config('site.contact.'.self::CONFIG[$field]);

        if (! is_array($fallback)) {
            return (string) ($fallback ?? '');
        }

        return (string) ($fallback[Locales::current()] ?? $fallback[Locales::default()] ?? '');
    }

    /** @return array{lat: string, lng: string} */
    public static function geo(): array
    {
        return [
            'lat' => self::value('plant_lat'),
            'lng' => self::value('plant_lng'),
        ];
    }

    /**
     * The social profiles that have a URL, in a fixed order.
     *
     * Empty ones are dropped here rather than in each template, so a network
     * the plant does not use simply does not appear.
     *
     * @return array<string, string>
     */
    public static function social(): array
    {
        $links = [];

        foreach (['linkedin', 'instagram', 'youtube', 'aparat'] as $network) {
            $stored = Setting::get('social.'.$network);

            $url = filled($stored) && is_string($stored)
                ? $stored
                : (string) (config('site.social.'.$network) ?? '');

            if (filled($url)) {
                $links[$network] = $url;
            }
        }

        return $links;
    }

    /** A phone number as `tel:` wants it: no spaces. */
    public static function tel(string $field): string
    {
        return str_replace(' ', '', self::value($field));
    }

    /**
     * Setting field → the path it falls back to under `config('site.contact')`.
     *
     * Kept explicit rather than derived: the setting names say what an editor
     * is looking at ("plant_lines"), the config keys are nested the way the
     * original array happened to be shaped, and neither should have to change
     * to keep matching the other.
     */
    private const CONFIG = [
        'phone' => 'phone',
        'fax' => 'fax',
        'email' => 'email',
        'sales_phone' => 'sales_phone',
        'sales_email' => 'sales_email',
        'export_email' => 'export_email',
        'whatsapp' => 'whatsapp',
        'hq_lines' => 'hq.lines',
        'hq_postal_code' => 'hq.postal_code',
        'plant_lines' => 'plant.lines',
        'plant_lat' => 'plant.geo.lat',
        'plant_lng' => 'plant.geo.lng',
        'hours' => 'hours',
    ];

    /**
     * Every field this class knows about, in the order the panel shows them.
     *
     * @return list<string>
     */
    public static function fields(): array
    {
        return array_keys(self::CONFIG);
    }

    /**
     * The shipped value behind each field: a string, or a map keyed by locale.
     *
     * The panel shows these as placeholders, so an empty box reads as "still
     * the value the site was built with" rather than as "nothing".
     *
     * @return array<string, string|array<string, string>>
     */
    public static function defaults(): array
    {
        $defaults = [];

        foreach (self::CONFIG as $field => $path) {
            $value = config('site.contact.'.$path);

            if (is_array($value) || filled($value)) {
                $defaults[$field] = is_array($value) ? $value : (string) $value;
            }
        }

        return $defaults;
    }
}
