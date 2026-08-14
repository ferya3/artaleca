@php
    $inputClass = 'w-full border border-ink-300 bg-white px-3 py-2.5 text-sm text-ink-900
                   focus:border-ink-500 focus:outline-none focus:ring-4 focus:ring-brand-500/12';
@endphp

<x-layouts.admin :title="$label">

    <a href="{{ route('admin.settings.index') }}" class="text-xs text-ink-500 hover:text-ink-900">
        <span class="inline-block rtl:rotate-180" aria-hidden="true">&larr;</span>
        {{ __('admin.settings') }}
    </a>

    <h1 class="mt-3 text-xl font-bold text-ink-950">{{ $label }}</h1>
    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-ink-600">{{ $summary }}</p>

    <form method="POST" action="{{ route('admin.settings.update', $group) }}" class="mt-8 space-y-4">
        @csrf
        @method('PUT')

        @foreach ($fields as $key => $definition)
            @php
                // `.` is the settings-key separator *and* Laravel's nested-input
                // separator, so field names use `|` and are mapped back on save.
                $field = str_replace('.', '|', $key);
                $type = $definition['type'] ?? 'text';
                $translatable = $definition['translatable'] ?? ! in_array($type, ['number', 'checkbox'], true);
                $stored = $values[$key] ?? null;
            @endphp

            <div class="panel p-4">
                <div class="mb-3 flex items-baseline justify-between gap-3">
                    <p class="text-sm font-medium text-ink-800">{{ $definition['label'] }}</p>
                    <code class="ltr-run text-[0.625rem] text-ink-400">{{ $key }}</code>
                </div>

                @if (! empty($definition['hint']))
                    <p class="mb-2.5 text-xs text-ink-500" dir="auto">{{ $definition['hint'] }}</p>
                @endif

                @if ($type === 'checkbox')
                    <label class="flex items-center gap-2.5 text-sm text-ink-700">
                        <input type="checkbox" name="{{ $field }}" value="1"
                               @checked(old($field, (bool) $stored))
                               class="h-4 w-4 border-ink-400 text-brand-600">
                        {{ $definition['label'] }}
                    </label>

                @elseif ($type === 'number')
                    <input type="number" name="{{ $field }}" dir="ltr"
                           value="{{ old($field, is_scalar($stored) ? $stored : '') }}"
                           min="0" max="{{ $definition['max'] ?? 999999999 }}"
                           class="{{ $inputClass }} @error($field) border-red-500 @enderror">

                @elseif (! $translatable)
                    <input type="text" name="{{ $field }}" dir="ltr"
                           value="{{ old($field, is_scalar($stored) ? $stored : '') }}"
                           placeholder="{{ $placeholders[$key] ?? '' }}"
                           class="{{ $inputClass }} @error($field) border-red-500 @enderror">

                @else
                    <div class="space-y-2.5">
                        @foreach ($locales as $code => $meta)
                            @php
                                $errorKey = $field.'.'.$code;
                                $value = old($errorKey, is_array($stored) ? ($stored[$code] ?? '') : '');
                            @endphp

                            <div class="flex items-start gap-2.5">
                                <span class="mt-2.5 w-7 shrink-0 text-[0.6875rem] font-semibold uppercase text-ink-400" dir="ltr">{{ $code }}</span>

                                @if ($type === 'textarea')
                                    <textarea name="{{ $field }}[{{ $code }}]" rows="3"
                                              lang="{{ $code }}" dir="{{ $meta['dir'] }}"
                                              placeholder="{{ $placeholders[$key.'.'.$code] ?? '' }}"
                                              class="{{ $inputClass }} @error($errorKey) border-red-500 @enderror">{{ $value }}</textarea>
                                @else
                                    <input type="text" name="{{ $field }}[{{ $code }}]" value="{{ $value }}"
                                           lang="{{ $code }}" dir="{{ $meta['dir'] }}"
                                           placeholder="{{ $placeholders[$key.'.'.$code] ?? '' }}"
                                           class="{{ $inputClass }} @error($errorKey) border-red-500 @enderror">
                                @endif
                            </div>

                            @error($errorKey)
                                <p class="ps-10 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endforeach
                    </div>
                @endif

                @error($field)
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        @if ($placeholders !== [])
            <p class="text-xs leading-relaxed text-ink-500">{{ __('admin.settings_placeholder_hint') }}</p>
        @endif

        <div class="sticky bottom-0 flex items-center gap-3 border-t border-hairline bg-ink-50/95 py-4 backdrop-blur-sm">
            <x-button type="submit">{{ __('admin.save') }}</x-button>
        </div>
    </form>

</x-layouts.admin>
