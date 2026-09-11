@props(['application', 'eager' => false])

{{-- Built like the product card, and for the same reason: a use is a thing you
     recognise by sight before you read its name. The whole card is one link
     (stretched-link pattern) so the tap target on a phone is the full tile,
     while the accessible name stays just the title.

     `x-media` falls back to the deterministic granule field when no image has
     been uploaded, so a use with no photograph yet is a card with a placeholder
     rather than a card with a gap. --}}
<article {{ $attributes->merge(['class' => 'panel panel-interactive group relative flex flex-col overflow-hidden']) }}>
    <x-media
        :src="$application->image"
        :seed="$application->slug"
        :alt="$application->name"
        :eager="$eager"
        ratio="1/1"
        sizes="(min-width: 1280px) 300px, (min-width: 768px) 33vw, 100vw"
    />

    <div class="flex flex-1 flex-col p-5">
        <h3 class="text-lg font-bold text-ink-950">
            <a href="{{ route('applications.show', ['application' => $application]) }}"
               class="before:absolute before:inset-0 transition-colors group-hover:text-brand-600">
                {{ $application->name }}
            </a>
        </h3>

        @if (filled($application->summary))
            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-ink-600">{{ $application->summary }}</p>
        @endif

        {{-- `mt-auto`, so the link sits on the bottom edge whatever the summary
             does — which is what keeps a row of cards looking like a row. --}}
        <span class="mt-auto pt-5 inline-flex items-center gap-2 text-sm font-semibold text-brand-600" aria-hidden="true">
            {{ content('common.learn_more') }}
            <span class="inline-block transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5">&rarr;</span>
        </span>
    </div>
</article>
