@props([
    'src' => null,
    'alt' => '',
    'seed' => 'arta',
    'ratio' => '4/3',
    // `cover` fills the box and crops what does not fit; `contain` fits the
    // whole picture inside it and letterboxes the remainder against the box's
    // own background. Photographs want the first, artwork the second.
    'fit' => 'cover',
    'tone' => 'light',
    'eager' => false,
    'sizes' => '(min-width: 1024px) 33vw, 100vw',
    'mobileSrc' => null,
    'fill' => false,
    'preloadMedia' => null,
    // The night versions. Both default to the day ones, which is the whole
    // point: a photograph needs one upload and behaves exactly as before.
    'darkSrc' => null,
    'darkMobileSrc' => null,
])

@php
    $darkSrc = $darkSrc ?: $src;
    $darkMobileSrc = $darkMobileSrc ?: $mobileSrc;

    /*
     * Only render two images when the two themes genuinely differ. Otherwise
     * this component emits exactly what it always did — one tag, one file, one
     * preload — so the second slot costs nothing until it is used.
     */
    $themed = ($darkSrc !== $src) || ($darkMobileSrc !== $mobileSrc);
@endphp

@php
    /*
     * When no photograph has been uploaded yet, render a deterministic
     * "granule field" instead of a grey box or a broken image: circles laid out
     * from a hash of the record's slug, so every product gets its own stable,
     * on-brand image that costs zero requests. The moment a real photo is
     * uploaded it takes over with no template change.
     *
     * `tone="dark"` swaps the ground and the granule palette so the same
     * component works inside the dark hero and CTA bands without washing out.
     */
    $placeholder = blank($src);

    if ($placeholder) {
        $dark = $tone === 'dark';

        $rng = crc32((string) $seed) ?: 1;
        $next = function (int $max) use (&$rng): int {
            // xorshift32 — cheap, stable across PHP versions, and good enough
            // for scattering decorative circles deterministically.
            $rng ^= ($rng << 13) & 0xFFFFFFFF;
            $rng ^= $rng >> 17;
            $rng ^= ($rng << 5) & 0xFFFFFFFF;

            return abs($rng) % max($max, 1);
        };

        // Two families of granule, mixed: clay for the fired-clay colour of the
        // product, ink for the shadowed granules behind them. A single hue
        // reads as decoration; two read as material.
        $granules = [];
        for ($i = 0; $i < 54; $i++) {
            $isClay = $next(100) < 62;

            $granules[] = [
                'cx' => $next(430) - 15,
                'cy' => $next(330) - 15,
                'r' => 7 + $next(23),
                'fill' => $isClay
                    ? ($dark ? 'var(--color-brand-500)' : 'var(--color-brand-600)')
                    : ($dark ? 'var(--color-night-300)' : 'var(--color-ink-700)'),
                'o' => ($isClay ? 34 + $next(46) : 10 + $next(22)) / 100,
            ];
        }
    }
@endphp

@php
    /*
     * The ratio is a class, not `style="aspect-ratio: …"`.
     *
     * A nonce authorises <style> elements; it does nothing for style
     * *attributes*, which `style-src-attr` blocks outright. The inline version
     * was therefore silently discarded on every image on the site — the box
     * reserved no height at all, which the placeholder SVG happened to hide
     * because it brings its own intrinsic size. A real uploaded photograph is
     * `h-full` inside that box and would have collapsed.
     *
     * Mapping to literal class names also keeps them statically visible to
     * Tailwind's scanner, which an interpolated `aspect-[{{ $ratio }}]` would
     * not be.
     */
    $aspect = match ($ratio) {
        '16/9' => 'aspect-[16/9]',
        '3/2' => 'aspect-[3/2]',
        '1/1' => 'aspect-square',
        '3/4' => 'aspect-[3/4]',
        default => 'aspect-[4/3]',
    };

    /*
     * `fill` drops the ratio and simply covers the parent. An aspect ratio and
     * a minimum height together derive a *width* from that height — 50vh at 4/3
     * is 563px, which overflowed a 390px phone — so a box whose size is decided
     * by its container must not carry a ratio at all.
     */
    if ($fill) {
        $aspect = 'absolute inset-0 h-full w-full';
    }
@endphp

@php
    /*
     * Literal class names, for the same reason the aspect ratio is one: an
     * interpolated `object-{{ $fit }}` is invisible to Tailwind's scanner and
     * would simply not be generated.
     */
    $objectFit = $fit === 'contain' ? 'object-contain' : 'object-cover';
@endphp

@php
    /*
     * `position` is chosen here rather than by writing both and hoping: two
     * position utilities on one element are resolved by Tailwind's own
     * ordering, not by the order they appear in the attribute — so emitting
     * `relative absolute` left the filling image in flow, doubling the height
     * of the block it was supposed to sit behind.
     */
    $position = $fill ? '' : 'relative';
@endphp

<div {{ $attributes->merge([
    'class' => trim($position.' overflow-hidden '.$aspect).' '
        .($placeholder && ($tone === 'dark') ? 'bg-night-900' : 'bg-ink-100'),
]) }}>
    @if ($placeholder)
        <svg viewBox="0 0 400 300" class="h-full w-full" preserveAspectRatio="xMidYMid slice" role="img"
             @if (filled($alt)) aria-label="{{ $alt }}" @else aria-hidden="true" @endif>
            <rect width="400" height="300" fill="{{ $dark ? 'var(--color-night-900)' : 'var(--color-ink-100)' }}"/>
            @foreach ($granules as $g)
                <circle cx="{{ $g['cx'] }}" cy="{{ $g['cy'] }}" r="{{ $g['r'] }}"
                        fill="{{ $g['fill'] }}" fill-opacity="{{ $g['o'] }}"/>
            @endforeach
        </svg>
    @else
        {{-- An eager image is by definition above the fold, so it is also the
             page's LCP candidate and the only one worth preloading. --}}
        @if (! $themed)
            <x-picture
                :src="$src"
                :mobile-src="$mobileSrc"
                :alt="$alt"
                :sizes="$sizes"
                :eager="$eager"
                :preload="$eager"
                :preload-media="$preloadMedia"
                class="h-full w-full {{ $objectFit }}"
            />
        @else
            {{--
                Two files, one shown.

                The choice is made in CSS rather than by a `<source media>`,
                because `prefers-color-scheme` only knows what the operating
                system says — it cannot see the site's own toggle. These
                wrappers are driven by the same media-query-plus-attribute pair
                as the palette, so the picture follows an explicit choice as
                well as a system one.

                `display: contents` on the shown half keeps it out of layout
                entirely, so the box still sizes exactly as it does with a
                single image.
            --}}
            <span class="theme-only-light">
                <x-picture
                    :src="$src"
                    :mobile-src="$mobileSrc"
                    :alt="$alt"
                    :sizes="$sizes"
                    :eager="$eager"
                    :preload="$eager"
                    :preload-media="$preloadMedia"
                    theme="light"
                    class="h-full w-full {{ $objectFit }}"
                />
            </span>

            <span class="theme-only-dark">
                <x-picture
                    :src="$darkSrc"
                    :mobile-src="$darkMobileSrc"
                    :alt="$alt"
                    :sizes="$sizes"
                    :eager="$eager"
                    :preload="$eager"
                    :preload-media="$preloadMedia"
                    theme="dark"
                    class="h-full w-full {{ $objectFit }}"
                />
            </span>
        @endif
    @endif
</div>
