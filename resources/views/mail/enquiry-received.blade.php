<x-mail::message>
@php
    $heading = match ($enquiry->type) {
        'quote' => 'New quotation request',
        'representation' => 'New representation application',
        default => 'New website enquiry',
    };
@endphp
# {{ $heading }}

Received {{ $enquiry->created_at->format('Y-m-d H:i') }} · site language: **{{ strtoupper($enquiry->locale) }}**

<x-mail::table>
| Field | Value |
|:------|:------|
| Name | {{ $enquiry->name }} |
| Company | {{ $enquiry->company ?: '—' }} |
| Email | {{ $enquiry->email }} |
| Phone | {{ $enquiry->phone ?: '—' }} |
| Country | {{ $enquiry->country_code ?: '—' }} |
@if ($enquiry->type === 'quote')
| Product | {{ $enquiry->product?->getTranslation('name', 'en') ?? '—' }} |
| Quantity | {{ $enquiry->quantity ?: '—' }} |
| Terms | {{ $enquiry->delivery_terms ?: '—' }} |
@endif
@foreach ($enquiry->details ?? [] as $field => $value)
| {{ ucfirst(str_replace('_', ' ', $field)) }} | {{ $value }} |
@endforeach
</x-mail::table>

@if (filled($enquiry->subject))
**Subject:** {{ $enquiry->subject }}
@endif

@if (filled($enquiry->message))
**Message**

{{ $enquiry->message }}
@endif

<x-mail::button :url="route('admin.enquiries.show', $enquiry)">
Open in admin
</x-mail::button>

<x-slot:subcopy>
Reply directly to this email to answer {{ $enquiry->name }} — the reply-to address is set to {{ $enquiry->email }}.
</x-slot:subcopy>
</x-mail::message>
