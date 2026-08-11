@php
    use App\Support\Locales;
    use Illuminate\Support\Facades\URL;

    // Error pages render outside the locale-prefixed route group, so the URL
    // generator has no default locale to fall back on — supply one explicitly,
    // preferring whatever locale the failed URL was already asking for.
    $locale = Locales::supports(request()->segment(1)) ? request()->segment(1) : Locales::default();
    app()->setLocale($locale);
    URL::defaults(['locale' => $locale]);
    seo()->title(__('common.no_results'))->noindex();
@endphp

<x-layouts.app>
    <section class="py-24 md:py-32">
        <div class="container-page max-w-xl text-center">
            <p class="ltr-run tabular text-6xl font-bold text-brand-500">404</p>

            <h1 class="mt-6 text-2xl font-bold text-ink-950 md:text-3xl">
                {{ __('common.no_results') }}
            </h1>

            <p class="mt-4 text-sm leading-relaxed text-ink-600">
                {{ __('search.empty', ['term' => request()->path()]) }}
            </p>

            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <x-button :href="route('home')">{{ __('nav.home') }}</x-button>
                <x-button :href="route('products.index')" variant="outline">{{ __('nav.products') }}</x-button>
                <x-button :href="route('contact')" variant="ghost">{{ __('nav.contact') }}</x-button>
            </div>
        </div>
    </section>
</x-layouts.app>
