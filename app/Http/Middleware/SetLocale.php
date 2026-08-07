<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the `{locale}` route prefix to the application and the URL generator.
 *
 * Every public route is nested under a `/{locale}` prefix, so the segment is
 * always present and always validated by the route pattern before we get here.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        if (! Locales::supports($locale)) {
            $locale = Locales::default();
        }

        App::setLocale($locale);

        // Makes every route() call default to the active locale, so templates
        // can write route('products.index') without threading the locale through.
        URL::defaults(['locale' => $locale]);

        /*
         * Controller actions receive route parameters *positionally*, in the
         * order they appear in the URI. Because `{locale}` is the first segment
         * of every route, leaving it in place would push it into the first
         * argument of every action — so `show(Product $product)` would be
         * handed the string "fa".
         *
         * The locale has already been applied to the app and the URL generator
         * above, so dropping it from the route's parameters keeps controller
         * signatures clean (`show(Product $product)`, not
         * `show(string $locale, Product $product)`) without losing anything.
         * This runs after SubstituteBindings, so model bindings are unaffected.
         */
        $request->route()?->forgetParameter('locale');

        setlocale(LC_TIME, match ($locale) {
            'fa' => ['fa_IR.UTF-8', 'fa_IR'],
            'ar' => ['ar_SA.UTF-8', 'ar_SA'],
            default => ['en_US.UTF-8', 'en_US'],
        });

        $response = $next($request);

        // Lets shared caches key on language without a Vary:Cookie penalty.
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
