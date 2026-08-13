@php
    use App\Support\Locales;
    use App\Support\Url;

    $alternates = Url::alternates();
    $schema = seo()->schemaGraph();
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

<title>{{ seo()->getTitle() }}</title>
<meta name="description" content="{{ seo()->getDescription() }}">

{{-- Canonical strips tracking parameters so ranking signals are not split
     across a dozen URLs that render the same page. --}}
<link rel="canonical" href="{{ seo()->getCanonical() }}">

@if (seo()->isNoindex())
    <meta name="robots" content="noindex, follow">
@else
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
@endif

{{-- Every language version points at every other, plus itself, plus an
     x-default — the set Google requires to treat them as one page. --}}
@foreach ($alternates as $code => $href)
    <link rel="alternate" hreflang="{{ Locales::hreflang($code) }}" href="{{ $href }}">
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ $alternates[Locales::default()] }}">

<meta property="og:type" content="{{ seo()->getType() }}">
<meta property="og:site_name" content="{{ config('site.company.brand') }}">
<meta property="og:title" content="{{ seo()->getTitle() }}">
<meta property="og:description" content="{{ seo()->getDescription() }}">
<meta property="og:url" content="{{ seo()->getCanonical() }}">
<meta property="og:image" content="{{ seo()->getImage() }}">
<meta property="og:locale" content="{{ str_replace('-', '_', Locales::hreflang()) }}">
@foreach (Locales::others() as $code => $meta)
    <meta property="og:locale:alternate" content="{{ str_replace('-', '_', $meta['hreflang']) }}">
@endforeach

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ seo()->getTitle() }}">
<meta name="twitter:description" content="{{ seo()->getDescription() }}">
<meta name="twitter:image" content="{{ seo()->getImage() }}">
@if (filled(config('site.seo.twitter_handle')))
    <meta name="twitter:site" content="{{ config('site.seo.twitter_handle') }}">
@endif

@if (filled(config('site.seo.google_site_verification')))
    <meta name="google-site-verification" content="{{ config('site.seo.google_site_verification') }}">
@endif

<meta name="theme-color" content="#101010">
<meta name="format-detection" content="telephone=no">

<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">

{{-- The single variable font is on the critical path for first paint in every
     language, so it is preloaded rather than discovered inside the stylesheet. --}}
<link rel="preload" href="/fonts/vazirmatn-variable.woff2" as="font" type="font/woff2" crossorigin>

{{-- Filled by <x-media> for the single above-the-fold image, and by nothing
     else: a preload that is not on the critical path only steals bandwidth
     from one that is. --}}
@stack('head')

@vite(['resources/css/app.css', 'resources/js/app.js'])

{{--
    The header's hide-until-scrolled behaviour lives here, inline and on its
    own, rather than in the bundle — because it is the only navigation on a
    phone and it must fail *open*.

    The hiding rule in app.css is keyed on `data-autohide`, which only this
    script sets. So anything that stops it running — a blocked or slow bundle,
    a throw in unrelated code, JavaScript switched off — leaves the header
    simply visible, which is the safe outcome. The previous arrangement had CSS
    hide the bar by default and script reveal it, so a single script failure
    removed the site's navigation permanently and no amount of scrolling
    brought it back.

    Inline and synchronous in <head> so the attribute is set before first
    paint: deferring it to the module would show the bar and then snatch it
    away. It carries the CSP nonce; nothing here needs the bundle.
--}}
{{--
    Theme, applied before first paint.

    Only an *explicit* choice is written here. The system preference is handled
    in CSS by `prefers-color-scheme`, so a visitor who has never touched the
    toggle gets the right theme with no JavaScript at all — and a script that
    fails to run costs them nothing. Writing the attribute unconditionally
    would have made the stylesheet depend on the script, which is the mistake
    the header already taught once.

    Separate from the block below, and first, because it must not be skipped if
    anything in the header logic throws.
--}}
<script @nonce>
    (function () {
        try {
            var choice = localStorage.getItem('theme');

            if (choice === 'dark' || choice === 'light') {
                document.documentElement.setAttribute('data-theme', choice);
            }
        } catch (e) {
            // A blocked localStorage just means the system preference wins.
        }
    })();
