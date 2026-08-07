<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Concerns\Publishable;
use App\Support\Search;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;
    use HasTranslations;
    use Publishable;

    protected array $translatable = [
        'name', 'tagline', 'summary', 'description', 'meta_title', 'meta_description',
    ];

    protected $fillable = [
        'product_category_id', 'slug', 'sku',
        'name', 'tagline', 'summary', 'description',
        'grain_min_mm', 'grain_max_mm', 'bulk_density_min', 'bulk_density_max',
        'particle_density', 'crushing_strength', 'thermal_conductivity',
        'water_absorption_24h', 'ph_value', 'fire_resistance_c',
        'specs', 'packaging', 'standards',
        'hero_image', 'gallery', 'datasheet_path',
        'meta_title', 'meta_description',
        'position', 'is_featured', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'tagline' => 'array',
            'summary' => 'array',
            'description' => 'array',
            'meta_title' => 'array',
            'meta_description' => 'array',
            'specs' => 'array',
            'packaging' => 'array',
            'standards' => 'array',
            'gallery' => 'array',
            'grain_min_mm' => 'float',
            'grain_max_mm' => 'float',
            'bulk_density_min' => 'integer',
            'bulk_density_max' => 'integer',
            'particle_density' => 'integer',
            'crushing_strength' => 'float',
            'thermal_conductivity' => 'float',
            'water_absorption_24h' => 'float',
            'ph_value' => 'float',
            'fire_resistance_c' => 'float',
            'position' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    /**
     * Products whose grain range overlaps the requested window.
     *
     * Overlap (not containment) is the useful test for a specifier who says
     * "I need something in the 4–8 mm area" — a 3–10 product should match.
     */
    public function scopeGrainBetween(Builder $query, ?float $min, ?float $max): Builder
    {
        if ($min !== null) {
            $query->where(fn (Builder $q) => $q->whereNull('grain_max_mm')->orWhere('grain_max_mm', '>=', $min));
        }

        if ($max !== null) {
            $query->where(fn (Builder $q) => $q->whereNull('grain_min_mm')->orWhere('grain_min_mm', '<=', $max));
        }

        return $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return blank($term)
            ? $query
            : Search::anyLike($query, ['sku', 'slug', 'name', 'summary'], $term);
    }

    /** e.g. "3 – 10 mm", or "0 – 3 mm" when only one bound is set. */
    public function grainRange(): ?string
    {
        if ($this->grain_min_mm === null && $this->grain_max_mm === null) {
            return null;
        }

        $min = $this->grain_min_mm === null ? null : rtrim(rtrim(number_format($this->grain_min_mm, 1, '.', ''), '0'), '.');
        $max = $this->grain_max_mm === null ? null : rtrim(rtrim(number_format($this->grain_max_mm, 1, '.', ''), '0'), '.');

        return match (true) {
            $min !== null && $max !== null => "{$min}–{$max}",
            $min !== null => "≥ {$min}",
            default => "≤ {$max}",
        };
    }

    public function bulkDensityRange(): ?string
    {
        if ($this->bulk_density_min === null && $this->bulk_density_max === null) {
            return null;
        }

        if ($this->bulk_density_min !== null && $this->bulk_density_max !== null) {
            return "{$this->bulk_density_min}–{$this->bulk_density_max}";
        }

        return (string) ($this->bulk_density_min ?? $this->bulk_density_max);
    }

    /** @return list<string> */
    public function galleryImages(): array
    {
        return array_values(array_filter($this->gallery ?? []));
    }

    public function primaryImage(): ?string
    {
        return $this->hero_image ?? ($this->galleryImages()[0] ?? null);
    }
}
