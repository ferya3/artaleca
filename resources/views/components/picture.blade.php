@props([
    'src',
    'alt' => '',
    'sizes' => '(min-width: 1024px) 33vw, 100vw',
    'eager' => false,
    'preload' => false,
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
@endphp

<picture>
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
         first" even when several components ask. --}}
    @once
        @push('head')
            <link rel="preload" as="image" fetchpriority="high"
                  imagesrcset="{{ $srcset }}" imagesizes="{{ $sizes }}">
        @endpush
    @endonce
@endif
