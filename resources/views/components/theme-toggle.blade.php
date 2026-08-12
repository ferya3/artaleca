{{--
    Day/night switch.

    Which icon shows is decided in CSS, not by the script: the sun is visible
    under the light theme and the moon under the dark one, through the same
    `prefers-color-scheme` + `data-theme` pair the palette itself uses. That is
    what keeps the control honest at first paint — a script that has not run
    yet cannot leave it showing the wrong state, because it was never the
    script's job.

    A real <button> rather than a styled div, so it is focusable, has a role,
    and can be operated from the keyboard with nothing added.
--}}
<button
    type="button"
    data-theme-toggle
    {{ $attributes->merge(['class' => 'theme-toggle inline-flex items-center justify-center rounded-md text-ink-600 transition-colors hover:bg-ink-50 hover:text-ink-950']) }}
    aria-label="{{ content('common.theme_toggle') }}"
    title="{{ content('common.theme_toggle') }}"
>
    <svg class="theme-toggle__light h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle cx="12" cy="12" r="4.25" stroke="currentColor" stroke-width="1.6"/>
        <path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M18.7 5.3l-1.4 1.4M6.7 17.3l-1.4 1.4"
              stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
    </svg>

    <svg class="theme-toggle__dark h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M20 13.4A8.2 8.2 0 1 1 10.6 4a6.6 6.6 0 0 0 9.4 9.4z"
              stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
    </svg>

    {{-- Optional visible label, used in the mobile menu where the row is wide
         and unlabelled icons are harder to justify. --}}
    {{ $slot }}
</button>
