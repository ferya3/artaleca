<x-layouts.app>

    <x-page-header
        :eyebrow="__('nav.products')"
        :title="$activeCategory?->name ?? __('nav.products')"
        :lead="$activeCategory?->summary ?? __('product.catalogue_intro')"
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
                class="mb-10 border border-hairline bg-surface-muted p-4 md:p-5"
            >
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="filter-grain" class="text-xs font-medium text-ink-600">{{ __('product.filter_by_grain') }}</label>
                        <select id="filter-grain" name="grain"
                                class="border border-ink-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-ink-900 focus:outline-none">
                            <option value="">{{ __('common.all') }}</option>
                            @foreach (['0-3', '3-10', '10-20', '20-30'] as $range)
                                <option value="{{ $range }}" @selected(request('grain') === $range)>
                                    {{ $range }} {{ __('product.grain_size_unit') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="filter-sort" class="text-xs font-medium text-ink-600">{{ __('common.sort') }}</label>
                        <select id="filter-sort" name="sort"
                                class="border border-ink-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-ink-900 focus:outline-none">
                            @foreach (['position', 'grain_asc', 'grain_desc', 'density_asc'] as $sort)
                                <option value="{{ $sort }}" @selected(request('sort', 'position') === $sort)>
                                    {{ __('product.sort_'.$sort) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5 md:col-span-2">
                        <label for="filter-q" class="text-xs font-medium text-ink-600">{{ __('nav.search') }}</label>
                        <div class="flex gap-2">
                            <input id="filter-q" type="search" name="q" value="{{ request('q') }}"
                                   placeholder="{{ __('search.placeholder') }}"
                                   class="w-full border border-ink-300 bg-white px-3 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 focus:border-ink-900 focus:outline-none">
                            <x-button type="submit" size="sm" data-filter-submit class="shrink-0">
                                {{ __('common.filter') }}
                            </x-button>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Category rail. Horizontally scrollable on mobile rather than
                 wrapping into four rows of chips. --}}
            @if ($categories->isNotEmpty())
                <nav class="no-scrollbar mb-10 -mx-5 overflow-x-auto px-5 md:mx-0 md:px-0" aria-label="{{ __('product.filter_by_category') }}">
                    <ul class="flex w-max gap-2 md:w-auto md:flex-wrap">
                        <li>
                            <a href="{{ route('products.index') }}"
                               @if (! $activeCategory) aria-current="page" @endif
                               class="inline-block whitespace-nowrap border px-4 py-2 text-sm transition-colors
                                      {{ ! $activeCategory ? 'border-ink-950 bg-ink-950 text-white' : 'border-hairline text-ink-700 hover:border-ink-400' }}">
                                {{ __('nav.all_products') }}
                            </a>
                        </li>
                        @foreach ($categories as $category)
                            @php $isActive = $activeCategory?->is($category); @endphp
                            <li>
                                <a href="{{ route('products.category', ['category' => $category]) }}"
                                   @if ($isActive) aria-current="page" @endif
                                   class="inline-block whitespace-nowrap border px-4 py-2 text-sm transition-colors
                                          {{ $isActive ? 'border-ink-950 bg-ink-950 text-white' : 'border-hairline text-ink-700 hover:border-ink-400' }}">
                                    {{ $category->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            @if ($products->isEmpty())
                <x-empty-state
                    :message="__('product.empty')"
                    :action="route('products.index')"
                    :action-label="__('common.clear_filters')"
                />
            @else
                <p class="tabular mb-6 text-xs text-ink-500">
                    <span class="ltr-run">{{ number_format($products->total()) }}</span> {{ __('common.results') }}
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
