<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Clears container bindings registered as `scoped` at the start of each request.
 *
 * The page's SEO state (title, structured data, breadcrumbs) is accumulated on
 * a scoped instance during a request. Under PHP-FPM every request gets a fresh
 * process, so that instance is naturally short-lived — but under Octane, or in
 * a test that issues two requests in one process, it is not: the first page's
 * JSON-LD would still be attached when the second page renders.
 *
 * Octane performs exactly this flush between requests. Doing it ourselves means
 * the application behaves identically on every runtime rather than depending on
 * which one happens to be deployed.
 */
class ResetScopedState
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->forgetScopedInstances();

        return $next($request);
    }
}
