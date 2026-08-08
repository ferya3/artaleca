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
