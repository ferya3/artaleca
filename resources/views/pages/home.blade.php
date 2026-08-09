<x-layouts.app>

    {{-- ── Hero ───────────────────────────────────────────────────────────
         On a phone the copy sits *over* the image; from `md` up the visual
         moves beside it, where the text needs no scrim and the LCP element is
         predictable. --}}
    <section class="relative overflow-hidden bg-ink-950 text-white">
        <x-hero-motion />

        <div class="container-page relative">
            {{--
                Below `md` both children are placed in the same cell
                (`col-start-1 row-start-1`), which stacks them without taking
                either out of flow — so the row still sizes itself to the taller
                one and nothing has to be positioned absolutely or measured.

                From `md` up, `col-start-auto`/`row-start-auto` hands placement
                back to normal flow and the old two-column layout returns.
            --}}
            {{-- `items-center` only from `md`. While the two share a cell the
                 default stretch is what lets the image fill whatever height the
                 copy asks for; centring them would leave the image at its
                 minimum and open a gap under it. --}}
            {{-- No padding on the grid itself below `md`: the copy carries its
                 own, and padding here would sit outside the image and show a
                 band of bare background under it. --}}
            <div class="grid md:items-center md:gap-12 md:py-16 lg:grid-cols-12 lg:gap-16 lg:py-24">
                <div class="z-10 col-start-1 row-start-1 min-w-0 py-14 md:col-start-auto md:row-start-auto md:py-0 lg:col-span-6">
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

                    {{--
                        A phone gets one action, not two. Over an image the
                        second button competes with the first for the same
                        glance, and the catalogue is one tap away in the menu
                        anyway — so only the quote CTA survives below `md`, at
                        the `md` size rather than `lg`, which was oversized on a
                        small screen. The `md:` overrides restore the large
                        button from the breakpoint up.
                    --}}
                    <div class="mt-7 flex flex-col gap-3 sm:flex-row md:mt-9">
                        {{-- Hidden by a wrapper rather than by a `hidden` class
                             on the button: the component already carries
                             `inline-flex`, and two plain display utilities on
                             one element are decided by Tailwind's own ordering,
                             not by the order they are written in. `md:contents`
                             dissolves the wrapper again so the button is a
                             direct flex item at every size it is visible. --}}
                        <div class="hidden md:contents">
                            <x-button :href="route('products.index')" variant="accent" size="lg" class="sm:w-auto">
                                {{ __('home.hero_primary_cta') }}
                            </x-button>
                        </div>
                        <x-button :href="route('quote')" variant="inverse" size="md"
                                  class="w-full sm:w-auto md:rounded-lg md:px-8 md:py-4 md:text-base">
                            {{ __('home.hero_secondary_cta') }}
                        </x-button>
                    </div>
                </div>

                {{-- Edge to edge on a phone with no frame around it, so a real
                     photograph reads as part of the hero rather than as a card
                     dropped into it. The negative inline margin cancels the
                     container's padding; the section already clips overflow. --}}
                {{-- `min-w-0` is load-bearing. A grid item's automatic minimum
                     size is derived from its content, and an aspect ratio turns
                     a minimum *height* into a minimum *width*: 50vh at 4/3 is
                     444px, which stretched the shared track past a 375px screen
                     and dragged the copy 89px off the edge with it. Setting the
                     minimum explicitly lets the track shrink to the container,
                     which then makes the ratio inert because both axes are
                     definite. --}}
                <div class="relative col-start-1 row-start-1 min-w-0 -mx-5 h-full md:col-start-auto md:row-start-auto md:mx-0 lg:col-span-6">
                    {{-- `h-full` so the image fills whatever height the copy
                         asks for, with a 50vh floor so it still reads as a hero
                         when the copy is short. `md:h-auto` hands the 4/3 ratio
                         back — an explicit height on a block whose width is
                         already definite simply makes the ratio inert. --}}
                    <x-media
                        :src="setting('media.hero')"
                        :mobile-src="setting('media.hero_mobile')"
                        seed="arta-hero"
                        ratio="4/3"
                        tone="dark"
                        eager
                        :alt="__('home.hero_title')"
                        sizes="(min-width: 1024px) 50vw, 100vw"
                        class="h-full min-h-[50vh] rounded-none border-0 md:h-auto md:min-h-0 md:rounded-lg md:border md:border-hairline-dark"
                    />

                    {{-- Legibility, not decoration: white copy over a
                         photograph nobody has approved yet needs a floor under
                         it. The copy spans the whole block, so this is close to
                         even rather than a bottom-weighted gradient — just
                         enough lift at the top for the image to breathe. Gone
                         entirely once the image moves beside the text. --}}
                    <div class="absolute inset-0 bg-gradient-to-b from-ink-950/45 via-ink-950/60 to-ink-950/70 md:hidden"
                         aria-hidden="true"></div>
                </div>
            </div>
        </div>

        {{-- The wrapper goes with the strip, or a phone keeps 64px of the
             padding that was holding it. --}}
        <div class="container-page relative hidden pb-16 md:block lg:pb-20">
            <x-stat-strip tone="dark" class="border border-hairline-dark" />
        </div>

        {{-- Sits in the padding below the copy on a phone and below the figures
             strip on a desktop, so it never lands on top of either. --}}
        <x-scroll-cue href="#intro" />
    </section>

    {{-- ── What the material does ─────────────────────────────────────── --}}
    <section id="intro" class="border-b border-hairline py-section">
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
                :src="setting('media.quality_lab')"
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
