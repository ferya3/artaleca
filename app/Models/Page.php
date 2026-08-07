<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Concerns\Publishable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A free-form content page.
 *
 * The About / Quality / Plant pages keep their bespoke layouts — they are
 * structured presentations, not prose. This model covers everything an editor
 * needs to add afterwards without a developer, and is served by a catch-all
 * route registered after every named route so it can never shadow one.
 */
class Page extends Model
{
    use HasFactory;
    use HasTranslations;
    use Publishable;

    protected array $translatable = ['title', 'lead', 'body', 'meta_title', 'meta_description'];

    protected $fillable = [
        'slug', 'title', 'lead', 'body', 'hero_image',
        'meta_title', 'meta_description', 'show_in_footer', 'position', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'lead' => 'array',
            'body' => 'array',
            'meta_title' => 'array',
            'meta_description' => 'array',
            'show_in_footer' => 'boolean',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
