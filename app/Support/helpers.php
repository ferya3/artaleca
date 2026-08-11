<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Support\Seo;
use App\Support\SiteImage;

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

if (! function_exists('setting')) {
    /**
     * An editor-managed setting, read straight from a template.
     *
     * Exists so a Blade view can reach the settings table without an import:
     * a `use` statement cannot live inside a component slot, which is where
     * most page templates put their PHP. Reads are memoised per request, so
     * calling this from several templates costs one query, not several.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('site_image')) {
    /**
     * A managed site image, as the two files a page can use.
     *
     * Returns `['light' => …, 'dark' => …]` for the active language, with the
     * night file falling back to the day one. Same reason as `setting()`: a
     * `use` statement cannot live inside a component slot.
     *
     * @return array{light: ?string, dark: ?string}
     */
    function site_image(string $key): array
    {
        return SiteImage::get($key);
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
