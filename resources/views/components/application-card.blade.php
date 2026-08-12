@props(['application'])

<article {{ $attributes->merge(['class' => 'panel panel-interactive group relative flex flex-col p-6']) }}>
    <h3 class="text-lg font-bold text-ink-950">
        <a href="{{ route('applications.show', ['application' => $application]) }}"
           class="before:absolute before:inset-0 transition-colors group-hover:text-brand-600">
            {{ $application->name }}
        </a>
    </h3>

    @if (filled($application->summary))
        <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-ink-600">{{ $application->summary }}</p>
    @endif

    <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-brand-600" aria-hidden="true">
        {{ content('common.learn_more') }}
        <span class="inline-block transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5">&rarr;</span>
    </span>
</article>
