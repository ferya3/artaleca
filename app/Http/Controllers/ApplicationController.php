<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Application;
use App\Support\Locales;
use App\Support\Schema;
use Illuminate\Contracts\View\View;

class ApplicationController extends Controller
{
    public function index(): View
    {
        $applications = Application::query()->active()->ordered()->get();

        seo()
            ->title(content('seo.applications_title'))
            ->description(content('seo.applications_description'))
            ->breadcrumbs($this->trail([
                ['label' => content('nav.applications'), 'url' => null],
            ]))
            ->schema(Schema::itemList($applications->map(fn (Application $a) => [
                'name' => (string) $a->name,
                'url' => route('applications.show', ['locale' => Locales::current(), 'application' => $a]),
            ])->all()));

        return view('pages.applications.index', compact('applications'));
    }

    public function show(Application $application): View
    {
        abort_unless($application->is_active, 404);

        $application->load([
            'products' => fn ($q) => $q->where('is_active', true)->orderBy('position')->with('category'),
            'projects' => fn ($q) => $q->where('is_active', true)->orderByDesc('year')->limit(3),
        ]);

        seo()
            ->title($application->meta_title ?? $application->name)
            ->description($application->meta_description ?? $application->summary)
            ->image($application->image)
            ->breadcrumbs($this->trail([
                ['label' => content('nav.applications'), 'url' => route('applications.index')],
                ['label' => (string) $application->name, 'url' => null],
            ]));

        return view('pages.applications.show', compact('application'));
    }
}
