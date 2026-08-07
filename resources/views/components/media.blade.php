@props([
    'src' => null,
    'alt' => '',
    'seed' => 'arta',
    'ratio' => '4/3',
    'tone' => 'light',
    'eager' => false,
    'sizes' => '(min-width: 1024px) 33vw, 100vw',
])

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
                    ? ($dark ? 'var(--color-clay-500)' : 'var(--color-clay-600)')
                    : ($dark ? 'var(--color-ink-300)' : 'var(--color-ink-700)'),
                'o' => ($isClay ? 34 + $next(46) : 10 + $next(22)) / 100,
            ];
        }
    }
@endphp

<div {{ $attributes->merge([
    'class' => 'relative overflow-hidden '.($placeholder && ($tone === 'dark') ? 'bg-ink-900' : 'bg-ink-100'),
]) }} style="aspect-ratio: {{ $ratio }};">
    @if ($placeholder)
        <svg viewBox="0 0 400 300" class="h-full w-full" preserveAspectRatio="xMidYMid slice" role="img"
             @if (filled($alt)) aria-label="{{ $alt }}" @else aria-hidden="true" @endif>
            <rect width="400" height="300" fill="{{ $dark ? 'var(--color-ink-900)' : 'var(--color-ink-100)' }}"/>
            @foreach ($granules as $g)
                <circle cx="{{ $g['cx'] }}" cy="{{ $g['cy'] }}" r="{{ $g['r'] }}"
                        fill="{{ $g['fill'] }}" fill-opacity="{{ $g['o'] }}"/>
            @endforeach
        </svg>
    @else
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            sizes="{{ $sizes }}"
            class="h-full w-full object-cover"
            {{-- Above-the-fold imagery is fetched eagerly and given high
                 priority; everything else defers so it never competes with
                 the LCP element. --}}
            loading="{{ $eager ? 'eager' : 'lazy' }}"
            fetchpriority="{{ $eager ? 'high' : 'auto' }}"
            decoding="{{ $eager ? 'sync' : 'async' }}"
        >
    @endif
</div>
