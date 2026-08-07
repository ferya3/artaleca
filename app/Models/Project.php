<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Concerns\Publishable;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    use HasFactory;
    use HasTranslations;
    use Publishable;

    protected array $translatable = [
        'title', 'client', 'location', 'summary', 'body', 'meta_title', 'meta_description',
    ];

    protected $fillable = [
        'slug', 'title', 'client', 'location', 'country_code', 'year',
        'summary', 'body', 'scope', 'volume_m3', 'cover_image', 'gallery',
        'meta_title', 'meta_description', 'position', 'is_featured', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'client' => 'array',
            'location' => 'array',
            'summary' => 'array',
            'body' => 'array',
            'meta_title' => 'array',
            'meta_description' => 'array',
            'scope' => 'array',
            'gallery' => 'array',
            'year' => 'integer',
            'volume_m3' => 'integer',
            'position' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class);
    }

    public function scopeRecentFirst(Builder $query): Builder
    {
        return $query->orderByDesc('year')->orderBy('position')->orderByDesc('id');
    }

    /**
     * The project's scope-of-work bullets, flattened to the active locale.
     * (Named to avoid Eloquent's `scopeX` query-scope convention.)
     *
     * @return list<string>
     */
    public function workScope(?string $locale = null): array
    {
        $locale = $locale ?? Locales::current();
        $default = Locales::default();

        return array_values(array_filter(array_map(
            fn ($item) => is_array($item)
                ? ($item[$locale] ?? $item[$default] ?? reset($item) ?: null)
                : $item,
            $this->scope ?? []
        )));
    }

    /** @return list<string> */
    public function galleryImages(): array
    {
        return array_values(array_filter($this->gallery ?? []));
    }
}
