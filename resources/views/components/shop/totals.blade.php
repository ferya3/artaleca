@props(['totals'])

@php use App\Shop\Shop; @endphp

<dl {{ $attributes->merge(['class' => 'space-y-2 text-sm']) }}>
    <div class="flex justify-between gap-4">
        <dt class="text-ink-600">{{ __('shop.subtotal') }}</dt>
        <dd class="tabular font-medium text-ink-900">{{ Shop::money($totals['subtotal']) }}</dd>
    </div>
    @if ($totals['tax_percent'] > 0)
        <div class="flex justify-between gap-4">
            <dt class="text-ink-600">{{ __('shop.tax', ['percent' => $totals['tax_percent']]) }}</dt>
            <dd class="tabular font-medium text-ink-900">{{ Shop::money($totals['tax']) }}</dd>
        </div>
    @endif
    <div class="flex justify-between gap-4 border-t border-hairline pt-3 text-base">
        <dt class="font-bold text-ink-950">{{ __('shop.total') }}</dt>
        <dd class="tabular font-bold text-ink-950" data-total>{{ Shop::money($totals['total']) }}</dd>
    </div>
</dl>
