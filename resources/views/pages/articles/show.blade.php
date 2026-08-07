<x-layouts.app>

    <article>
        <div class="border-b border-hairline bg-surface-muted">
            <div class="container-page py-10 md:py-14">
                <x-breadcrumbs class="mb-6" />

                <p class="eyebrow mb-3 flex flex-wrap items-center gap-x-2">
                    <span>{{ __('articles.types.'.$post->type) }}</span>
                    @if ($post->published_at)
                        <span class="text-ink-300" aria-hidden="true">·</span>
                        <time datetime="{{ $post->published_at->toDateString() }}" class="tabular eyebrow-muted">
                            {{ $post->published_at->isoFormat('D MMMM Y') }}
                        </time>
                    @endif
                    @if ($post->reading_minutes)
                        <span class="text-ink-300" aria-hidden="true">·</span>
                        <span class="eyebrow-muted">{{ __('articles.reading_time', ['minutes' => $post->reading_minutes]) }}</span>
                    @endif
                </p>

                <h1 class="max-w-3xl text-3xl font-bold text-ink-950 md:text-4xl">{{ $post->title }}</h1>

                @if (filled($post->excerpt))
                    <p class="mt-5 max-w-2xl text-base leading-relaxed text-ink-600 md:text-lg">{{ $post->excerpt }}</p>
                @endif
            </div>
        </div>

        <div class="container-page py-section">
            <x-media
                :src="$post->cover_image"
                :seed="$post->slug"
                :alt="$post->title"
                eager
                ratio="16/9"
                sizes="(min-width: 1024px) 68rem, 100vw"
                class="border border-hairline"
            />

            @if (filled($post->body))
                <div class="prose-industrial mx-auto mt-12">{!! nl2br(e($post->body)) !!}</div>
            @endif

            <div class="mx-auto mt-12 max-w-[68ch] border-t border-hairline pt-6">
                <x-button :href="route('articles.index')" variant="ghost" size="sm">
                    {{ __('articles.back_to_list') }}
                </x-button>
            </div>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="border-t border-hairline bg-surface-muted py-section">
            <div class="container-page">
                <x-section-heading :title="__('common.related_articles')" :href="route('articles.index')" />
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach ($related as $item)
                        <x-post-card :post="$item" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-cta-band />

</x-layouts.app>
