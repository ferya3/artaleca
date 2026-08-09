<?php

declare(strict_types=1);

use App\Http\Controllers\AboutController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\RepresentativeController;
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
         * Flat product URLs — /products/leca-structure-4-10 — with categories
         * on their own branch so a category slug can never be mistaken for a
         * product one.
         */
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::get('products/category/{category:slug}', [ProductController::class, 'category'])->name('products.category');
        Route::get('products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
        Route::get('products/{product:slug}/datasheet', [ProductController::class, 'datasheet'])
            ->middleware('throttle:30,1')
            ->name('products.datasheet');

        Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::get('applications/{application:slug}', [ApplicationController::class, 'show'])->name('applications.show');

        Route::get('representatives', [RepresentativeController::class, 'index'])->name('representatives');

        Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');

        Route::get('articles', [PostController::class, 'index'])->name('articles.index');
        Route::get('articles/{post:slug}', [PostController::class, 'show'])->name('articles.show');

        Route::get('projects-gallery', [GalleryController::class, 'index'])->name('gallery');

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

        /*
         * Editor-created pages. Registered last so it can only ever match a
         * path no named route claimed — a page slug can never shadow a section.
         */
        Route::get('{page:slug}', [PageController::class, 'show'])
            ->where('page', '[a-z0-9-]+')
            ->name('pages.show');
    });
