@props(['title' => null])

@php
    use App\Support\Locales;

    $user = auth()->user();

    // The panel follows the operator's own language preference, independent of
    // whichever locale the public site was last viewed in.
    $adminLocale = $user?->locale ?? Locales::default();
    $dir = Locales::direction($adminLocale);

    /*
     * The sidebar, grouped by the job rather than by the table.
     *
     * Two rules hold it together. A group heading names the area and never
     * repeats the first link inside it; and anything an editor goes looking for
     * by name gets a line of its own rather than being a section of a longer
     * page — the plant address used to be a stretch of one enormous settings
     * form, which is to say it could not be found at all.
     */
    $sections = [
        __('admin.groups.inbox') => [
            ['route' => 'admin.enquiries.index', 'label' => __('admin.enquiries'), 'badge' => \App\Models\ContactMessage::query()->unhandled()->count()],
        ],

        // What the plant sells.
        __('admin.groups.catalogue') => [
            ['route' => 'admin.products.index', 'label' => __('admin.products')],
            ['route' => 'admin.product-categories.index', 'label' => __('admin.product_categories')],
            ['route' => 'admin.applications.index', 'label' => __('admin.applications')],
        ],

        // The words on the pages.
        __('admin.groups.content') => [
            ['route' => 'admin.content.index', 'label' => __('admin.site_content')],
            ['route' => 'admin.faqs.index', 'label' => __('admin.faqs')],
            ['route' => 'admin.pages.index', 'label' => __('admin.pages')],
            ['route' => 'admin.projects.index', 'label' => __('admin.projects')],
            ['route' => 'admin.posts.index', 'label' => __('admin.posts')],
        ],

        // The files: pictures, documents, logos.
        __('admin.groups.library') => [
            ['route' => 'admin.site-images.edit', 'label' => __('admin.site_images')],
            ['route' => 'admin.gallery.index', 'label' => __('admin.gallery')],
            ['route' => 'admin.downloads.index', 'label' => __('admin.downloads')],
            ['route' => 'admin.certificates.index', 'label' => __('admin.certificates')],
            ['route' => 'admin.partners.index', 'label' => __('admin.partners')],
        ],
    ];

    if ($user?->isAdmin()) {
        // Facts about the company, as opposed to content about it. The two
        // most-wanted areas are linked directly; the rest are one click on.
        $sections[__('admin.groups.company')] = [
            ['route' => 'admin.offices.index', 'label' => __('admin.offices')],
            ['route' => 'admin.settings.edit', 'params' => 'contact', 'label' => __('admin.settings_groups.contact')],
            ['route' => 'admin.settings.edit', 'params' => 'figures', 'label' => __('admin.settings_groups.figures')],
            ['route' => 'admin.settings.index', 'label' => __('admin.settings_all')],
        ];

        $sections[__('admin.groups.system')] = [
            ['route' => 'admin.redirects.index', 'label' => __('admin.redirects')],
            ['route' => 'admin.users.index', 'label' => __('admin.users')],
        ];
    }
@endphp

<!DOCTYPE html>
{{-- `data-theme="light"` pins the panel, whatever the operator's system says.
     The public site's dark theme works by inverting the ink ramp; the admin is
     full of literal white surfaces that would not invert with it, and it is a
     tool rather than a page. --}}
