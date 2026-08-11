<x-layouts.admin :title="__('admin.site_images')">

    <h1 class="text-xl font-bold text-ink-950">{{ __('admin.site_images') }}</h1>
    <p class="mt-2 max-w-2xl text-sm text-ink-600">{{ __('admin.site_images_intro') }}</p>

    <form method="POST" action="{{ route('admin.site-images.update') }}" enctype="multipart/form-data" class="mt-8 space-y-10">
        @csrf
        @method('PUT')

        @foreach ($groups as $group => $definitions)
            <section>
                <h2 class="eyebrow mb-4">{{ __('admin.site_image_groups.'.$group) }}</h2>

                <div class="space-y-4">
                    @foreach ($definitions as $key => $definition)
                        @php
                            // `.` is the settings-key separator *and* Laravel's nested-input
                            // separator, so field names use `|` and are mapped back on save.
                            $field = str_replace('.', '|', $key);
                            $stored = $values[$key] ?? null;
                        @endphp

                        <div class="panel p-5">
                            <div class="mb-3 flex items-baseline justify-between gap-3">
                                <p class="text-sm font-medium text-ink-800">{{ $definition['label'] }}</p>
                                <code class="ltr-run text-[0.625rem] text-ink-400">{{ $key }}</code>
                            </div>

                            @if (! empty($definition['hint']))
                                <p class="mb-4 text-xs leading-relaxed text-ink-500" dir="auto">{{ $definition['hint'] }}</p>
                            @endif

                            {{-- One row per language, one column per theme. Both
                                 axes fall back rather than fail, and the cell
                                 says which of the two it is doing — an inherited
                                 picture is shown greyed with the reason under
                                 it, so the state that would otherwise produce a
                                 wrong image is visible on the screen where it is
                                 fixed. --}}
                            <div class="space-y-5">
                                @foreach ($locales as $code => $meta)
                                    <div>
                                        <p class="mb-2 text-[0.6875rem] font-semibold uppercase tracking-wider text-ink-400" dir="ltr">
                                            {{ $code }} <span class="font-normal normal-case tracking-normal text-ink-500">— {{ $meta['native'] }}</span>
                                        </p>

                                        <div class="grid gap-3 sm:grid-cols-2">
                                            @foreach ($themes as $theme)
                                                @php
                                                    $file = fn ($value) => is_string($value) && $value !== '' ? $value : null;

                                                    // What this slot holds, and — when it holds nothing —
                                                    // what the page will show instead and why.
                                                    $own = $file(data_get($stored, "$code.$theme"));
                                                    $shown = $own;
                                                    $inheritedFrom = null;

                                                    if (! $own) {
                                                        $fromDay = __('admin.image_from_day');
                                                        $fromDefault = __('admin.image_from_locale', [
                                                            'locale' => $locales[$defaultLocale]['native'] ?? $defaultLocale,
                                                        ]);

                                                        // Ordered the way the site resolves it: this language's
                                                        // day image, then anything stored before images had
                                                        // themes or languages, then the default language's.
                                                        $candidates = [
                                                            [$theme === 'dark' ? $file(data_get($stored, "$code.light")) : null, $fromDay],
                                                            [$file(is_string($stored) ? $stored : data_get($stored, $code)), $fromDay],
                                                            [$file(data_get($stored, "$defaultLocale.$theme")), $fromDefault],
                                                            [$file(data_get($stored, "$defaultLocale.light")), $fromDefault],
                                                        ];

                                                        foreach ($candidates as [$candidate, $reason]) {
                                                            if ($candidate) {
                                                                $shown = $candidate;
                                                                $inheritedFrom = $reason;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                @endphp

                                                <div class="rounded-lg border border-hairline p-3 {{ $theme === 'dark' ? 'bg-ink-50' : '' }}">
                                                    <p class="mb-2 flex items-center gap-1.5 text-xs font-medium text-ink-700">
                                                        <span aria-hidden="true">{{ $theme === 'dark' ? '🌙' : '☀️' }}</span>
                                                        {{ __('admin.image_theme_'.$theme) }}
                                                    </p>

                                                    @if (is_string($shown) && filled($shown))
                                                        <img src="{{ \App\Support\Image::thumb($shown, 480) }}" alt="" loading="lazy" decoding="async"
                                                             class="mb-2 h-20 w-full rounded-md border border-hairline object-cover {{ $inheritedFrom ? 'opacity-40' : '' }}">
                                                    @endif

                                                    <input type="file" name="{{ $field }}[{{ $code }}][{{ $theme }}]"
                                                           accept="{{ collect(config('site.uploads.image_mimes'))->map(fn ($m) => '.'.$m)->implode(',') }}"
                                                           class="w-full min-w-0 text-xs text-ink-600 file:me-2 file:border-0 file:bg-ink-950 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">

                                                    @error($field.'.'.$code.'.'.$theme)
                                                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                                    @enderror

                                                    @if ($inheritedFrom)
                                                        <p class="mt-1.5 text-[0.6875rem] leading-relaxed text-ink-500">{{ $inheritedFrom }}</p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="sticky bottom-0 -mx-1 border-t border-hairline bg-surface/95 px-1 py-4">
            <button type="submit" class="rounded-md bg-ink-950 px-6 py-3 text-sm font-semibold text-white">
                {{ __('admin.save') }}
            </button>
        </div>
    </form>

</x-layouts.admin>
