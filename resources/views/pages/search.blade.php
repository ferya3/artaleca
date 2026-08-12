<x-layouts.app>

    <x-page-header
        :eyebrow="content('nav.search')"
        :title="$term === '' ? content('search.title') : content('search.results_for', ['term' => $term])"
        :lead="$term !== '' && $total > 0 ? content('search.summary', ['count' => $total]) : null"
    >
        <form method="GET" action="{{ route('search') }}" class="mt-8 flex max-w-xl gap-2">
            <label for="search-q" class="sr-only">{{ content('nav.search') }}</label>
            <input
                id="search-q"
                type="search"
                name="q"
                value="{{ $term }}"
                placeholder="{{ content('search.placeholder') }}"
                autofocus
                class="w-full border border-ink-300 bg-surface px-4 py-3 text-sm text-ink-900 placeholder:text-ink-400 focus:border-ink-500 focus:outline-none focus:ring-4 focus:ring-brand-500/12"
            >
            <x-button type="submit" class="shrink-0">{{ content('nav.search') }}</x-button>
        </form>
    </x-page-header>

    <section class="py-section">
        <div class="container-page">
            @if ($term === '')
                <x-empty-state :message="content('search.prompt')" />
            @elseif ($total === 0)
                <x-empty-state :message="content('search.empty', ['term' => $term])" />
            @else
                <div class="space-y-14">
                    @foreach ($results as $group => $items)
                        <div>
                            <h2 class="eyebrow mb-6">{{ __('search.group_'.$group) }}</h2>

                            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($items as $item)
                                    @switch ($group)
                                        @case('products')  <x-product-card :product="$item" /> @break
                                        @case('applications') <x-application-card :application="$item" /> @break
                                        @case('projects')  <x-project-card :project="$item" /> @break
                                        @case('posts')     <x-post-card :post="$item" /> @break
                                    @endswitch
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

</x-layouts.app>
