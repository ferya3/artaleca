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
            ->title(__('seo.about_title'))
            ->description(__('seo.about_description'))
            ->breadcrumbs($this->trail([
                ['label' => __('nav.about'), 'url' => null],
            ]))
            ->schema(Schema::organization());

        return view('pages.about.index', [
            'certificates' => Certificate::query()->active()->ordered()->get(),
        ]);
    }

    public function quality(): View
    {
        seo()
            ->title(__('seo.quality_title'))
            ->description(__('seo.quality_description'))
            ->breadcrumbs($this->trail([
                ['label' => __('nav.about'), 'url' => route('about')],
                ['label' => __('nav.quality'), 'url' => null],
            ]));

        return view('pages.about.quality', [
            'certificates' => Certificate::query()->active()->ordered()->get(),
        ]);
    }

    public function plant(): View
    {
        seo()
            ->title(__('seo.plant_title'))
            ->description(__('seo.plant_description'))
            ->breadcrumbs($this->trail([
                ['label' => __('nav.about'), 'url' => route('about')],
                ['label' => __('nav.plant'), 'url' => null],
            ]));

        return view('pages.about.plant');
    }
}
