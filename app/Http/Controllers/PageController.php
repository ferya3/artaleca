<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function show(Page $page): View
    {
        abort_unless($page->is_active, 404);

        seo()
            ->title($page->meta_title ?? $page->title)
            ->description($page->meta_description ?? $page->lead)
            ->image($page->hero_image)
            ->breadcrumbs($this->trail([
                ['label' => (string) $page->title, 'url' => null],
            ]));

        return view('pages.page', compact('page'));
    }
}
