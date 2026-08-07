<x-layouts.app>

    @php
        $gallery = $product->galleryImages();
        $main = $product->primaryImage();
    @endphp

    <div class="border-b border-hairline bg-surface-muted">
        <div class="container-page py-8">
            <x-breadcrumbs />
        </div>
    </div>

    {{-- ── Product head: image, identity, headline specs, actions ─────── --}}
    <section class="py-10 md:py-14">
        <div class="container-page grid gap-10 lg:grid-cols-2 lg:gap-16">

            <div data-gallery>
                <x-media
                    :src="$main"
                    :seed="$product->slug"
                    :alt="$product->name"
                    eager
                    ratio="4/3"
                    sizes="(min-width: 1024px) 50vw, 100vw"
                    class="border border-hairline"
                />

                @if (count($gallery) > 1)
                    <ul class="mt-3 grid grid-cols-4 gap-3">
                        @foreach ($gallery as $index => $image)
                            <li>
                                <button
                                    type="button"
                                    data-gallery-thumb="{{ $image }}"
                                    aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                                    class="block w-full border border-hairline transition-colors hover:border-ink-900 aria-[current=true]:border-ink-950"
                                >
                                    <img src="{{ $image }}" alt="{{ $product->name }} — {{ $index + 1 }}"
                                         loading="lazy" decoding="async"
                                         class="aspect-[4/3] w-full object-cover">
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div>
                @if ($product->category)
                    <a href="{{ route('products.category', ['category' => $product->category]) }}" class="eyebrow hover:underline">
                        {{ $product->category->name }}
                    </a>
                @endif

                <h1 class="mt-3 text-3xl font-bold text-ink-950 md:text-4xl">{{ $product->name }}</h1>

                @if (filled($product->sku))
                    <p class="tabular mt-2 text-sm text-ink-500">
                        {{ __('product.sku') }} <span class="ltr-run font-medium text-ink-700">{{ $product->sku }}</span>
                    </p>
                @endif

                @if (filled($product->tagline))
                    <p class="mt-5 text-base leading-relaxed text-ink-600 md:text-lg">{{ $product->tagline }}</p>
                @endif

                @if (filled($product->summary))
                    <p class="mt-4 text-sm leading-relaxed text-ink-600">{{ $product->summary }}</p>
                @endif

                {{-- The two numbers a specifier checks first, pulled out of the
                     full table so they are legible without scrolling. --}}
                @php
                    $headline = array_filter([
                        __('product.grain_size') => [$product->grainRange(), __('product.grain_size_unit')],
                        __('product.bulk_density') => [$product->bulkDensityRange(), __('product.bulk_density_unit')],
                    ], fn ($row) => filled($row[0]));
                @endphp

                @if ($headline !== [])
                    <dl class="mt-8 grid grid-cols-2 gap-px border border-hairline bg-hairline">
                        @foreach ($headline as $label => [$value, $unit])
                            <div class="bg-white p-5">
                                <dt class="text-xs uppercase tracking-wider text-ink-500">{{ $label }}</dt>
                                <dd class="tabular mt-2 flex items-baseline gap-1.5">
                                    <span class="ltr-run text-2xl font-bold text-ink-950">{{ $value }}</span>
                                    <span class="text-xs text-ink-500">{{ $unit }}</span>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <x-button :href="route('quote', ['product' => $product->slug])" variant="primary">
                        {{ __('product.request_quote') }}
                    </x-button>

                    @if (filled($product->datasheet_path))
                        <x-button :href="$product->datasheet_path" variant="outline" download>
                            {{ __('common.download_pdf') }}
                        </x-button>
                    @else
                        <x-button :href="route('contact')" variant="outline">
                            {{ __('product.ask_engineer') }}
                        </x-button>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ── Technical data + description ───────────────────────────────── --}}
    <section class="border-t border-hairline bg-surface-muted py-section">
        <div class="container-page grid gap-12 lg:grid-cols-12 lg:gap-16">

            <div class="lg:col-span-7">
                @if (filled($product->description))
                    <h2 class="text-xl font-bold text-ink-950">{{ __('product.description') }}</h2>
                    <div class="prose-industrial mt-5">{!! nl2br(e($product->description)) !!}</div>
                @endif

                @if (filled($product->packaging))
                    <h2 class="mt-12 text-xl font-bold text-ink-950">{{ __('product.packaging') }}</h2>
                    <ul class="mt-5 divide-y divide-hairline border-y border-hairline">
                        @foreach ($product->packaging as $option)
                            <li class="flex flex-wrap items-baseline justify-between gap-3 py-3.5 text-sm">
                                <span class="text-ink-800">
                                    {{ data_get($option, 'type.'.app()->getLocale()) ?? data_get($option, 'type.fa') ?? data_get($option, 'type') }}
                                </span>
                                <span class="ltr-run tabular text-ink-500">{{ data_get($option, 'volume') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if (filled($product->standards))
                    <h2 class="mt-12 text-xl font-bold text-ink-950">{{ __('product.standards') }}</h2>
                    <ul class="mt-5 flex flex-wrap gap-2">
                        @foreach ($product->standards as $standard)
                            <li class="ltr-run border border-hairline bg-white px-3.5 py-2 text-xs text-ink-700">{{ $standard }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="lg:col-span-5">
                <div class="border border-hairline bg-white p-6">
                    <h2 class="text-xl font-bold text-ink-950">{{ __('product.technical_data') }}</h2>
                    <x-spec-table :product="$product" class="mt-4" />
                    <p class="mt-5 border-t border-hairline pt-4 text-xs leading-relaxed text-ink-500">
                        {{ __('legal.terms_sections.0.body') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Applications ───────────────────────────────────────────────── --}}
    @if ($product->applications->isNotEmpty())
        <section class="py-section">
            <div class="container-page">
                <x-section-heading :title="__('product.applications')" :href="route('applications.index')" />

                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($product->applications as $application)
                        <x-application-card :application="$application" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ── Related ────────────────────────────────────────────────────── --}}
    @if ($related->isNotEmpty())
        <section class="border-t border-hairline py-section">
            <div class="container-page">
                <x-section-heading :title="__('common.related_products')" :href="route('products.index')" />

                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)
                        <x-product-card :product="$item" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-cta-band />

</x-layouts.app>
