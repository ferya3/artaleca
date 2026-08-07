<?php

declare(strict_types=1);

use App\Http\Controllers\AboutController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site
|--------------------------------------------------------------------------
|
| Every page lives under a mandatory /{locale} prefix. The prefix is the only
| thing that differs between language versions of a page — slugs are shared —
| so alternate/hreflang URLs are derived by swapping one route parameter.
|
*/

Route::get('/', function (Request $request) {
    return redirect()->route('home', [
        'locale' => Locales::negotiate($request->header('Accept-Language')),
    ], 302);
})->name('root');

// Search engines look for these at the domain root, never under a locale.
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::prefix('{locale}')
    ->middleware('locale')
    ->whereIn('locale', Locales::codes())
    ->group(function () {

        Route::get('/', [HomeController::class, 'index'])->name('home');

        Route::get('about', [AboutController::class, 'index'])->name('about');
        Route::get('about/quality', [AboutController::class, 'quality'])->name('about.quality');
        Route::get('about/plant', [AboutController::class, 'plant'])->name('about.plant');

        /*
         * Products are nested under their category so the URL carries the
         * hierarchy the breadcrumb and the schema.org trail already describe.
         */
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::get('products/{category:slug}', [ProductController::class, 'category'])->name('products.category');
        Route::get('products/{category:slug}/{product:slug}', [ProductController::class, 'show'])->name('products.show');

        Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::get('applications/{application:slug}', [ApplicationController::class, 'show'])->name('applications.show');

        Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');

        Route::get('news', [PostController::class, 'index'])->name('news.index');
        Route::get('news/{post:slug}', [PostController::class, 'show'])->name('news.show');

        Route::get('downloads', [DownloadController::class, 'index'])->name('downloads.index');
        Route::get('downloads/{download:slug}', [DownloadController::class, 'download'])
            ->middleware('throttle:30,1')
            ->name('downloads.file');

        Route::get('faq', [FaqController::class, 'index'])->name('faq');
        Route::get('search', [SearchController::class, 'index'])->name('search');

        Route::get('contact', [ContactController::class, 'index'])->name('contact');
        Route::post('contact', [ContactController::class, 'store'])
            ->middleware('throttle:contact-form')
            ->name('contact.store');

        Route::get('quote', [QuoteController::class, 'index'])->name('quote');
        Route::post('quote', [QuoteController::class, 'store'])
            ->middleware('throttle:contact-form')
            ->name('quote.store');

        Route::get('privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
        Route::get('terms', [LegalController::class, 'terms'])->name('legal.terms');
    });
