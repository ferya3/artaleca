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

    public function mapUrl(): string
    {
        return sprintf(
            'https://www.openstreetmap.org/?mlat=%s&mlon=%s#map=13/%s/%s',
            $this->latitude,
            $this->longitude,
            $this->latitude,
            $this->longitude,
        );
    }

    public function telephone(): string
    {
        return str_replace(' ', '', (string) $this->phone);
    }
}
