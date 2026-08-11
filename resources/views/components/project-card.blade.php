@props(['project', 'eager' => false])

<article {{ $attributes->merge(['class' => 'panel panel-interactive group relative flex flex-col overflow-hidden']) }}>
    <x-media
        :src="$project->cover_image"
        :seed="$project->slug"
        :alt="$project->title"
        :eager="$eager"
        ratio="3/2"
        sizes="(min-width: 1024px) 33vw, 100vw"
    />

    <div class="flex flex-1 flex-col p-5">
        <p class="eyebrow eyebrow-muted mb-2 flex flex-wrap items-center gap-x-2">
            @if ($project->year)
                <span class="ltr-run tabular">{{ $project->year }}</span>
            @endif
            @if (filled($project->location))
                <span class="text-ink-300" aria-hidden="true">·</span>
                <span>{{ $project->location }}</span>
            @endif
        </p>

        <h3 class="text-lg font-bold text-ink-950">
            <a href="{{ route('projects.show', ['project' => $project]) }}"
               class="before:absolute before:inset-0 transition-colors group-hover:text-brand-600">
                {{ $project->title }}
            </a>
        </h3>

        @if (filled($project->summary))
            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-ink-600">{{ $project->summary }}</p>
        @endif

        @if ($project->volume_m3)
            <p class="tabular mt-auto border-t border-hairline pt-4 text-sm text-ink-700">
                <span class="text-ink-500">{{ __('projects.volume') }}</span>
                <span class="ltr-run ms-2 font-semibold text-ink-950">{{ number_format($project->volume_m3) }}</span>
                <span class="text-xs text-ink-500">{{ __('projects.volume_unit') }}</span>
            </p>
        @endif
    </div>
</article>
