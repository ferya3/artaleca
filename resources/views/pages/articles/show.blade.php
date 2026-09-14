<x-layouts.app>

    {{--
        ── A technical article ────────────────────────────────────────────
        One column, one measure, one start edge.

        What it was: the cover picture ran the full 1216px container and stood
        684px tall — a screen and a half of photograph before the first
        sentence — while the text sat in a 611px column centred underneath it.
        Four different edges down the page and nothing aligned to anything, so
        the eye had no line to follow. The body itself was `nl2br(e($body))`:
        one element holding twelve `<br>`s and not a single paragraph, which is
        why no spacing rule in the stylesheet had anything to apply to.

        What it is: one centred column of a single measure, from the
        breadcrumbs to the last paragraph. The heading, the picture and the body
        share that measure and that edge, and the picture is a figure inside the
        column rather than a banner above it. The body is real paragraphs now —
        see `App\Support\Prose` — which is what makes the spacing and the
        hierarchy possible at all.

        `.article-measure` is a class rather than a `max-w-*` utility because
        three wrappers have to agree on the number, and a number repeated three
        times is a number that drifts.
    --}}
    <article>
        <header class="border-b border-hairline bg-surface-muted">
            <div class="container-page py-10 md:py-14">
                <div class="article-measure mx-auto">
                    <x-breadcrumbs class="mb-6" />

                    {{-- Kind, date, reading time: the three things somebody
                         checks before deciding to read, on one line above the
                         title. --}}
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
                            <span class="eyebrow-muted">{{ content('articles.reading_time', ['minutes' => $post->reading_minutes]) }}</span>
                        @endif
                    </p>

                    <h1 class="text-3xl font-bold leading-tight text-ink-950 md:text-4xl">
                        {{ $post->title }}
                    </h1>

                    @if (filled($post->excerpt))
                        {{-- The standfirst: larger than the body and lighter
                             than the title, which is what marks it as the
                             summary rather than the first paragraph. --}}
                        <p class="mt-5 text-lg leading-relaxed text-ink-600 md:text-xl">
                            {{ $post->excerpt }}
                        </p>
                    @endif
                </div>
            </div>
        </header>

        <div class="container-page py-12 md:py-16">
            <div class="article-measure mx-auto">
                {{-- 1:1, the same frame as the card the reader just clicked.
                     It was 16:9 here, which made the article the one record on
                     the site still asking for two crops of one upload — and
                     the wide crop is the one that cuts through a subject shot
                     square. One frame now, so the photograph the editor chose
                     is the photograph that appears.

                     Inset rather than full-column: a square at the full 42rem
                     measure stands 672px tall, which is the screenful of
                     photograph this page was redesigned to get rid of. Capped
                     it is 416px, and the first sentence stays above the fold.

                     `alt=""` on purpose: the picture sits directly under a
                     heading that already says what the article is, and a second
                     reading of the same words is noise to anyone listening
                     rather than looking. --}}
                <figure class="article-figure">
                    <x-media
                        :src="$post->cover_image"
                        :seed="$post->slug"
                        alt=""
                        eager
                        ratio="1/1"
                        sizes="(min-width: 1024px) 26rem, 100vw"
                        class="rounded-lg border border-hairline shadow-soft"
                    />
                </figure>

                <x-prose :text="$post->body" long class="mt-10" />

                {{-- The two things to do at the end of an article, in the order
                     they are wanted: another article, or a price. The band below
                     carries the second one, so this is the first. --}}
                <nav class="mt-12 border-t border-hairline pt-6" aria-label="{{ content('nav.articles') }}">
                    <x-button :href="route('articles.index')" variant="ghost" size="sm">
                        {{ content('articles.back_to_list') }}
                    </x-button>
                </nav>
            </div>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="border-t border-hairline bg-surface-muted py-section">
            <div class="container-page">
                <x-section-heading :title="content('common.related_articles')" :href="route('articles.index')" />

                <ul class="card-grid mt-10">
                    @foreach ($related as $item)
                        <li class="flex">
                            <x-post-card :post="$item" class="w-full" />
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <x-cta-band />

</x-layouts.app>
