<x-layouts.admin :title="$label">

    <div class="flex flex-wrap items-baseline justify-between gap-3">
        <div>
            <a href="{{ route('admin.content.index') }}" class="text-xs text-ink-500 hover:text-ink-900">
                <span class="inline-block rtl:rotate-180" aria-hidden="true">&larr;</span>
                {{ __('admin.site_content') }}
            </a>
            <h1 class="mt-1 text-xl font-bold text-ink-950">{{ $label }}</h1>
        </div>
        <code class="ltr-run text-[0.625rem] text-ink-400">{{ $group }}</code>
    </div>

    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-ink-600">{{ __('admin.site_content_hint') }}</p>

    <form method="POST" action="{{ route('admin.content.update', $group) }}" class="mt-8 space-y-4">
        @csrf
        @method('PUT')

        @foreach ($keys as $key => $default)
            @php
                // `.` is the settings-key separator *and* Laravel's nested-input
                // separator, so field names use `|` and are mapped back on save.
                $field = str_replace('.', '|', $key);
                $stored = $values['content.'.$group.'.'.$key] ?? null;
                $long = \App\Support\SiteContent::isLong($default);
            @endphp

            <div class="panel p-4">
                <div class="mb-3 flex items-baseline justify-between gap-3">
                    <code class="ltr-run text-[0.6875rem] font-medium text-ink-700">{{ $key }}</code>

                    @if (is_array($stored) && array_filter($stored, 'filled') !== [])
                        <span class="shrink-0 rounded-md bg-brand-50 px-2 py-0.5 text-[0.625rem] font-medium text-brand-700">
                            {{ __('admin.content_overridden') }}
                        </span>
                    @endif
                </div>

                <div class="space-y-2.5">
                    @foreach ($locales as $code => $meta)
                        @php
                            $errorKey = $field.'.'.$code;
                            $shipped = \Illuminate\Support\Facades\Lang::get($group.'.'.$key, [], $code);
                            $shipped = is_string($shipped) ? $shipped : '';

                            /*
                             * Prefilled with what the page actually renders
                             * today, not left blank with the default behind it
                             * as a placeholder. That is what makes the rule
                             * simple enough to trust: the box holds the text,
                             * so emptying the box empties the text. With the
                             * default merely hinted, clearing a field put it
                             * straight back and looked like the save had failed.
                             */
                            $current = is_array($stored) && array_key_exists($code, $stored)
                                ? (string) $stored[$code]
                                : $shipped;

                            $value = old($errorKey, $current);
                        @endphp

                        <div class="flex items-start gap-2.5">
                            <span class="mt-2.5 w-7 shrink-0 text-[0.6875rem] font-semibold uppercase text-ink-400" dir="ltr">{{ $code }}</span>

                            @if ($long)
                                <textarea name="{{ $field }}[{{ $code }}]" rows="3"
                                          lang="{{ $code }}" dir="{{ $meta['dir'] }}"
                                          placeholder="{{ __('admin.content_empty_placeholder') }}"
                                          class="w-full rounded-md border border-ink-300 bg-white px-3 py-2.5 text-sm leading-relaxed text-ink-900 placeholder:text-ink-400 focus:border-ink-500 focus:outline-none focus:ring-4 focus:ring-brand-500/12 @error($errorKey) border-red-500 @enderror">{{ $value }}</textarea>
                            @else
                                <input type="text" name="{{ $field }}[{{ $code }}]" value="{{ $value }}"
                                       lang="{{ $code }}" dir="{{ $meta['dir'] }}"
                                       placeholder="{{ __('admin.content_empty_placeholder') }}"
                                       class="w-full rounded-md border border-ink-300 bg-white px-3 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 focus:border-ink-500 focus:outline-none focus:ring-4 focus:ring-brand-500/12 @error($errorKey) border-red-500 @enderror">
                            @endif
                        </div>

                        @error($errorKey)
                            <p class="ps-10 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    @endforeach
                </div>

                {{-- The only way back to the shipped wording once a field has
                     been saved, since the box no longer carries it as a hint. --}}
                <label class="mt-3 flex items-center gap-2 text-xs text-ink-500">
                    <input type="checkbox" name="reset[{{ $field }}]" value="1" class="h-3.5 w-3.5 border-ink-400 text-brand-600">
                    {{ __('admin.content_reset') }}
                </label>
            </div>
        @endforeach

        <div class="sticky bottom-0 flex items-center gap-3 border-t border-hairline bg-ink-50/95 py-4 backdrop-blur-sm">
            <x-button type="submit">{{ __('admin.save') }}</x-button>
            <p class="text-xs text-ink-500">{{ __('admin.content_clear_hint') }}</p>
        </div>
    </form>

</x-layouts.admin>
