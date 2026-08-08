{{--
    The hero's moving background.

    Four layers, all decorative and all `aria-hidden` — the headline above them
    is the content, and nothing here carries meaning a screen reader needs.

    Every layer animates `transform` or `opacity` and nothing else, so the whole
    thing runs on the compositor: no layout, no repaint, no main-thread work
    competing with the LCP text sitting on top of it. Each drift translates by
    exactly one pattern tile, which is what makes an infinite loop seamless
    rather than visibly snapping back.

    The subject is the material and the process: a rotary kiln glow breathing
    behind a slow drift of expanded-clay granules, over the blueprint grid the
    rest of the dark bands already use.

    `prefers-reduced-motion` freezes all of it through the global base rule in
    app.css — there is no separate opt-out to keep in sync here.
--}}
<div class="hero-motion" aria-hidden="true">
    {{-- 1. Kiln glow. One warm radial that swells and drifts, off-centre so it
            reads as a heat source rather than as a vignette. --}}
    <div class="hero-motion__glow"></div>

    {{-- 2. The blueprint grid, panning diagonally by exactly one 72px tile. --}}
    <div class="hero-motion__grid hairline-grid"></div>

    {{-- 3 & 4. Two granule fields at different scales and speeds. The parallax
            between them is what gives the band depth; a single layer just looks
            like a texture sliding. --}}
    <div class="hero-motion__granules hero-motion__granules--far">
        <svg width="100%" height="100%">
            <defs>
                <pattern id="granules-far" width="180" height="180" patternUnits="userSpaceOnUse">
                    <circle cx="28" cy="34" r="5" fill="var(--color-clay-500)" fill-opacity="0.07"/>
                    <circle cx="96" cy="20" r="3" fill="var(--color-ink-300)" fill-opacity="0.05"/>
                    <circle cx="148" cy="52" r="6" fill="var(--color-clay-600)" fill-opacity="0.06"/>
                    <circle cx="62" cy="88" r="4" fill="var(--color-ink-200)" fill-opacity="0.035"/>
                    <circle cx="124" cy="112" r="3.5" fill="var(--color-clay-400)" fill-opacity="0.05"/>
                    <circle cx="22" cy="136" r="7" fill="var(--color-clay-500)" fill-opacity="0.045"/>
                    <circle cx="90" cy="152" r="4.5" fill="var(--color-ink-300)" fill-opacity="0.04"/>
                    <circle cx="156" cy="146" r="3" fill="var(--color-clay-300)" fill-opacity="0.055"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#granules-far)"/>
        </svg>
    </div>

    <div class="hero-motion__granules hero-motion__granules--near">
        <svg width="100%" height="100%">
            <defs>
                <pattern id="granules-near" width="260" height="260" patternUnits="userSpaceOnUse">
                    <circle cx="44" cy="52" r="11" fill="var(--color-clay-600)" fill-opacity="0.055"/>
                    <circle cx="164" cy="30" r="7" fill="var(--color-ink-400)" fill-opacity="0.03"/>
                    <circle cx="226" cy="104" r="9" fill="var(--color-clay-500)" fill-opacity="0.045"/>
                    <circle cx="112" cy="140" r="13" fill="var(--color-clay-700)" fill-opacity="0.06"/>
                    <circle cx="30" cy="196" r="8" fill="var(--color-ink-300)" fill-opacity="0.025"/>
                    <circle cx="196" cy="216" r="10" fill="var(--color-clay-400)" fill-opacity="0.04"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#granules-near)"/>
        </svg>
    </div>
</div>
