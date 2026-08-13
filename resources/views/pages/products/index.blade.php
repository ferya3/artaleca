<x-layouts.app>

    <x-page-header
        :eyebrow="content('nav.products')"
        :title="$activeCategory?->name ?? content('nav.products')"
        :lead="$activeCategory?->summary ?? content('product.catalogue_intro')"
    />

    <section class="py-12 md:py-16">
        <div class="container-page">

            {{-- Filters. A plain GET form: every filter combination is a real,
                 shareable, crawlable URL, and the page works with JavaScript
                 off — app.js only removes the submit button and auto-submits
                 on change. --}}
            <form
                method="GET"
                action="{{ $activeCategory ? route('products.category', ['category' => $activeCategory]) : route('products.index') }}"
                data-auto-submit
                class="mb-10 panel-muted p-4 md:p-5"
            >
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="filter-grain" class="text-xs font-medium text-ink-600">{{ content('product.filter_by_grain') }}</label>
                        <select id="filter-grain" name="grain"
                                class="border border-ink-300 bg-surface px-3 py-2.5 text-sm text-ink-900 focus:border-ink-500 focus:outline-none">
                            <option value="">{{ content('common.all') }}</option>
                            @foreach (['0-3', '3-10', '10-20', '20-30'] as $range)
                                <option value="{{ $range }}" @selected(request('grain') === $range)>
                                    {{ $range }} {{ content('product.grain_size_unit') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="filter-sort" class="text-xs font-medium text-ink-600">{{ content('common.sort') }}</label>
                        <select id="filter-sort" name="sort"
                                class="border border-ink-300 bg-surface px-3 py-2.5 text-sm text-ink-900 focus:border-ink-500 focus:outline-none">
                            @foreach (['position', 'grain_asc', 'grain_desc', 'density_asc'] as $sort)
                                <option value="{{ $sort }}" @selected(request('sort', 'position') === $sort)>
                                    {{ __('product.sort_'.$sort) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5 md:col-span-2">
                        <label for="filter-q" class="text-xs font-medium text-ink-600">{{ content('nav.search') }}</label>
                        <div class="flex gap-2">
                            <input id="filter-q" type="search" name="q" value="{{ request('q') }}"
                                   placeholder="{{ content('search.placeholder') }}"
                                   class="w-full border border-ink-300 bg-surface px-3 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 focus:border-ink-500 focus:outline-none">
                            <x-button type="submit" size="sm" data-filter-submit class="shrink-0">
                                {{ content('common.filter') }}
                            </x-button>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Category rail. Horizontally scrollable on mobile rather than
                 wrapping into four rows of chips. --}}
            @if ($categories->isNotEmpty())
                <nav class="no-scrollbar mb-10 -mx-5 overflow-x-auto px-5 md:mx-0 md:px-0" aria-label="{{ content('product.filter_by_category') }}">
                    <ul class="flex w-max gap-2 md:w-auto md:flex-wrap">
                        <li>
                            <a href="{{ route('products.index') }}"
                               @if (! $activeCategory) aria-current="page" @endif
                               class="inline-block whitespace-nowrap rounded-md border px-4 py-2 text-sm transition-colors
                                      {{ ! $activeCategory ? 'border-ink-950 bg-ink-950 text-surface' : 'border-hairline text-ink-700 hover:border-ink-400' }}">
                                {{ content('nav.all_products') }}
                            </a>
                        </li>
                        @foreach ($categories as $category)
                            @php $isActive = $activeCategory?->is($category); @endphp
                            <li>
                                <a href="{{ route('products.category', ['category' => $category]) }}"
                                   @if ($isActive) aria-current="page" @endif
                                   class="inline-block whitespace-nowrap rounded-md border px-4 py-2 text-sm transition-colors
                                          {{ $isActive ? 'border-ink-950 bg-ink-950 text-surface' : 'border-hairline text-ink-700 hover:border-ink-400' }}">
                                    {{ $category->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            @if ($products->isEmpty())
                <x-empty-state
                    :message="content('product.empty')"
                    :action="route('products.index')"
                    :action-label="content('common.clear_filters')"
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

    {{--
        ── Clay, sold in bulk ─────────────────────────────────────────────
        The plant sells the raw material as well as the fired product, and this
        is the page a buyer looking for either one lands on. Two blocks rather
        than one wall of text: what is sold, and what it is bought for.

        The uses are a real list rather than lines in a paragraph — the markup
        says "these are separate items" to anything not looking at the screen,
        and each one is its own key, so a language can drop or reword an entry
        without the others moving.
    --}}
    <section class="border-t border-hairline bg-surface-muted py-section">
        <div class="container-page grid gap-12 lg:grid-cols-2 lg:gap-16">
            <div class="min-w-0">
                <h2 class="text-2xl font-bold text-ink-950 md:text-3xl">{{ content('product.clay_title') }}</h2>
                <p class="mt-5 text-base leading-relaxed text-ink-600">{{ content('product.clay_body') }}</p>
                <p class="mt-4 text-base leading-relaxed text-ink-600">{{ content('product.clay_contact') }}</p>

                <x-button :href="route('contact')" variant="outline" class="mt-8">
                    {{ content('nav.contact') }}
                </x-button>
            </div>

            <div class="min-w-0">
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

    <x-cta-band />

</x-layouts.app>
