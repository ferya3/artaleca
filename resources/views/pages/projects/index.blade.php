<x-layouts.app>

    <x-page-header
        :eyebrow="__('nav.projects')"
        :title="__('projects.title')"
        :lead="__('projects.intro')"
    />

    <section class="py-section">
        <div class="container-page">

            @if ($applications->isNotEmpty())
                <nav class="no-scrollbar mb-10 -mx-5 overflow-x-auto px-5 md:mx-0 md:px-0" aria-label="{{ __('product.filter_by_application') }}">
                    <ul class="flex w-max gap-2 md:w-auto md:flex-wrap">
                        <li>
                            <a href="{{ route('projects.index') }}"
                               @if (! $activeApplication) aria-current="page" @endif
                               class="inline-block whitespace-nowrap border px-4 py-2 text-sm transition-colors
                                      {{ ! $activeApplication ? 'border-ink-950 bg-ink-950 text-surface' : 'border-hairline text-ink-700 hover:border-ink-400' }}">
                                {{ __('projects.filter_all') }}
                            </a>
                        </li>
                        @foreach ($applications as $application)
                            @php $isActive = $activeApplication === $application->slug; @endphp
                            <li>
                                <a href="{{ route('projects.index', ['application' => $application->slug]) }}"
                                   @if ($isActive) aria-current="page" @endif
                                   class="inline-block whitespace-nowrap border px-4 py-2 text-sm transition-colors
                                          {{ $isActive ? 'border-ink-950 bg-ink-950 text-surface' : 'border-hairline text-ink-700 hover:border-ink-400' }}">
                                    {{ $application->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            @if ($projects->isEmpty())
                <x-empty-state :message="__('projects.empty')" :action="route('projects.index')" />
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($projects as $project)
                        <x-project-card :project="$project" :eager="$loop->index < 3" />
                    @endforeach
                </div>

                {{ $projects->links() }}
            @endif
        </div>
    </section>

    <x-cta-band />

</x-layouts.app>
