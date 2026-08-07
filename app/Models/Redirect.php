<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Editor-managed 301/302 redirects.
 *
 * Deliberately exact-match on a normalised path rather than regex: a pattern
 * table is the kind of feature that silently redirects a whole section into a
 * loop, and the common need — "we changed this URL, keep the link juice" — is
 * served fine by exact matches.
 */
class Redirect extends Model
{
    use HasFactory;

    public const CACHE_KEY = 'redirects.map';

    protected $fillable = ['source', 'destination', 'status', 'hits', 'last_used_at', 'is_active'];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'hits' => 'integer',
            'last_used_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Leading slash, no trailing slash, lower case — so `/Old-Page/` and
     * `old-page` are the same rule and an editor cannot create a redirect that
     * silently never fires because of a stray slash.
     */
    public static function normalise(string $path): string
    {
        $path = strtok($path, '?') ?: $path;
        $path = '/'.trim(mb_strtolower(trim($path)), '/');

        return $path === '//' ? '/' : $path;
    }

    /**
     * The whole table as a source => [destination, status] map.
     *
     * Redirects are consulted on every 404-bound request, so the lookup must
     * not be a query per miss.
     *
     * @return array<string, array{destination:string, status:int}>
     */
    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()
            ->where('is_active', true)
            ->get(['source', 'destination', 'status'])
            ->mapWithKeys(fn (self $r) => [
                $r->source => ['destination' => $r->destination, 'status' => $r->status],
            ])
            ->all());
    }
}
