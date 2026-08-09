@props([
    'src',
    'alt' => '',
    'sizes' => '(min-width: 1024px) 33vw, 100vw',
    'eager' => false,
    'preload' => false,
    'mobileSrc' => null,
    'mobileSizes' => '100vw',
    'mobileUpTo' => '767.98px',
])

@php
    use App\Support\Image;

    /*
     * A stored image's intrinsic size travels in its filename
     * (`…-1600x1200.jpg`), put there by the upload pipeline. Two things fall
     * out of that with no filesystem access on the render path:
     *
     *  - `width`/`height` on the tag, so the browser reserves the right box
     *    before the bytes arrive and the page does not shift (CLS), and
     *  - the exact set of WebP derivatives that exists, so `srcset` can be
     *    written without stat-ing five candidate paths per image.
     *
     * Images stored before the pipeline existed have no dimensions in their
     * name and degrade to a plain tag rather than pointing at derivatives that
     * were never written.
     */
    $dimensions = Image::dimensions($src);
    $srcset = Image::srcset($src);

    /*
     * Art direction, not just resolution switching. `mobileSrc` is a different
     * photograph — cropped for a tall narrow screen — rather than a smaller
     * copy of the same one, which is why it needs `<source media>` and not
     * another `srcset` candidate.
     *
     * The browser evaluates sources in order and fetches exactly one, so the
     * phone never downloads the desktop image and vice versa. `max-width` in
     * the media query mirrors how the layout itself breaks, so the image
     * changes on the same line the design does.
     */
    $art = filled($mobileSrc) && $mobileSrc !== $src;
    $mobileSrcset = $art ? Image::srcset($mobileSrc) : null;
    $mobileQuery = "(max-width: {$mobileUpTo})";
@endphp

<picture>
    @if ($art)
        @if ($mobileSrcset)
            <source media="{{ $mobileQuery }}" type="image/webp"
                    srcset="{{ $mobileSrcset }}" sizes="{{ $mobileSizes }}">
        @endif
        {{-- The original format as well, for a browser that cannot take WebP:
             without it such a browser would fall through to the desktop
             photograph rather than to this one. --}}
        <source media="{{ $mobileQuery }}" srcset="{{ $mobileSrc }}" sizes="{{ $mobileSizes }}">
    @endif

    @if ($srcset)
        <source type="image/webp" srcset="{{ $srcset }}" sizes="{{ $sizes }}">
    @endif

    <img
        src="{{ $src }}"
        alt="{{ $alt }}"
        @if ($dimensions) width="{{ $dimensions[0] }}" height="{{ $dimensions[1] }}" @endif
        {{-- Above-the-fold imagery is fetched eagerly and given high priority;
             everything else defers so it never competes with the LCP element. --}}
        loading="{{ $eager ? 'eager' : 'lazy' }}"
        fetchpriority="{{ $eager ? 'high' : 'auto' }}"
        decoding="{{ $eager ? 'sync' : 'async' }}"
        {{ $attributes }}
    >
</picture>

@if ($preload && $srcset)
    {{-- Preload is reserved for the one image likely to be the LCP element.
         Preloading more does not make a page faster; it makes every preload
         compete for the same bandwidth. `@once` is what enforces "only the
         first" even when several components ask.

         With art direction there are two, each carrying the same `media` query
         as its source — so a phone still fetches exactly one, and it is the
         one it is about to display. A preload without the query would pull the
         desktop photograph onto a phone that never shows it. --}}
    @once
        @push('head')
            @if ($mobileSrcset)
                <link rel="preload" as="image" fetchpriority="high" media="{{ $mobileQuery }}"
                      imagesrcset="{{ $mobileSrcset }}" imagesizes="{{ $mobileSizes }}">
                <link rel="preload" as="image" fetchpriority="high" media="(min-width: {{ $mobileUpTo }})"
                      imagesrcset="{{ $srcset }}" imagesizes="{{ $sizes }}">
            @else
                <link rel="preload" as="image" fetchpriority="high"
                      imagesrcset="{{ $srcset }}" imagesizes="{{ $sizes }}">
            @endif
        @endpush
    @endonce
@endif
