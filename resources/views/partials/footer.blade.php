@php
    use App\Support\Contact;
    use App\Support\Navigation;

    // The company's main address and the works, from the same list the contact
    // page reads — so the footer can never fall behind an office that moved.
    $head = Contact::headOffice();
    $plant = Contact::plant();
@endphp

<footer class="bg-night-950 text-night-300">
    {{-- Two columns from the smallest screen up. Five stacked blocks put the
         legal line about a screen and a half below the last thing anyone came
         here to read; paired up, the whole footer is one thumb-scroll. The
         brand block spans both so the three link groups and the address fall
         into two even rows beneath it. --}}
    <div class="container-page grid grid-cols-2 gap-x-6 gap-y-8 py-10 lg:grid-cols-12 lg:gap-8 lg:py-20">

        <div class="col-span-2 lg:col-span-4">
            <a href="{{ route('home') }}" class="text-white" aria-label="{{ config('site.company.brand') }}">
                <x-brand.logo />
            </a>

            <p class="mt-4 max-w-xs text-sm leading-relaxed text-night-400 lg:mt-5">
                {{ content('seo.brand_tagline') }} — {{ content('common.figures.since') }} {{ config('site.figures.since') }}.
            </p>

            @if (filled($social = Contact::social()))
            <div class="mt-5 flex gap-3 lg:mt-6">
                @foreach ($social as $network => $href)
                    <a
                        href="{{ $href }}"
                        rel="noopener noreferrer me"
                        target="_blank"
                        class="flex h-9 w-9 items-center justify-center rounded-md border border-night-line text-night-400 transition-colors hover:border-brand-500 hover:text-white"
                        aria-label="{{ ucfirst($network) }}"
                    >
                        <span class="text-[0.6875rem] font-semibold uppercase" aria-hidden="true">{{ substr($network, 0, 2) }}</span>
                    </a>
                @endforeach
            </div>
            @endif
        </div>

        @foreach (Navigation::footer() as $heading => $links)
            <nav class="lg:col-span-2" aria-label="{{ $heading }}">
                <h2 class="eyebrow text-night-400!">{{ $heading }}</h2>
                <ul class="mt-3.5 space-y-2 lg:mt-4 lg:space-y-2.5">
                    @foreach ($links as $link)
                        <li>
                            <a href="{{ route($link['route']) }}"
                               class="text-sm text-night-300 transition-colors hover:text-white">{{ $link['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endforeach

        {{-- The main address and the works. Not every office: the footer sits
             under every page and a list of six would bury the navigation above
             it. The contact page is where they all are, and it is one link
             away in the column beside this one. --}}
        <div class="lg:col-span-2">
            <h2 class="eyebrow text-night-400!">{{ $head?->name ?? content('common.headquarters') }}</h2>
            <address class="mt-3.5 space-y-2.5 text-sm not-italic leading-relaxed text-night-400 lg:mt-4 lg:space-y-3">
                @if ($head)
                    <p>{{ $head->address }}</p>

                    @if (filled($head->phone))
                        <p>
                            <span class="text-night-400 lg:block">{{ content('common.phone') }}:</span>
                            <a class="ltr-run whitespace-nowrap text-night-200 hover:text-white" href="tel:{{ $head->telephone() }}">{{ $head->phone }}</a>
                        </p>
                    @endif
                @endif

                <p>
                    <span class="text-night-400 lg:block">{{ content('common.email') }}:</span>
                    <a class="ltr-run text-night-200 hover:text-white" href="mailto:{{ Contact::value('email') }}">{{ Contact::value('email') }}</a>
                </p>

                @if ($plant)
                    <p>
                        <span class="text-night-400 lg:block">{{ $plant->name }}:</span>
                        {{ $plant->address }}
                    </p>
                @endif
            </address>
        </div>
    </div>

    <div class="border-t border-night-line">
        <div class="container-page flex flex-col gap-3 py-5 text-xs text-night-400 lg:py-6 sm:flex-row sm:items-center sm:justify-between">
            <p>{{ content('common.copyright', ['year' => now()->year, 'brand' => config('site.company.legal_name')]) }}</p>

            <ul class="flex flex-wrap items-center gap-x-5 gap-y-2">
                <li><a class="transition-colors hover:text-night-200" href="{{ route('legal.privacy') }}">{{ content('common.privacy') }}</a></li>
                <li><a class="transition-colors hover:text-night-200" href="{{ route('legal.terms') }}">{{ content('common.terms') }}</a></li>
                <li><a class="transition-colors hover:text-night-200" href="{{ route('sitemap') }}">{{ content('common.sitemap') }}</a></li>
            </ul>
        </div>
    </div>
</footer>
