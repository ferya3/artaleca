@php
    use App\Support\Locales;
    use App\Support\Navigation;
@endphp

{{--
    A <details> element, not a JavaScript drawer: the menu opens, closes and is
    keyboard-operable with no script at all. app.js only adds the body-scroll
    lock while it is open.
--}}
<details data-mobile-menu class="lg:hidden [&[open]_.menu-open-icon]:hidden [&[open]_.menu-close-icon]:block">
    <summary
        class="flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-md text-ink-800 transition-colors hover:bg-ink-50 marker:hidden [&::-webkit-details-marker]:hidden"
        aria-label="{{ __('nav.open_menu') }}"
    >
        <svg viewBox="0 0 22 22" class="menu-open-icon h-5 w-5" fill="none" aria-hidden="true">
            <path d="M2 6h18M2 11h18M2 16h18" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
        </svg>
        <svg viewBox="0 0 22 22" class="menu-close-icon hidden h-5 w-5" fill="none" aria-hidden="true">
            <path d="M4.5 4.5l13 13M17.5 4.5l-13 13" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
        </svg>
    </summary>

    <div class="fixed inset-x-0 bottom-0 top-16 z-40 overflow-y-auto overscroll-contain bg-surface">
        <nav class="container-page py-6" aria-label="{{ __('nav.main_navigation') }}">
            <ul class="divide-y divide-hairline border-y border-hairline">
                @foreach ($nav as $item)
                    @php $active = Navigation::isActive($item['match']); @endphp

                    <li>
                        @if (! empty($item['children']) && $item['children']->isNotEmpty())
                            <details class="group">
                                <summary class="flex cursor-pointer list-none items-center justify-between py-4 text-base font-medium
                                                {{ $active ? 'text-brand-600' : 'text-ink-900' }} [&::-webkit-details-marker]:hidden">
                                    {{ $item['label'] }}
                                    <svg viewBox="0 0 12 12" class="h-3 w-3 text-ink-400 transition-transform group-open:rotate-45" aria-hidden="true">
                                        <path d="M6 1v10M1 6h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                </summary>

                                <ul class="pb-4 ps-4">
                                    <li>
                                        <a href="{{ route($item['route']) }}"
                                           class="block py-2 text-sm font-semibold text-brand-600">{{ __('common.view_all') }}</a>
                                    </li>
                                    @foreach ($item['children'] as $child)
                                        <li>
                                            <a
                                                href="{{ $item['match'] === 'products.'
                                                    ? route('products.category', ['category' => $child])
                                                    : route('applications.show', ['application' => $child]) }}"
                                                class="block py-2 text-sm text-ink-600"
                                            >{{ $child->name }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @else
                            <a
                                href="{{ route($item['route']) }}"
                                @if ($active) aria-current="page" @endif
                                class="block py-4 text-base font-medium {{ $active ? 'text-brand-600' : 'text-ink-900' }}"
                            >{{ $item['label'] }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>

            <div class="mt-6 grid gap-3">
                <a href="{{ route('quote') }}"
                   class="rounded-md bg-ink-950 px-5 py-3.5 text-center text-sm font-semibold text-surface shadow-soft">
                    {{ __('nav.quote') }}
                </a>
                <a href="{{ route('search') }}"
                   class="rounded-md border border-hairline px-5 py-3.5 text-center text-sm font-medium text-ink-800">
                    {{ __('nav.search') }}
                </a>

                {{-- The toggle lives here rather than in the header bar: that
                     row is a three-column grid whose whole point is an optically
                     centred logo, and a fourth control would put the logo back
                     off-centre on a phone. --}}
                <x-theme-toggle class="w-full gap-2.5 rounded-md border border-hairline px-5 py-3.5 text-sm font-medium text-ink-800">
                    <span>{{ __('common.theme_toggle') }}</span>
                </x-theme-toggle>
            </div>

            <div class="mt-8 border-t border-hairline pt-6">
                <p class="eyebrow eyebrow-muted mb-3">{{ __('nav.language') }}</p>
                <ul class="flex flex-wrap gap-2">
                    @foreach (Locales::all() as $code => $meta)
                        <li>
                            <a
                                href="{{ $alternates[$code] }}"
                                hreflang="{{ $meta['hreflang'] }}"
                                lang="{{ $code }}"
                                data-keep-header
                                class="inline-block rounded-md border px-4 py-2 text-sm transition-colors
                                       {{ $code === Locales::current()
                                           ? 'border-ink-950 bg-ink-950 text-surface'
                                           : 'border-hairline text-ink-700' }}"
                            >{{ $meta['native'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="mt-8 space-y-2 border-t border-hairline pt-6 text-sm text-ink-600">
                <a class="block" href="tel:{{ str_replace(' ', '', config('site.contact.sales_phone')) }}">
                    <span class="text-ink-400">{{ __('common.sales') }}</span>
                    <span class="ltr-run ms-1.5 font-medium">{{ config('site.contact.sales_phone') }}</span>
                </a>
                <a class="block" href="mailto:{{ config('site.contact.export_email') }}">
                    <span class="text-ink-400">{{ __('common.export') }}</span>
                    <span class="ltr-run ms-1.5 font-medium">{{ config('site.contact.export_email') }}</span>
                </a>
            </div>
        </nav>
    </div>
</details>
