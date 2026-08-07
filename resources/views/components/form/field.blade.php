@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'hint' => null,
    'placeholder' => null,
    'options' => null,
    'rows' => 6,
])

@php
    $id = 'f-'.$name;
    $errorId = $id.'-error';
    $hintId = $id.'-hint';
    $hasError = $errors->has($name);
    $current = old($name, $value);

    $describedBy = trim(
        ($hint ? $hintId.' ' : '').($hasError ? $errorId : '')
    ) ?: null;

    $control = 'w-full border bg-white px-3.5 py-3 text-sm text-ink-900 transition-colors
                placeholder:text-ink-400 focus:outline-none focus:ring-2 focus:ring-clay-500/30 '
        .($hasError
            ? 'border-red-500 focus:border-red-500'
            : 'border-ink-300 focus:border-ink-900');
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1.5']) }}>
    <label for="{{ $id }}" class="text-sm font-medium text-ink-800">
        {{ $label }}
        @if ($required)
            <span class="text-clay-600" aria-hidden="true">*</span>
            <span class="sr-only">({{ __('common.required') }})</span>
        @else
            <span class="ms-1 text-xs font-normal text-ink-400">({{ __('common.optional') }})</span>
        @endif
    </label>

    @if ($hint)
        <p id="{{ $hintId }}" class="text-xs text-ink-500">{{ $hint }}</p>
    @endif

    @if ($type === 'textarea')
        <textarea
            id="{{ $id }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            @if ($required) required @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($hasError) aria-invalid="true" @endif
            class="{{ $control }} resize-y"
        >{{ $current }}</textarea>
    @elseif ($type === 'select')
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            @if ($required) required @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($hasError) aria-invalid="true" @endif
            class="{{ $control }}"
        >
            <option value="">{{ $placeholder ?? __('form.select_placeholder') }}</option>
            @foreach ($options ?? [] as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>
    @else
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $current }}"
            @if ($required) required @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($hasError) aria-invalid="true" @endif
            {{-- Native autocomplete tokens let a browser fill an enquiry form
                 in one tap, which measurably lifts completion on mobile. --}}
            autocomplete="{{ match ($name) {
                'name' => 'name',
                'email' => 'email',
                'phone' => 'tel',
                'company' => 'organization',
                'country_code' => 'country',
                default => 'off',
            } }}"
            class="{{ $control }}"
            @if ($type === 'tel') dir="ltr" @endif
        >
    @endif

    @error($name)
        <p id="{{ $errorId }}" class="text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
