<x-layouts.app>

    <x-page-header
        :eyebrow="__('nav.about')"
        :title="__('about.title')"
        :lead="__('about.lead')"
    />

    <x-stat-strip tone="light" class="border-b border-hairline" />

    <section class="py-section">
        <div class="container-page grid gap-12 lg:grid-cols-2 lg:gap-16">
            <div>
                <h2 class="text-2xl font-bold text-ink-950">{{ __('about.story_title') }}</h2>
                <p class="mt-5 text-base leading-relaxed text-ink-600">{{ __('about.story_body') }}</p>

                <h2 class="mt-12 text-2xl font-bold text-ink-950">{{ __('about.approach_title') }}</h2>
                <p class="mt-5 text-base leading-relaxed text-ink-600">{{ __('about.approach_body') }}</p>
            </div>

            <x-media
                :src="setting('media.plant_exterior')"
                seed="arta-plant-exterior"
                ratio="4/3"
                :alt="__('about.title')"
                sizes="(min-width: 1024px) 50vw, 100vw"
                class="rounded-lg border border-hairline shadow-soft lg:self-start"
            />
        </div>
    </section>

    {{-- ── Values ─────────────────────────────────────────────────────── --}}
    <section class="border-y border-hairline bg-surface-muted py-section">
        <div class="container-page">
            <div class="grid gap-px bg-hairline md:grid-cols-3">
                @foreach (['consistency', 'traceability', 'support'] as $index => $key)
                    <div class="bg-surface-muted p-7">
                        <p class="tabular text-xs font-semibold tracking-widest text-clay-600">
                            {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                        </p>
                        <h3 class="mt-4 text-lg font-bold text-ink-950">{{ __("about.values.$key.title") }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-ink-600">{{ __("about.values.$key.body") }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── Timeline ───────────────────────────────────────────────────── --}}
    <section class="py-section">
        <div class="container-page">
            <h2 class="text-2xl font-bold text-ink-950 md:text-3xl">{{ __('about.timeline_title') }}</h2>

            <ol class="mt-12 border-s border-hairline">
                @foreach (__('about.timeline') as $entry)
                    <li class="relative ps-8 pb-10 last:pb-0">
                        <span class="absolute start-0 top-1.5 h-2 w-2 -translate-x-1/2 rounded-full bg-clay-500 rtl:translate-x-1/2" aria-hidden="true"></span>
                        <p class="ltr-run tabular text-sm font-bold text-clay-600">{{ $entry['year'] }}</p>
                        <h3 class="mt-1 text-base font-semibold text-ink-950">{{ $entry['title'] }}</h3>
                        <p class="mt-2 max-w-xl text-sm leading-relaxed text-ink-600">{{ $entry['body'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ── Certificates ───────────────────────────────────────────────── --}}
    @if ($certificates->isNotEmpty())
        <section class="border-t border-hairline bg-surface-muted py-section">
            <div class="container-page">
                <x-section-heading
                    :title="__('common.certificates')"
                    :href="route('about.quality')"
                    :link-label="__('nav.quality')"
                />

                <ul class="mt-10 grid gap-px bg-hairline sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($certificates as $certificate)
                        <li class="bg-white p-6">
                            <p class="text-sm font-semibold text-ink-950">{{ $certificate->title }}</p>
                            @if (filled($certificate->issuer))
                                <p class="mt-1.5 text-xs text-ink-500">{{ $certificate->issuer }}</p>
                            @endif
                            @if ($certificate->year)
                                <p class="ltr-run tabular mt-3 text-xs text-ink-400">{{ $certificate->year }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <x-cta-band />

</x-layouts.app>
