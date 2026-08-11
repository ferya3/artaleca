@props([
    'src',
    'alt' => '',
    'sizes' => '(min-width: 1024px) 33vw, 100vw',
    'eager' => false,
    'preload' => false,
    'preloadMedia' => null,
    'mobileSrc' => null,
    'mobileSizes' => '100vw',
    'mobileUpTo' => '767.98px',
    // Set when this tag is one half of a themed pair, so the preload it pushes
    // is scoped to the theme it will actually be shown in.
    'theme' => null,
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

    /*
     * The art-directed source carries its *own* `width`/`height`.
     *
     * Those attributes on the <img> describe the desktop photograph, and the
     * browser reserves that ratio before it knows which source it will take —
     * so a taller phone crop pushed everything below it down the moment it
     * arrived. Measured on the applications infographic: a box reserved at
     * 348x218 that settled at 348x618.
     *
     * Only matters where the image sizes itself. Inside a fixed-ratio box the
     * intrinsic ratio never reaches the layout, so this is free there.
     */
    $mobileDimensions = $art ? Image::dimensions($mobileSrc) : null;
@endphp

{{-- `contents` removes the <picture> box from layout entirely. It is
     `display: inline` by default, so an <img class="h-full"> inside it has no
     definite parent height to resolve against and silently fails to fill. --}}
<picture class="contents">
    @if ($art)
        @if ($mobileSrcset)
            <source media="{{ $mobileQuery }}" type="image/webp"
                    srcset="{{ $mobileSrcset }}" sizes="{{ $mobileSizes }}"
                    @if ($mobileDimensions) width="{{ $mobileDimensions[0] }}" height="{{ $mobileDimensions[1] }}" @endif>
        @endif
        {{-- The original format as well, for a browser that cannot take WebP:
             without it such a browser would fall through to the desktop
             photograph rather than to this one. --}}
        <source media="{{ $mobileQuery }}" srcset="{{ $mobileSrc }}" sizes="{{ $mobileSizes }}"
                @if ($mobileDimensions) width="{{ $mobileDimensions[0] }}" height="{{ $mobileDimensions[1] }}" @endif>
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

@php
    /*
     * A themed pair pushes two preloads, one per colour scheme, so the browser
     * fetches the half it is about to show and not both. It only narrows the
     * viewport condition that may already be there — an `and` of the two, since
     * a preload takes a single media condition.
     *
     * This follows the *system* preference, which is what decides the theme on
     * a first paint. A visitor who has flipped the toggle may get the other
     * file preloaded; the right one still loads normally, so the cost is a hint
     * that missed, not a wrong picture.
     */
    $preloadQuery = $preloadMedia;

    if ($theme) {
        $scheme = "(prefers-color-scheme: {$theme})";
        $preloadQuery = $preloadQuery ? "{$preloadQuery} and {$scheme}" : $scheme;
    }
@endphp

@if ($preload && $srcset)
    {{-- Preload is reserved for whichever image is the LCP element on this
         viewport. The two heroes are separate blocks, so each carries its own
         media query and a phone never eagerly fetches the desktop photograph
         it is not going to display.

         Deliberately not wrapped in `@once`: that is keyed per call site, so
         with one component rendered twice the second push would be silently
         dropped — and the desktop hero would lose its preload. Only the heroes
         pass `preload`, so there is nothing to deduplicate. --}}
    @push('head')
        <link rel="preload" as="image" fetchpriority="high"
              @if ($preloadQuery) media="{{ $preloadQuery }}" @endif
              imagesrcset="{{ $srcset }}" imagesizes="{{ $sizes }}">
    @endpush
@endif
