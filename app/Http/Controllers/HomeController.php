<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Certificate;
use App\Models\Post;
use App\Models\Product;
use App\Models\Project;
use App\Support\Schema;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        /*
         * Five indexed queries returning a handful of rows each. Caching the
         * model graph here would buy almost nothing and cost correctness — the
         * effective lever for a page this static is an HTTP-level cache in
         * front of the app, not an object cache behind it. Navigation and
         * settings, which are read on *every* request, are cached instead.
         */
        $data = [
            'products' => Product::query()
                ->active()->featured()->ordered()
                ->with('category')
                ->take(4)->get(),

            'applications' => Application::query()
                ->active()->ordered()
                ->take(6)->get(),

            'projects' => Project::query()
                ->active()->featured()->recentFirst()
                ->take(3)->get(),

            'posts' => Post::query()
                ->published()->latestFirst()
                ->take(3)->get(),

            'certificates' => Certificate::query()
                ->active()->ordered()
                ->take(6)->get(),
        ];

        seo()
            ->title(__('seo.home_title'))
            ->description(__('seo.home_description'))
            ->schema(Schema::organization())
            ->schema(Schema::website())
            ->schema(Schema::localBusiness());

        return view('pages.home', $data);
    }
}
