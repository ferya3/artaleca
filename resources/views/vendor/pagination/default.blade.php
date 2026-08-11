@if ($paginator->hasPages())
    {{-- rel=prev/next tells crawlers how the sequence fits together; the
         numbered links keep deep pages reachable without JavaScript. --}}
    <nav role="navigation" aria-label="{{ __('common.pagination.next') }}" class="mt-12 border-t border-hairline pt-6">
        <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
            <p class="tabular text-xs text-ink-500">
                {{ __('common.pagination.showing', [
                    'first' => $paginator->firstItem(),
                    'last' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ]) }}
            </p>

            <ul class="flex flex-wrap items-center gap-1">
                <li>
                    @if ($paginator->onFirstPage())
                        <span class="inline-flex h-9 items-center px-3 text-sm text-ink-300">{{ __('common.pagination.previous') }}</span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                           class="inline-flex h-9 items-center rounded-md border border-hairline px-3 text-sm text-ink-700 transition-colors hover:border-ink-400 hover:text-ink-950">
                            {{ __('common.pagination.previous') }}
                        </a>
                    @endif
                </li>

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li><span class="inline-flex h-9 w-9 items-center justify-center text-sm text-ink-400">{{ $element }}</span></li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <li>
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page"
                                          class="tabular inline-flex h-9 w-9 items-center justify-center rounded-md bg-ink-950 text-sm font-semibold text-surface">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}"
                                       class="tabular inline-flex h-9 w-9 items-center justify-center rounded-md border border-hairline text-sm text-ink-700 transition-colors hover:border-ink-400 hover:text-ink-950">{{ $page }}</a>
                                @endif
                            </li>
                        @endforeach
                    @endif
                @endforeach

                <li>
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                           class="inline-flex h-9 items-center rounded-md border border-hairline px-3 text-sm text-ink-700 transition-colors hover:border-ink-400 hover:text-ink-950">
                            {{ __('common.pagination.next') }}
                        </a>
                    @else
                        <span class="inline-flex h-9 items-center px-3 text-sm text-ink-300">{{ __('common.pagination.next') }}</span>
                    @endif
                </li>
            </ul>
        </div>
    </nav>
@endif
