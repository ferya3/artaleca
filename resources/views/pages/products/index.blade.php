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
                    The grades read as a row you scroll, not a block that ends.
                    Card widths are a little under an exact fit at every
                    breakpoint, so part of the next one is always on screen —
                    which is the whole signal that there is more to the right.
                    See `.carousel` for the mechanics.

                    The negative margin matches the page gutter at each
                    breakpoint, so the row bleeds to the edge of the screen on a
                    phone while the first card still lines up with the heading
                    above it.
                --}}
                <div class="relative">
                    <ul
                        data-carousel
                        tabindex="0"
                        aria-label="{{ content('product.grades_title') }}"
                        class="no-scrollbar carousel -mx-5 px-5 pb-2 md:-mx-8 md:px-8"
                    >
                        @foreach ($products as $product)
                            <li class="flex shrink-0 basis-[78%] sm:basis-[42%] lg:basis-[29%] xl:basis-[22%]">
                                <x-product-card :product="$product" :eager="$loop->index < 4" class="w-full" />
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

                {{ $products->links() }}
            @endif
        </div>
    </section>

    <x-cta-band />

</x-layouts.app>
