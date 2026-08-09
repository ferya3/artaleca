<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Concerns\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;
    use HasTranslations;
    use Publishable;

    public const KINDS = ['representative', 'partner', 'client', 'association'];

    protected array $translatable = ['name', 'summary'];

    protected $fillable = ['slug', 'name', 'summary', 'logo', 'website', 'kind', 'position', 'is_active'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'summary' => 'array',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeOfKind(Builder $query, ?string $kind): Builder
    {
        return blank($kind) ? $query : $query->where('kind', $kind);
    }
}
