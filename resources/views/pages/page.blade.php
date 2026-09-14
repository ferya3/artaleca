<x-layouts.app>

    <x-page-header :title="$page->title" :lead="$page->lead" />

    <section class="py-section">
        <div class="container-page">
            @if ($page->hero_image)
                <x-media
                    :src="$page->hero_image"
                    :seed="$page->slug"
                    :alt="$page->title"
                    eager
                    ratio="16/9"
                    sizes="(min-width: 1024px) 68rem, 100vw"
                    class="mb-12 rounded-lg border border-hairline shadow-soft"
                />
            @endif

            <x-prose :text="$page->body" class="mx-auto" />
        </div>
    </section>

    <x-cta-band />

</x-layouts.app>