</script>

<script @nonce>
    (function () {
        var root = document.documentElement;

        try {
            root.setAttribute('data-autohide', '');

            var REVEAL_AT = 140, HIDE_BELOW = 40, bar = null, ticking = false;

            function header() {
                return bar || (bar = document.querySelector('[data-site-header]'));
            }

            function update() {
                ticking = false;
                var h = header();
                if (!h) return;

                var menu = document.querySelector('[data-mobile-menu]');

                // The menu's close button is inside the header, so the bar has
                // to stay put while the menu is open however far the page has
                // scrolled.
                if ((menu && menu.open) || window.scrollY >= REVEAL_AT) {
                    h.setAttribute('data-revealed', '');
                } else if (window.scrollY <= HIDE_BELOW) {
                    h.removeAttribute('data-revealed');
                }
            }

            addEventListener('scroll', function () {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(update);
            }, { passive: true });

            /*
             * Release focus after a pointer click inside the bar.
             *
             * The header reveals itself for anything focused inside it, so a
             * keyboard user tabbing in never lands on a hidden control. A tap
             * also leaves focus behind — on the menu button, on the theme
             * toggle — and the bar then stayed pinned open at the top of the
             * page for the rest of the visit. Opening and closing the menu was
             * the loudest version: `data-revealed` came off correctly and the
             * CSS still would not hide it, because the rule is
             * `:not(:focus-within)`.
             *
             * Handled here, once, for the whole header rather than per control:
             * this is the second time the same shape of bug appeared, and the
             * next control added to that bar would have been the third.
             *
             * `event.detail` is the click count for a pointer and 0 for a
             * keyboard activation, which is exactly the distinction needed —
             * the keyboard user keeps both focus and header, the tap does not.
             * Deferred a frame so the click's own default action, opening the
             * <details>, happens first.
             */
            addEventListener('click', function (event) {
                if (event.detail === 0) return;

                var h = header();
                if (!h || !h.contains(event.target)) return;

                requestAnimationFrame(function () {
                    var active = document.activeElement;

                    if (active && active !== document.body && h.contains(active) && active.blur) {
                        active.blur();
                    }

                    update();
                });
            });

            /*
             * Switching language keeps your place. A language link is clicked
             * from inside the header, so landing at the top of the new page
             * would put the fixed bar over content the visitor never asked to
             * be taken to. Restoring the offset reveals the header on its own,
             * because the page is scrolled, and covers nothing.
             */
            var KEY = 'header:offset', saved = null;

            try {
                saved = sessionStorage.getItem(KEY);
                sessionStorage.removeItem(KEY);
            } catch (e) { /* storage denied: land at the top, as normal */ }

            addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-keep-header]').forEach(function (link) {
                    link.addEventListener('click', function () {
                        try {
                            sessionStorage.setItem(KEY, String(window.scrollY));
                        } catch (e) { /* ignore */ }
                    });
                });

                if (saved !== null && Number(saved) > 0) {
                    history.scrollRestoration = 'manual';
                    // Two-argument form: the options object with
                    // `behavior: 'instant'` is not understood everywhere.
                    window.scrollTo(0, Number(saved));
                }

                update();
            });
        } catch (e) {
            // Fail open. Whatever went wrong, the navigation stays reachable.
            root.removeAttribute('data-autohide');
        }
    })();
</script>

@if ($schema)
    {{--
        JSON_HEX_TAG is load-bearing, not cosmetic. The graph is built from
        editor-supplied text (product names, article titles), and inside a
        <script> block the HTML parser looks for `</script` before the JSON
        parser ever runs — so a title containing `</script><script>…` would
        break out and execute. Encoding < and > as </> is still valid
        JSON, and search engines read it identically.

        JSON_UNESCAPED_SLASHES is deliberately absent for the same reason: it
        would leave `</script>` intact in any URL-bearing value.
    --}}
    <script type="application/ld+json" @nonce>{!! json_encode(
        $schema,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
    ) !!}</script>
@endif
