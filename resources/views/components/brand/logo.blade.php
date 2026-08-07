@props(['showWordmark' => true])

{{--
    Inline SVG rather than an <img>: it costs no extra request, stays crisp at
    any size, and inherits currentColor so the same markup works on the light
    header and the dark footer. The mark is three graded granules — the
    material itself, not a generic abstract shape.
--}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg
        viewBox="0 0 32 32"
        class="h-8 w-8 shrink-0"
        role="img"
        aria-hidden="{{ $showWordmark ? 'true' : 'false' }}"
        @unless($showWordmark) aria-label="{{ config('site.company.brand') }}" @endunless
        fill="none"
    >
        <rect x="0.75" y="0.75" width="30.5" height="30.5" rx="1.25" stroke="currentColor" stroke-opacity="0.28" stroke-width="1.5"/>
        <circle cx="11" cy="12" r="5.25" fill="var(--color-clay-500)"/>
        <circle cx="21.5" cy="17" r="3.5" fill="currentColor" fill-opacity="0.85"/>
        <circle cx="13.5" cy="22" r="2.25" fill="currentColor" fill-opacity="0.45"/>
    </svg>

    @if ($showWordmark)
        <span class="flex flex-col leading-none" dir="ltr">
            <span class="text-[0.95rem] font-bold tracking-[0.22em]">ARTA</span>
            <span class="text-[0.95rem] font-bold tracking-[0.22em] text-clay-500">LECA</span>
        </span>
    @endif
</span>
