<x-layouts.admin :title="$order->number">

    <a href="{{ route('admin.orders.index') }}" class="text-xs text-ink-500 hover:text-ink-900">
        <span class="inline-block rtl:rotate-180" aria-hidden="true">&larr;</span> {{ __('admin.orders.title') }}
    </a>

    <div class="mt-4 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="panel p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="ltr-run text-lg font-bold text-ink-950" data-latin-digits>{{ $order->number }}</h1>
                        <p class="text-xs text-ink-500">{{ \App\Support\Dates::format($order->created_at) }} · {{ strtoupper($order->locale) }}</p>
                    </div>
                    <x-shop.status :status="$order->status" />
                </div>

                <table class="mt-6 w-full text-sm">
                    <thead>
                        <tr class="border-b border-hairline text-xs text-ink-500">
                            <th class="py-2 text-start font-medium">{{ __('shop.items') }}</th>
                            <th class="py-2 text-end font-medium">{{ __('shop.quantity') }}</th>
                            <th class="py-2 text-end font-medium">{{ __('shop.unit_price') }}</th>
                            <th class="py-2 text-end font-medium">{{ __('shop.line_total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hairline">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="py-3">{{ $item->product_name }} — {{ $item->variant_name }}</td>
                                <td class="tabular py-3 text-end">{{ number_format($item->quantity) }} {{ $item->unit }}</td>
                                <td class="tabular py-3 text-end">{{ \App\Shop\Shop::money($item->unit_price) }}</td>
                                <td class="tabular py-3 text-end font-medium">{{ \App\Shop\Shop::money($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <x-shop.totals :totals="$order->only(['subtotal', 'tax', 'tax_percent', 'shipping', 'total'])" class="mt-5 border-t border-hairline pt-4 sm:ms-auto sm:max-w-xs" />
            </div>

            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="panel p-6">
                @csrf
                @method('PATCH')

                <div class="grid gap-5 sm:grid-cols-2">
                    @if ($order->status->next() !== [])
                        <div class="flex flex-col gap-1.5">
                            <label for="status" class="text-sm font-medium text-ink-800">{{ __('admin.orders.move_to') }}</label>
                            <select id="status" name="status" class="border border-ink-300 px-3 py-2.5 text-sm focus:border-ink-500 focus:outline-none">
                                <option value="">{{ __('admin.orders.keep_status', ['status' => $order->status->label()]) }}</option>
                                @foreach ($order->status->next() as $next)
                                    <option value="{{ $next->value }}">{{ $next->label() }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-ink-500">{{ __('admin.orders.move_hint') }}</p>
                        </div>
                    @endif

                    <div class="flex flex-col gap-1.5 sm:col-span-2">
                        <label for="staff_note" class="text-sm font-medium text-ink-800">{{ __('admin.orders.staff_note') }}</label>
                        <textarea id="staff_note" name="staff_note" rows="4"
                                  class="border border-ink-300 px-3 py-2.5 text-sm focus:border-ink-500 focus:outline-none">{{ old('staff_note', $order->staff_note) }}</textarea>
                    </div>
                </div>

                @error('status') <p class="mt-3 text-xs text-red-600">{{ $message }}</p> @enderror

                <button type="submit" class="mt-5 bg-ink-950 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-600">{{ __('admin.save') }}</button>
            </form>

            @if ($order->payments->isNotEmpty())
                <div class="panel p-6">
                    <h2 class="font-bold text-ink-950">{{ __('admin.orders.payments') }}</h2>
                    <ul class="mt-4 divide-y divide-hairline text-sm">
                        @foreach ($order->payments as $payment)
                            <li class="flex flex-wrap justify-between gap-2 py-3">
                                <span>
                                    {{ $payment->gateway }} · {{ \App\Shop\Shop::money($payment->amount) }}
                                    <span class="block text-xs text-ink-500">{{ \App\Support\Dates::format($payment->created_at) }}</span>
                                </span>
                                <span class="text-end text-xs">
                                    <span class="font-semibold {{ $payment->status === 'paid' ? 'text-green-700' : ($payment->status === 'failed' ? 'text-red-600' : 'text-ink-500') }}">{{ __('admin.orders.payment_status.'.$payment->status) }}</span>
                                    @if ($payment->reference) <span class="ltr-run block">{{ __('shop.bank_reference') }}: {{ $payment->reference }}</span> @endif
                                    @if ($payment->card_pan) <span class="ltr-run block" data-latin-digits>{{ $payment->card_pan }}</span> @endif
                                    @if ($payment->failure) <span class="block text-ink-500">{{ $payment->failure }}</span> @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <aside class="space-y-6">
            <div class="panel space-y-2 p-6 text-sm">
                <h2 class="font-bold text-ink-950">{{ __('shop.customer') }}</h2>
                <p class="font-medium text-ink-900">{{ $order->customer_name }}</p>
                <p><a class="ltr-run text-brand-700" href="tel:{{ $order->mobile }}">{{ $order->mobile }}</a></p>
                @if ($order->email) <p><a class="ltr-run text-brand-700" href="mailto:{{ $order->email }}">{{ $order->email }}</a></p> @endif
                @if ($order->company) <p>{{ __('shop.fields.company') }}: {{ $order->company }}</p> @endif
                @if ($order->national_id) <p>{{ __('shop.fields.national_id') }}: <span class="ltr-run">{{ $order->national_id }}</span></p> @endif
                @if ($order->economic_code) <p>{{ __('shop.fields.economic_code') }}: <span class="ltr-run">{{ $order->economic_code }}</span></p> @endif
            </div>

            <div class="panel space-y-2 p-6 text-sm">
                <h2 class="font-bold text-ink-950">{{ __('shop.delivery') }}</h2>
                <p>{{ __('shop.shipping.'.$order->shipping_method) }}</p>
                <p>{{ $order->province }}، {{ $order->city }}</p>
                <p class="text-ink-700">{{ $order->address }}</p>
                @if ($order->postal_code) <p>{{ __('shop.fields.postal_code') }}: <span class="ltr-run">{{ $order->postal_code }}</span></p> @endif
                @if ($order->notes) <p class="whitespace-pre-line border-t border-hairline pt-2 text-ink-600">{{ $order->notes }}</p> @endif
            </div>

            <div class="panel p-6 text-sm">
                <h2 class="font-bold text-ink-950">{{ __('shop.sections.payment') }}</h2>
                <p class="mt-2">{{ __('shop.payment.'.$order->payment_method) }}</p>
                @if ($order->paid_at) <p class="mt-1 text-green-700">{{ __('shop.paid_on') }} {{ \App\Support\Dates::format($order->paid_at) }}</p> @endif
                <a href="{{ route('orders.show', ['locale' => $order->locale, 'token' => $order->token]) }}" target="_blank" rel="noopener"
                   class="mt-4 inline-block text-xs text-ink-500 underline">{{ __('admin.orders.customer_page') }}</a>
            </div>
        </aside>
    </div>
</x-layouts.admin>
