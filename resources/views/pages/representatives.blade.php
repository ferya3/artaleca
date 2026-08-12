<x-layouts.app>

    <x-page-header
        :eyebrow="content('nav.representatives')"
        :title="content('representatives.title')"
        :lead="content('representatives.intro')"
    />

    <section class="py-section">
        <div class="container-page">
            @if ($representatives->isEmpty())
                <x-empty-state :message="content('representatives.empty')" :action="route('contact')"
                               :action-label="content('nav.contact')" />
            @else
                <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($representatives as $representative)
                        <li class="panel flex min-w-0 flex-col p-6">
                            @if (filled($representative->logo))
                                <img src="{{ \App\Support\Image::thumb($representative->logo, 480) }}"
                                     alt="{{ $representative->name }}"
                                     loading="lazy" decoding="async"
                                     class="mb-5 h-12 w-auto max-w-[10rem] object-contain">
                            @endif

                            <h2 class="text-lg font-bold text-ink-950">{{ $representative->name }}</h2>

                            @if (filled($representative->summary))
                                <p class="mt-2 text-sm leading-relaxed text-ink-600">{{ $representative->summary }}</p>
                            @endif

                            @if (filled($representative->website))
                                {{-- An outbound link to a company we do not control:
                                     `noopener` so the target cannot reach back
                                     through `window.opener`. --}}
                                <a href="{{ $representative->website }}"
                                   target="_blank" rel="noopener noreferrer"
                                   class="ltr-run mt-auto pt-5 text-sm font-semibold text-brand-600 hover:underline">
                                    {{ content('representatives.website') }}
                                    <span class="inline-block rtl:rotate-180" aria-hidden="true">&rarr;</span>
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

        </div>
    </section>

    {{--
        ── Applying to represent the plant ────────────────────────────────
        On the page a prospective distributor is already reading, rather than
        behind a link to the general contact form: they arrived here to find out
        who covers their region, and the answer "nobody yet" is exactly the
        moment to ask.

        The structured questions are what make an application comparable to the
        next one. The free text is last and optional, so it is where something
        unusual goes rather than where everything goes.
    --}}
    <section id="apply" class="border-t border-hairline bg-surface-muted py-section">
        <div class="container-page grid gap-12 lg:grid-cols-12 lg:gap-16">

            <div class="min-w-0 lg:col-span-7">
                <p class="eyebrow mb-3">{{ content('representatives.apply_eyebrow') }}</p>
                <h2 class="text-2xl font-bold text-ink-950 md:text-3xl">{{ content('representatives.apply_title') }}</h2>
                <p class="mt-4 text-base leading-relaxed text-ink-600">{{ content('representatives.apply_intro') }}</p>

                <div class="mt-8">
                    <x-form.status />
                </div>

                <form method="POST" action="{{ route('representatives.store') }}" class="mt-2 grid gap-5 sm:grid-cols-2">
                    @csrf
                    <x-form.honeypot />

                    <x-form.field name="name" :label="__('form.name')" required />
                    <x-form.field name="company" :label="__('form.company')" required />
                    <x-form.field name="email" type="email" :label="__('form.email')" required />
                    <x-form.field name="phone" type="tel" :label="__('form.phone')" required />

                    <x-form.field
                        name="territory"
                        :label="__('form.territory')"
                        :placeholder="__('form.territory_placeholder')"
                        required
                    />

                    <x-form.field
                        name="activity"
                        :label="__('form.activity')"
                        :placeholder="__('form.activity_placeholder')"
                        required
                    />

                    <x-form.field name="experience_years" type="number" :label="__('form.experience_years')" />
                    <x-form.field name="warehouse_m2" type="number" :label="__('form.warehouse_m2')" />

                    <x-form.field
                        name="monthly_volume"
                        :label="__('form.monthly_volume')"
                        :placeholder="__('form.monthly_volume_placeholder')"
                        class="sm:col-span-2"
                    />

                    <x-form.field
                        name="message"
                        type="textarea"
                        :label="__('form.message')"
                        :hint="content('representatives.apply_message_hint')"
                        :rows="5"
                        class="sm:col-span-2"
                    />

                    <div class="sm:col-span-2">
                        <x-form.consent />
                    </div>

                    <div class="sm:col-span-2">
                        <x-button type="submit" size="lg">{{ content('representatives.apply_submit') }}</x-button>
                    </div>
                </form>
            </div>

            <aside class="min-w-0 lg:col-span-5">
                <div class="panel p-6 lg:sticky lg:top-28">
                    <h2 class="text-lg font-bold text-ink-950">{{ content('representatives.apply_criteria_title') }}</h2>

                    <ul class="mt-5 space-y-4">
                        @foreach (['territory', 'storage', 'technical', 'commitment'] as $criterion)
                            <li class="flex gap-3">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-600" aria-hidden="true"></span>
                                <span class="text-sm leading-relaxed text-ink-600">
                                    {{ __('representatives.apply_criteria.'.$criterion) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-6 space-y-2 border-t border-hairline pt-5 text-sm">
                        <p class="text-ink-500">{{ content('representatives.apply_desk') }}</p>
                        <a class="ltr-run block font-medium text-ink-900 hover:text-brand-600"
                           href="mailto:{{ config('site.contact.sales_email') ?? config('site.contact.export_email') }}">{{ config('site.contact.sales_email') ?? config('site.contact.export_email') }}</a>
                        <a class="ltr-run block text-ink-600 hover:text-brand-600"
                           href="tel:{{ str_replace(' ', '', config('site.contact.sales_phone')) }}">{{ config('site.contact.sales_phone') }}</a>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <x-cta-band />

</x-layouts.app>
