@props([
    'eyebrow' => null,
    'title',
    'body' => null,
    'href' => null,
    'linkLabel' => null,
    'level' => 'h2',
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-6 md:flex-row md:items-end md:justify-between']) }}>
    <div class="max-w-2xl">
        @if ($eyebrow)
            <p class="eyebrow mb-3">{{ $eyebrow }}</p>
        @endif

        <{{ $level }} class="text-2xl font-bold text-ink-950 md:text-3xl">{{ $title }}</{{ $level }}>

        @if ($body)
            <p class="mt-4 text-base leading-relaxed text-ink-600">{{ $body }}</p>
        @endif
    </div>

    @if ($href)
        <a href="{{ $href }}"
           class="group inline-flex shrink-0 items-center gap-2 border-b-2 border-ink-950 pb-1 text-sm font-semibold text-ink-950 transition-colors hover:border-clay-600 hover:text-clay-600">
            {{ $linkLabel ?? __('common.view_all') }}
            <span class="inline-block transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" aria-hidden="true">&rarr;</span>
        </a>
    @endif
</div>
