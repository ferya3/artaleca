<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Concerns\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    use HasFactory;
    use HasTranslations;
    use Publishable;

    public const CATEGORIES = ['catalogue', 'datasheet', 'certificate', 'guide'];

    protected array $translatable = ['title', 'description'];

    protected $fillable = [
        'slug', 'title', 'description', 'category', 'file_path', 'file_extension',
        'file_size', 'locale', 'download_count', 'requires_form', 'position', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'file_size' => 'integer',
            'download_count' => 'integer',
            'requires_form' => 'boolean',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** Language-neutral files (locale = null) are offered in every language. */
    public function scopeAvailableIn(Builder $query, string $locale): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('locale')->orWhere('locale', $locale));
    }

    public function scopeOfCategory(Builder $query, ?string $category): Builder
    {
        return blank($category) ? $query : $query->where('category', $category);
    }

    public function humanSize(): ?string
    {
        if (! $this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, $size < 10 && $unit > 0 ? 1 : 0).' '.$units[$unit];
    }
}
