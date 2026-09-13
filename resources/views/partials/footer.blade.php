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
         this footer used to run past a screen and a half.

         A flex row rather than the twelve-column grid it was laid out on. The
         grid gave the brand a third of the width for a block only as wide as a
         logo, so the links began 300px away from it with nothing in between and
         the mark sat marooned in the corner. Sized by content instead, each
         block takes what it needs and the address takes the rest. --}}
    <div class="container-page py-10 lg:flex lg:items-start lg:gap-14 lg:py-16">

        {{-- Centred on a phone, where it is the first thing in the footer and
             has the width to itself; back to a column at the start of the line
             on a desktop, where it heads the row. --}}
        <div class="flex flex-col items-center gap-5 lg:shrink-0 lg:items-start lg:gap-6">
            {{-- `on-dark`: this band is night-palette in both themes, so the
                 logo must always take the file made for a dark ground rather
                 than the one the current theme would pick. --}}
            <a href="{{ route('home') }}" class="text-white" aria-label="{{ config('site.company.brand') }}">
                <x-brand.logo size="xl" on-dark />
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

        <div class="mt-8 grid grid-cols-2 gap-x-6 gap-y-2.5 lg:mt-0 lg:shrink-0 lg:gap-x-12">
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
        <address class="no-justify mt-8 space-y-2 border-t border-night-line pt-6 text-sm not-italic leading-relaxed text-night-400 lg:mt-0 lg:flex-1 lg:border-0 lg:pt-0">
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
