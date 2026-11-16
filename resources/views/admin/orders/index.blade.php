<x-layouts.admin :title="__('admin.orders.title')">

    <h1 class="text-xl font-bold text-ink-950">{{ __('admin.orders.title') }}</h1>

    <div class="mt-5 flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.orders.index') }}"
           class="border px-3 py-1.5 text-xs transition-colors {{ ! $activeStatus ? 'border-ink-950 bg-ink-950 text-white' : 'border-hairline text-ink-600 hover:border-ink-400' }}">
            {{ __('common.view_all') }}
        </a>
        @foreach (\App\Shop\OrderStatus::cases() as $status)
            <a href="{{ route('admin.orders.index', ['status' => $status->value]) }}"
               class="border px-3 py-1.5 text-xs transition-colors {{ $activeStatus === $status->value ? 'border-ink-950 bg-ink-950 text-white' : 'border-hairline text-ink-600 hover:border-ink-400' }}">
                {{ $status->label() }}
            </a>
        @endforeach

        <form method="GET" action="{{ route('admin.orders.index') }}" class="ms-auto flex gap-2">
            @if ($activeStatus) <input type="hidden" name="status" value="{{ $activeStatus }}"> @endif
            <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('admin.orders.search') }}"
                   class="w-56 border border-ink-300 px-3 py-1.5 text-sm focus:border-ink-500 focus:outline-none">
        </form>
    </div>

    @if ($orders->isEmpty())
        <p class="mt-6 rounded-lg border border-dashed border-ink-300 bg-white px-5 py-14 text-center text-sm text-ink-500">
            {{ __('admin.no_records') }}
        </p>
    @else
        <div class="mt-6 overflow-x-auto panel">
            <table class="w-full min-w-[52rem] text-sm">
                <thead>
                    <tr class="border-b border-hairline bg-ink-50 text-xs font-semibold uppercase tracking-wider text-ink-500">
                        <th scope="col" class="px-4 py-3 text-start">{{ __('shop.order_number') }}</th>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('shop.customer') }}</th>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('shop.total') }}</th>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('admin.status') }}</th>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('shop.order_date') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-hairline">
                    @foreach ($orders as $order)
                        <tr class="{{ in_array($order->status, [\App\Shop\OrderStatus::Paid, \App\Shop\OrderStatus::AwaitingTransfer], true) ? 'bg-brand-50/40' : '' }}">
                            <td class="px-4 py-3 align-top">
                                <a href="{{ route('admin.orders.show', $order) }}" class="ltr-run font-semibold text-ink-900 hover:text-brand-600" data-latin-digits>{{ $order->number }}</a>
                                <span class="block text-xs text-ink-400">{{ __('admin.orders.items_count', ['count' => $order->items_count]) }}</span>
                            </td>
                            <td class="px-4 py-3 align-top">
                                {{ $order->customer_name }}
                                <span class="ltr-run block text-xs text-ink-500">{{ $order->mobile }}</span>
                                <span class="block text-xs text-ink-400">{{ $order->city }}</span>
                            </td>
                            <td class="tabular px-4 py-3 align-top font-medium">{{ \App\Shop\Shop::money($order->total) }}
                                <span class="block text-xs font-normal text-ink-500">{{ __('shop.payment.'.$order->payment_method) }}</span>
                            </td>
                            <td class="px-4 py-3 align-top"><x-shop.status :status="$order->status" /></td>
                            <td class="px-4 py-3 align-top text-xs text-ink-500">{{ \App\Support\Dates::format($order->created_at) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $orders->links() }}
    @endif
</x-layouts.admin>
