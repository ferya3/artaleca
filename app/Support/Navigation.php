<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Application;
use App\Models\ProductCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * The site's navigation tree.
 *
 * Header, mobile menu and footer all read from here, so a new product category
 * appears in all three at once and the "current section" highlight can never
 * disagree between them.
 */
final class Navigation
{
    /**
     * Primary navigation. `match` is the route-name prefix used to decide
     * whether an item is the active section.
     *
     * @return list<array{label:string,route:string,match:string,children?:Collection}>
     */
    public static function primary(): array
    {
        return [
            [
                'label' => __('nav.products'),
                'route' => 'products.index',
                'match' => 'products.',
                'children' => self::productCategories(),
            ],
            [
                'label' => __('nav.applications'),
                'route' => 'applications.index',
                'match' => 'applications.',
                'children' => self::applications(),
            ],
            [
                'label' => __('nav.projects'),
                'route' => 'projects.index',
                'match' => 'projects.',
            ],
            [
                'label' => __('nav.about'),
                'route' => 'about',
                'match' => 'about',
            ],
            [
                'label' => __('nav.news'),
                'route' => 'news.index',
                'match' => 'news.',
            ],
            [
                'label' => __('nav.downloads'),
                'route' => 'downloads.index',
                'match' => 'downloads.',
            ],
            [
                'label' => __('nav.contact'),
                'route' => 'contact',
                'match' => 'contact',
            ],
        ];
    }

    /** @return Collection<int, ProductCategory> */
    public static function productCategories(): Collection
    {
        return self::cachedRows('nav.product-categories', ProductCategory::class);
    }

    /** @return Collection<int, Application> */
    public static function applications(): Collection
    {
        return self::cachedRows('nav.applications', Application::class);
    }

    /**
     * Cache a model's rows as plain attribute arrays and rehydrate them.
     *
     * The navigation is rendered on every request, so this is worth caching —
     * but the cached payload is deliberately raw arrays rather than Eloquent
     * objects: they serialise predictably, survive a framework upgrade, and
     * cost less to unserialise than a graph of models. `hydrate()` rebuilds
     * real model instances without touching the database, so callers and Blade
     * see no difference.
     *
     * @param  class-string<ProductCategory|Application>  $model
     * @return Collection<int, ProductCategory|Application>
     */
    private static function cachedRows(string $key, string $model): Collection
    {
        $rows = Cache::remember(
            $key,
            now()->addHours(6),
            fn () => $model::query()->active()->ordered()->get()
                ->map(fn ($record) => $record->getAttributes())
                ->all(),
        );

        return $model::hydrate($rows);
    }

    /**
     * True when the current route belongs to the given section.
     *
     * A trailing dot means "this route group" (`products.` matches
     * products.index and products.show); anything else is an exact match.
     */
    public static function isActive(string $match): bool
    {
        $current = Route::currentRouteName();

        if ($current === null) {
            return false;
        }

        return str_ends_with($match, '.')
            ? str_starts_with($current, $match)
            : $current === $match;
    }

    /**
     * Footer link groups. Kept separate from the header: a footer is a
     * site index, not a duplicate of the primary nav.
     *
     * @return array<string, list<array{label:string,route:string}>>
     */
    public static function footer(): array
    {
        return [
            __('nav.products') => [
                ['label' => __('nav.all_products'), 'route' => 'products.index'],
                ['label' => __('nav.applications'), 'route' => 'applications.index'],
                ['label' => __('nav.downloads'), 'route' => 'downloads.index'],
            ],
            __('nav.about') => [
                ['label' => __('nav.about'), 'route' => 'about'],
                ['label' => __('nav.quality'), 'route' => 'about.quality'],
                ['label' => __('nav.plant'), 'route' => 'about.plant'],
                ['label' => __('nav.projects'), 'route' => 'projects.index'],
            ],
            __('nav.contact') => [
                ['label' => __('nav.contact'), 'route' => 'contact'],
                ['label' => __('nav.quote'), 'route' => 'quote'],
                ['label' => __('nav.faq'), 'route' => 'faq'],
                ['label' => __('nav.news'), 'route' => 'news.index'],
            ],
        ];
    }

    /** Clears every cached navigation fragment; called when content is saved. */
    public static function flush(): void
    {
        Cache::forget('nav.product-categories');
        Cache::forget('nav.applications');
        Cache::forget('home.blocks');
        Cache::forget('sitemap.xml');
    }
}
