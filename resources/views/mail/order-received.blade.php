<x-mail::message>
# {{ $event === 'paid' ? 'Order paid' : 'New order' }} — {{ $order->number }}

Placed {{ $order->created_at->format('Y-m-d H:i') }} · site language: **{{ strtoupper($order->locale) }}** · payment: **{{ $order->payment_method }}** · status: **{{ $order->status->value }}**

<x-mail::table>
| Item | Qty | Unit price | Line |
|:-----|----:|-----------:|-----:|
@foreach ($order->items as $item)
| {{ $item->getTranslation('product_name', 'fa') }} — {{ $item->getTranslation('variant_name', 'fa') }} | {{ number_format($item->quantity) }} {{ $item->getTranslation('unit', 'fa') }} | {{ number_format($item->unit_price) }} | {{ number_format($item->line_total) }} |
@endforeach
| | | Subtotal | {{ number_format($order->subtotal) }} |
@if ($order->tax > 0)
| | | VAT {{ $order->tax_percent }}% | {{ number_format($order->tax) }} |
@endif
| | | **Total (toman)** | **{{ number_format($order->total) }}** |
</x-mail::table>

<x-mail::table>
| Field | Value |
|:------|:------|
| Name | {{ $order->customer_name }} |
| Mobile | {{ $order->mobile }} |
| Email | {{ $order->email ?: '—' }} |
| Company | {{ $order->company ?: '—' }} |
| National id / economic code | {{ $order->national_id ?: '—' }} / {{ $order->economic_code ?: '—' }} |
| Delivery | {{ $order->shipping_method }} |
| Address | {{ $order->province }}، {{ $order->city }}، {{ $order->address }} |
| Postal code | {{ $order->postal_code ?: '—' }} |
</x-mail::table>

@if (filled($order->notes))
**Customer note**

{{ $order->notes }}
@endif

<x-mail::button :url="route('admin.orders.show', $order)">
Open in the panel
</x-mail::button>
</x-mail::message>
