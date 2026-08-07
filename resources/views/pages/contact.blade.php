{{-- `use` must sit at the top level of the compiled view: inside a component
     slot Blade compiles the block into a closure body, where it is a syntax
     error. --}}
@php
    use App\Support\Locales;
@endphp

<x-layouts.app>

    @php
        $contact = config('site.contact');
        $locale = Locales::current();
    @endphp

    <x-page-header
        :eyebrow="__('nav.contact')"
        :title="__('contact.title')"
        :lead="__('contact.intro')"
    />

    <section class="py-section">
        <div class="container-page grid gap-12 lg:grid-cols-12 lg:gap-16">

            {{-- ── Form ──────────────────────────────────────────────── --}}
            <div class="lg:col-span-7" id="form">
                <x-form.status />

                <h2 class="text-xl font-bold text-ink-950">{{ __('form.contact_title') }}</h2>
                <p class="mt-2 text-sm text-ink-600">{{ __('form.contact_intro') }}</p>

                <form method="POST" action="{{ route('contact.store') }}" class="relative mt-8 grid gap-5 sm:grid-cols-2">
                    @csrf
                    <x-form.honeypot />

                    <x-form.field name="name" :label="__('form.name')" required />
                    <x-form.field name="company" :label="__('form.company')" />
                    <x-form.field name="email" type="email" :label="__('form.email')" required />
                    <x-form.field name="phone" type="tel" :label="__('form.phone')" />

                    <x-form.field name="subject" :label="__('form.subject')" class="sm:col-span-2" />
                    <x-form.field name="message" type="textarea" :label="__('form.message')" required class="sm:col-span-2" />

                    <div class="sm:col-span-2">
                        <x-form.consent />
                    </div>

                    <div class="sm:col-span-2">
                        <x-button type="submit" size="lg">{{ __('common.send') }}</x-button>
                        <p class="mt-3 text-xs text-ink-500">{{ __('contact.response_note') }}</p>
                    </div>
                </form>

                <p class="mt-8 border-t border-hairline pt-6 text-sm text-ink-600">
                    <span class="font-medium text-ink-900">{{ __('contact.prefer_quote') }}</span>
                    <a href="{{ route('quote') }}" class="ms-1 text-clay-600 underline underline-offset-2">
                        {{ __('contact.prefer_quote_link') }}
                    </a>
                </p>
            </div>

            {{-- ── Contact details ───────────────────────────────────── --}}
            <aside class="lg:col-span-5">
                <div class="divide-y divide-hairline border border-hairline">

                    <div class="p-6">
                        <h2 class="eyebrow mb-4">{{ __('common.headquarters') }}</h2>
                        <address class="space-y-3 text-sm not-italic leading-relaxed text-ink-600">
                            <p class="text-ink-800">{{ $contact['hq']['lines'][$locale] ?? $contact['hq']['lines']['en'] }}</p>
                            <p>
                                <span class="text-ink-500">{{ __('common.postal_code') }}</span>
                                <span class="ltr-run tabular ms-2 text-ink-800">{{ $contact['hq']['postal_code'] }}</span>
                            </p>
                            <p>
                                <span class="text-ink-500">{{ __('common.phone') }}</span>
                                <a class="ltr-run ms-2 font-medium text-ink-900 hover:text-clay-600" href="tel:{{ str_replace(' ', '', $contact['phone']) }}">{{ $contact['phone'] }}</a>
                            </p>
                            <p>
                                <span class="text-ink-500">{{ __('common.fax') }}</span>
                                <span class="ltr-run ms-2 text-ink-800">{{ $contact['fax'] }}</span>
                            </p>
                        </address>
                    </div>

                    <div class="p-6">
                        <h2 class="eyebrow mb-4">{{ __('common.plant') }}</h2>
                        <address class="text-sm not-italic leading-relaxed text-ink-800">
                            {{ $contact['plant']['lines'][$locale] ?? $contact['plant']['lines']['en'] }}
                        </address>
                    </div>

                    <div class="grid gap-5 p-6 sm:grid-cols-2 lg:grid-cols-1">
                        <div>
                            <h2 class="eyebrow mb-2">{{ __('contact.sales_desk') }}</h2>
                            <a class="ltr-run block text-sm font-medium text-ink-900 hover:text-clay-600" href="tel:{{ str_replace(' ', '', $contact['sales_phone']) }}">{{ $contact['sales_phone'] }}</a>
                            <a class="ltr-run block text-sm text-ink-600 hover:text-clay-600" href="mailto:{{ $contact['sales_email'] }}">{{ $contact['sales_email'] }}</a>
                        </div>

                        <div>
                            <h2 class="eyebrow mb-2">{{ __('contact.export_desk') }}</h2>
                            <a class="ltr-run block text-sm text-ink-600 hover:text-clay-600" href="mailto:{{ $contact['export_email'] }}">{{ $contact['export_email'] }}</a>
                        </div>
                    </div>

                    <div class="p-6">
                        <h2 class="eyebrow mb-2">{{ __('common.working_hours') }}</h2>
                        <p class="text-sm text-ink-800">{{ $contact['hours'][$locale] ?? $contact['hours']['en'] }}</p>
                    </div>
                </div>

                {{-- A static map link rather than an embedded iframe: no
                     third-party script, no cookie, no CSP exception, and one
                     fewer render-blocking request. --}}
                <a
                    href="https://www.openstreetmap.org/?mlat={{ $contact['plant']['geo']['lat'] }}&mlon={{ $contact['plant']['geo']['lng'] }}#map=13/{{ $contact['plant']['geo']['lat'] }}/{{ $contact['plant']['geo']['lng'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-6 flex items-center justify-between border border-hairline bg-surface-muted px-5 py-4 text-sm transition-colors hover:border-ink-400"
                >
                    <span class="font-medium text-ink-900">{{ __('contact.find_us') }}</span>
                    <span class="ltr-run tabular text-xs text-ink-500">
                        {{ $contact['plant']['geo']['lat'] }}, {{ $contact['plant']['geo']['lng'] }}
                    </span>
                </a>
            </aside>
        </div>
    </section>

</x-layouts.app>
