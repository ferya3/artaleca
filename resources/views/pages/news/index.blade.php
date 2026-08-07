<x-layouts.app>

    <x-page-header
        :eyebrow="__('nav.news')"
        :title="__('news.title')"
        :lead="__('news.intro')"
    />

    <section class="py-section">
        <div class="container-page">

            <nav class="no-scrollbar mb-10 -mx-5 overflow-x-auto px-5 md:mx-0 md:px-0" aria-label="{{ __('news.title') }}">
                <ul class="flex w-max gap-2 md:w-auto md:flex-wrap">
                    <li>
                        <a href="{{ route('news.index') }}"
                           @if (! $activeType) aria-current="page" @endif
                           class="inline-block whitespace-nowrap border px-4 py-2 text-sm transition-colors
                                  {{ ! $activeType ? 'border-ink-950 bg-ink-950 text-white' : 'border-hairline text-ink-700 hover:border-ink-400' }}">
                            {{ __('news.types.all') }}
                        </a>
                    </li>
                    @foreach (\App\Models\Post::TYPES as $type)
                        <li>
                            <a href="{{ route('news.index', ['type' => $type]) }}"
                               @if ($activeType === $type) aria-current="page" @endif
                               class="inline-block whitespace-nowrap border px-4 py-2 text-sm transition-colors
                                      {{ $activeType === $type ? 'border-ink-950 bg-ink-950 text-white' : 'border-hairline text-ink-700 hover:border-ink-400' }}">
                                {{ __('news.types.'.$type) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            @if ($posts->isEmpty())
                <x-empty-state :message="__('news.empty')" :action="route('news.index')" />
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($posts as $post)
                        <x-post-card :post="$post" :eager="$loop->index < 3" />
                    @endforeach
                </div>

                {{ $posts->links() }}
            @endif
        </div>
    </section>

    <x-cta-band />

</x-layouts.app>
