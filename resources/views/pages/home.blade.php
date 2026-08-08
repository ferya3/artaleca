<x-layouts.app>

    {{-- ── Hero ───────────────────────────────────────────────────────────
         One headline, one paragraph, two actions. The visual sits beside the
         copy rather than behind it, so the text never needs a scrim and the
         LCP element is predictable. --}}
    <section class="relative overflow-hidden bg-ink-950 text-white">
        <x-hero-motion />

        <div class="container-page relative">
            {{-- Stacked on a phone with the image leading and no padding above
                 it, so the photograph starts at the very top of the viewport.
                 At `lg` the two become columns again and `order-none` hands the
                 placement back to source order. --}}
            <div class="grid items-center gap-8 pb-14 md:gap-12 md:py-16 lg:grid-cols-12 lg:gap-16 lg:py-24">
                <div class="order-2 lg:order-none lg:col-span-6">
                    <p class="eyebrow text-clay-400!">{{ __('home.hero_eyebrow') }}</p>

                    {{-- 30px on a phone rather than 36px: at the larger size a
                         three-word Persian line wraps to four rows and pushes
                         the buttons off the first screen. --}}
                    <h1 class="mt-4 text-3xl font-bold leading-[1.2] sm:text-4xl md:text-5xl md:leading-[1.15] lg:text-[3.25rem]">
                        {{ __('home.hero_title') }}
                    </h1>

                    <p class="mt-5 max-w-xl text-[0.9375rem] leading-relaxed text-ink-300 md:mt-6 md:text-lg">
                        {{ __('home.hero_body') }}
                    </p>

                    {{-- Full-width stacked actions on a phone: a 48px-tall bar
                         spanning the column is a far easier target than two
                         side-by-side pills, and the primary one stays first. --}}
                    <div class="mt-7 flex flex-col gap-3 sm:flex-row md:mt-9">
                        <x-button :href="route('products.index')" variant="accent" size="lg" class="w-full sm:w-auto">
                            {{ __('home.hero_primary_cta') }}
                        </x-button>
                        <x-button :href="route('quote')" variant="inverse" size="lg" class="w-full sm:w-auto">
                            {{ __('home.hero_secondary_cta') }}
                        </x-button>
                    </div>
                </div>

                {{-- Edge to edge on a phone with no frame around it, so a real
                     photograph reads as part of the hero rather than as a card
                     dropped into it. The negative inline margin cancels the
                     container's padding; the section already clips overflow. --}}
                <div class="order-1 -mx-5 md:mx-0 lg:order-none lg:col-span-6">
                    {{-- A fixed 50vh on a phone rather than the 4/3 ratio: half
                         the screen is the brief, and an explicit height on a
                         block whose width is already definite simply wins over
                         the component's inline `aspect-ratio`. `md:h-auto`
                         hands the ratio back. --}}
                    <x-media
                        seed="arta-hero"
                        ratio="4/3"
                        tone="dark"
                        eager
                        :alt="__('home.hero_title')"
                        sizes="(min-width: 1024px) 50vw, 100vw"
                        class="h-[50vh] rounded-none border-0 md:h-auto md:rounded-lg md:border md:border-hairline-dark"
                    />
                </div>
            </div>
        </div>

        {{-- The wrapper goes with the strip, or a phone keeps 64px of the
             padding that was holding it. --}}
        <div class="container-page relative hidden pb-16 md:block lg:pb-20">
            <x-stat-strip tone="dark" class="border border-hairline-dark" />
        </div>
    </section>

    {{-- ── What the material does ─────────────────────────────────────── --}}
    <section class="border-b border-hairline py-section">
        <div class="container-page">
            <div class="max-w-3xl">
                <p class="eyebrow mb-3">{{ __('nav.about') }}</p>
                <h2 class="text-2xl font-bold text-ink-950 md:text-3xl">{{ __('home.intro_title') }}</h2>
                <p class="mt-5 text-base leading-relaxed text-ink-600 md:text-lg">{{ __('home.intro_body') }}</p>
            </div>

            <div class="mt-14 grid gap-px bg-hairline md:grid-cols-3">
                @foreach (['weight', 'thermal', 'durability'] as $index => $key)
                    <div class="bg-white pt-8 md:px-7">
                        <p class="tabular text-xs font-semibold tracking-widest text-clay-600">
                            {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                        </p>
                        <h3 class="mt-4 text-lg font-bold text-ink-950">{{ __("home.pillars.$key.title") }}</h3>
                        <p class="mt-3 pb-8 text-sm leading-relaxed text-ink-600">{{ __("home.pillars.$key.body") }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── Products ───────────────────────────────────────────────────── --}}
    @if ($products->isNotEmpty())
        <section class="py-section">
            <div class="container-page">
                <x-section-heading
                    :eyebrow="__('nav.products')"
                    :title="__('home.products_title')"
                    :body="__('home.products_body')"
                    :href="route('products.index')"
                />

                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ── Applications ───────────────────────────────────────────────── --}}
    @if ($applications->isNotEmpty())
        <section class="border-y border-hairline bg-surface-muted py-section">
            <div class="container-page">
                <x-section-heading
                    :eyebrow="__('nav.applications')"
                    :title="__('home.applications_title')"
                    :body="__('home.applications_body')"
                    :href="route('applications.index')"
                />

                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($applications as $application)
                        <x-application-card :application="$application" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ── Quality ────────────────────────────────────────────────────── --}}
    <section class="py-section">
        <div class="container-page grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
            <x-media
                seed="arta-quality-lab"
                ratio="4/3"
                :alt="__('home.quality_title')"
                sizes="(min-width: 1024px) 50vw, 100vw"
                class="rounded-lg border border-hairline shadow-soft"
            />

            <div>
                <p class="eyebrow mb-3">{{ __('nav.quality') }}</p>
                <h2 class="text-2xl font-bold text-ink-950 md:text-3xl">{{ __('home.quality_title') }}</h2>
                <p class="mt-5 text-base leading-relaxed text-ink-600">{{ __('home.quality_body') }}</p>

                @if ($certificates->isNotEmpty())
                    <ul class="mt-8 flex flex-wrap gap-2">
                        @foreach ($certificates as $certificate)
                            <li class="rounded-md border border-hairline px-3.5 py-2 text-xs text-ink-600">
                                {{ $certificate->title }}
                                @if ($certificate->year)
                                    <span class="ltr-run tabular ms-1 text-ink-400">{{ $certificate->year }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                <x-button :href="route('about.quality')" variant="outline" class="mt-8">
                    {{ __('home.quality_cta') }}
                </x-button>
            </div>
        </div>
    </section>

    {{-- ── Certifications & partners ──────────────────────────────────── --}}
    @if ($partners->isNotEmpty())
        <section class="border-t border-hairline py-section">
            <div class="container-page">
                <x-section-heading
                    :eyebrow="__('common.certificates')"
                    :title="__('partners.home_title')"
                    :body="__('partners.home_body')"
                />

                {{-- A quiet logo rail, not a carousel: eight marks in a grid
                     read faster than eight marks that move. --}}
                <ul class="mt-10 grid grid-cols-2 gap-px bg-hairline sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($partners as $partner)
                        <li class="flex items-center justify-center bg-white p-6">
                            @if ($partner->website)
                                <a href="{{ $partner->website }}" target="_blank" rel="noopener noreferrer"
                                   class="flex w-full items-center justify-center">
                            @endif

                            @if ($partner->logo)
                                <img src="{{ $partner->logo }}" alt="{{ $partner->name }}"
                                     loading="lazy" decoding="async"
                                     class="h-10 w-auto max-w-full object-contain opacity-70 transition-opacity hover:opacity-100">
                            @else
                                <span class="text-center text-xs font-medium text-ink-600">{{ $partner->name }}</span>
                            @endif

                            @if ($partner->website)
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ── Projects ───────────────────────────────────────────────────── --}}
    @if ($projects->isNotEmpty())
        <section class="border-y border-hairline bg-surface-muted py-section">
            <div class="container-page">
                <x-section-heading
                    :eyebrow="__('nav.projects')"
                    :title="__('home.projects_title')"
                    :body="__('home.projects_body')"
                    :href="route('projects.index')"
                />

                <div class="mt-12 grid gap-6 md:grid-cols-3">
                    @foreach ($projects as $project)
                        <x-project-card :project="$project" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ── News ───────────────────────────────────────────────────────── --}}
    @if ($posts->isNotEmpty())
        <section class="py-section">
            <div class="container-page">
                <x-section-heading
                    :eyebrow="__('nav.news')"
                    :title="__('home.news_title')"
                    :body="__('home.news_body')"
                    :href="route('articles.index')"
                />

                <div class="mt-12 grid gap-6 md:grid-cols-3">
                    @foreach ($posts as $post)
                        <x-post-card :post="$post" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-cta-band />

</x-layouts.app>
