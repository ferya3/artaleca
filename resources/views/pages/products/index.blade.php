<x-layouts.app>

    <x-page-header
        :eyebrow="content('nav.products')"
        :title="$activeCategory?->name ?? content('nav.products')"
        :lead="$activeCategory?->summary ?? content('product.catalogue_intro')"
    />

    {{--
        ── Clay, sold in bulk ─────────────────────────────────────────────
        The plant sells the raw material as well as the fired product, and this
        is the page a buyer looking for either one lands on. It leads the page,
        above the catalogue grid, because it is the part a clay buyer came for
        and the grid is a long scroll to get past.

        Two blocks stacked rather than side by side: read one, then the other,
        in the same order on a phone and on a desktop. Each is capped at a
        readable measure instead of running the full container width.

        The uses are a real list rather than lines in a paragraph — the markup
        says "these are separate items" to anything not looking at the screen,
        and each one is its own key, so a language can drop or reword an entry
        without the others moving.
    --}}
    <section class="py-section">
        <div class="container-page">
            <h2 class="max-w-3xl text-2xl font-bold text-ink-950 md:text-3xl">
                {{ content('product.clay_section_title') }}
            </h2>

            <div class="mt-10 space-y-12 md:mt-12 md:space-y-16">
                <div class="max-w-3xl">
                    <h3 class="text-xl font-bold text-ink-950 md:text-2xl">{{ content('product.clay_title') }}</h3>
                    <p class="mt-5 text-base leading-relaxed text-ink-600">{{ content('product.clay_body') }}</p>
                    <p class="mt-4 text-base leading-relaxed text-ink-600">{{ content('product.clay_contact') }}</p>
                </div>

                <div class="max-w-3xl">
                    <h3 class="text-xl font-bold text-ink-950 md:text-2xl">{{ content('product.clay_industries_title') }}</h3>
                    <p class="mt-5 text-base leading-relaxed text-ink-600">{{ content('product.clay_industries_body') }}</p>
                    <p class="mt-4 text-base leading-relaxed text-ink-600">{{ content('product.clay_industries_lead') }}</p>

                    <ul class="mt-5 space-y-3">
                        @foreach (['brick', 'tile', 'materials', 'mineral', 'technical'] as $use)
                            <li class="flex gap-3">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-600" aria-hidden="true"></span>
                                <span class="text-base leading-relaxed text-ink-600">
                                    {{ content('product.clay_uses.'.$use) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <p class="mt-6 text-base leading-relaxed text-ink-600">{{ content('product.clay_industries_note') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{--
        The catalogue itself. The grain / sort / search form and the category
        chip rail were taken off this page; the controller still honours every
        one of those query parameters, so `?grain=`, `?sort=` and `?q=` URLs —
        and the per-category routes the header dropdown links to — keep working
        exactly as before. Putting the controls back is markup only.
    --}}
    <section class="border-t border-hairline py-12 md:py-16">
        <div class="container-page">
            @if ($products->isEmpty())
                <x-empty-state
                    :message="content('product.empty')"
                    :action="route('products.index')"
                    :action-label="content('nav.all_products')"
                />
            @else
                <h2 class="mb-8 max-w-3xl text-2xl font-bold text-ink-950 md:mb-10 md:text-3xl">
                    {{ content('product.grades_title') }}
                </h2>

                {{--
                    A grid, and emphatically not the scroller this used to be.

                    The row was built to read as "there is more to the right",
                    and on the home page that is exactly right: a teaser with a
                    link to the full list. Here it was wrong in a way no amount
                    of peek could fix. This *is* the full list — the page a
                    visitor reaches by following that link — and it showed two
                    grades of seven on a phone and four on a laptop, with the
                    rest behind a sideways swipe nobody has a reason to try on
                    a page they believe they are already at the end of.

                    Every grade is on the page now. `.card-grid` centres a short
                    last row, which is what makes an odd count (seven grades,
                    three to a row) wrap without leaving a hole in the corner.
                --}}
                <ul class="card-grid">
                    @foreach ($products as $product)
                        <li class="flex">
                            <x-product-card :product="$product" :eager="$loop->index < 3" class="w-full" />
                        </li>
                    @endforeach
                </ul>

                {{ $products->links() }}
            @endif
        </div>
    </section>

    <x-cta-band />

</x-layouts.app>
