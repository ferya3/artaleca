<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Application;
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
         * Three indexed queries returning a handful of rows each. Caching the
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

            /*
             * Seven, which is every use the plant currently lists — and a cap
             * rather than a coincidence: this row is a carousel on a phone and
             * a grid above it, and an eighth use should extend the page it has
             * of its own, not this one.
             */
            'applications' => Application::query()
                ->active()->ordered()
                ->take(7)->get(),

            'projects' => Project::query()
                ->active()->featured()->recentFirst()
                ->take(3)->get(),

            'posts' => Post::query()
                ->published()->latestFirst()
                ->take(3)->get(),
        ];

        seo()
            ->title(content('seo.home_title'))
            ->description(content('seo.home_description'))
            ->schema(Schema::organization())
            ->schema(Schema::website())
            ->schema(Schema::localBusiness());

        return view('pages.home', $data);
    }
}
