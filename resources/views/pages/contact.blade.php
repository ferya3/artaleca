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
        :eyebrow="content('nav.contact')"
        :title="content('contact.title')"
        :lead="content('contact.intro')"
    />

    {{--
        ── Where we are ───────────────────────────────────────────────────
        A band of its own, above the form and across the full width.

        These details used to live in a narrow column beside the form, where
        every address wrapped after four words and every label sat on one edge
        of the line with its value on the other — so finding the plant address
        meant reading the whole panel. Three cards with room to breathe, each
        answering one question ("where is the office", "where is the plant",
        "who do I call"), and each value stacked under its own label.
    --}}
    <section class="border-b border-hairline bg-surface-muted py-12 md:py-16">
        <div class="container-page">
            <h2 class="text-xl font-bold text-ink-950 md:text-2xl">{{ content('contact.reach_us') }}</h2>

            <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">

                {{-- Head office --}}
                <div class="panel h-full p-6">
                    <h3 class="eyebrow mb-4">{{ content('common.headquarters') }}</h3>

                    <address class="text-sm not-italic leading-relaxed text-ink-800">
                        {{ $contact['hq']['lines'][$locale] ?? $contact['hq']['lines']['en'] }}
                    </address>

                    <dl class="mt-5 space-y-4 text-sm">
                        <div>
                            <dt class="text-xs text-ink-500">{{ content('common.postal_code') }}</dt>
                            <dd class="ltr-run tabular mt-1 text-ink-900">{{ $contact['hq']['postal_code'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">{{ content('common.phone') }}</dt>
                            <dd class="mt-1">
                                <a class="ltr-run font-medium text-ink-900 hover:text-brand-600"
                                   href="tel:{{ str_replace(' ', '', $contact['phone']) }}">{{ $contact['phone'] }}</a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">{{ content('common.fax') }}</dt>
                            <dd class="ltr-run mt-1 text-ink-900">{{ $contact['fax'] }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Plant --}}
                <div class="panel flex h-full flex-col p-6">
                    <h3 class="eyebrow mb-4">{{ content('common.plant') }}</h3>

                    <address class="text-sm not-italic leading-relaxed text-ink-800">
                        {{ $contact['plant']['lines'][$locale] ?? $contact['plant']['lines']['en'] }}
                    </address>

                    <dl class="mt-5 text-sm">
                        <dt class="text-xs text-ink-500">{{ content('common.working_hours') }}</dt>
                        <dd class="mt-1 text-ink-900">{{ $contact['hours'][$locale] ?? $contact['hours']['en'] }}</dd>
                    </dl>

                    {{-- A static map link rather than an embedded iframe: no
                         third-party script, no cookie, no CSP exception, and one
                         fewer render-blocking request. --}}
                    <a
                        href="https://www.openstreetmap.org/?mlat={{ $contact['plant']['geo']['lat'] }}&mlon={{ $contact['plant']['geo']['lng'] }}#map=13/{{ $contact['plant']['geo']['lat'] }}/{{ $contact['plant']['geo']['lng'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-6 flex items-center justify-between gap-4 rounded-md border border-hairline px-4 py-3 text-sm transition-colors hover:border-ink-400"
                    >
                        <span class="font-medium text-ink-900">{{ content('contact.find_us') }}</span>
                        <span class="ltr-run tabular shrink-0 text-xs text-ink-500">
                            {{ $contact['plant']['geo']['lat'] }}, {{ $contact['plant']['geo']['lng'] }}
                        </span>
                    </a>
                </div>

                {{-- Desks. On a phone this is the card most visitors want, but
                     it sits last because the two above answer "who are you and
                     where" first — and it is one screen away, not five. --}}
                <div class="panel h-full min-w-0 p-6 md:col-span-2 lg:col-span-1">
                    <h3 class="eyebrow mb-4">{{ content('contact.desks') }}</h3>

                    <dl class="grid gap-5 text-sm sm:grid-cols-2 lg:grid-cols-1">
                        <div>
                            <dt class="text-xs text-ink-500">{{ content('contact.sales_desk') }}</dt>
                            <dd class="mt-1 space-y-1">
                                <a class="ltr-run block font-medium text-ink-900 hover:text-brand-600"
                                   href="tel:{{ str_replace(' ', '', $contact['sales_phone']) }}">{{ $contact['sales_phone'] }}</a>
                                <a class="ltr-run block text-ink-600 hover:text-brand-600"
                                   href="mailto:{{ $contact['sales_email'] }}">{{ $contact['sales_email'] }}</a>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs text-ink-500">{{ content('contact.export_desk') }}</dt>
                            <dd class="mt-1">
                                <a class="ltr-run block text-ink-600 hover:text-brand-600"
                                   href="mailto:{{ $contact['export_email'] }}">{{ $contact['export_email'] }}</a>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Message form ──────────────────────────────────────────────────
         Now that the details have their own band, the form gets the width it
         needs instead of seven columns of twelve, and the page reads as two
         things rather than two half-things side by side. --}}
    <section class="py-section">
        <div class="container-page max-w-3xl" id="form">
            <x-form.status />

            <h2 class="text-xl font-bold text-ink-950 md:text-2xl">{{ content('form.contact_title') }}</h2>
            <p class="mt-2 text-sm text-ink-600">{{ content('form.contact_intro') }}</p>

            <form method="POST" action="{{ route('contact.store') }}" class="relative mt-8 grid gap-5 sm:grid-cols-2">
                @csrf
                <x-form.honeypot />

                <x-form.field name="name" :label="content('form.name')" required />
                <x-form.field name="company" :label="content('form.company')" />
                <x-form.field name="email" type="email" :label="content('form.email')" required />
                <x-form.field name="phone" type="tel" :label="content('form.phone')" />

                <x-form.field name="subject" :label="content('form.subject')" class="sm:col-span-2" />
                <x-form.field name="message" type="textarea" :label="content('form.message')" required class="sm:col-span-2" />

                <div class="sm:col-span-2">
                    <x-form.consent />
                </div>

                <div class="sm:col-span-2">
                    <x-button type="submit" size="lg">{{ content('common.send') }}</x-button>
                    <p class="mt-3 text-xs text-ink-500">{{ content('contact.response_note') }}</p>
                </div>
            </form>

            <p class="mt-8 border-t border-hairline pt-6 text-sm text-ink-600">
                <span class="font-medium text-ink-900">{{ content('contact.prefer_quote') }}</span>
                <a href="{{ route('quote') }}" class="ms-1 text-brand-600 underline underline-offset-2">
                    {{ content('contact.prefer_quote_link') }}
                </a>
            </p>
        </div>
    </section>

</x-layouts.app>
