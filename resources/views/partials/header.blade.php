@php
    use App\Support\Contact;
    use App\Support\Locales;
    use App\Support\Navigation;
    use App\Support\Url;

    $nav = Navigation::primary();
    $alternates = Url::alternates();
@endphp

{{--
    Opaque, and deliberately *not* `backdrop-blur`. A backdrop-filter makes the
    element a containing block for its `position: fixed` descendants, which
    silently collapsed the full-screen mobile menu inside it to a 64px-tall
    strip — the menu opened, but measured zero pixels high. Solid white also
    costs no per-frame blur and keeps the header off the §24 glassmorphism list.

    The bar starts hidden and is revealed by scrolling — `data-revealed` is
    added by app.js and the hidden state lives in app.css, so the header is
    already out of the way at first paint rather than flashing in and out.
    The transition is on transform and opacity only, so it composites on the
    GPU without laying anything out again.

    `fixed`, not `sticky`: a sticky header keeps its space in the flow even
    while translated away, which left a blank band the height of the header
    above the hero. Out of flow, the page starts at the top of the viewport and
    the bar floats over it when it returns.
--}}
<header
    data-site-header
    class="fixed inset-x-0 top-0 z-50 border-b border-hairline bg-surface shadow-header
           transition-[transform,opacity] duration-300 ease-out"
