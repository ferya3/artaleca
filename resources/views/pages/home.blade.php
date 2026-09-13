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

            {{-- A fixed 2:3, so the picture behind it has one shape.

                 This was `min-h-[72svh]`, which made the hero a different
                 proportion on every handset — 0.64 on a tall screen, 0.78 on a
                 short one — and left nobody able to say what size to export
                 the photograph at. 2:3 answers that with 1280 x 1920 and holds
                 from 320px to 767px wide.

                 `aspect-ratio` is a preference, not a cap: this is a flex
                 column, so its `min-height: auto` still comes from the content
                 and a headline long enough to need more room grows the box
                 rather than being clipped by it. --}}
            <div class="container-page relative z-10 flex aspect-[2/3] flex-col justify-center py-14">
                <p class="eyebrow text-brand-400!">{{ content('home.hero_eyebrow') }}</p>

                <h1 class="mt-4 text-3xl font-bold">
                    {{ content('home.hero_title') }}
                </h1>
            </div>
        </div>

        <x-scroll-cue href="#intro" />
    </section>

    {{--
        ── Hero, desktop ──────────────────────────────────────────────────
        A photograph fills the band, and the copy and the framed picture sit
        together on one glass panel over it: frosted through the panel, sharp
        around its edges. That contrast is the effect — blur the whole band and
        there is no glass, only a soft photograph.

        The backdrop is fetched eagerly but deliberately not preloaded, so the
        one preload this band pushes still belongs to the framed picture.
    --}}
    @php
        /*
         * One photograph, two treatments. The backdrop has a slot of its own
         * for anyone who wants a different picture behind the glass, and falls
         * back to the hero image so the effect is there after a single upload
         * rather than waiting for a second one.
         */
        $hero = site_image('media.hero');
        $backdrop = site_image('media.hero_backdrop');
        $backdropLight = $backdrop['light'] ?: $hero['light'];
        $backdropDark = $backdrop['dark'] ?: $hero['dark'];
    @endphp

    <section class="relative hidden overflow-hidden bg-night-950 text-white md:block">
        @if (filled($backdropLight))
            {{-- Decorative, hence the empty alt: this is the same photograph as
                 the framed one below, which already carries the description. --}}
            <x-media
                fill
                :src="$backdropLight"
                :dark-src="$backdropDark"
                tone="dark"
                eager
                :preload="false"
                alt=""
                sizes="100vw"
            />

            {{-- The glass alone cannot guarantee white text over a photograph
                 nobody has approved yet, so the picture is dimmed first and the
                 panel then works against a known ground. --}}
            <div class="absolute inset-0 bg-gradient-to-b from-night-950/60 via-night-950/55 to-night-950/75"
                 aria-hidden="true"></div>
        @endif

        <x-hero-motion />

        {{-- Deeper at the bottom than the top: the scroll cue is absolutely
             positioned at the foot of the band, and with even padding it landed
             on the panel's lower edge instead of below it. --}}
        <div class="container-page relative pb-20 pt-12 lg:pb-24 lg:pt-16">
            <div class="hero-glass grid items-center gap-12 px-8 py-12 lg:grid-cols-12 lg:gap-16 lg:px-12 lg:py-14">
                <div class="min-w-0 lg:col-span-6">
                    <p class="eyebrow text-brand-400!">{{ content('home.hero_eyebrow') }}</p>

                    <h1 class="mt-4 text-4xl font-bold lg:text-[2.5rem]">
                        {{ content('home.hero_title') }}
                    </h1>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <x-button :href="route('quote')" variant="inverse" size="lg">
                            {{ content('home.hero_secondary_cta') }}
                        </x-button>
                    </div>
                </div>

                <div class="min-w-0 lg:col-span-6">
                    <x-media
                        :src="$hero['light']"
                        :dark-src="$hero['dark']"
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

                {{-- The same material is bought under four or five different
                     names, and someone searching for one of them has no way of
                     knowing the other four describe what they want. Saying so
                     costs a sentence and saves the visitor a wrong turn. --}}
                <p class="mt-4 text-base leading-relaxed text-ink-600">{{ content('home.intro_names') }}</p>
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

                {{--
                    A carousel on a phone, the grid it always was above that.

                    One element, not two: rendering the cards twice and hiding a
                    copy would double the markup and the image URLs for a
                    section that shows the same four products either way. The
                    carousel mechanics switch off at `sm` — see
                    `.carousel-phone` — and the grid utilities here take over.

                    No arrows and no `tabindex` unlike the catalogue row: this
                    is only ever a carousel at phone widths, where the swipe is
                    the control and there is no keyboard to serve.
                --}}
                <ul class="no-scrollbar carousel carousel-phone mt-12 -mx-5 px-5 pb-2
                           sm:mx-0 sm:grid sm:grid-cols-2 sm:px-0 sm:pb-0 lg:grid-cols-4">
                    @foreach ($products as $product)
                        <li class="flex shrink-0 basis-[78%] sm:basis-auto sm:shrink">
                            <x-product-card :product="$product" class="w-full" />
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{--
        ── Applications ───────────────────────────────────────────────────
        Seven cards, one per use, built like the product cards above them: a
        picture, a name, a line of what it is for.

        There was an infographic here — one picture of how grade relates to use,
        which replaced an earlier card grid. It is gone: it explained the
        relationship and then left you nowhere to go, because a picture cannot
        link to the seven pages behind it. The cards can.
    --}}
    <section class="border-y border-hairline bg-surface-muted py-section">
        <div class="container-page">
            <x-section-heading
                :eyebrow="content('nav.applications')"
                :title="content('home.applications_title')"
                :body="content('home.applications_body')"
                :href="route('applications.index')"
            />

            {{--
                A carousel at every width, arrows and all — the same component
                as the grades row on the catalogue page.

                It was a grid above `sm`, and seven cards into four columns left
                a row of four and a row of three with a hole beside it. A hole
                in a grid reads as something failing to load rather than as a
                deliberate wrap. One row that scrolls has no last row to leave
                short, and it holds however many uses an editor adds.
            --}}
            @if ($applications->isNotEmpty())
                <div class="relative mt-12">
                    <ul
                        data-carousel
                        tabindex="0"
                        aria-label="{{ content('home.applications_title') }}"
                        class="no-scrollbar carousel -mx-5 px-5 pb-2 md:-mx-8 md:px-8"
                    >
                        @foreach ($applications as $application)
                            <li class="flex shrink-0 basis-[78%] sm:basis-[42%] lg:basis-[29%] xl:basis-[22%]">
                                <x-application-card :application="$application" class="w-full" />
                            </li>
                        @endforeach
                    </ul>

                    {{-- Hidden until app.js wires them up, and never on a
                         touch-sized screen where the swipe is the control. --}}
                    <div
                        data-carousel-controls
                        class="pointer-events-none absolute inset-y-0 -start-4 -end-4 items-center justify-between"
                    >
                        @foreach ([['prev', 'previous', 'M7 1L2 6l5 5'], ['next', 'next', 'M2 1l5 5-5 5']] as [$action, $label, $path])
                            <button
                                type="button"
                                data-carousel-{{ $action }}
                                aria-label="{{ content('common.pagination.'.$label) }}"
                                class="pointer-events-auto flex h-10 w-10 items-center justify-center rounded-full border border-hairline
                                       bg-surface text-ink-700 shadow-lift transition-opacity hover:text-brand-600
                                       disabled:pointer-events-none disabled:opacity-0"
                            >
                                <svg viewBox="0 0 9 12" class="h-3 w-2.5 rtl:rotate-180" fill="none" aria-hidden="true">
                                    <path d="{{ $path }}" stroke="currentColor" stroke-width="1.6"
                                          stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{--
        ── Quality ────────────────────────────────────────────────────────
        The two layouts want the button in different places: under the picture
        on a phone, at the end of the copy on desktop.

        Rather than rendering it twice and hiding one — two identical links in
        the document, one of them always a lie — the copy column is `contents`
        below `lg`. The wrapper dissolves, its children become grid items in
        their own right, and `order` can then put the button after the image.
        Above `lg` the wrapper is a block again and the button is simply the
        last thing in the column, where it was.
    --}}
    <section class="py-section">
        <div class="container-page grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
            <div class="contents lg:block lg:min-w-0">
                <div class="order-1 min-w-0 lg:order-none">
                    <p class="eyebrow mb-3">{{ content('nav.quality') }}</p>
                    <h2 class="text-2xl font-bold text-ink-950 md:text-3xl">{{ content('home.quality_title') }}</h2>
                    <p class="mt-5 text-base leading-relaxed text-ink-600">{{ content('home.quality_body') }}</p>
                </div>

                {{-- `justify-self-start`: as a grid item on the phone it would
                     otherwise be stretched to the full column width, which no
                     other outline button on the site is. --}}
                <x-button
                    :href="route('about.quality')"
                    variant="outline"
                    class="order-3 mt-2 justify-self-start lg:order-none lg:mt-8"
                >
                    {{ content('home.quality_cta') }}
                </x-button>
            </div>

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
                class="order-2 min-w-0 rounded-lg border border-hairline shadow-soft lg:order-first"
            />
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
        {{-- Pinned at every width; a fixed 4:5 on a phone, the viewport's own
             height from `md` up.

             The shape is fixed on a phone because `svh` made it a different
             proportion on every handset — 0.94 on a short screen, 0.77 on a
             tall one — and left nobody able to say what size to export the
             picture at. 4:5 makes that 1080 x 1350. The desktop panel keeps
             its viewport height, where filling the screen is the point.

             The pin itself is the same at both: the sections below have an
             opaque background and a position of their own, so they paint over
             a held image instead of scrolling past it. What made it read
             badly on a phone before was the caption — buried under the first
             section that rose over it, leaving a slice of photograph with
             text disappearing behind an edge. With the caption gone the band
             is just a picture being covered, which is what it was meant to
             be. --}}
        <div class="sticky top-0 aspect-[4/5] overflow-hidden bg-night-950 md:aspect-auto md:h-[78svh]">
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

            {{-- Only the top edge earns its keep now that the caption is gone:
                 it keeps the header off bare photograph as it reveals. The
                 floor at the bottom is left in place because it is what stops
                 the picture meeting the next section on a hard line. --}}
            <div class="absolute inset-0 bg-gradient-to-t from-night-950/70 via-transparent to-night-950/35"
                 aria-hidden="true"></div>

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

                {{-- Same carousel as the products and uses rows: swipeable on a
                     phone, the three-up grid it always was from `sm`. One
                     element rather than two, so the cards and their image URLs
                     are in the document once. --}}
                <ul class="no-scrollbar carousel carousel-phone mt-12 -mx-5 px-5 pb-2
                           sm:mx-0 sm:grid sm:grid-cols-2 sm:px-0 sm:pb-0 md:grid-cols-3">
                    @foreach ($projects as $project)
                        <li class="flex shrink-0 basis-[78%] sm:basis-auto sm:shrink">
                            <x-project-card :project="$project" class="w-full" />
                        </li>
                    @endforeach
                </ul>
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

                {{-- Same carousel as the products and uses rows: swipeable on a
                     phone, the three-up grid it always was from `sm`. One
                     element rather than two, so the cards and their image URLs
                     are in the document once. --}}
                <ul class="no-scrollbar carousel carousel-phone mt-12 -mx-5 px-5 pb-2
                           sm:mx-0 sm:grid sm:grid-cols-2 sm:px-0 sm:pb-0 md:grid-cols-3">
                    @foreach ($posts as $post)
                        <li class="flex shrink-0 basis-[78%] sm:basis-auto sm:shrink">
                            <x-post-card :post="$post" class="w-full" />
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <x-cta-band class="relative" />
    </div>

</x-layouts.app>
