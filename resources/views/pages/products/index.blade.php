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
        <div class="container-page space-y-12 md:space-y-16">
            <div class="max-w-3xl">
                <h2 class="text-2xl font-bold text-ink-950 md:text-3xl">{{ content('product.clay_title') }}</h2>
                <p class="mt-5 text-base leading-relaxed text-ink-600">{{ content('product.clay_body') }}</p>
                <p class="mt-4 text-base leading-relaxed text-ink-600">{{ content('product.clay_contact') }}</p>

                <x-button :href="route('contact')" variant="outline" class="mt-8">
                    {{ content('nav.contact') }}
                </x-button>
            </div>

            <div class="max-w-3xl">
                <h2 class="text-2xl font-bold text-ink-950 md:text-3xl">{{ content('product.clay_industries_title') }}</h2>
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
                <p class="tabular mb-6 text-xs text-ink-500">
                    <span class="ltr-run">{{ number_format($products->total()) }}</span> {{ content('common.results') }}
                </p>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" :eager="$loop->index < 4" />
                    @endforeach
                </div>

                {{ $products->links() }}
            @endif
        </div>
    </section>

    <x-cta-band />

</x-layouts.app>
