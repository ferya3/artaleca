<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Concerns\Publishable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * An industry / use-case LECA is specified for: structural lightweight
 * concrete, geotechnical fill, green roofs, horticulture, insulation…
 */
class Application extends Model
{
    use HasFactory;
    use HasTranslations;
    use Publishable;

    protected array $translatable = ['name', 'summary', 'description', 'meta_title', 'meta_description'];

    protected $fillable = [
        'slug', 'name', 'summary', 'description', 'benefits',
        'icon', 'image', 'meta_title', 'meta_description', 'position', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'summary' => 'array',
            'description' => 'array',
            'meta_title' => 'array',
            'meta_description' => 'array',
            'benefits' => 'array',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }
}
