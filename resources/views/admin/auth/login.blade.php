@php
    use App\Support\Locales;
    $dir = Locales::direction(Locales::default());
@endphp
<!DOCTYPE html>
{{-- Pinned to light, like the rest of the panel: it is a tool, and its
     surfaces are literal white rather than ramp steps that invert. --}}
<html lang="{{ Locales::default() }}" dir="{{ $dir }}" class="h-full" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('admin.sign_in') }} — {{ __('admin.title') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preload" href="/fonts/vazirmatn-variable.woff2" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-full items-center justify-center bg-ink-950 px-5 py-12">

<div class="hairline-grid fixed inset-0" aria-hidden="true"></div>

<main class="relative w-full max-w-sm">
    <div class="mb-8 flex justify-center text-white">
        <x-brand.logo />
    </div>

    <div class="bg-white p-7">
        <h1 class="text-lg font-bold text-ink-950">{{ __('admin.sign_in') }}</h1>

        @if ($errors->any())
            <div role="alert" class="mt-5 border-s-2 border-red-600 bg-red-50 px-4 py-3 text-sm text-red-900">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.attempt') }}" class="mt-6 space-y-5">
            @csrf

            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-sm font-medium text-ink-800">{{ __('admin.email') }}</label>
                <input id="email" name="email" type="email" required autofocus autocomplete="username"
                       value="{{ old('email') }}" dir="ltr"
                       class="w-full border border-ink-300 px-3.5 py-3 text-sm focus:border-ink-500 focus:outline-none focus:ring-4 focus:ring-brand-500/12">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-sm font-medium text-ink-800">{{ __('admin.password') }}</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" dir="ltr"
                       class="w-full border border-ink-300 px-3.5 py-3 text-sm focus:border-ink-500 focus:outline-none focus:ring-4 focus:ring-brand-500/12">
            </div>

            <label class="flex items-center gap-2.5 text-sm text-ink-600">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 border-ink-400 text-brand-600">
                {{ __('admin.remember') }}
            </label>

            <x-button type="submit" class="w-full">{{ __('admin.sign_in') }}</x-button>
        </form>
    </div>

    <p class="mt-6 text-center text-xs text-ink-500">
        <a href="{{ route('home', ['locale' => Locales::default()]) }}" class="hover:text-ink-300">
            {{ config('site.company.brand') }}
        </a>
    </p>
</main>

</body>
</html>
