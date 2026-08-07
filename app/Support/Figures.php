<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;

/**
 * The headline numbers shown in the hero strip and on the about page.
 *
 * They live in the settings table so the company can update them without a
 * deploy — capacity changes when a line is commissioned, headcount drifts,
 * export markets are added. `config/site.php` only supplies the fallback used
 * before the settings row exists (a fresh install, or a test database).
 *
 * Labels and units stay in the language files: they are interface copy, and an
 * editor changing "annual capacity" to something else in one language only
 * would break the other two.
 */
final class Figures
{
    /** setting key => [config fallback, unit lang key, label lang key] */
    private const MAP = [
        'figures.annual_capacity_m3' => ['annual_capacity_m3', 'capacity_unit', 'capacity'],
        'figures.plant_area_m2' => ['plant_area_m2', 'area_unit', 'area'],
        'figures.kiln_lines' => ['kiln_lines', 'kilns_unit', 'kilns'],
        'figures.export_countries' => ['export_countries', 'countries_unit', 'countries'],
        'figures.employees' => ['employees', 'employees_unit', 'employees'],
    ];

    /** Raw numeric value for one figure, settings first then config. */
    public static function value(string $key): int
    {
        [$fallback] = self::MAP['figures.'.$key] ?? [$key];

        return (int) (Setting::get('figures.'.$key) ?? config("site.figures.{$fallback}", 0));
    }

    public static function since(): int
    {
        return (int) (Setting::get('figures.since') ?? config('site.figures.since', 1996));
    }

    /**
     * Everything the stat strip renders, already formatted.
     *
     * A figure set to zero is dropped rather than rendered as "0" — that is how
     * an editor removes one from the strip without a developer.
     *
     * @return list<array{value:string, unit:string, label:string}>
     */
    public static function strip(): array
    {
        $items = [];

        foreach (self::MAP as $settingKey => [$fallback, $unitKey, $labelKey]) {
            $value = (int) (Setting::get($settingKey) ?? config("site.figures.{$fallback}", 0));

            if ($value <= 0) {
                continue;
            }

            $items[] = [
                'value' => number_format($value),
                'unit' => __("common.figures.{$unitKey}"),
                'label' => __("common.figures.{$labelKey}"),
            ];
        }

        return $items;
    }

    /** Years in business, derived rather than stored so it can never go stale. */
    public static function yearsOfExperience(): int
    {
        return max(0, (int) now()->format('Y') - self::since());
    }
}
