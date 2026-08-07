<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Concerns\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;
    use HasTranslations;
    use Publishable;

    public const GROUPS = ['general', 'technical', 'ordering', 'export'];

    protected array $translatable = ['question', 'answer'];

    protected $fillable = ['question', 'answer', 'group', 'position', 'is_active'];

    protected function casts(): array
    {
        return [
            'question' => 'array',
            'answer' => 'array',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeOfGroup(Builder $query, ?string $group): Builder
    {
        return blank($group) ? $query : $query->where('group', $group);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
