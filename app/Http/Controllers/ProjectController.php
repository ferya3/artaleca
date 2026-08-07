<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Project;
use App\Support\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'application' => ['nullable', 'string', 'max:80'],
        ]);

        $projects = Project::query()
            ->active()
            ->recentFirst()
            ->when(
                filled($validated['application'] ?? null),
                fn ($q) => $q->whereHas('applications', fn ($a) => $a->where('slug', $validated['application']))
            )
            ->paginate(9)
            ->withQueryString();

        seo()
            ->title(__('seo.projects_title'))
            ->description(__('seo.projects_description'))
            ->breadcrumbs($this->trail([
                ['label' => __('nav.projects'), 'url' => null],
            ]))
            ->noindex($projects->currentPage() > 1);

        return view('pages.projects.index', [
            'projects' => $projects,
            'applications' => Application::query()->active()->ordered()->get(),
            'activeApplication' => $validated['application'] ?? null,
        ]);
    }

    public function show(Project $project): View
    {
        abort_unless($project->is_active, 404);

        $project->load([
            'products' => fn ($q) => $q->where('is_active', true)->with('category'),
            'applications' => fn ($q) => $q->where('is_active', true),
        ]);

        $related = Project::query()
            ->active()
            ->whereKeyNot($project->getKey())
            ->recentFirst()
            ->take(3)
            ->get();

        seo()
            ->title($project->meta_title ?? $project->title)
            ->description($project->meta_description ?? $project->summary)
            ->image($project->cover_image)
            ->type('article')
            ->breadcrumbs($this->trail([
                ['label' => __('nav.projects'), 'url' => route('projects.index')],
                ['label' => (string) $project->title, 'url' => null],
            ]))
            ->schema(Schema::project($project));

        return view('pages.projects.show', compact('project', 'related'));
    }
}
