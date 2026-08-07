@props([
    'href' => null,
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'submit',
])

@php
    $variants = [
        'primary' => 'bg-ink-950 text-white hover:bg-clay-600 border border-transparent',
        'accent' => 'bg-clay-600 text-white hover:bg-clay-700 border border-transparent',
        'outline' => 'border border-ink-300 text-ink-900 hover:border-ink-950 hover:bg-ink-50',
        'ghost' => 'border border-transparent text-ink-700 hover:text-ink-950 hover:bg-ink-50',
        'inverse' => 'border border-white/25 text-white hover:bg-white hover:text-ink-950',
    ];

    $sizes = [
        'sm' => 'px-4 py-2 text-xs',
        'md' => 'px-6 py-3 text-sm',
        'lg' => 'px-8 py-4 text-base',
    ];

    $classes = implode(' ', [
        'inline-flex items-center justify-center gap-2 font-semibold transition-colors',
        'disabled:cursor-not-allowed disabled:opacity-60',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
