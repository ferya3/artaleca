<x-layouts.admin :title="__('admin.settings')">

    <h1 class="text-xl font-bold text-ink-950">{{ __('admin.settings') }}</h1>
    <p class="mt-2 text-sm text-ink-600">{{ __('admin.translation_hint') }}</p>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-8 space-y-5">
        @csrf
        @method('PUT')

        @foreach ($schema as $key => $definition)
            @php
                // `.` is both the settings-key separator and Laravel's nested
                // input separator, so the field name uses `|` and the
                // controller maps it back on save.
                $field = str_replace('.', '|', $key);
                $stored = $values[$key] ?? [];
            @endphp

            <div class="border border-hairline bg-white p-4">
                <div class="mb-3 flex items-baseline justify-between gap-3">
                    <p class="text-sm font-medium text-ink-800">{{ $definition['label'] }}</p>
                    <code class="ltr-run text-[0.625rem] text-ink-400">{{ $key }}</code>
                </div>

                <div class="space-y-2.5">
                    @foreach ($locales as $code => $meta)
                        @php
                            $inputName = $field.'['.$code.']';
                            $errorKey = $field.'.'.$code;
                            $value = old($errorKey, is_array($stored) ? ($stored[$code] ?? '') : '');
                        @endphp

                        <div class="flex items-start gap-2.5">
                            <span class="mt-2.5 w-7 shrink-0 text-[0.6875rem] font-semibold uppercase text-ink-400" dir="ltr">{{ $code }}</span>

                            @if ($definition['type'] === 'textarea')
                                <textarea name="{{ $inputName }}" rows="3" lang="{{ $code }}" dir="{{ $meta['dir'] }}"
                                          class="w-full border border-ink-300 px-3 py-2.5 text-sm focus:border-ink-900 focus:outline-none @error($errorKey) border-red-500 @enderror">{{ $value }}</textarea>
                            @else
                                <input type="text" name="{{ $inputName }}" value="{{ $value }}"
                                       lang="{{ $code }}" dir="{{ $meta['dir'] }}"
                                       class="w-full border border-ink-300 px-3 py-2.5 text-sm focus:border-ink-900 focus:outline-none @error($errorKey) border-red-500 @enderror">
                            @endif
                        </div>

                        @error($errorKey)
                            <p class="ps-10 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="sticky bottom-0 flex items-center gap-3 border-t border-hairline bg-ink-50/95 py-4 backdrop-blur-sm">
            <x-button type="submit">{{ __('admin.save') }}</x-button>
        </div>
    </form>

</x-layouts.admin>
