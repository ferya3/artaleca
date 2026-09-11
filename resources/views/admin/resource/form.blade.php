@php
    use App\Support\Locales;

    $isNew = ! $record->exists;
    $action = $isNew
        ? route('admin.'.$routeName.'.store')
        : route('admin.'.$routeName.'.update', $record);

    $inputClass = 'w-full border border-ink-300 bg-white px-3 py-2.5 text-sm text-ink-900
                   focus:border-ink-500 focus:outline-none focus:ring-4 focus:ring-brand-500/12';
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

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="mt-8">
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

                <div class="{{ $wide ? 'md:col-span-2' : '' }} panel p-4">
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
                                    $key = $name.'.'.$code;

                                    /*
                                     * A translatable *list* is stored as a list
                                     * of per-locale maps, not a per-locale map
                                     * of strings — so its value comes from
                                     * `bullets()`, one entry per line, and
                                     * `getTranslations()` would hand back the
                                     * wrong shape entirely. It did, and the
                                     * form died on "Array to string
                                     * conversion" for every seeded record.
                                     */
                                    $value = $type === 'list'
                                        ? old($key, implode("\n", $record->bullets($name, $code)))
                                        : old($key, $record->getTranslations($name)[$code] ?? '');
                                @endphp

                                <div class="flex items-start gap-2.5">
                                    <span class="mt-2.5 w-7 shrink-0 text-[0.6875rem] font-semibold uppercase text-ink-400"
                                          dir="ltr">{{ $code }}</span>

                                    @if ($type === 'list')
                                        <textarea name="{{ $inputName }}" rows="{{ $field['rows'] ?? 4 }}"
                                                  lang="{{ $code }}" dir="{{ $meta['dir'] }}"
                                                  class="{{ $inputClass }} @error($key) border-red-500 @enderror">{{ $value }}</textarea>
                                    @elseif ($type === 'textarea')
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
                                   class="h-4 w-4 border-ink-400 text-brand-600">
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
                             controller parses them back. Editors never see JSON.

                             Read through `bullets()` rather than imploding the
                             raw column: a list of plain strings comes back
                             unchanged, and one that happens to hold per-locale
                             maps is flattened instead of throwing. --}}
                        <textarea id="f-{{ $name }}" name="{{ $name }}" rows="{{ $field['rows'] ?? 5 }}"
                                  class="{{ $inputClass }} font-mono text-xs">{{ old($name, implode("\n", $record->bullets($name))) }}</textarea>

                    @elseif ($type === 'pairs')
                        @php
                            $pairs = collect($record->{$name} ?? [])
                                ->map(fn ($row) => (data_get($row, 'label') ?? '').' | '.(data_get($row, 'value') ?? ''))
                                ->implode("\n");
                        @endphp
                        <textarea id="f-{{ $name }}" name="{{ $name }}" rows="{{ $field['rows'] ?? 5 }}"
                                  class="{{ $inputClass }} font-mono text-xs">{{ old($name, $pairs) }}</textarea>

                    @elseif ($type === 'image')
                        @php $current = $record->{$name} ?? null; @endphp

                        <div class="flex flex-wrap items-start gap-4">
                            @if ($current)
                                <img src="{{ \App\Support\Image::thumb($current, 480) }}" alt="" loading="lazy" decoding="async"
                                     class="h-24 w-32 shrink-0 rounded-lg border border-hairline object-cover">
                            @endif

                            <div class="min-w-0 flex-1 space-y-2">
                                <input id="f-{{ $name }}" type="file" name="{{ $name }}"
                                       accept="{{ collect(config('site.uploads.image_mimes'))->map(fn ($m) => '.'.$m)->implode(',') }}"
                                       class="w-full text-sm text-ink-600 file:me-3 file:border-0 file:bg-ink-950 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white">

                                @if ($current)
                                    {{-- Uploading nothing keeps the existing file; this is the
                                         only way to actually remove one. --}}
                                    <label class="flex items-center gap-2 text-xs text-ink-500">
                                        <input type="checkbox" name="{{ $name }}_clear" value="1" class="h-3.5 w-3.5 border-ink-400 text-brand-600">
                                        {{ __('admin.remove_file') }}
                                    </label>
                                @endif
                            </div>
                        </div>

                    @elseif ($type === 'gallery')
                        @php $images = array_values(array_filter((array) ($record->{$name} ?? []))); @endphp

                        @if ($images !== [])
                            <ul class="mb-3 grid grid-cols-3 gap-3 sm:grid-cols-5">
                                @foreach ($images as $image)
                                    <li class="rounded-md border border-hairline p-1.5">
                                        <img src="{{ \App\Support\Image::thumb($image, 480) }}" alt="" loading="lazy" decoding="async" class="aspect-[4/3] w-full object-cover">
                                        <label class="mt-1.5 flex items-center gap-1.5 text-[0.625rem] text-ink-500">
                                            <input type="checkbox" name="{{ $name }}_remove[]" value="{{ $image }}"
                                                   class="h-3 w-3 border-ink-400 text-brand-600">
                                            {{ __('admin.delete') }}
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <input id="f-{{ $name }}" type="file" name="{{ $name }}[]" multiple
                               accept="{{ collect(config('site.uploads.image_mimes'))->map(fn ($m) => '.'.$m)->implode(',') }}"
                               class="w-full text-sm text-ink-600 file:me-3 file:border-0 file:bg-ink-950 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white">

                    @elseif ($type === 'document')
                        @php $current = $record->{$name} ?? null; @endphp

                        @if ($current)
                            <p class="ltr-run mb-2 text-xs text-ink-500">{{ $current }}</p>
                        @endif

                        <input id="f-{{ $name }}" type="file" name="{{ $name }}"
                               accept="{{ collect(config('site.uploads.document_mimes'))->map(fn ($m) => '.'.$m)->implode(',') }}"
                               class="w-full text-sm text-ink-600 file:me-3 file:border-0 file:bg-ink-950 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white">

                    @elseif ($type === 'relation')
                        @php
                            $relation = $field['relation'] ?? $name;
                            $selected = $record->exists
                                ? $record->{$relation}()->pluck($record->{$relation}()->getRelated()->getKeyName())->all()
                                : [];
                        @endphp

                        <div class="max-h-56 space-y-1.5 overflow-y-auto rounded-md border border-hairline p-3">
                            @forelse ($options as $value => $label)
                                <label class="flex items-center gap-2.5 text-sm text-ink-700">
                                    <input type="checkbox" name="{{ $name }}[]" value="{{ $value }}"
                                           @checked(in_array($value, old($name, $selected) ?? [], false))
                                           class="h-4 w-4 border-ink-400 text-brand-600">
                                    {{ $label }}
                                </label>
                            @empty
                                <p class="text-xs text-ink-400">{{ __('admin.no_records') }}</p>
                            @endforelse
                        </div>

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
