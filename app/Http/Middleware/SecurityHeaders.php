<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline response hardening.
 *
 * The CSP is nonce-based rather than 'unsafe-inline': the nonce is generated
 * per request, shared with Blade through the container, and stamped on the few
 * inline <script>/<style> blocks the layout emits. Anything injected into a
 * page later has no valid nonce and will not execute.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(24);
        app()->instance('csp-nonce', $nonce);

        $response = $next($request);

        // Skip binary/streamed downloads — headers there are pure overhead.
        if ($response->headers->has('Content-Disposition')) {
            return $response;
        }

        foreach ($this->headers($nonce) as $name => $value) {
            $response->headers->set($name, $value, false);
        }

        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }

    /** @return array<string, string> */
    protected function headers(string $nonce): array
    {
        return [
            'Content-Security-Policy' => $this->contentSecurityPolicy($nonce),
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'Permissions-Policy' => implode(', ', [
                'accelerometer=()',
                'autoplay=()',
                'camera=()',
                'display-capture=()',
                'geolocation=()',
                'gyroscope=()',
                'magnetometer=()',
                'microphone=()',
                'payment=()',
                'usb=()',
                'interest-cohort=()',
            ]),
        ];
    }

    protected function contentSecurityPolicy(string $nonce): string
    {
        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "img-src 'self' data: blob:",
            "font-src 'self'",
            "connect-src 'self'",
            "media-src 'self'",
            "manifest-src 'self'",
            "worker-src 'self' blob:",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'nonce-{$nonce}'",
        ];

        // Vite's dev server injects its client over http/ws on a separate port
        // and rewrites styles at runtime, neither of which survives a strict CSP.
        if (app()->environment('local') && file_exists(public_path('hot'))) {
            $host = trim((string) file_get_contents(public_path('hot')));

            $directives = array_map(function (string $directive) use ($host) {
                return match (true) {
                    str_starts_with($directive, 'script-src') => "{$directive} {$host} 'unsafe-eval'",
                    str_starts_with($directive, 'style-src') => "{$directive} {$host} 'unsafe-inline'",
                    str_starts_with($directive, 'connect-src') => "{$directive} {$host} ".str_replace('http', 'ws', $host),
                    default => $directive,
                };
            }, $directives);
        }

        return implode('; ', $directives);
    }
}
