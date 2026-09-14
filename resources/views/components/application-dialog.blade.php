@props(['application'])

@php
    $id = 'use-'.$application->slug;
    $benefits = $application->bullets('benefits');
@endphp

{{--
    The use, read without leaving the page.

    A visitor scanning seven uses is comparing them, and a full page load per
    card turns comparing into a sequence of back buttons. The page still exists
    and still holds the recommended grades and the reference projects — this
    carries the part someone is actually deciding on: what the material does
    here, and the engineering case for it.

    A native <dialog>, so Escape, the top layer (it is never clipped by the
    carousel it sits inside) and focus handling are the browser's rather than
    three hundred lines of ours. Everything that closes it is markup: the form
    below submits with `method="dialog"`, which works with no JavaScript at all.
--}}
<dialog id="{{ $id }}" class="dialog-panel" data-dialog-panel aria-labelledby="{{ $id }}-title">
    <form method="dialog" class="dialog-panel__dismiss">
        <button type="submit" class="dialog-panel__close" aria-label="{{ content('common.close') }}">
            <svg viewBox="0 0 14 14" class="h-3.5 w-3.5" fill="none" aria-hidden="true">
                <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
        </button>
    </form>

    <div class="dialog-panel__body">
        <p class="eyebrow">{{ content('nav.applications') }}</p>

        <h2 id="{{ $id }}-title" class="mt-2 text-xl font-bold text-ink-950 md:text-2xl">
            {{ $application->name }}
        </h2>

        @if (filled($application->summary))
            <p class="mt-3 text-base leading-relaxed text-ink-600">{{ $application->summary }}</p>
        @endif

        <x-prose :text="$application->description" class="mt-6" />

        @if ($benefits !== [])
            <div class="panel-muted mt-8 p-5">
                <h3 class="text-base font-bold text-ink-950">{{ content('applications.benefits') }}</h3>

                <ul class="mt-4 space-y-3">
                    @foreach ($benefits as $benefit)
                        <li class="flex gap-3 text-sm leading-relaxed text-ink-700">
                            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500" aria-hidden="true"></span>
                            <span>{{ $benefit }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</dialog>
