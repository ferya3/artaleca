@props(['variant'])

@php
    use App\Shop\Shop;

    $unit = $variant->unit;
    $available = $variant->available();
@endphp

{{-- One way of buying a product, with its price and an "add" form.

     The quantity input carries the variant's own minimum, step and ceiling,
     so the browser stops "7 bags" of something sold in fives before the
     server has to. The server clamps it again anyway (ProductVariant::
     clampQuantity) — the input is a convenience, not the rule. --}}
<form method="POST" action="{{ route('cart.store') }}"
      {{ $attributes->merge(['class' => 'flex flex-col gap-4 rounded-lg border border-hairline bg-surface p-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    @csrf
    <input type="hidden" name="variant" value="{{ $variant->id }}">

    <div class="min-w-0">
        <p class="font-semibold text-ink-950">{{ $variant->name }}</p>
        <p class="mt-1 text-lg font-bold text-brand-700">{{ __('shop.per_unit', ['price' => Shop::money($variant->price), 'unit' => $unit]) }}</p>
        <p class="mt-1 text-xs text-ink-500">
            @if ($variant->min_quantity > 1)
                {{ __('shop.min_order', ['quantity' => number_format($variant->min_quantity), 'unit' => $unit]) }}
            @endif
            @if ($variant->step > 1)
                <span class="ms-2">{{ __('shop.step', ['step' => number_format($variant->step), 'unit' => $unit]) }}</span>
            @endif
            @if ($variant->stock !== null)
                <span class="ms-2">{{ __('shop.stock_left', ['quantity' => number_format($variant->stock), 'unit' => $unit]) }}</span>
            @endif
        </p>
    </div>

    <div class="flex items-end gap-2">
        <label class="flex flex-1 flex-col gap-1 text-xs text-ink-600 sm:flex-none">
            {{ __('shop.quantity') }}
            <input type="number" name="quantity" inputmode="numeric"
                   value="{{ max(1, $variant->min_quantity) }}"
                   min="{{ max(1, $variant->min_quantity) }}"
                   step="{{ max(1, $variant->step) }}"
                   @if ($available !== null) max="{{ $available }}" @endif
                   required
                   class="w-full rounded-md border sm:w-24 border-ink-300 bg-surface px-3 py-2.5 text-sm text-ink-900 focus:border-ink-500 focus:outline-none focus:ring-4 focus:ring-brand-500/12">
        </label>
        <x-button type="submit" variant="accent" class="shrink-0 whitespace-nowrap">{{ __('shop.add_to_cart') }}</x-button>
    </div>
</form>
