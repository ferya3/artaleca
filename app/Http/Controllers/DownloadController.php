<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Download;
use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'category' => ['nullable', 'in:'.implode(',', Download::CATEGORIES)],
        ]);

        $downloads = Download::query()
            ->active()
            ->availableIn(Locales::current())
            ->ofCategory($validated['category'] ?? null)
            ->ordered()
            ->get()
            ->groupBy('category');

        seo()
            ->title(content('seo.downloads_title'))
            ->description(content('seo.downloads_description'))
            ->breadcrumbs($this->trail([
                ['label' => content('nav.downloads'), 'url' => null],
            ]));

        return view('pages.downloads.index', [
            'groups' => $downloads,
            'activeCategory' => $validated['category'] ?? null,
        ]);
    }

    /**
     * Stream a document from private storage.
     *
     * Files are never served straight off `public/`: routing them through here
     * means an unpublished document is genuinely unreachable, downloads can be
     * counted, and the path in the database can never be turned into a
     * traversal by an editor typo.
     */
    public function download(Download $download): StreamedResponse
    {
        abort_unless($download->is_active, 404);

        $disk = Storage::disk('documents');
        $path = ltrim($download->file_path, '/');

        abort_if(str_contains($path, '..'), 404);
        abort_unless($disk->exists($path), 404);

        // Counter only; not worth a write-lock or a queued job at this volume.
        $download->incrementQuietly('download_count');

        $filename = sprintf(
            '%s-%s.%s',
            str($download->slug)->slug(),
            Locales::current(),
            $download->file_extension ?: pathinfo($path, PATHINFO_EXTENSION),
        );

        return $disk->download($path, $filename, [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
