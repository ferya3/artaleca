<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Concerns\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A place the company can be found: a sales office, or the plant.
 *
 * The site began with exactly two addresses fixed in a config file — one head
 * office, one plant — which is a fine model of a company right up until it opens
 * a second office. A row per location instead: the plant adds Ardabil beside
 * Tehran without anyone touching the code, and the contact page, the footer and
 * the structured data all read the same list.
 *
 * `kind` is not decoration. It is what tells the footer which address to print
 * as the works, and what keeps the LocalBusiness node pointing at the factory
 * rather than at whichever office happens to sort first.
 */
class Office extends Model
{
    use HasFactory;
    use HasTranslations;
    use Publishable;

    public const KIND_OFFICE = 'office';

    public const KIND_PLANT = 'plant';

    public const KINDS = [self::KIND_OFFICE, self::KIND_PLANT];

    protected array $translatable = ['name', 'address', 'hours'];

    protected $fillable = [
        'name', 'kind', 'address', 'hours', 'postal_code',
        'phone', 'fax', 'email', 'latitude', 'longitude',
        'position', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'address' => 'array',
            'hours' => 'array',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** The admin list is read by a person, so it shows "Plant", not "plant". */
    public function getKindLabelAttribute(): string
    {
        return (string) __('admin.office_kinds.'.$this->kind);
    }

    public function scopeOfKind(Builder $query, string $kind): Builder
    {
        return $query->where('kind', $kind);
    }

    /** The offices, then the plant — the order the contact page reads in. */
    public function scopeForContactPage(Builder $query): Builder
    {
        return $query->orderByRaw("CASE kind WHEN 'office' THEN 0 ELSE 1 END");
    }

    /** Ids never appear in a public URL; these records have no page of their own. */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function hasMap(): bool
    {
        return filled($this->latitude) && filled($this->longitude);
    }

    /**
     * The link everything can follow.
     *
     * Google's universal maps URL rather than a provider-specific one: on a
     * desktop it opens a map in the browser, and on a phone with the Google
     * Maps app installed the operating system hands it straight to the app.
     */
    public function mapUrl(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='
            .$this->latitude.','.$this->longitude;
    }

    /**
     * The same point as an RFC 5870 `geo:` URI.
     *
     * This is the one that asks the phone rather than the browser: Android
     * offers whichever navigation apps are installed — Neshan, Balad, Waze,
     * Google Maps — instead of assuming one. Nothing else handles the scheme
     * reliably, so it is offered alongside `mapUrl()` rather than instead of
     * it, and app.js swaps it in only where it is an improvement.
     */
    public function geoUri(): string
    {
        $point = $this->latitude.','.$this->longitude;

        return 'geo:'.$point.'?q='.$point.'('.rawurlencode((string) $this->name).')';
    }

    public function telephone(): string
    {
        return str_replace(' ', '', (string) $this->phone);
    }
}
