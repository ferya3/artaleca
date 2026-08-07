<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

final class Url
{
    /**
     * The current page in another language.
     *
     * Because slugs are shared across locales, the alternate URL is the same
     * route with one parameter swapped — no per-locale slug lookup, and no risk
     * of an hreflang pointing at a page that does not exist.
     */
    public static function forLocale(string $locale): string
    {
        $route = Route::current();
        $name = $route?->getName();

        if ($name === null || $name === 'root') {
            return route('home', ['locale' => $locale]);
        }

        $parameters = array_merge($route->parameters(), ['locale' => $locale]);

        try {
            return route($name, $parameters + Request::query());
        } catch (\Throwable) {
            // A route that cannot be rebuilt (missing optional binding, etc.)
            // still deserves a working language switch.
            return route('home', ['locale' => $locale]);
        }
    }

    /** @return array<string, string> locale => absolute URL */
    public static function alternates(): array
    {
        $alternates = [];

        foreach (Locales::codes() as $code) {
            $alternates[$code] = self::forLocale($code);
        }

        return $alternates;
    }

    /**
     * Canonical URL for the current request: path only, query dropped except
     * for parameters that genuinely produce different content (pagination,
     * catalogue filters). Everything else — tracking tags, session noise —
     * would otherwise split ranking signals across duplicate URLs.
     */
    public static function canonical(): string
    {
        $meaningful = ['page', 'category', 'application', 'type', 'q', 'grain'];

        $query = array_filter(
            Request::only($meaningful),
            fn ($value) => filled($value)
        );

        ksort($query);

        $url = Request::url();

        return $query === [] ? $url : $url.'?'.http_build_query($query);
    }
}
