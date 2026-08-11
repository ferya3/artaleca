@props(['showWordmark' => true])

@php
    // An uploaded file wins over the drawn mark, and each theme gets its own —
    // a logo lettered in dark green needs a light version on a dark header.
    $uploaded = site_image('media.logo');
    $themed = filled($uploaded['dark']) && $uploaded['dark'] !== $uploaded['light'];
@endphp

@if (filled($uploaded['light']))
    <span {{ $attributes->merge(['class' => 'inline-flex items-center']) }}>
        @if ($themed)
            <span class="theme-only-light">
                <img src="{{ $uploaded['light'] }}" alt="{{ config('site.company.brand') }}"
                     class="h-9 w-auto max-w-[11rem] object-contain lg:h-11" decoding="async">
            </span>
            <span class="theme-only-dark">
                <img src="{{ $uploaded['dark'] }}" alt="{{ config('site.company.brand') }}"
                     class="h-9 w-auto max-w-[11rem] object-contain lg:h-11" decoding="async">
            </span>
        @else
            <img src="{{ $uploaded['light'] }}" alt="{{ config('site.company.brand') }}"
                 class="h-9 w-auto max-w-[11rem] object-contain lg:h-11" decoding="async">
        @endif
    </span>
@else
    {{--
        The drawn mark, used until a file is uploaded.

        Inline SVG rather than an <img>: it costs no extra request, stays crisp
        at any size, and the deep green comes from `currentColor` so the same
        markup works on the light header and the dark footer without a second
        file. The leaf keeps its own green, which is the one fixed colour in the
        mark — it is the brand.
    --}}
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
        <svg
            viewBox="0 0 40 48"
            class="h-9 w-auto shrink-0 lg:h-11"
            role="img"
            aria-hidden="{{ $showWordmark ? 'true' : 'false' }}"
            @unless($showWordmark) aria-label="{{ config('site.company.brand') }}" @endunless
            fill="none"
        >
            {{-- The flame: a droplet drawn to a point, the kiln the clay is
                 fired in. --}}
            <path d="M20.4 2.5c6.4 6.7 10.4 12.4 10.4 18.4 0 7.4-4.9 12.6-11 12.6S8.8 28.3 8.8 20.9c0-6 5.2-11.7 11.6-18.4z"
                  fill="currentColor"/>

            {{-- The leaf inside it, its midrib cut back to the flame so the two
                 shapes read as one object rather than a sticker on top. --}}
            <path d="M19.2 33.2c-5.8-.2-10.4-4.3-10.4-9.9 0-5.4 4.8-9.6 11-11.2-.8 7.2-1 14.2-.6 21.1z"
                  fill="var(--color-brand-500)"/>
            <path d="M19.8 33.1c-.4-6.9-.2-13.9.6-21.1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>

            {{-- Two wisps of smoke. Strokes, not filled shapes: at 36px a filled
                 crescent collapses into a smudge, while a stroke keeps its line
                 all the way down. --}}
            <path d="M32.8 14.2c2.8 4 3.5 7.6 2.1 10.9-.7 1.7-1.9 3.3-3.6 4.8"
                  stroke="currentColor" stroke-width="2.4" stroke-linecap="round" fill="none"/>
            <path d="M35 30.6c.3 4.3-2 7.9-6.9 10.8"
                  stroke="currentColor" stroke-width="2.4" stroke-linecap="round" fill="none"
                  stroke-opacity="0.75"/>

            {{-- The grass the droplet lands in, closing the shape at the foot. --}}
            <path d="M7.2 35.6c2.6 4.6 6.9 7.7 12.8 9.4"
                  stroke="currentColor" stroke-width="2.4" stroke-linecap="round" fill="none"
                  stroke-opacity="0.85"/>
        </svg>

        @if ($showWordmark)
            <span class="flex flex-col leading-none" dir="ltr">
                <span class="text-[0.95rem] font-bold tracking-[0.22em]">ARTA</span>
                <span class="text-[0.95rem] font-bold tracking-[0.22em] text-brand-500">LECA</span>
            </span>
        @endif
    </span>
@endif
