<x-layouts.app>
    @php
        $locale = app()->getLocale();
        $paid = $order->payments->firstWhere('status', \App\Models\Payment::PAID);
    @endphp

    <x-page-header :eyebrow="__('shop.order_number')" :title="__('shop.order_title', ['number' => $order->number])" />

    <section class="py-section">
        <div class="container-page grid gap-10 lg:grid-cols-12">
            <div class="min-w-0 space-y-8 lg:col-span-7">
                <x-form.status />
                @error('payment')
                    <div role="alert" class="rounded-md border-s-2 border-red-600 bg-red-50 px-5 py-4 text-sm text-red-900">{{ $message }}</div>
                @enderror

                <dl class="grid gap-4 rounded-lg border border-hairline bg-surface p-5 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs text-ink-500">{{ __('shop.order_number') }}</dt>
                        <dd class="ltr-run mt-1 font-bold text-ink-950" data-latin-digits>{{ $order->number }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-500">{{ __('shop.order_date') }}</dt>
                        <dd class="mt-1 text-ink-900">{{ \App\Support\Dates::format($order->created_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-500">{{ __('shop.order_status') }}</dt>
                        <dd class="mt-1"><x-shop.status :status="$order->status" /></dd>
                    </div>
                </dl>

                @if ($canPayOnline)
                    <form method="POST" action="{{ route('orders.pay', $order->token) }}" class="rounded-lg border border-brand-200 bg-brand-50/60 p-5">
                        @csrf
                        <p class="text-sm text-ink-700">{{ __('shop.total') }}: <strong class="tabular">{{ \App\Shop\Shop::money($order->total) }}</strong></p>
                        <x-button type="submit" variant="accent" size="lg" class="mt-4">
                            {{ $order->payments->isEmpty() ? __('shop.pay_now') : __('shop.pay_again') }}
                        </x-button>
                    </form>
                @endif

                @if ($order->status === \App\Shop\OrderStatus::AwaitingTransfer && filled($transferDetails))
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-5">
                        <h2 class="font-bold text-amber-950">{{ __('shop.transfer_title') }}</h2>
                        <p class="mt-2 text-sm text-amber-900">{{ __('shop.transfer_help', ['total' => \App\Shop\Shop::money($order->total), 'number' => $order->number]) }}</p>
                        <p class="mt-4 rounded-md bg-white/70 p-4 text-sm font-medium leading-loose text-ink-900" data-latin-digits data-transfer-details>{!! \App\Shop\Shop::transferDetailsHtml() !!}</p>
                    </div>
                @endif

                @if ($paid)
                    <p class="text-sm text-ink-600">
                        {{ __('shop.paid_on') }} {{ \App\Support\Dates::format($paid->verified_at ?? $order->paid_at) }}
                        @if ($paid->reference) — {{ __('shop.bank_reference') }}: <span class="ltr-run font-medium text-ink-900">{{ $paid->reference }}</span>@endif
                        @if ($paid->card_pan) — {{ __('shop.card') }}: <span class="ltr-run" data-latin-digits>{{ $paid->card_pan }}</span>@endif
                    </p>
                @endif

                <div>
                    <h2 class="mb-3 text-lg font-bold text-ink-950">{{ __('shop.items') }}</h2>
                    <ul class="divide-y divide-hairline rounded-lg border border-hairline bg-surface text-sm">
                        @foreach ($order->items as $item)
                            <li class="flex justify-between gap-4 p-4">
                                <span>
                                    <span class="font-medium text-ink-950">{{ $item->product_name }} — {{ $item->variant_name }}</span>
                                    <span class="block text-xs text-ink-500">{{ number_format($item->quantity) }} {{ $item->unit }} × {{ \App\Shop\Shop::money($item->unit_price) }}</span>
                                </span>
                                <span class="tabular shrink-0 font-medium">{{ \App\Shop\Shop::money($item->line_total) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="text-sm text-ink-500">{{ __('shop.keep_link') }}</p>
            </div>

            <aside class="min-w-0 space-y-6 lg:col-span-5">
                <div class="panel-muted p-6">
                    <x-shop.totals :totals="$order->only(['subtotal', 'tax', 'tax_percent', 'shipping', 'total'])" />
                    <p class="mt-4 text-xs leading-relaxed text-ink-500">{{ __('shop.shipping_note') }}</p>
                </div>

                <div class="panel-muted space-y-2 p-6 text-sm">
                    <h2 class="font-bold text-ink-950">{{ __('shop.delivery') }}</h2>
                    <p class="text-ink-700">{{ $order->customer_name }} — <span class="ltr-run">{{ $order->mobile }}</span></p>
                    <p class="text-ink-700">{{ \App\Shop\Shop::provinces()[$order->province] ?? $order->province }}، {{ $order->city }}</p>
                    <p class="text-ink-600">{{ $order->address }}</p>
                    <p class="text-ink-600">{{ __('shop.shipping.'.$order->shipping_method) }} · {{ __('shop.payment.'.$order->payment_method) }}</p>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.app>
