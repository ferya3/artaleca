@php
    $inputClass = 'w-full border border-ink-300 bg-white px-3 py-2.5 text-sm text-ink-900
                   focus:border-ink-500 focus:outline-none focus:ring-4 focus:ring-clay-500/12';
@endphp

<x-layouts.admin :title="__('admin.settings')">

    <h1 class="text-xl font-bold text-ink-950">{{ __('admin.settings') }}</h1>
    <p class="mt-2 text-sm text-ink-600">{{ __('admin.translation_hint') }}</p>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="mt-8 space-y-10">
        @csrf
        @method('PUT')

        @foreach ($groups as $group => $definitions)
            <section>
                <h2 class="eyebrow mb-4">{{ __('admin.settings_groups.'.$group) }}</h2>

                <div class="space-y-4">
                    @foreach ($definitions as $key => $definition)
                        @php
                            // `.` is the settings-key separator *and* Laravel's nested-input
                            // separator, so field names use `|` and are mapped back on save.
                            $field = str_replace('.', '|', $key);
                            $type = $definition['type'] ?? 'text';
                            $translatable = $definition['translatable']
                                ?? ! in_array($type, ['number', 'checkbox', 'image'], true);
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
                                           class="h-4 w-4 border-ink-400 text-clay-600">
                                    {{ $definition['label'] }}
                                </label>

                            @elseif ($type === 'number')
                                <input type="number" name="{{ $field }}" dir="ltr"
                                       value="{{ old($field, is_scalar($stored) ? $stored : '') }}"
                                       min="0" max="{{ $definition['max'] ?? 999999999 }}"
                                       class="{{ $inputClass }} @error($field) border-red-500 @enderror">

                            @elseif ($type === 'image')
                                <div class="flex flex-wrap items-start gap-4">
                                    @if (is_string($stored) && filled($stored))
                                        <img src="{{ \App\Support\Image::thumb($stored, 480) }}" alt="" loading="lazy" decoding="async" class="h-20 w-36 shrink-0 rounded-lg border border-hairline object-cover">
                                    @endif
                                    <input type="file" name="{{ $field }}"
                                           accept="{{ collect(config('site.uploads.image_mimes'))->map(fn ($m) => '.'.$m)->implode(',') }}"
                                           class="min-w-0 flex-1 text-sm text-ink-600 file:me-3 file:border-0 file:bg-ink-950 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white">
                                </div>

                            @elseif (! $translatable)
                                <input type="text" name="{{ $field }}" dir="ltr"
                                       value="{{ old($field, is_scalar($stored) ? $stored : '') }}"
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
                                                          class="{{ $inputClass }} @error($errorKey) border-red-500 @enderror">{{ $value }}</textarea>
                                            @else
                                                <input type="text" name="{{ $field }}[{{ $code }}]" value="{{ $value }}"
                                                       lang="{{ $code }}" dir="{{ $meta['dir'] }}"
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
                </div>
            </section>
        @endforeach

        <div class="sticky bottom-0 flex items-center gap-3 border-t border-hairline bg-ink-50/95 py-4 backdrop-blur-sm">
            <x-button type="submit">{{ __('admin.save') }}</x-button>
        </div>
    </form>

</x-layouts.admin>
