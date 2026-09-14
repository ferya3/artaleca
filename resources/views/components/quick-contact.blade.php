@php
    use App\Support\QuickContact;

    $channels = QuickContact::channels();
@endphp

@if ($channels !== [])
    {{--
        Support, one tap from any page.

        A `<details>`, not a scripted drawer — the same decision the mobile menu
        made and for the same reason: it opens, closes and is keyboard-operable
        with no JavaScript at all, so the one control on the site that exists to
        rescue a stuck visitor cannot be broken by a failed script.

        `z-30` on purpose. The mobile menu is a fixed panel at `z-40`, and a
        floating button that sat on top of an open menu would be a button
        covering the navigation.

        It is hidden while printing: a floating "chat to us" panel stamped over
        a printed datasheet is nobody's idea of useful.
    --}}
    <details class="quick-contact print:hidden" data-quick-contact>
        <summary
            class="quick-contact__toggle"
            aria-label="{{ content('common.support.open') }}"
        >
            <svg viewBox="0 0 24 24" class="quick-contact__open h-6 w-6" fill="currentColor" aria-hidden="true">
                <path d="M12 2.6c-5.3 0-9.6 3.8-9.6 8.5 0 2.7 1.4 5.1 3.6 6.7v3.1c0 .6.7 1 1.2.6l2.8-1.9c.6.1 1.3.2 2 .2 5.3 0 9.6-3.8 9.6-8.5S17.3 2.6 12 2.6z"/>
            </svg>
            <svg viewBox="0 0 24 24" class="quick-contact__close h-5 w-5" fill="none" aria-hidden="true">
                <path d="M5 5l14 14M19 5L5 19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </summary>

        <div class="quick-contact__panel">
            <div class="quick-contact__head">
                <p class="quick-contact__title">{{ content('common.support.title') }}</p>
                <p class="quick-contact__note">{{ content('common.support.note') }}</p>
            </div>

            <ul>
                @foreach ($channels as $channel => $link)
                    <li>
                        <a
                            href="{{ $link['href'] }}"
                            @if ($link['external']) target="_blank" rel="noopener noreferrer" @endif
                            class="quick-contact__row"
                        >
                            <span class="quick-contact__mark" aria-hidden="true">
                                <x-messenger-icon :channel="$channel" />
                            </span>

                            <span class="quick-contact__body">
                                <span class="quick-contact__name">{{ content('common.support.channels.'.$channel) }}</span>

                                @if (filled($link['detail']))
                                    {{-- The number or the handle, so somebody
                                         can write it down or ring it from
                                         another phone rather than only tap.

                                         `ltr-run` but no `data-latin-digits`: a
                                         phone number reads in Persian digits on
                                         the Persian site, the same as the one in
                                         the mobile menu and the footer. --}}
                                    <span class="quick-contact__detail ltr-run">{{ $link['detail'] }}</span>
                                @endif
                            </span>

                            <svg viewBox="0 0 16 16" class="quick-contact__chevron" fill="none" aria-hidden="true">
                                <path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="1.6"
                                      stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- The slow, complete channel, under a rule and named as such. A
                 messenger answers a question; a quote needs the grade, the
                 volume and the destination, and this is where that is asked. --}}
            <a href="{{ route('quote') }}" class="quick-contact__quote">
                {{ content('common.support.quote') }}
            </a>
        </div>
    </details>
@endif
