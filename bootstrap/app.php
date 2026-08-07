<?php

use App\Http\Middleware\EnsureUserIsStaff;
use App\Http\Middleware\HandleRedirects;
use App\Http\Middleware\ResetScopedState;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            ResetScopedState::class,
        ]);

        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        /*
         * Global, not route middleware: a URL that matches no route never
         * enters the `web` group, so a redirect registered there would never
         * fire for exactly the 404s it exists to catch.
         */
        $middleware->append(HandleRedirects::class);

        $middleware->alias([
            'locale' => SetLocale::class,
            'admin' => EnsureUserIsStaff::class,
        ]);

        $middleware->trustProxies(at: '*');

        // The only guarded area is the back office, and its sign-in page is
        // not at the framework's default `login` route.
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
