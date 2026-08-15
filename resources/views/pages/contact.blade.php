{{-- `use` must sit at the top level of the compiled view: inside a component
     slot Blade compiles the block into a closure body, where it is a syntax
     error. --}}
@php
    use App\Support\Contact;
@endphp

<x-layouts.app>

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

            {{-- One card per place. The plant had two addresses fixed in a
                 config file when it had two; adding an office is now a row in
                 the panel, and this grid simply grows. --}}
            <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($offices as $office)
                    <div class="panel flex h-full min-w-0 flex-col p-6">
                        <h3 class="eyebrow mb-4">{{ $office->name }}</h3>

                        <address class="text-sm not-italic leading-relaxed text-ink-800">
                            {{ $office->address }}
                        </address>

                        <dl class="mt-5 space-y-4 text-sm">
                            @if (filled($office->postal_code))
                                <div>
                                    <dt class="text-xs text-ink-500">{{ content('common.postal_code') }}</dt>
                                    <dd class="ltr-run tabular mt-1 text-ink-900">{{ $office->postal_code }}</dd>
                                </div>
                            @endif

                            @if (filled($office->phone))
                                <div>
                                    <dt class="text-xs text-ink-500">{{ content('common.phone') }}</dt>
                                    <dd class="mt-1">
                                        <a class="ltr-run font-medium text-ink-900 hover:text-brand-600"
                                           href="tel:{{ $office->telephone() }}">{{ $office->phone }}</a>
                                    </dd>
                                </div>
                            @endif

                            @if (filled($office->fax))
                                <div>
                                    <dt class="text-xs text-ink-500">{{ content('common.fax') }}</dt>
                                    <dd class="ltr-run mt-1 text-ink-900">{{ $office->fax }}</dd>
                                </div>
                            @endif

                            @if (filled($office->email))
                                <div>
                                    <dt class="text-xs text-ink-500">{{ content('common.email') }}</dt>
                                    <dd class="mt-1">
                                        <a class="ltr-run text-ink-600 hover:text-brand-600"
                                           href="mailto:{{ $office->email }}">{{ $office->email }}</a>
                                    </dd>
                                </div>
                            @endif

                            @if (filled($office->hours))
                                <div>
                                    <dt class="text-xs text-ink-500">{{ content('common.working_hours') }}</dt>
                                    <dd class="mt-1 text-ink-900">{{ $office->hours }}</dd>
                                </div>
                            @endif
                        </dl>

                        @if ($office->hasMap())
                            {{-- A static map link rather than an embedded iframe: no
                                 third-party script, no cookie, no CSP exception, and
                                 one fewer render-blocking request.

                                 The coordinates themselves are not printed. They are
                                 how the link is built, not something a visitor reads —
                                 a pair of decimals beside the address answers no
                                 question anyone arrived with. --}}
                            <a
                                href="{{ $office->mapUrl() }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-6 flex items-center justify-between gap-4 rounded-md border border-hairline px-4 py-3 text-sm transition-colors hover:border-ink-400"
                            >
                                <span class="font-medium text-ink-900">{{ content('contact.find_us') }}</span>
                                <span class="shrink-0 text-ink-400 rtl:rotate-180" aria-hidden="true">&rarr;</span>
                            </a>
                        @endif
                    </div>
                @endforeach

                {{-- Desks. Not a place: one sales line and one export mailbox
                     serve every office, so they get their own card rather than
                     being repeated on each. --}}
                <div class="panel h-full min-w-0 p-6">
                    <h3 class="eyebrow mb-4">{{ content('contact.desks') }}</h3>

                    <dl class="grid gap-5 text-sm sm:grid-cols-2 lg:grid-cols-1">
                        <div>
                            <dt class="text-xs text-ink-500">{{ content('contact.sales_desk') }}</dt>
                            <dd class="mt-1 space-y-1">
                                <a class="ltr-run block font-medium text-ink-900 hover:text-brand-600"
                                   href="tel:{{ Contact::tel('sales_phone') }}">{{ Contact::value('sales_phone') }}</a>
                                <a class="ltr-run block text-ink-600 hover:text-brand-600"
                                   href="mailto:{{ Contact::value('sales_email') }}">{{ Contact::value('sales_email') }}</a>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs text-ink-500">{{ content('contact.export_desk') }}</dt>
                            <dd class="mt-1">
                                <a class="ltr-run block text-ink-600 hover:text-brand-600"
                                   href="mailto:{{ Contact::value('export_email') }}">{{ Contact::value('export_email') }}</a>
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
