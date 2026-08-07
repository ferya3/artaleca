<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies editor-managed 301/302 redirects.
 *
 * It runs *after* the request, only when the response is a 404. Checking
 * beforehand would put a lookup in front of every request on the site to serve
 * a table that is almost always irrelevant; checking on the way out means the
 * cost is paid only by URLs that were going to fail anyway.
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 404 || ! $request->isMethod('GET')) {
            return $response;
        }

        $map = Redirect::map();
        $source = Redirect::normalise($request->getPathInfo());

        if (! isset($map[$source])) {
            return $response;
        }

        $rule = $map[$source];

        // Fire-and-forget counter: useful for spotting rules that can be
        // retired, never worth slowing a redirect down for.
        Redirect::query()
            ->where('source', $source)
            ->update(['hits' => DB::raw('hits + 1'), 'last_used_at' => now()]);

        $destination = $rule['destination'];

        // A relative destination keeps the visitor on this host; an absolute
        // one is passed through untouched so a moved page can point off-site.
        if (! str_starts_with($destination, 'http://') && ! str_starts_with($destination, 'https://')) {
            $destination = url($destination);
        }

        return redirect()->away($destination, $rule['status']);
    }
}
