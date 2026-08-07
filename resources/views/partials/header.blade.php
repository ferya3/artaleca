@php
    use App\Support\Locales;
    use App\Support\Navigation;
    use App\Support\Url;

    $nav = Navigation::primary();
    $alternates = Url::alternates();
@endphp

<header
    data-site-header
    class="sticky top-0 z-50 bg-white/95 backdrop-blur-sm transition-shadow
           border-b border-transparent data-scrolled:border-hairline data-scrolled:shadow-header"
>
    {{-- Utility strip: contact routes for a visitor who arrived ready to buy,
         and the language switcher. Hidden on mobile, where it becomes part of
         the full-screen menu instead. --}}
    <div class="hidden border-b border-hairline lg:block">
        <div class="container-page flex h-9 items-center justify-between gap-6 text-xs text-ink-500">
            <div class="flex items-center gap-5">
                <a href="tel:{{ str_replace(' ', '', config('site.contact.sales_phone')) }}"
                   class="hover:text-ink-900 transition-colors">
                    <span class="text-ink-400">{{ __('common.sales') }}</span>
                    <span class="ltr-run ms-1.5 font-medium tabular">{{ config('site.contact.sales_phone') }}</span>
                </a>
                <span class="h-3 w-px bg-hairline" aria-hidden="true"></span>
                <a href="mailto:{{ config('site.contact.export_email') }}"
                   class="hover:text-ink-900 transition-colors">
                    <span class="text-ink-400">{{ __('common.export') }}</span>
                    <span class="ltr-run ms-1.5 font-medium">{{ config('site.contact.export_email') }}</span>
                </a>
            </div>

            <nav class="flex items-center gap-5" aria-label="{{ __('nav.language') }}">
                <a href="{{ route('faq') }}" class="hover:text-ink-900 transition-colors">{{ __('nav.faq') }}</a>

                <span class="h-3 w-px bg-hairline" aria-hidden="true"></span>

                <ul class="flex items-center gap-3">
                    @foreach (Locales::all() as $code => $meta)
                        <li>
                            <a
                                href="{{ $alternates[$code] }}"
                                hreflang="{{ $meta['hreflang'] }}"
                                lang="{{ $code }}"
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

    <div class="container-page flex h-16 items-center justify-between gap-8 lg:h-20">
        <a href="{{ route('home') }}" class="text-ink-900 shrink-0" aria-label="{{ config('site.company.brand') }}">
            <x-brand.logo />
        </a>

        {{-- Desktop navigation. Sections with children open on hover *and* on
             focus, so the menu is reachable by keyboard without any script. --}}
        <nav class="hidden lg:flex lg:items-center lg:gap-1" aria-label="{{ __('nav.main_navigation') }}">
            @foreach ($nav as $item)
                @php $active = Navigation::isActive($item['match']); @endphp

                @if (! empty($item['children']) && $item['children']->isNotEmpty())
                    <div class="group relative">
                        <a
                            href="{{ route($item['route']) }}"
                            @if ($active) aria-current="page" @endif
                            class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium transition-colors
                                   {{ $active ? 'text-clay-600' : 'text-ink-700 hover:text-ink-950' }}"
                        >
                            {{ $item['label'] }}
                            <svg viewBox="0 0 10 6" class="h-1.5 w-2.5 text-ink-400 transition-transform group-hover:rotate-180" aria-hidden="true">
                                <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                            </svg>
                        </a>

                        <div
                            class="invisible absolute top-full start-0 z-10 w-72 translate-y-1 overflow-hidden
                                   rounded-lg border border-hairline bg-white opacity-0 shadow-lift transition-all duration-150
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
                                            class="block px-4 py-2 text-sm text-ink-700 transition-colors hover:bg-ink-50 hover:text-clay-600"
                                        >
                                            {{ $child->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="border-t border-hairline">
                                <a href="{{ route($item['route']) }}"
                                   class="block px-4 py-2.5 text-xs font-semibold tracking-wide text-clay-600 hover:bg-clay-50">
                                    {{ __('common.view_all') }}
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
                               {{ $active ? 'text-clay-600' : 'text-ink-700 hover:text-ink-950' }}"
                    >{{ $item['label'] }}</a>
                @endif
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('search') }}"
                class="hidden h-10 w-10 items-center justify-center rounded-md text-ink-600 transition-colors hover:bg-ink-50 hover:text-ink-950 lg:flex"
                aria-label="{{ __('nav.search') }}"
            >
                <svg viewBox="0 0 20 20" class="h-4.5 w-4.5" fill="none" aria-hidden="true">
                    <circle cx="9" cy="9" r="6.25" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M13.5 13.5L18 18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </a>

            <a
                href="{{ route('quote') }}"
                class="hidden rounded-md bg-ink-950 px-5 py-2.5 text-sm font-semibold text-white shadow-soft transition-[background-color,box-shadow] duration-200 hover:bg-clay-600 hover:shadow-lift lg:inline-flex"
            >{{ __('nav.quote') }}</a>

            @include('partials.mobile-nav', ['nav' => $nav, 'alternates' => $alternates])
        </div>
    </div>
</header>
