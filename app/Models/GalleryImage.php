<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Concerns\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    use HasFactory;
    use HasTranslations;
    use Publishable;

    public const ALBUMS = ['plant', 'products', 'projects', 'events'];

    protected array $translatable = ['title', 'alt'];

    protected $fillable = ['path', 'title', 'alt', 'album', 'position', 'is_active'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'alt' => 'array',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeOfAlbum(Builder $query, ?string $album): Builder
    {
        return blank($album) ? $query : $query->where('album', $album);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Alt text is an accessibility and SEO requirement, not an option — fall
     * back to the caption rather than shipping an empty alt attribute.
     */
    public function altText(): string
    {
        return (string) ($this->alt ?? $this->title ?? '');
    }
}
