<x-layouts.app>

    <x-page-header
        :eyebrow="__('nav.representatives')"
        :title="__('representatives.title')"
        :lead="__('representatives.intro')"
    />

    <section class="py-section">
        <div class="container-page">
            @if ($representatives->isEmpty())
                <x-empty-state :message="__('representatives.empty')" :action="route('contact')"
                               :action-label="__('nav.contact')" />
            @else
                <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($representatives as $representative)
                        <li class="panel flex min-w-0 flex-col p-6">
                            @if (filled($representative->logo))
                                <img src="{{ \App\Support\Image::thumb($representative->logo, 480) }}"
                                     alt="{{ $representative->name }}"
                                     loading="lazy" decoding="async"
                                     class="mb-5 h-12 w-auto max-w-[10rem] object-contain">
                            @endif

                            <h2 class="text-lg font-bold text-ink-950">{{ $representative->name }}</h2>

                            @if (filled($representative->summary))
                                <p class="mt-2 text-sm leading-relaxed text-ink-600">{{ $representative->summary }}</p>
                            @endif

                            @if (filled($representative->website))
                                {{-- An outbound link to a company we do not control:
                                     `noopener` so the target cannot reach back
                                     through `window.opener`. --}}
                                <a href="{{ $representative->website }}"
                                   target="_blank" rel="noopener noreferrer"
                                   class="ltr-run mt-auto pt-5 text-sm font-semibold text-clay-600 hover:underline">
                                    {{ __('representatives.website') }}
                                    <span class="inline-block rtl:rotate-180" aria-hidden="true">&rarr;</span>
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            <p class="mt-12 text-sm text-ink-600">
                {{ __('representatives.cta') }}
                <a href="{{ route('contact') }}" class="ms-1 font-medium text-clay-600 underline underline-offset-2">
                    {{ __('nav.contact') }}
                </a>
            </p>
        </div>
    </section>

    <x-cta-band />

</x-layouts.app>
