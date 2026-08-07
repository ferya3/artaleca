<?php

declare(strict_types=1);

use App\Support\Seo;

if (! function_exists('seo')) {
    /**
     * The current request's SEO state. Controllers describe the page with it;
     * the layout renders it.
     */
    function seo(): Seo
    {
        return app(Seo::class);
    }
}

if (! function_exists('csp_nonce')) {
    /**
     * Nonce for inline <script>/<style> in the layout. Returns an empty string
     * outside the web middleware stack (console, tests) so Blade stays safe.
     */
    function csp_nonce(): string
    {
        return app()->bound('csp-nonce') ? (string) app('csp-nonce') : '';
    }
}
