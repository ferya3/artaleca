<x-layouts.app>

    <x-page-header :title="__('shop.cart')" />

    <section class="py-section">
        <div class="container-page">
            <x-form.status />
            @error('cart')
                <div role="alert" class="mb-8 rounded-md border-s-2 border-red-600 bg-red-50 px-5 py-4 text-sm text-red-900">{{ $message }}</div>
            @enderror

            @if ($lines->isEmpty())
                <x-empty-state :message="__('shop.empty')" :action="route('shop.index')" :action-label="__('shop.continue')" />
            @else
                <div class="grid gap-10 lg:grid-cols-12">
                    <div class="min-w-0 lg:col-span-8">
                        <ul class="divide-y divide-hairline rounded-lg border border-hairline bg-surface">
                            @foreach ($lines as $line)
                                @php
                                    $variant = $line['variant'];
                                @endphp
                                <li class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:gap-6 sm:p-5" data-cart-line>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('products.show', ['product' => $variant->product]) }}" class="font-semibold text-ink-950 hover:text-brand-600">{{ $variant->product->name }}</a>
                                        <p class="text-sm text-ink-600">{{ $variant->name }}</p>
                                        <p class="mt-1 text-xs text-ink-500">{{ __('shop.per_unit', ['price' => \App\Shop\Shop::money($variant->price), 'unit' => $variant->unit]) }}</p>
                                    </div>

                                    <form method="POST" action="{{ route('cart.update', ['variant' => $variant->id]) }}" class="flex items-end gap-2">
                                        @csrf @method('PATCH')
                                        <label class="flex flex-col gap-1 text-xs text-ink-600">
                                            {{ __('shop.quantity') }} ({{ $variant->unit }})
                                            <input type="number" name="quantity" inputmode="numeric" value="{{ $line['quantity'] }}"
                                                   min="0" step="{{ max(1, $variant->step) }}"
                                                   @if ($variant->available() !== null) max="{{ $variant->available() }}" @endif
                                                   class="w-24 rounded-md border border-ink-300 bg-surface px-3 py-2 text-sm">
                                        </label>
                                        <x-button type="submit" variant="outline" size="sm">{{ __('shop.update') }}</x-button>
                                    </form>

                                    <div class="flex items-center justify-between gap-4 sm:w-44 sm:flex-col sm:items-end">
                                        <span class="tabular font-bold text-ink-950">{{ \App\Shop\Shop::money($line['line_total']) }}</span>
                                        <form method="POST" action="{{ route('cart.destroy', ['variant' => $variant->id]) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs text-ink-500 underline hover:text-red-600">{{ __('shop.remove') }}</button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        <a href="{{ route('shop.index') }}" class="mt-4 inline-block text-sm text-brand-700 underline">{{ __('shop.continue') }}</a>
                    </div>

                    <aside class="min-w-0 lg:col-span-4">
                        <div class="panel-muted p-6 lg:sticky lg:top-28">
                            <x-shop.totals :totals="$totals" />
                            <p class="mt-4 text-xs leading-relaxed text-ink-500">{{ __('shop.shipping_note') }}</p>
                            <x-button :href="route('checkout.create')" variant="accent" size="lg" class="mt-6 w-full">{{ __('shop.proceed') }}</x-button>
                        </div>
                    </aside>
                </div>
            @endif
        </div>
    </section>
</x-layouts.app>
