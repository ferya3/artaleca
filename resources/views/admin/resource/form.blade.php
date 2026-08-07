@php
    use App\Support\Locales;

    $isNew = ! $record->exists;
    $action = $isNew
        ? route('admin.'.$routeName.'.store')
        : route('admin.'.$routeName.'.update', $record);

    $inputClass = 'w-full border border-ink-300 bg-white px-3 py-2.5 text-sm text-ink-900
                   focus:border-ink-900 focus:outline-none focus:ring-2 focus:ring-clay-500/25';
@endphp

<x-layouts.admin :title="$title">

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.'.$routeName.'.index') }}" class="text-xs text-ink-500 hover:text-ink-900">
                <span class="inline-block rtl:rotate-180" aria-hidden="true">&larr;</span> {{ $title }}
            </a>
            <h1 class="mt-1 text-xl font-bold text-ink-950">
                {{ $isNew ? __('admin.create') : __('admin.edit') }}
            </h1>
        </div>

        @if (! $isNew)
            @can('delete', $record)
                <form method="POST" action="{{ route('admin.'.$routeName.'.destroy', $record) }}"
                      onsubmit="return confirm(@js(__('admin.confirm_delete')))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">
                        {{ __('admin.delete') }}
                    </button>
                </form>
            @endcan
        @endif
    </div>

    <form method="POST" action="{{ $action }}" class="mt-8">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        <div class="grid gap-5 md:grid-cols-2">
            @foreach ($fields as $field)
                @php
                    $name = $field['name'];
                    $type = $field['type'] ?? 'text';
                    $translatable = $field['translatable'] ?? false;
                    $wide = ($field['width'] ?? 'full') === 'full';
                    $options = isset($field['options']) && is_callable($field['options'])
                        ? ($field['options'])()
                        : ($field['options'] ?? []);
                @endphp

                <div class="{{ $wide ? 'md:col-span-2' : '' }} border border-hairline bg-white p-4">
                    <div class="mb-2 flex items-baseline justify-between gap-3">
                        <label class="text-sm font-medium text-ink-800" @unless($translatable) for="f-{{ $name }}" @endunless>
                            {{ $field['label'] ?? $name }}
                        </label>
                        @if ($translatable)
                            <span class="text-[0.625rem] uppercase tracking-wider text-ink-400">{{ __('admin.translations') }}</span>
                        @endif
                    </div>

                    @if (! empty($field['hint']))
                        {{-- dir="auto" so an English hint inside the Persian
                             panel is not reordered by the bidi algorithm — it
                             is what moves a trailing full stop to the front. --}}
                        <p class="mb-2.5 text-xs text-ink-500" dir="auto">{{ $field['hint'] }}</p>
                    @endif

                    @if ($translatable)
                        {{-- One input per locale, each tagged with its own lang
                             and dir so an Arabic field types right-to-left even
                             while the panel itself is in Persian or English. --}}
                        <div class="space-y-2.5">
                            @foreach ($locales as $code => $meta)
                                @php
                                    $inputName = $name.'['.$code.']';
                                    $value = old($name.'.'.$code, $record->getTranslations($name)[$code] ?? '');
                                    $key = $name.'.'.$code;
                                @endphp

                                <div class="flex items-start gap-2.5">
                                    <span class="mt-2.5 w-7 shrink-0 text-[0.6875rem] font-semibold uppercase text-ink-400"
                                          dir="ltr">{{ $code }}</span>

                                    @if ($type === 'textarea')
                                        <textarea name="{{ $inputName }}" rows="{{ $field['rows'] ?? 4 }}"
                                                  lang="{{ $code }}" dir="{{ $meta['dir'] }}"
                                                  class="{{ $inputClass }} @error($key) border-red-500 @enderror">{{ $value }}</textarea>
                                    @else
                                        <input type="text" name="{{ $inputName }}" value="{{ $value }}"
                                               lang="{{ $code }}" dir="{{ $meta['dir'] }}"
                                               class="{{ $inputClass }} @error($key) border-red-500 @enderror">
                                    @endif
                                </div>

                                @error($key)
                                    <p class="ps-10 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endforeach
                        </div>

                    @elseif ($type === 'checkbox')
                        <label class="flex items-center gap-2.5 text-sm text-ink-700">
                            <input id="f-{{ $name }}" type="checkbox" name="{{ $name }}" value="1"
                                   @checked(old($name, $record->{$name} ?? false))
                                   class="h-4 w-4 border-ink-400 text-clay-600">
                            {{ $field['label'] ?? $name }}
                        </label>

                    @elseif ($type === 'select')
                        <select id="f-{{ $name }}" name="{{ $name }}" class="{{ $inputClass }}">
                            <option value="">{{ __('form.select_placeholder') }}</option>
                            @foreach ($options as $value => $label)
                                <option value="{{ $value }}" @selected((string) old($name, $record->{$name} ?? '') === (string) $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                    @elseif ($type === 'list')
                        {{-- JSON lists are edited as one item per line; the
                             controller parses them back. Editors never see JSON. --}}
                        <textarea id="f-{{ $name }}" name="{{ $name }}" rows="{{ $field['rows'] ?? 5 }}"
                                  class="{{ $inputClass }} font-mono text-xs">{{ old($name, implode("\n", (array) ($record->{$name} ?? []))) }}</textarea>

                    @elseif ($type === 'pairs')
                        @php
                            $pairs = collect($record->{$name} ?? [])
                                ->map(fn ($row) => (data_get($row, 'label') ?? '').' | '.(data_get($row, 'value') ?? ''))
                                ->implode("\n");
                        @endphp
                        <textarea id="f-{{ $name }}" name="{{ $name }}" rows="{{ $field['rows'] ?? 5 }}"
                                  class="{{ $inputClass }} font-mono text-xs">{{ old($name, $pairs) }}</textarea>

                    @elseif ($type === 'datetime-local')
                        <input id="f-{{ $name }}" type="datetime-local" name="{{ $name }}" dir="ltr"
                               value="{{ old($name, $record->{$name}?->format('Y-m-d\TH:i')) }}"
                               class="{{ $inputClass }}">

                    @else
                        <input id="f-{{ $name }}" type="{{ $type }}" name="{{ $name }}"
                               value="{{ old($name, $record->{$name} ?? '') }}"
                               @isset($field['step']) step="{{ $field['step'] }}" @endisset
                               @if (in_array($type, ['number', 'url'], true) || $name === 'slug') dir="ltr" @endif
                               class="{{ $inputClass }} @error($name) border-red-500 @enderror">
                    @endif

                    @error($name)
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>

        <div class="sticky bottom-0 mt-6 flex items-center gap-3 border-t border-hairline bg-ink-50/95 py-4 backdrop-blur-sm">
            <x-button type="submit">{{ __('admin.save') }}</x-button>
            <a href="{{ route('admin.'.$routeName.'.index') }}" class="text-sm text-ink-500 hover:text-ink-900">
                {{ __('admin.cancel') }}
            </a>
        </div>
    </form>

</x-layouts.admin>
