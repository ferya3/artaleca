@props(['project'])

@php
    $id = 'project-'.$project->slug;
    $scope = $project->workScope();

    /* The same four facts the project page leads with, in the same order, and
       filtered the same way — a project with no client named should not show an
       empty row where the client goes. */
    $facts = array_filter([
        content('projects.client') => $project->client,
        content('projects.location') => $project->location,
        content('projects.year') => $project->year,
        content('projects.volume') => $project->volume_m3
            ? number_format($project->volume_m3).' '.content('projects.volume_unit')
            : null,
    ], 'filled');
@endphp

{{--
    The project, read without leaving the row.

    Same reasoning as the use panel next to it: a row of finished projects is
    something you scan, and a page load for each one turns scanning into
    navigation. What a visitor is weighing is here — where it was, how much went
    into it, and what the work involved. The page keeps the photographs.
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
        <p class="eyebrow">{{ content('nav.projects') }}</p>

        <h2 id="{{ $id }}-title" class="mt-2 text-xl font-bold text-ink-950 md:text-2xl">
            {{ $project->title }}
        </h2>

        @if (filled($project->summary))
            <p class="mt-3 text-base leading-relaxed text-ink-600">{{ $project->summary }}</p>
        @endif

        @if ($facts !== [])
            {{-- A description list rather than a table: four labelled values are
                 pairs, and a table would promise columns that are not there. --}}
            <dl class="mt-6 grid grid-cols-2 gap-x-6 gap-y-4 border-y border-hairline py-5">
                @foreach ($facts as $label => $value)
                    <div class="min-w-0">
                        <dt class="text-xs text-ink-500">{{ $label }}</dt>
                        <dd class="tabular mt-1 text-sm font-semibold text-ink-950">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif

        @if (filled($project->body))
            <div class="prose-industrial mt-6">{!! nl2br(e($project->body)) !!}</div>
        @endif

        @if ($scope !== [])
            <div class="panel-muted mt-8 p-5">
                <h3 class="text-base font-bold text-ink-950">{{ content('projects.scope') }}</h3>

                <ul class="mt-4 space-y-3">
                    @foreach ($scope as $item)
                        <li class="flex gap-3 text-sm leading-relaxed text-ink-700">
                            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500" aria-hidden="true"></span>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</dialog>
