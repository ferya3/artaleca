<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\ContactMessage;
use App\Support\Locales;
use App\Support\Seo;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Seo::class, fn () => new Seo);
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureUrls();
        $this->configureRateLimiting();
        $this->configureValidation();
        $this->configureBlade();
        $this->configurePagination();
        $this->configureRouteBindings();
    }

    /**
     * Every admin resource route uses a single `{record}` parameter. Rather
     * than writing a typed edit/update/destroy triple per resource, the binder
     * asks the route's controller which model it manages.
     *
     * Resolution goes through each model's own route key — slug for content,
     * id for records that have none — so it matches what `route(..., $record)`
     * generates and the two can never disagree.
     */
    private function configureRouteBindings(): void
    {
        Route::bind('record', function (string $value, RoutingRoute $route) {
            $controller = $route->getController();

            abort_unless($controller instanceof ResourceController, 404);

            $model = $controller->model();

            return $model::query()
                ->where((new $model)->getRouteKeyName(), $value)
                ->firstOrFail();
        });
    }

    private function configurePagination(): void
    {
        Paginator::defaultView('vendor.pagination.default');
        Paginator::defaultSimpleView('vendor.pagination.default');
    }

    private function configureModels(): void
    {
        // Fail loudly on a typo'd attribute in a template instead of silently
        // rendering nothing, and refuse to lazy-load in a loop.
        Model::shouldBeStrict($this->app->isLocal());
        Model::automaticallyEagerLoadRelationships();
    }

    private function configureUrls(): void
    {
        // Behind a TLS-terminating proxy the app otherwise generates http://
        // links, which breaks canonical tags and triggers mixed content.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    private function configureRateLimiting(): void
    {
        /*
         * Public forms: tight per-IP ceiling. Enquiries are low-frequency by
         * nature, so a legitimate visitor never approaches this, while a bot
         * hammering the endpoint is stopped before it reaches validation.
         */
        RateLimiter::for('contact-form', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perDay(20)->by($request->ip()),
        ]);

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perMinute(5)->by((string) $request->input('email')),
        ]);
    }

    private function configureValidation(): void
    {
        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(12)->letters()->mixedCase()->numbers()->symbols()->uncompromised()
            : Password::min(8));
    }

    private function configureBlade(): void
    {
        /*
         * @nonce prints the request's CSP nonce attribute. Every inline script
         * or style block in the layout carries it; anything injected later has
         * no valid nonce and is refused by the browser.
         */
        Blade::directive('nonce', fn () => "<?php echo 'nonce=\"'.e(csp_nonce()).'\"'; ?>");

        Blade::if('rtl', fn () => Locales::isRtl());

        // Convenience for enquiry-count badges in the admin shell.
        Blade::directive('newEnquiries', fn () => '<?php echo '.ContactMessage::class.'::unhandled()->count(); ?>');
    }
}
