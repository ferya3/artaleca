@props(['href' => '#intro'])

{{--
    The "there is more below" cue at the foot of the hero.

    A real anchor to the next section rather than a decorative flourish: it
    works without JavaScript, it is focusable and announced, and `html` already
    carries `scroll-padding-top` so the target does not land under the header
    when it arrives. The chevron is the animation; the link is the meaning.
--}}
<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'scroll-cue group absolute bottom-5 start-1/2 z-20 flex -translate-x-1/2 flex-col items-center gap-1.5 rtl:translate-x-1/2']) }}
    aria-label="{{ __('common.scroll_down') }}"
>
    <span class="text-[0.625rem] font-medium uppercase tracking-[0.18em] text-white/45 transition-colors group-hover:text-white/80">
        {{ __('common.scroll_down') }}
    </span>

    <span class="scroll-cue__chevron flex h-6 w-6 items-center justify-center text-white/55 transition-colors group-hover:text-white">
        <svg viewBox="0 0 16 16" class="h-4 w-4" fill="none" aria-hidden="true">
            <path d="M3 6l5 5 5-5" stroke="currentColor" stroke-width="1.6"
                  stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
</a>
