<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Office;
use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * How to reach the company, and where it is.
 *
 * Two different kinds of fact, kept apart on purpose.
 *
 * A *place* is a row in `offices`: an address, its own phone, its own opening
 * hours. There can be any number of them — a sales office in Tehran, another in
 * Ardabil, the plant — and adding one is a form in the panel rather than a
 * deploy.
 *
 * A *desk* belongs to no building: the sales line, the export mailbox, the
 * general address. Those stay settings, because duplicating them onto every
 * office row is how three offices come to list three different sales numbers.
 *
 * Every template reads through this class rather than the config array or the
 * model, so the contact page, the footer and the structured data cannot end up
 * disagreeing about either kind.
 */
final class Contact
{
    /** Per-request memo: the footer and the page both want the same rows. */
    private const OFFICES_KEY = 'contact.offices';

    /** A desk: a way to reach the company that is not tied to an address. */
    public static function value(string $field): string
    {
        $stored = Setting::get('contact.'.$field);

        return filled($stored) && is_string($stored)
            ? $stored
            : (string) (config('site.contact.'.(self::CONFIG[$field] ?? $field)) ?? '');
    }

    /** A phone number as `tel:` wants it: no spaces. */
    public static function tel(string $field): string
    {
        return str_replace(' ', '', self::value($field));
    }

    /**
     * Every published place, offices first and the plant last.
     *
     * @return Collection<int, Office>
     */
    public static function offices(): Collection
    {
        if (! app()->bound(self::OFFICES_KEY)) {
            app()->scoped(self::OFFICES_KEY, fn () => Office::query()
                ->active()
                ->forContactPage()
                ->ordered()
                ->get());
        }

        return app()->make(self::OFFICES_KEY);
    }

    /**
     * The address that stands for the company — the first office, or failing
     * that whatever place exists. This is what the footer prints and what goes
     * into the Organization node.
     */
    public static function headOffice(): ?Office
    {
        return self::offices()->firstWhere('kind', Office::KIND_OFFICE)
            ?? self::offices()->first();
    }

    /** The works. What LocalBusiness has to point at, if it is published. */
    public static function plant(): ?Office
    {
        return self::offices()->firstWhere('kind', Office::KIND_PLANT);
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

    /**
     * Desk field → the key it falls back to under `config('site.contact')`.
     *
     * The addresses that used to be listed here moved into the `offices` table;
     * what remains is the handful of company-wide contact points, which are
     * still worth a shipped default so a fresh install is never blank.
     */
    private const CONFIG = [
        'email' => 'email',
        'sales_phone' => 'sales_phone',
        'sales_email' => 'sales_email',
        'export_email' => 'export_email',
        'whatsapp' => 'whatsapp',
    ];

    /**
     * Every desk field, in the order the panel shows them.
     *
     * @return list<string>
     */
    public static function fields(): array
    {
        return array_keys(self::CONFIG);
    }

    /**
     * The shipped value behind each desk field.
     *
     * The panel shows these as placeholders, so an empty box reads as "still
     * the value the site was built with" rather than as "nothing".
     *
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        $defaults = [];

        foreach (self::CONFIG as $field => $path) {
            $value = config('site.contact.'.$path);

            if (filled($value)) {
                $defaults[$field] = (string) $value;
            }
        }

        return $defaults;
    }
}
