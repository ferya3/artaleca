@props(['channel'])

{{--
    The four quick-contact marks, drawn in one style.

    Deliberately monochrome and deliberately not the official logos. Two
    reasons, and the second is the one that decided it. A brand mark is the
    brand's property and an approximation of one in its own colours is a worse
    thing to ship than a clean glyph. And of the three messengers, two have
    colours everybody knows and the third does not — so a "brand colours" set
    would have been two right and one invented, which reads as a mistake rather
    than as a decision.

    The name is next to every one of these in the widget, so identification is
    done by the word. The icon's job here is to make the row scannable, and one
    coherent set does that better than three foreign palettes in the corner of
    an industrial site.
--}}
@switch($channel)

    {{-- A round bubble with the handset knocked out of it, which is the shape
         everyone recognises even without the green. `evenodd` is what makes
         the handset a hole rather than a second solid. --}}
    @case('whatsapp')
        <svg viewBox="0 0 24 24" fill="currentColor" fill-rule="evenodd" aria-hidden="true"
             {{ $attributes->merge(['class' => 'h-5 w-5']) }}>
            <path d="M12 2.4a9.6 9.6 0 0 0-8.2 14.6L2.3 21.9l5.1-1.4A9.6 9.6 0 1 0 12 2.4zm0 1.8a7.8 7.8 0 1 1-4 14.5l-.4-.2-2.4.7.7-2.3-.2-.4A7.8 7.8 0 0 1 12 4.2z"/>
            <path d="M9.5 7.4c-.2 0-.5 0-.7.2-.3.2-.9.7-.9 1.7 0 1 .7 2 .8 2.1.1.2 1.4 2.2 3.4 3 1.9.8 2.3.7 2.8.6.4-.1 1.3-.5 1.5-1.1.2-.5.2-1 .1-1.1 0-.1-.2-.2-.4-.3l-1.6-.7c-.2-.1-.4 0-.5.1l-.6.7c-.1.2-.3.2-.5.1a6 6 0 0 1-1.5-1 5 5 0 0 1-.9-1.2c-.1-.2 0-.3.1-.4l.5-.5c.1-.2.2-.3.1-.5l-.7-1.6c-.1-.3-.2-.3-.4-.3z"/>
        </svg>
        @break

    {{-- The paper plane, one fill. --}}
    @case('telegram')
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"
             {{ $attributes->merge(['class' => 'h-5 w-5']) }}>
            <path d="M21.4 3.1 2.7 10.4c-.8.3-.8 1.5.1 1.7l4.4 1.4 1.7 5.3c.2.7 1.1.9 1.6.3l2.3-2.5 4.3 3.1c.6.4 1.4.1 1.6-.6l3.5-14.7c.2-.8-.6-1.5-1.4-1.3zm-2.9 3.1-8.1 6.4c-.2.1-.3.3-.3.5l-.4 2.7-1.2-3.7z"/>
        </svg>
        @break

    {{-- A speech bubble with three dots: the generic messenger glyph, which is
         the honest thing to draw for a mark this file should not invent. --}}
    @case('rubika')
        <svg viewBox="0 0 24 24" fill="currentColor" fill-rule="evenodd" aria-hidden="true"
             {{ $attributes->merge(['class' => 'h-5 w-5']) }}>
            <path d="M5.2 3.4h13.6A3.2 3.2 0 0 1 22 6.6v7.6a3.2 3.2 0 0 1-3.2 3.2h-5.9l-3.7 3.2c-.6.5-1.6.1-1.6-.7v-2.5H5.2A3.2 3.2 0 0 1 2 14.2V6.6a3.2 3.2 0 0 1 3.2-3.2zm0 1.8c-.8 0-1.4.6-1.4 1.4v7.6c0 .8.6 1.4 1.4 1.4h3.3c.5 0 .9.4.9.9v1.8l2.6-2.3c.2-.2.4-.3.6-.3h6.2c.8 0 1.4-.6 1.4-1.4V6.6c0-.8-.6-1.4-1.4-1.4z"/>
            <circle cx="8.3" cy="10.4" r="1.2"/>
            <circle cx="12" cy="10.4" r="1.2"/>
            <circle cx="15.7" cy="10.4" r="1.2"/>
        </svg>
        @break

    {{-- The handset, for the one channel that is not an app. --}}
    @case('phone')
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"
             {{ $attributes->merge(['class' => 'h-5 w-5']) }}>
            <path d="M7.4 2.6c.8 0 1.5.5 1.8 1.2l1 2.5c.3.7.1 1.5-.4 2l-1 1a12 12 0 0 0 4.9 4.9l1-1c.5-.5 1.3-.7 2-.4l2.5 1c.7.3 1.2 1 1.2 1.8v2.6c0 1.1-.9 2-2 2A17.4 17.4 0 0 1 2.8 6.6c0-1.1.9-2 2-2z"/>
        </svg>
        @break

@endswitch
