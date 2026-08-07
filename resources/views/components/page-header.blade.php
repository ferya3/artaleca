@props([
    'eyebrow' => null,
    'title',
    'lead' => null,
])

{{-- The standard opening block for every inner page: breadcrumb, optional
     eyebrow, H1 and a single lead paragraph. Nothing else — the page's own
     content starts immediately below. --}}
<section class="border-b border-hairline bg-surface-muted">
    <div class="container-page py-10 md:py-14">
        <x-breadcrumbs class="mb-6" />

        @if ($eyebrow)
            <p class="eyebrow mb-3">{{ $eyebrow }}</p>
        @endif

        <h1 class="max-w-3xl text-3xl font-bold text-ink-950 md:text-4xl lg:text-[2.75rem]">
            {{ $title }}
        </h1>

        @if ($lead)
            <p class="mt-5 max-w-2xl text-base leading-relaxed text-ink-600 md:text-lg">
                {{ $lead }}
            </p>
        @endif

        {{ $slot ?? '' }}
    </div>
</section>