>
    {{-- Utility strip: contact routes for a visitor who arrived ready to buy,
         and the language switcher. Hidden on mobile, where it becomes part of
         the full-screen menu instead. --}}
    <div class="hidden border-b border-hairline lg:block">
        <div class="container-page flex h-9 items-center justify-between gap-6 text-xs text-ink-500">
            <div class="flex items-center gap-5">
                <a href="tel:{{ Contact::tel('sales_phone') }}"
                   class="hover:text-ink-900 transition-colors">
                    <span class="text-ink-400">{{ content('common.sales') }}</span>
                    <span class="ltr-run ms-1.5 font-medium tabular">{{ Contact::value('sales_phone') }}</span>
                </a>
                <span class="h-3 w-px bg-hairline" aria-hidden="true"></span>
                <a href="mailto:{{ Contact::value('export_email') }}"
                   class="hover:text-ink-900 transition-colors">
                    <span class="text-ink-400">{{ content('common.export') }}</span>
                    <span class="ltr-run ms-1.5 font-medium">{{ Contact::value('export_email') }}</span>
                </a>
            </div>

            <nav class="flex items-center gap-5" aria-label="{{ content('nav.language') }}">
                <a href="{{ route('faq') }}" class="hover:text-ink-900 transition-colors">{{ content('nav.faq') }}</a>

                <span class="h-3 w-px bg-hairline" aria-hidden="true"></span>

                <x-theme-toggle class="-my-1 h-7 w-7" />

                <span class="h-3 w-px bg-hairline" aria-hidden="true"></span>

                <ul class="flex items-center gap-3">
                    @foreach (Locales::all() as $code => $meta)
                        <li>
                            <a
                                href="{{ $alternates[$code] }}"
                                hreflang="{{ $meta['hreflang'] }}"
                                lang="{{ $code }}"
                                data-keep-header
                                @if ($code === Locales::current()) aria-current="true" @endif
                                class="transition-colors {{ $code === Locales::current()
                                    ? 'font-semibold text-ink-900'
                                    : 'text-ink-500 hover:text-ink-900' }}"
                            >{{ $meta['native'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </div>

    {{--
        Mobile is a three-column grid — menu button, logo, spacer — so the logo
        sits optically centred rather than merely "after" the button. Grid
        columns follow the writing direction on their own, which puts the button
        at the start of the line in every language: on the right in Persian and
        Arabic, on the left in English, with no direction-specific classes.

        Desktop drops back to the usual flex row, where the logo leads.
    --}}
    <div class="container-page grid h-16 grid-cols-[2.75rem_1fr_2.75rem] items-center
                lg:flex lg:h-20 lg:items-center lg:justify-between lg:gap-8">
        @include('partials.mobile-nav', ['nav' => $nav, 'alternates' => $alternates])

        <a href="{{ route('home') }}"
           class="justify-self-center text-ink-900 shrink-0 lg:order-first lg:justify-self-start"
           aria-label="{{ config('site.company.brand') }}">
            <x-brand.logo />
        </a>

        {{-- Desktop navigation. Sections with children open on hover *and* on
             focus, so the menu is reachable by keyboard without any script. --}}
        <nav class="hidden lg:flex lg:items-center lg:gap-1" aria-label="{{ content('nav.main_navigation') }}">
            @foreach ($nav as $item)
                @php $active = Navigation::isActive($item['match']); @endphp

                @if (! empty($item['children']) && $item['children']->isNotEmpty())
                    <div class="group relative">
                        <a
                            href="{{ route($item['route']) }}"
                            @if ($active) aria-current="page" @endif
                            class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium transition-colors
                                   {{ $active ? 'text-brand-600' : 'text-ink-700 hover:text-ink-950' }}"
                        >
                            {{ $item['label'] }}
                            <svg viewBox="0 0 10 6" class="h-1.5 w-2.5 text-ink-400 transition-transform group-hover:rotate-180" aria-hidden="true">
                                <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                            </svg>
                        </a>

                        <div
                            class="invisible absolute top-full start-0 z-10 w-72 translate-y-1 overflow-hidden
                                   rounded-lg border border-hairline bg-surface opacity-0 shadow-lift transition-all duration-150
                                   group-hover:visible group-hover:translate-y-0 group-hover:opacity-100
                                   group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100"
                        >
                            <ul class="py-2">
                                @foreach ($item['children'] as $child)
                                    <li>
                                        <a
                                            href="{{ $item['match'] === 'products.'
                                                ? route('products.category', ['category' => $child])
                                                : route('applications.show', ['application' => $child]) }}"
                                            class="block px-4 py-2 text-sm text-ink-700 transition-colors hover:bg-ink-50 hover:text-brand-600"
                                        >
                                            {{ $child->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="border-t border-hairline">
                                <a href="{{ route($item['route']) }}"
                                   class="block px-4 py-2.5 text-xs font-semibold tracking-wide text-brand-600 hover:bg-brand-50">
                                    {{ content('common.view_all') }}
                                    <span class="inline-block rtl:rotate-180" aria-hidden="true">&rarr;</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @else
                    <a
                        href="{{ route($item['route']) }}"
                        @if ($active) aria-current="page" @endif
                        class="px-3 py-2 text-sm font-medium transition-colors
                               {{ $active ? 'text-brand-600' : 'text-ink-700 hover:text-ink-950' }}"
                    >{{ $item['label'] }}</a>
                @endif
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('search') }}"
                class="hidden h-10 w-10 items-center justify-center rounded-md text-ink-600 transition-colors hover:bg-ink-50 hover:text-ink-950 lg:flex"
                aria-label="{{ content('nav.search') }}"
            >
                <svg viewBox="0 0 20 20" class="h-4.5 w-4.5" fill="none" aria-hidden="true">
                    <circle cx="9" cy="9" r="6.25" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M13.5 13.5L18 18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </a>

            <a
                href="{{ route('quote') }}"
                class="hidden rounded-md bg-ink-950 px-5 py-2.5 text-sm font-semibold text-surface shadow-soft transition-[background-color,box-shadow] duration-200 hover:bg-brand-600 hover:shadow-lift lg:inline-flex"
            >{{ content('nav.quote') }}</a>

            {{--
                Language switcher for phones. On desktop it lives in the utility
                strip; on a phone that strip is hidden, so the only way to
                change language was to open the menu and scroll to the bottom —
                which nobody discovers.

                A globe is the one icon that reads as "language" without a
                label in any of the three scripts. It is a <details>, like the
                menu, so it opens, closes and is keyboard-operable with no
                script at all.
            --}}
            <details data-dismissable data-language-switcher class="relative lg:hidden">
                <summary
                    class="flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-md text-ink-800 transition-colors hover:bg-ink-50 marker:hidden [&::-webkit-details-marker]:hidden"
                    aria-label="{{ content('nav.language') }}"
                >
                    <svg viewBox="0 0 24 24" class="h-5.5 w-5.5" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>
                        <path d="M3 12h18" stroke="currentColor" stroke-width="1.6"/>
                        <path d="M12 3c2.5 2.4 3.9 5.6 3.9 9s-1.4 6.6-3.9 9c-2.5-2.4-3.9-5.6-3.9-9S9.5 5.4 12 3z"
                              stroke="currentColor" stroke-width="1.6"/>
                    </svg>
                    {{-- The active language, so the control says what it is
                         currently set to rather than only what it does. --}}
                    <span class="sr-only">{{ Locales::all()[Locales::current()]['native'] }}</span>
                </summary>

                <div class="absolute end-0 top-full z-50 mt-1 w-44 overflow-hidden rounded-lg border border-hairline bg-surface shadow-lift">
                    <ul class="py-1">
                        @foreach (Locales::all() as $code => $meta)
                            <li>
                                <a
                                    href="{{ $alternates[$code] }}"
                                    hreflang="{{ $meta['hreflang'] }}"
                                    lang="{{ $code }}"
                                    data-keep-header
                                    @if ($code === Locales::current()) aria-current="true" @endif
                                    class="flex items-center justify-between px-4 py-2.5 text-sm transition-colors
                                           {{ $code === Locales::current()
                                               ? 'font-semibold text-brand-600'
                                               : 'text-ink-700 hover:bg-ink-50' }}"
                                >
                                    {{ $meta['native'] }}
                                    @if ($code === Locales::current())
                                        <svg viewBox="0 0 14 14" class="h-3.5 w-3.5" fill="none" aria-hidden="true">
                                            <path d="M2 7.5l3.5 3.5L12 4" stroke="currentColor" stroke-width="1.8"
                                                  stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </details>
        </div>
    </div>
</header>
