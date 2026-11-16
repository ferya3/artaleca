<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Shop\Shop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The shop's pages exist only while the shop is switched on. Off, they are a
 * plain 404 — not a "coming soon", which search engines would index.
 *
 * Order pages and the payment callback are deliberately *not* behind this:
 * closing the shop must not strand an order that is half-way through the bank.
 */
class EnsureShopIsOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Shop::enabled(), 404);

        return $next($request);
    }
}