<html lang="{{ $adminLocale }}" dir="{{ $dir }}" class="h-full" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- The back office must never be indexed, whatever robots.txt says. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' — ' : '' }}{{ __('admin.title') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preload" href="/fonts/vazirmatn-variable.woff2" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-ink-50 text-ink-900">

<div class="flex min-h-screen flex-col lg:flex-row">

    {{-- ── Sidebar ─────────────────────────────────────────────────────── --}}
    <aside class="border-b border-hairline bg-surface lg:w-64 lg:shrink-0 lg:border-b-0 lg:border-e">
        <div class="flex items-center justify-between px-5 py-4 lg:border-b lg:border-hairline">
            <a href="{{ route('admin.dashboard') }}" class="text-ink-900">
                <x-brand.logo />
            </a>

            <form method="POST" action="{{ route('admin.logout') }}" class="lg:hidden">
                @csrf
                <button type="submit" class="text-xs text-ink-500 hover:text-ink-900">{{ __('admin.sign_out') }}</button>
            </form>
        </div>

        <nav class="px-3 py-4 lg:sticky lg:top-0" aria-label="{{ __('admin.title') }}">
            <a href="{{ route('admin.dashboard') }}"
               @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif
               class="mb-4 block rounded-md px-3 py-2 text-sm font-medium transition-colors
                      {{ request()->routeIs('admin.dashboard') ? 'bg-ink-950 text-white' : 'text-ink-700 hover:bg-ink-50' }}">
                {{ __('admin.dashboard') }}
            </a>

            @foreach ($sections as $heading => $links)
                <div class="mb-4">
                    <p class="eyebrow eyebrow-muted px-3 pb-2">{{ $heading }}</p>
                    <ul class="space-y-0.5">
                        @foreach ($links as $link)
                            @php
                                $active = request()->routeIs(str_replace('.index', '.*', str_replace('.edit', '.*', $link['route'])));

                                /*
                                 * Two links can share a route name and differ only in
                                 * the segment after it — Settings has one line per area.
                                 * Highlighting by name alone would light all of them up,
                                 * so a link that carries a parameter has to match it too;
                                 * and the parameterless "all settings" link steps aside
                                 * whenever one of its siblings is the page being shown.
                                 */
                                if (isset($link['params'])) {
                                    $active = $active && request()->route('group') === $link['params'];
                                } elseif ($link['route'] === 'admin.settings.index') {
                                    $active = request()->routeIs('admin.settings.index');
                                }
                            @endphp
                            <li>
                                <a href="{{ route($link['route'], $link['params'] ?? []) }}"
                                   @if ($active) aria-current="page" @endif
                                   class="flex items-center justify-between rounded-md px-3 py-2 text-sm transition-colors
                                          {{ $active ? 'bg-brand-50 font-medium text-brand-700' : 'text-ink-600 hover:bg-ink-50' }}">
                                    <span>{{ $link['label'] }}</span>
                                    @if (! empty($link['badge']))
                                        <span class="tabular ltr-run rounded-sm bg-brand-600 px-1.5 py-0.5 text-[0.625rem] font-bold text-white">{{ $link['badge'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div class="mt-6 hidden border-t border-hairline pt-4 lg:block">
                <p class="px-3 text-xs text-ink-500">{{ $user?->name }}</p>
                <p class="px-3 text-[0.6875rem] text-ink-400">{{ $user?->role }}</p>

                <div class="mt-3 flex flex-col gap-1">
                    <a href="{{ route('home', ['locale' => $adminLocale]) }}" target="_blank" rel="noopener"
                       class="px-3 py-1.5 text-xs text-ink-600 hover:text-ink-900">↗ {{ config('site.company.brand') }}</a>

                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="w-full px-3 py-1.5 text-start text-xs text-ink-600 hover:text-ink-900">
                            {{ __('admin.sign_out') }}
                        </button>
                    </form>
                </div>
            </div>
        </nav>
    </aside>

    {{-- ── Content ─────────────────────────────────────────────────────── --}}
    <main class="min-w-0 flex-1">
        <div class="mx-auto max-w-6xl px-5 py-8 md:px-8 md:py-10">

            @if (session('status'))
                <div role="status" class="mb-6 rounded-md border-s-2 border-green-600 bg-green-50 px-4 py-3 text-sm text-green-900">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mb-6 rounded-md border-s-2 border-red-600 bg-red-50 px-4 py-3 text-sm text-red-900">
                    <p class="font-semibold">{{ __('form.has_errors') }}</p>
                    <ul class="mt-2 list-disc space-y-1 ps-4">
                        @foreach ($errors->unique() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </div>
    </main>
</div>

</body>
</html>
