@php
    use App\Support\Contact;
    use App\Support\Navigation;

    // The company's main address and the works, from the same list the contact
    // page reads — so the footer can never fall behind an office that moved.
    $head = Contact::headOffice();
    $plant = Contact::plant();
    $social = Contact::social();
@endphp

<footer class="bg-night-950 text-night-300">
    {{-- Two columns of links, then the contact details one line each beneath
         them. Group headings are the accessible name of each <nav> and nothing
         more: drawn, a heading costs as much height as a link, and on a phone
         this footer used to run past a screen and a half. --}}
    <div class="container-page py-10 lg:grid lg:grid-cols-12 lg:items-start lg:gap-10 lg:py-16">

        <div class="flex items-center justify-between gap-4 lg:col-span-4 lg:flex-col lg:items-start lg:gap-6">
            <a href="{{ route('home') }}" class="text-white" aria-label="{{ config('site.company.brand') }}">
                <x-brand.logo />
            </a>

            @if (filled($social))
                <div class="flex gap-2.5">
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

        <div class="mt-8 grid grid-cols-2 gap-x-6 gap-y-2.5 lg:col-span-4 lg:mt-0">
            @foreach (Navigation::footer() as $heading => $links)
                <nav aria-label="{{ $heading }}">
                    <ul class="space-y-2.5">
                        @foreach ($links as $link)
                            <li>
                                <a href="{{ route($link['route']) }}"
                                   class="text-sm text-night-300 transition-colors hover:text-white">{{ $link['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endforeach
        </div>

        {{-- One line each. `no-justify` because the page justifies body text,
             and justifying a short address stretches it into a row of gaps. --}}
        <address class="no-justify mt-8 space-y-2 border-t border-night-line pt-6 text-sm not-italic leading-relaxed text-night-400 lg:col-span-4 lg:mt-0 lg:border-0 lg:pt-0">
            @if ($plant)
                <p><span class="text-night-500">{{ content('common.plant_address') }}:</span> {{ $plant->address }}</p>
            @endif

            @if ($head)
                <p><span class="text-night-500">{{ content('common.head_office_address') }}:</span> {{ $head->address }}</p>

                @if (filled($head->phone))
                    <p>
                        <span class="text-night-500">{{ content('common.phones') }}:</span>
                        <a class="ltr-run whitespace-nowrap text-night-200 hover:text-white" href="tel:{{ $head->telephone() }}">{{ $head->phone }}</a>
                    </p>
                @endif
            @endif

            <p>
                <span class="text-night-500">{{ content('common.email') }}:</span>
                <a class="ltr-run text-night-200 hover:text-white" href="mailto:{{ Contact::value('email') }}">{{ Contact::value('email') }}</a>
            </p>
        </address>
    </div>

    <div class="border-t border-night-line">
        <div class="container-page flex flex-col gap-3 py-5 text-xs text-night-400 sm:flex-row sm:items-center sm:justify-between lg:py-6">
            <p>{{ content('common.copyright', ['year' => now()->year, 'brand' => config('site.company.legal_name')]) }}</p>

            <ul class="flex flex-wrap items-center gap-x-5 gap-y-2">
                <li><a class="transition-colors hover:text-night-200" href="{{ route('legal.privacy') }}">{{ content('common.privacy') }}</a></li>
                <li><a class="transition-colors hover:text-night-200" href="{{ route('legal.terms') }}">{{ content('common.terms') }}</a></li>
                <li><a class="transition-colors hover:text-night-200" href="{{ route('sitemap') }}">{{ content('common.sitemap') }}</a></li>
            </ul>
        </div>
    </div>
</footer>
