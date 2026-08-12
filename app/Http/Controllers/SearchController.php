<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Post;
use App\Models\Product;
use App\Models\Project;
use App\Support\Search;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
        ]);

        $term = trim($validated['q'] ?? '');
        $results = collect();

        if (mb_strlen($term) >= 2) {
            $results = collect([
                'products' => Search::anyLike(
                    Product::query()->active()->with('category'), ['name', 'summary', 'sku', 'slug'], $term
                )->ordered()->take(8)->get(),

                'applications' => Search::anyLike(
                    Application::query()->active(), ['name', 'summary'], $term
                )->ordered()->take(5)->get(),

                'projects' => Search::anyLike(
                    Project::query()->active(), ['title', 'summary', 'client', 'location'], $term
                )->recentFirst()->take(5)->get(),

                'posts' => Search::anyLike(
                    Post::query()->published(), ['title', 'excerpt'], $term
                )->latestFirst()->take(5)->get(),
            ])->filter(fn ($group) => $group->isNotEmpty());
        }

        seo()
            ->title($term === '' ? content('search.title') : content('search.results_for', ['term' => $term]))
            ->description(content('search.description'))
            // Search result pages are classic thin/duplicate content.
            ->noindex();

        return view('pages.search', [
            'term' => $term,
            'results' => $results,
            'total' => $results->sum(fn ($group) => $group->count()),
        ]);
    }
}
