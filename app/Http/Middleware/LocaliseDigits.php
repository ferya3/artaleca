<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Digits;
use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rewrites the digits on the Persian pages into Persian digits.
 *
 * Editors type numbers on a Latin keyboard and the templates print them with
 * PHP's own formatters; a reader of the Persian site expects ۱۲۳. Rather than
 * ask every template and every editor to remember, the whole response is
 * converted on the way out. See App\Support\Digits for what is deliberately
 * left untouched.
 *
 * The admin panel is excluded. It is the one place where the numbers on screen
 * are the numbers in the database, and where they are about to be typed back
 * in — converting them there would quietly write Persian digits into columns
 * that are read as numbers.
 */
class LocaliseDigits
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->applies($request, $response)) {
            return $response;
        }

        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return $response;
        }

        $response->setContent(Digits::html($content));

        // Persian digits are three bytes where ASCII is one, so a length
        // measured before the rewrite would truncate the page mid-word.
        $response->headers->remove('Content-Length');

        return $response;
    }

    private function applies(Request $request, Response $response): bool
    {
        if (Locales::current() !== 'fa') {
            return false;
        }

        if ($request->is('admin', 'admin/*')) {
            return false;
        }

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }

        return str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }
}
