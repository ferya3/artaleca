@props(['product', 'eager' => false])

@php
    $url = route('products.show', ['product' => $product]);
    $grain = $product->grainRange();
    $density = $product->bulkDensityRange();
@endphp

{{-- The whole card is one link (stretched-link pattern) so the tap target on
     mobile is the full tile, while the accessible name stays just the title. --}}
<article {{ $attributes->merge(['class' => 'panel panel-interactive group relative flex flex-col overflow-hidden']) }}>
    <x-media
        :src="$product->primaryImage()"
        :seed="$product->slug"
        :alt="$product->name"
        :eager="$eager"
        ratio="4/3"
        sizes="(min-width: 1280px) 300px, (min-width: 768px) 33vw, 100vw"
    />

    <div class="flex flex-1 flex-col p-5">
        @if ($product->category)
            <p class="eyebrow eyebrow-muted mb-2">{{ $product->category->name }}</p>
        @endif

        <h3 class="text-lg font-bold text-ink-950">
            <a href="{{ $url }}" class="before:absolute before:inset-0 group-hover:text-clay-600 transition-colors">
                {{ $product->name }}
            </a>
        </h3>

        @if (filled($product->tagline))
            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-ink-600">{{ $product->tagline }}</p>
        @endif

        {{-- Two headline numbers, not the whole datasheet: grain size and bulk
             density are what a specifier scans a catalogue grid for. --}}
        @if ($grain || $density)
            <dl class="tabular mt-auto grid grid-cols-2 gap-px border-t border-hairline bg-hairline pt-px">
                @if ($grain)
                    <div class="bg-white pt-4">
                        <dt class="text-[0.6875rem] uppercase tracking-wider text-ink-500">{{ __('product.grain_size') }}</dt>
                        <dd class="ltr-run mt-1 text-base font-semibold text-ink-950">
                            {{ $grain }} <span class="text-xs font-normal text-ink-500">{{ __('product.grain_size_unit') }}</span>
                        </dd>
                    </div>
                @endif

                @if ($density)
                    <div class="bg-white pt-4 {{ $grain ? 'ps-4' : '' }}">
                        <dt class="text-[0.6875rem] uppercase tracking-wider text-ink-500">{{ __('product.bulk_density') }}</dt>
                        <dd class="ltr-run mt-1 text-base font-semibold text-ink-950">
                            {{ $density }} <span class="text-xs font-normal text-ink-500">{{ __('product.bulk_density_unit') }}</span>
                        </dd>
                    </div>
                @endif
            </dl>
        @endif
    </div>
</article>
