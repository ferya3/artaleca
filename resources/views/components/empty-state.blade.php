@props(['message', 'action' => null, 'actionLabel' => null])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-dashed border-ink-300 bg-surface-muted px-6 py-16 text-center']) }}>
    <p class="mx-auto max-w-md text-sm leading-relaxed text-ink-600">{{ $message }}</p>

    @if ($action)
        <x-button :href="$action" variant="outline" size="sm" class="mt-6">
            {{ $actionLabel ?? __('common.clear_filters') }}
        </x-button>
    @endif
</div>
