@props(['post', 'eager' => false])

<article {{ $attributes->merge(['class' => 'panel panel-interactive group relative flex flex-col overflow-hidden']) }}>
    <x-media
        :src="$post->cover_image"
        :seed="$post->slug"
        :alt="$post->title"
        :eager="$eager"
        ratio="16/9"
        sizes="(min-width: 1024px) 33vw, 100vw"
    />

    <div class="flex flex-1 flex-col p-5">
        <p class="eyebrow eyebrow-muted mb-2 flex flex-wrap items-center gap-x-2">
            <span>{{ __('articles.types.'.$post->type) }}</span>
            @if ($post->published_at)
                <span class="text-ink-300" aria-hidden="true">·</span>
                <time datetime="{{ $post->published_at->toDateString() }}" class="tabular">
                    {{ $post->published_at->isoFormat('D MMMM Y') }}
                </time>
            @endif
        </p>

        <h3 class="text-lg font-bold leading-snug text-ink-950">
            <a href="{{ route('articles.show', ['post' => $post]) }}"
               class="before:absolute before:inset-0 transition-colors group-hover:text-brand-600">
                {{ $post->title }}
            </a>
        </h3>

        @if (filled($post->excerpt))
            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-ink-600">{{ $post->excerpt }}</p>
        @endif

        @if ($post->reading_minutes)
            <p class="mt-auto pt-4 text-xs text-ink-500">
                {{ __('articles.reading_time', ['minutes' => $post->reading_minutes]) }}
            </p>
        @endif
    </div>
</article>
