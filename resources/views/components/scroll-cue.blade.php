@props(['href' => '#intro'])

{{--
    The "there is more below" cue at the foot of the hero.

    A real anchor to the next section rather than a decorative flourish: it
    works without JavaScript, it is focusable and announced, and `html` already
    carries `scroll-padding-top` so the target does not land under the header
    when it arrives.

    No visible label — the oval and the moving chevron say it on their own in
    every language, which is worth more here than a word that has to be
    translated three times. The name is still announced through `aria-label`,
    so nothing is lost to a screen reader.
--}}
<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'scroll-cue group absolute bottom-6 start-1/2 z-20 flex h-11 w-7 -translate-x-1/2 items-center justify-center rounded-full border border-white/30 transition-colors hover:border-white/70 rtl:translate-x-1/2']) }}
    aria-label="{{ content('common.scroll_down') }}"
>
    <svg viewBox="0 0 16 16" class="scroll-cue__chevron h-3.5 w-3.5 text-white/60 transition-colors group-hover:text-white"
         fill="none" aria-hidden="true">
        <path d="M3 6l5 5 5-5" stroke="currentColor" stroke-width="1.8"
              stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
</a>
