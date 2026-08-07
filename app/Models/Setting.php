<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Locales;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Editor-owned copy that would otherwise be hard-coded in Blade.
 *
 * The whole table is small and read on nearly every request, so it is cached
 * as a single map and invalidated on any write.
 */
class Setting extends Model
{
    public const CACHE_KEY = 'settings.all';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value', 'group', 'is_translatable'];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_translatable' => 'boolean',
        ];
    }

    /** Per-request memo, so one page render reads the store once. */
    private const CONTAINER_KEY = 'settings.resolved';

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
        static::deleted(fn () => self::flushCache());
    }

    private static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        app()->forgetInstance(self::CONTAINER_KEY);
    }

    /**
     * The whole settings table as a key => value map.
     *
     * Memoised in the container as well as cached: a single page render calls
     * `get()` a dozen times (figures, SEO defaults, LocalBusiness), and with a
     * database cache driver each of those would otherwise be its own query.
     * `scoped` means the memo is discarded between requests under Octane —
     * ResetScopedState clears it — so a saved setting is never stale.
     *
     * @return array<string, mixed>
     */
    public static function map(): array
    {
        if (! app()->bound(self::CONTAINER_KEY)) {
            app()->scoped(self::CONTAINER_KEY, fn () => Cache::rememberForever(
                self::CACHE_KEY,
                fn () => static::query()->pluck('value', 'key')->all(),
            ));
        }

        return app()->make(self::CONTAINER_KEY);
    }

    /**
     * Read a setting, resolving per-locale maps to a plain string.
     *
     * `Setting::get('home.hero_headline')` returns the active-language value;
     * `Setting::get('home.hero_headline', locale: 'en')` forces one.
     */
    public static function get(string $key, mixed $default = null, ?string $locale = null): mixed
    {
        $value = static::map()[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        if (! is_array($value)) {
            return $value;
        }

        // A per-locale map is an array keyed entirely by known locale codes.
        $keys = array_keys($value);
        $isLocaleMap = $keys !== [] && array_diff($keys, Locales::codes()) === [];

        if (! $isLocaleMap) {
            return $value;
        }

        $locale = $locale ?? Locales::current();

        return $value[$locale]
            ?? $value[Locales::default()]
            ?? (array_filter($value)[array_key_first(array_filter($value)) ?? ''] ?? $default);
    }

    public static function put(string $key, mixed $value, string $group = 'general', bool $translatable = true): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'is_translatable' => $translatable],
        );
    }
}
