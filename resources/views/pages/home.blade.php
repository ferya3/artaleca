<x-layouts.app>

    {{--
        ── Hero, phone ────────────────────────────────────────────────────
        Its own block rather than a responsive variant of the desktop one.
        The two want opposite things — a full-bleed backdrop with the copy on
        top, against a photograph beside the copy — and every attempt to make
        one element do both ended with the image's own aspect ratio deciding
        the layout's width. Here the image simply covers a box the copy sizes,
        so it has no say in anything.
    --}}
    <section class="relative overflow-hidden bg-night-950 text-white md:hidden">
        <x-hero-motion />

        <div class="relative">
            {{-- Absolute and ratio-free: it fills whatever the copy asks for. --}}
            <x-media
                fill
                :src="site_image('media.hero_mobile')['light'] ?: site_image('media.hero')['light']"
                :dark-src="site_image('media.hero_mobile')['dark'] ?: site_image('media.hero')['dark']"
                seed="arta-hero-mobile"
                tone="dark"
                eager
                preload-media="(max-width: 767.98px)"
                :alt="content('home.hero_title')"
                sizes="100vw"
            />

            {{-- Legibility over a photograph nobody has approved yet. --}}
            <div class="absolute inset-0 bg-gradient-to-b from-night-950/45 via-night-950/60 to-night-950/72"
                 aria-hidden="true"></div>

            {{-- `min-h` on the copy, not on the image: the content decides the
                 height and the picture follows, never the other way round.
                 `svh` rather than `vh` so a phone's collapsing address bar
                 cannot leave the block taller than the screen. --}}
            <div class="container-page relative z-10 flex min-h-[72svh] flex-col justify-center py-14">
                <p class="eyebrow text-brand-400!">{{ content('home.hero_eyebrow') }}</p>

                <h1 class="mt-4 text-3xl font-bold leading-[1.2] sm:text-4xl">
                    {{ content('home.hero_title') }}
                </h1>

                <p class="mt-5 max-w-md text-[0.9375rem] leading-relaxed text-night-200">
                    {{ content('home.hero_body') }}
                </p>
            </div>
        </div>

        <x-scroll-cue href="#intro" />
    </section>

    {{--
        ── Hero, desktop ──────────────────────────────────────────────────
        The visual sits beside the copy, so the text needs no scrim and the
        LCP element is predictable.
    --}}
    <section class="relative hidden overflow-hidden bg-night-950 text-white md:block">
        <x-hero-motion />

        <div class="container-page relative">
            <div class="grid items-center gap-12 pb-24 pt-16 lg:grid-cols-12 lg:gap-16 lg:pb-28 lg:pt-24">
                <div class="min-w-0 lg:col-span-6">
                    <p class="eyebrow text-brand-400!">{{ content('home.hero_eyebrow') }}</p>

                    <h1 class="mt-4 text-5xl font-bold leading-[1.15] lg:text-[3.25rem]">
                        {{ content('home.hero_title') }}
                    </h1>

                    <p class="mt-6 max-w-xl text-lg leading-relaxed text-night-300">
                        {{ content('home.hero_body') }}
                    </p>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <x-button :href="route('products.index')" variant="accent" size="lg">
                            {{ content('home.hero_primary_cta') }}
                        </x-button>
                        <x-button :href="route('quote')" variant="inverse" size="lg">
                            {{ content('home.hero_secondary_cta') }}
                        </x-button>
                    </div>
                </div>

                <div class="min-w-0 lg:col-span-6">
                    <x-media
                        :src="site_image('media.hero')['light']"
                        :dark-src="site_image('media.hero')['dark']"
                        seed="arta-hero"
                        {{-- 3:2, the shape the artwork is supplied in (1536x1024).
                             The box holds that ratio whatever is uploaded, so an
                             image cut to any other shape is cropped to fit rather
                             than moving the column beside it. --}}
                        ratio="3/2"
                        tone="dark"
                        eager
                        preload-media="(min-width: 768px)"
                        :alt="content('home.hero_title')"
                        sizes="50vw"
                        class="rounded-lg border border-night-line"
                    />
                </div>
            </div>
        </div>

        <x-scroll-cue href="#intro" />
    </section>

    {{-- ── What the material does ─────────────────────────────────────── --}}
    <section id="intro" class="border-b border-hairline py-section">
        <div class="container-page">
            <div class="max-w-3xl">
                <p class="eyebrow mb-3">{{ content('nav.about') }}</p>
                <h2 class="text-2xl font-bold text-ink-950 md:text-3xl">{{ content('home.intro_title') }}</h2>
                <p class="mt-5 text-base leading-relaxed text-ink-600 md:text-lg">{{ content('home.intro_body') }}</p>
            </div>
        </div>
    </section>

    {{-- ── Products ───────────────────────────────────────────────────── --}}
    @if ($products->isNotEmpty())
        <section class="py-section">
            <div class="container-page">
                <x-section-heading
                    :eyebrow="content('nav.products')"
                    :title="content('home.products_title')"
                    :body="content('home.products_body')"
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

    {{--
        ── Applications ───────────────────────────────────────────────────
        One infographic instead of a card grid: the relationship between grade
        and use is a single picture, and nine cards were telling it in pieces.

        The image is deliberately *not* `object-cover` like every other
        photograph on the site. An infographic carries text, and cropping it to
        a fixed ratio would cut that text off — so it keeps its own aspect ratio
        and the box takes whatever height the artwork asks for. `width`/`height`
        still come off the filename, so the space is reserved before it loads
        and nothing below it shifts.
    --}}
    @php($infographic = site_image('media.applications_infographic'))
    @php($infographicMobile = site_image('media.applications_infographic_mobile'))
    <section class="border-y border-hairline bg-surface-muted py-section">
        <div class="container-page">
            <x-section-heading
                :eyebrow="content('nav.applications')"
                :title="content('home.applications_title')"
                :body="content('home.applications_body')"
                :href="route('applications.index')"
            />

            {{-- Full width of the page container, in a framed box. Edge to edge
                 was tried and pulled back: at 1440px the artwork ran wider than
                 every other block on the page and stopped reading as part of
                 it. --}}
            <div class="mt-12 overflow-hidden rounded-lg border border-hairline bg-surface shadow-soft">
                @if ($infographic['light'])
                    <span class="theme-only-light">
                        <x-picture
                            :src="$infographic['light']"
                            :mobile-src="$infographicMobile['light']"
                            :alt="content('home.applications_infographic_alt')"
                            sizes="(min-width: 1280px) 1216px, (min-width: 768px) calc(100vw - 4rem), calc(100vw - 2.5rem)"
                            mobile-sizes="calc(100vw - 2.5rem)"
                            class="h-auto w-full"
                        />
                    </span>

                    @if ($infographic['dark'] !== $infographic['light'] || $infographicMobile['dark'] !== $infographicMobile['light'])
                        <span class="theme-only-dark">
                            <x-picture
                                :src="$infographic['dark']"
                                :mobile-src="$infographicMobile['dark']"
                                :alt="content('home.applications_infographic_alt')"
                                sizes="(min-width: 1280px) 1216px, (min-width: 768px) calc(100vw - 4rem), calc(100vw - 2.5rem)"
                                mobile-sizes="calc(100vw - 2.5rem)"
                                class="h-auto w-full"
                            />
                        </span>
                    @endif
                @else
                    {{-- Nothing uploaded yet: the granule field holds the space
                         rather than an empty box collapsing the section. Portrait
                         on a phone and landscape above it, matching the two
                         uploads the admin asks for — so the empty state is the
                         shape of the thing that will replace it. --}}
                    <x-media
                        seed="arta-applications"
                        ratio="3/4"
                        :alt="content('home.applications_title')"
                        sizes="calc(100vw - 2.5rem)"
                        class="md:hidden"
                    />
                    <x-media
                        seed="arta-applications"
                        ratio="16/9"
                        :alt="content('home.applications_title')"
                        sizes="(min-width: 1280px) 1216px, (min-width: 768px) calc(100vw - 4rem), calc(100vw - 2.5rem)"
                        class="hidden md:block"
                    />
                @endif
            </div>
        </div>
    </section>

    {{--
        ── Quality ────────────────────────────────────────────────────────
        The copy leads and the photograph follows it, which is the order the
        section is read in on a phone. Source order carries that; the desktop
        arrangement — picture beside the text — is one `lg:order-first`, so the
        two layouts share one block rather than being written twice.

        The button sits with the image rather than at the end of the copy, so
        it lands under the picture in both layouts.
    --}}
    <section class="py-section">
        <div class="container-page grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
            <div class="min-w-0">
                <p class="eyebrow mb-3">{{ content('nav.quality') }}</p>
                <h2 class="text-2xl font-bold text-ink-950 md:text-3xl">{{ content('home.quality_title') }}</h2>
                <p class="mt-5 text-base leading-relaxed text-ink-600">{{ content('home.quality_body') }}</p>
            </div>

            <div class="min-w-0 lg:order-first">
                <x-media
                    :src="site_image('media.quality_lab')['light']"
                    :dark-src="site_image('media.quality_lab')['dark']"
                    seed="arta-quality-lab"
                    {{-- 3:2, matching the hero — same reason, and the two are the
                         only photographs on the home page that sit beside a column
                         of copy. --}}
                    ratio="3/2"
                    :alt="content('home.quality_title')"
                    sizes="(min-width: 1024px) 50vw, 100vw"
                    class="rounded-lg border border-hairline shadow-soft"
                />

                <x-button :href="route('about.quality')" variant="outline" class="mt-6">
                    {{ content('home.quality_cta') }}
                </x-button>
            </div>
        </div>
    </section>

    {{--
        ── Pinned showcase ────────────────────────────────────────────────
        The image stays put while the sections below ride up over it.

        Pure CSS: `position: sticky` pins the picture to the top of the
        viewport, and because a sticky element still occupies its place in the
        flow, everything after it simply scrolls past — no scroll listener, no
        measuring, nothing to run on the main thread. The blocks that pass over
        it need an opaque background and a position of their own; later siblings
        paint above an earlier one at the same z-index, which is what covers the
        image rather than blending with it.

        The wrapper is what decides how long the pin lasts: the image is held
        for as long as the wrapper is still on screen.
    --}}
    <div class="relative">
        <div class="sticky top-0 h-[60svh] overflow-hidden bg-night-950 md:h-[78svh]">
            <x-media
                fill
                :src="site_image('media.showcase')['light']"
                :dark-src="site_image('media.showcase')['dark']"
                :mobile-src="site_image('media.showcase_mobile')['light']"
                :dark-mobile-src="site_image('media.showcase_mobile')['dark']"
                seed="arta-showcase"
                tone="dark"
                :alt="content('home.showcase_alt')"
                sizes="100vw"
            />

            {{-- A floor under the caption, and a top edge dark enough that the
                 header does not sit on bare photograph when it reveals. --}}
            <div class="absolute inset-0 bg-gradient-to-t from-night-950/70 via-transparent to-night-950/35"
                 aria-hidden="true"></div>

            <div class="container-page absolute inset-x-0 bottom-0 pb-14 md:pb-20">
                <p class="eyebrow text-brand-400!">{{ content('common.certificates') }}</p>
                <p class="mt-3 max-w-lg text-xl font-bold text-white md:text-2xl">
                    {{ content('home.showcase_caption') }}
                </p>
            </div>
        </div>

    {{-- ── Projects ───────────────────────────────────────────────────── --}}
    @if ($projects->isNotEmpty())
        <section class="relative border-y border-hairline bg-surface-muted py-section">
            <div class="container-page">
                <x-section-heading
                    :eyebrow="content('nav.projects')"
                    :title="content('home.projects_title')"
                    :body="content('home.projects_body')"
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
        <section class="relative bg-surface py-section">
            <div class="container-page">
                <x-section-heading
                    :eyebrow="content('nav.news')"
                    :title="content('home.news_title')"
                    :body="content('home.news_body')"
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

    <x-cta-band class="relative" />
    </div>

</x-layouts.app>
