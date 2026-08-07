@php
    /*
     * Renders whatever the controller passed to `seo()->breadcrumbs()`, so the
     * visible trail and the BreadcrumbList JSON-LD are generated from one
     * source and cannot drift apart.
     */
    $crumbs = seo()->getBreadcrumbs();
@endphp

@if (count($crumbs) > 1)
    <nav aria-label="{{ __('nav.breadcrumb') }}" {{ $attributes->merge(['class' => 'text-xs']) }}>
        <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-ink-500">
            @foreach ($crumbs as $index => $crumb)
                <li class="flex items-center gap-2">
                    @if ($index > 0)
                        <span class="text-ink-300 rtl:rotate-180" aria-hidden="true">&rsaquo;</span>
                    @endif

                    @if (! empty($crumb['url']) && ! $loop->last)
                        <a href="{{ $crumb['url'] }}" class="transition-colors hover:text-ink-900">{{ $crumb['label'] }}</a>
                    @else
                        <span class="text-ink-800" aria-current="page">{{ $crumb['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
