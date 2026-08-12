<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DownloadController;
use App\Http\Controllers\Admin\EnquiryController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SiteContentController;
use App\Http\Controllers\Admin\SiteImageController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
|
| Deliberately outside the /{locale} prefix: the back office is a single
| interface, not a translated public page. Authorisation is enforced by
| Policies registered in AuthServiceProvider — `can:` middleware here is the
| coarse gate, the Policy is the real decision.
|
*/

Route::prefix('admin')->name('admin.')->group(function () {

    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'show'])->name('login');
        Route::post('login', [AuthController::class, 'store'])
            ->middleware('throttle:login')
            ->name('login.attempt');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('logout', [AuthController::class, 'destroy'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        /*
         * All content resources share one CRUD implementation, so they also
         * share one route parameter name (`record`) — resolved to the right
         * model by the binding registered in AppServiceProvider.
         */
        $resources = [
            'product-categories' => ProductCategoryController::class,
            'products' => ProductController::class,
            'applications' => ApplicationController::class,
            'projects' => ProjectController::class,
            'posts' => PostController::class,
            'downloads' => DownloadController::class,
            'certificates' => CertificateController::class,
            'faqs' => FaqController::class,
            'pages' => PageController::class,
            'partners' => PartnerController::class,
            'gallery' => GalleryController::class,
            'redirects' => RedirectController::class,
        ];

        foreach ($resources as $uri => $controller) {
            Route::resource($uri, $controller)
                ->except('show')
                ->parameters([$uri => 'record']);
        }

        Route::get('enquiries', [EnquiryController::class, 'index'])->name('enquiries.index');
        Route::get('enquiries/{enquiry}', [EnquiryController::class, 'show'])->name('enquiries.show');
        Route::patch('enquiries/{enquiry}', [EnquiryController::class, 'update'])->name('enquiries.update');
        Route::delete('enquiries/{enquiry}', [EnquiryController::class, 'destroy'])->name('enquiries.destroy');

        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('content', [SiteContentController::class, 'index'])->name('content.index');
        Route::get('content/{group}', [SiteContentController::class, 'edit'])->name('content.edit');
        Route::put('content/{group}', [SiteContentController::class, 'update'])->name('content.update');

        Route::get('site-images', [SiteImageController::class, 'edit'])->name('site-images.edit');
        Route::put('site-images', [SiteImageController::class, 'update'])->name('site-images.update');

        Route::resource('users', UserController::class)->except('show');
    });
});
