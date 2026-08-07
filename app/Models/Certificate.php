<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Concerns\Publishable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;
    use HasTranslations;
    use Publishable;

    protected array $translatable = ['title', 'issuer'];

    protected $fillable = ['title', 'issuer', 'image', 'reference', 'year', 'position', 'is_active'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'issuer' => 'array',
            'year' => 'integer',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
