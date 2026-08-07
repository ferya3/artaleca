<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GalleryImage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GalleryController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'album' => ['nullable', Rule::in(GalleryImage::ALBUMS)],
        ]);

        $images = GalleryImage::query()
            ->active()
            ->ofAlbum($validated['album'] ?? null)
            ->ordered()
            ->get();

        seo()
            ->title(__('gallery.title'))
            ->description(__('gallery.intro'))
            ->breadcrumbs($this->trail([
                ['label' => __('gallery.title'), 'url' => null],
            ]));

        return view('pages.gallery', [
            'images' => $images,
            'activeAlbum' => $validated['album'] ?? null,
        ]);
    }
}
