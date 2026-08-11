@php
    use App\Support\Locales;

    $meta = Locales::meta();
@endphp
<!DOCTYPE html>
<html lang="{{ Locales::current() }}" dir="{{ $meta['dir'] }}" class="h-full">
<head>
    @include('partials.head')
</head>
<body class="flex min-h-full flex-col bg-surface antialiased">

    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:z-[60] focus:m-3 focus:bg-ink-950 focus:px-4 focus:py-2 focus:text-sm focus:text-surface">
        {{ __('nav.skip_to_content') }}
    </a>

    @include('partials.header')

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    @include('partials.footer')

</body>
</html>
