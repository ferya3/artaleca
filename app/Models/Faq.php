<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Concerns\Publishable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One flat list, in the order the editor puts it in.
 *
 * It used to be filed under four fixed groups. Nobody outside the plant knows
 * which bucket a question belongs in, and a reader looking for an answer reads
 * down the page rather than picking a category first — so the groups went and
 * `position` is now the only thing that arranges the page.
 */
class Faq extends Model
{
    use HasFactory;
    use HasTranslations;
    use Publishable;

    protected array $translatable = ['question', 'answer'];

    protected $fillable = ['question', 'answer', 'position', 'is_active'];

    protected function casts(): array
    {
        return [
            'question' => 'array',
            'answer' => 'array',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
