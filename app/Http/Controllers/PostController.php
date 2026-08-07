<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'type' => ['nullable', 'in:'.implode(',', Post::TYPES)],
        ]);

        $posts = Post::query()
            ->published()
            ->ofType($validated['type'] ?? null)
            ->latestFirst()
            ->paginate(9)
            ->withQueryString();

        seo()
            ->title(__('seo.news_title'))
            ->description(__('seo.news_description'))
            ->breadcrumbs($this->trail([
                ['label' => __('nav.news'), 'url' => null],
            ]))
            ->noindex($posts->currentPage() > 1);

        return view('pages.news.index', [
            'posts' => $posts,
            'activeType' => $validated['type'] ?? null,
        ]);
    }

    public function show(Post $post): View
    {
        abort_unless($post->isPublished(), 404);

        $related = Post::query()
            ->published()
            ->where('type', $post->type)
            ->whereKeyNot($post->getKey())
            ->latestFirst()
            ->take(3)
            ->get();

        seo()
            ->title($post->meta_title ?? $post->title)
            ->description($post->meta_description ?? $post->excerpt)
            ->image($post->cover_image)
            ->type('article')
            ->breadcrumbs($this->trail([
                ['label' => __('nav.news'), 'url' => route('news.index')],
                ['label' => (string) $post->title, 'url' => null],
            ]))
            ->schema(Schema::article($post));

        return view('pages.news.show', compact('post', 'related'));
    }
}
