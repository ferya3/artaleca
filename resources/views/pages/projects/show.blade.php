<x-layouts.app>

    @php
        $facts = array_filter([
            __('projects.client') => $project->client,
            __('projects.location') => $project->location,
            __('projects.year') => $project->year,
            __('projects.volume') => $project->volume_m3
                ? number_format($project->volume_m3).' '.__('projects.volume_unit')
                : null,
        ], 'filled');

        $scope = $project->workScope();
    @endphp

    <x-page-header
        :eyebrow="__('nav.projects')"
        :title="$project->title"
        :lead="$project->summary"
    />

    <section class="py-section">
        <div class="container-page grid gap-12 lg:grid-cols-12 lg:gap-16">

            <div class="min-w-0 lg:col-span-8">
                <x-media
                    :src="$project->cover_image"
                    :seed="$project->slug"
                    :alt="$project->title"
                    eager
                    ratio="16/9"
                    sizes="(min-width: 1024px) 66vw, 100vw"
                    class="rounded-lg border border-hairline shadow-soft"
                />

                @if (filled($project->body))
                    <div class="prose-industrial mt-10">{!! nl2br(e($project->body)) !!}</div>
                @endif

                @if ($scope !== [])
                    <h2 class="mt-12 text-xl font-bold text-ink-950">{{ __('projects.scope') }}</h2>
                    <ul class="mt-5 space-y-3">
                        @foreach ($scope as $item)
                            <li class="flex gap-3 text-sm leading-relaxed text-ink-700">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-clay-500" aria-hidden="true"></span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if (count($project->galleryImages()) > 0)
                    <ul class="mt-12 grid grid-cols-2 gap-4 md:grid-cols-3">
                        @foreach ($project->galleryImages() as $index => $image)
                            <li>
                                <x-picture
                                    :src="$image"
                                    :alt="$project->title.' — '.($index + 1)"
                                    sizes="(min-width: 768px) 33vw, 50vw"
                                    class="aspect-[4/3] w-full rounded-lg border border-hairline object-cover"
                                />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <aside class="min-w-0 lg:col-span-4">
                <div class="lg:sticky lg:top-28">
                    @if ($facts !== [])
                        <dl class="divide-y divide-hairline overflow-hidden rounded-lg border border-hairline bg-surface-muted">
                            @foreach ($facts as $label => $value)
                                <div class="flex items-baseline justify-between gap-4 px-5 py-4">
                                    <dt class="text-xs uppercase tracking-wider text-ink-500">{{ $label }}</dt>
                                    <dd class="ltr-run tabular text-end text-sm font-semibold text-ink-950">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif

                    @if ($project->products->isNotEmpty())
                        <div class="mt-6 panel-muted p-5">
                            <h2 class="text-sm font-bold text-ink-950">{{ __('projects.products_used') }}</h2>
                            <ul class="mt-3 space-y-2">
                                @foreach ($project->products as $product)
                                    <li>
                                        <a href="{{ route('products.show', ['product' => $product]) }}"
                                           class="text-sm text-clay-600 hover:underline">{{ $product->name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <x-button :href="route('projects.index')" variant="ghost" size="sm" class="mt-6">
                        {{ __('projects.back_to_list') }}
                    </x-button>
                </div>
            </aside>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="border-t border-hairline py-section">
            <div class="container-page">
                <x-section-heading :title="__('common.related_projects')" :href="route('projects.index')" />
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach ($related as $item)
                        <x-project-card :project="$item" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-cta-band />

</x-layouts.app>
