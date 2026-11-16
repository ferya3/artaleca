<x-layouts.app>

    <x-page-header :eyebrow="content('seo.brand_name')" :title="__('shop.title')" :lead="__('shop.intro')" />

    <section class="py-section">
        <div class="container-page">
            <x-form.status />

            @if ($products->isEmpty())
                <x-empty-state :message="__('shop.empty_shop')" />
            @else
                <div class="space-y-8">
                    @foreach ($products as $product)
                        {{-- A thumbnail beside the name on a phone, where a full-width
                             square per product turned three products into four
                             screens of picture; a column of its own above that. --}}
                        <article class="panel grid grid-cols-[5.5rem_1fr] gap-x-4 gap-y-4 overflow-hidden p-4 sm:p-6 md:grid-cols-[12rem_1fr] md:gap-x-6" id="p-{{ $product->slug }}">
                            <a href="{{ route('products.show', ['product' => $product]) }}" class="block overflow-hidden rounded-lg md:row-span-2">
                                <x-media :src="$product->primaryImage()" :seed="$product->slug" :alt="$product->name"
                                         ratio="1/1" sizes="(min-width: 768px) 12rem, 6rem" :eager="$loop->first" />
                            </a>

                            <div class="min-w-0 self-center md:self-start">
                                @if ($product->category)
                                    <p class="eyebrow eyebrow-muted mb-1">{{ $product->category->name }}</p>
                                @endif
                                <h2 class="text-lg font-bold text-ink-950 sm:text-xl">
                                    <a href="{{ route('products.show', ['product' => $product]) }}" class="hover:text-brand-600">{{ $product->name }}</a>
                                </h2>
                                @if (filled($product->tagline))
                                    <p class="mt-1 line-clamp-2 text-sm text-ink-600">{{ $product->tagline }}</p>
                                @endif
                            </div>

                            <div class="col-span-2 min-w-0 space-y-3 md:col-span-1 md:col-start-2">
                                @foreach ($product->variants as $variant)
                                    <x-shop.buy-form :variant="$variant->setRelation('product', $product)" />
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-10 space-y-2 text-sm text-ink-600">
                    <p>{{ __('shop.shipping_note') }}</p>
                    @if (app()->getLocale() !== 'fa')
                        <p>{{ __('shop.foreign_note') }}</p>
                    @endif
                    <p><a class="font-medium text-brand-700 underline" href="{{ route('quote') }}">{{ __('shop.quote_instead') }}</a></p>
                </div>
            @endif
        </div>
    </section>

</x-layouts.app>
