<x-layouts.app>

    <x-page-header
        :eyebrow="__('nav.applications')"
        :title="$application->name"
        :lead="$application->summary"
    />

    <section class="py-section">
        <div class="container-page grid gap-12 lg:grid-cols-12 lg:gap-16">

            <div class="min-w-0 lg:col-span-7">
                <x-media
                    :src="$application->image"
                    :seed="$application->slug"
                    :alt="$application->name"
                    eager
                    ratio="16/9"
                    sizes="(min-width: 1024px) 58vw, 100vw"
                    class="rounded-lg border border-hairline shadow-soft"
                />

                @if (filled($application->description))
                    <div class="prose-industrial mt-10">{!! nl2br(e($application->description)) !!}</div>
                @endif
            </div>

            @php $benefits = $application->benefitsForLocale(); @endphp

            @if ($benefits !== [])
                <aside class="min-w-0 lg:col-span-5">
                    <div class="panel-muted p-6 lg:sticky lg:top-28">
                        <h2 class="text-lg font-bold text-ink-950">{{ __('applications.benefits') }}</h2>
                        <ul class="mt-5 space-y-4">
                            @foreach ($benefits as $benefit)
                                <li class="flex gap-3 text-sm leading-relaxed text-ink-700">
                                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-clay-500" aria-hidden="true"></span>
                                    <span>{{ $benefit }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </aside>
            @endif
        </div>
    </section>

    @if ($application->products->isNotEmpty())
        <section class="border-y border-hairline bg-surface-muted py-section">
            <div class="container-page">
                <x-section-heading :title="__('applications.recommended_products')" :href="route('products.index')" />

                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($application->products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($application->projects->isNotEmpty())
        <section class="py-section">
            <div class="container-page">
                <x-section-heading :title="__('applications.reference_projects')" :href="route('projects.index')" />

                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach ($application->projects as $project)
                        <x-project-card :project="$project" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-cta-band />

</x-layouts.app>
