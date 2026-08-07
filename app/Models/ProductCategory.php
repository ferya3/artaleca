<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Concerns\Publishable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use HasFactory;
    use HasTranslations;
    use Publishable;

    protected array $translatable = ['name', 'summary', 'description', 'meta_title', 'meta_description'];

    protected $fillable = [
        'slug', 'name', 'summary', 'description', 'image', 'icon',
        'meta_title', 'meta_description', 'position', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'summary' => 'array',
            'description' => 'array',
            'meta_title' => 'array',
            'meta_description' => 'array',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function activeProducts(): HasMany
    {
        return $this->products()->where('is_active', true)->orderBy('position');
    }
}
