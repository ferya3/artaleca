<x-layouts.app>

    <x-page-header
        :eyebrow="__('nav.downloads')"
        :title="__('downloads.title')"
        :lead="__('downloads.intro')"
    />

    <section class="py-section">
        <div class="container-page">

            <nav class="no-scrollbar mb-10 -mx-5 overflow-x-auto px-5 md:mx-0 md:px-0" aria-label="{{ __('downloads.title') }}">
                <ul class="flex w-max gap-2 md:w-auto md:flex-wrap">
                    <li>
                        <a href="{{ route('downloads.index') }}"
                           @if (! $activeCategory) aria-current="page" @endif
                           class="inline-block whitespace-nowrap border px-4 py-2 text-sm transition-colors
                                  {{ ! $activeCategory ? 'border-ink-950 bg-ink-950 text-white' : 'border-hairline text-ink-700 hover:border-ink-400' }}">
                            {{ __('downloads.all') }}
                        </a>
                    </li>
                    @foreach (\App\Models\Download::CATEGORIES as $category)
                        <li>
                            <a href="{{ route('downloads.index', ['category' => $category]) }}"
                               @if ($activeCategory === $category) aria-current="page" @endif
                               class="inline-block whitespace-nowrap border px-4 py-2 text-sm transition-colors
                                      {{ $activeCategory === $category ? 'border-ink-950 bg-ink-950 text-white' : 'border-hairline text-ink-700 hover:border-ink-400' }}">
                                {{ __('downloads.categories.'.$category) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            @if ($groups->isEmpty())
                <x-empty-state :message="__('downloads.empty')" :action="route('downloads.index')" />
            @else
                <div class="space-y-14">
                    @foreach ($groups as $category => $files)
                        <div>
                            <h2 class="text-lg font-bold text-ink-950">{{ __('downloads.categories.'.$category) }}</h2>

                            <ul class="mt-5 divide-y divide-hairline border-y border-hairline">
                                @foreach ($files as $file)
                                    <li>
                                        <a
                                            href="{{ route('downloads.file', ['download' => $file]) }}"
                                            class="group flex flex-wrap items-center gap-4 py-5 transition-colors hover:bg-surface-muted"
                                        >
                                            {{-- A file-type badge rather than a generic icon: the
                                                 extension is the useful information. --}}
                                            <span class="ltr-run flex h-11 w-11 shrink-0 items-center justify-center rounded-md border border-hairline bg-white text-[0.625rem] font-bold uppercase text-ink-500 group-hover:border-clay-400 group-hover:text-clay-600">
                                                {{ $file->file_extension ?: 'PDF' }}
                                            </span>

                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-semibold text-ink-950 group-hover:text-clay-600">{{ $file->title }}</span>
                                                @if (filled($file->description))
                                                    <span class="mt-1 block text-xs leading-relaxed text-ink-500">{{ $file->description }}</span>
                                                @endif
                                            </span>

                                            <span class="ltr-run tabular shrink-0 text-xs text-ink-400">{{ $file->humanSize() }}</span>

                                            <span class="shrink-0 text-sm font-semibold text-clay-600">
                                                {{ __('common.download') }}
                                                <span class="inline-block rtl:rotate-180" aria-hidden="true">&darr;</span>
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <x-cta-band />

</x-layouts.app>
