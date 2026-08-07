<x-layouts.app>

    <x-page-header
        :eyebrow="__('nav.gallery')"
        :title="__('gallery.title')"
        :lead="__('gallery.intro')"
    />

    <section class="py-section">
        <div class="container-page">

            <nav class="no-scrollbar mb-10 -mx-5 overflow-x-auto px-5 md:mx-0 md:px-0" aria-label="{{ __('gallery.title') }}">
                <ul class="flex w-max gap-2 md:w-auto md:flex-wrap">
                    <li>
                        <a href="{{ route('gallery') }}"
                           @if (! $activeAlbum) aria-current="page" @endif
                           class="inline-block whitespace-nowrap border px-4 py-2 text-sm transition-colors
                                  {{ ! $activeAlbum ? 'border-ink-950 bg-ink-950 text-white' : 'border-hairline text-ink-700 hover:border-ink-400' }}">
                            {{ __('gallery.all') }}
                        </a>
                    </li>
                    @foreach (\App\Models\GalleryImage::ALBUMS as $album)
                        <li>
                            <a href="{{ route('gallery', ['album' => $album]) }}"
                               @if ($activeAlbum === $album) aria-current="page" @endif
                               class="inline-block whitespace-nowrap border px-4 py-2 text-sm transition-colors
                                      {{ $activeAlbum === $album ? 'border-ink-950 bg-ink-950 text-white' : 'border-hairline text-ink-700 hover:border-ink-400' }}">
                                {{ __('gallery.albums.'.$album) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            @if ($images->isEmpty())
                <x-empty-state :message="__('gallery.empty')" :action="route('gallery')" />
            @else
                {{-- Plain grid, no lightbox: a lightbox is a JavaScript dependency
                     and a keyboard trap for a page whose whole job is showing
                     images that are already legible at this size. --}}
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($images as $image)
                        <li class="panel overflow-hidden">
                            <img
                                src="{{ $image->path }}"
                                alt="{{ $image->altText() }}"
                                loading="{{ $loop->index < 6 ? 'eager' : 'lazy' }}"
                                decoding="async"
                                class="aspect-[4/3] w-full object-cover"
                            >
                            @if (filled($image->title))
                                <p class="px-4 py-3 text-sm text-ink-700">{{ $image->title }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    <x-cta-band />

</x-layouts.app>
