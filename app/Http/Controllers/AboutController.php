<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Support\Schema;
use Illuminate\Contracts\View\View;

class AboutController extends Controller
{
    public function index(): View
    {
        seo()
            ->title(content('seo.about_title'))
            ->description(content('seo.about_description'))
            ->breadcrumbs($this->trail([
                ['label' => content('nav.about'), 'url' => null],
            ]))
            ->schema(Schema::organization());

        return view('pages.about.index', [
            'certificates' => Certificate::query()->active()->ordered()->get(),
        ]);
    }

    public function quality(): View
    {
        seo()
            ->title(content('seo.quality_title'))
            ->description(content('seo.quality_description'))
            ->breadcrumbs($this->trail([
                ['label' => content('nav.about'), 'url' => route('about')],
                ['label' => content('nav.quality'), 'url' => null],
            ]));

        return view('pages.about.quality', [
            'certificates' => Certificate::query()->active()->ordered()->get(),
        ]);
    }

    public function plant(): View
    {
        seo()
            ->title(content('seo.plant_title'))
            ->description(content('seo.plant_description'))
            ->breadcrumbs($this->trail([
                ['label' => content('nav.about'), 'url' => route('about')],
                ['label' => content('nav.plant'), 'url' => null],
            ]));

        return view('pages.about.plant');
    }
}
