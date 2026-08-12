<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ProjectController extends ResourceController
{
    public function model(): string
    {
        return Project::class;
    }

    protected function routeName(): string
    {
        return 'projects';
    }

    protected function title(): string
    {
        return __('admin.projects');
    }

    protected function searchable(): array
    {
        return ['slug', 'title', 'client'];
    }

    protected function defaultSortColumn(): string
    {
        return 'year';
    }

    protected function defaultSortDirection(): string
    {
        return 'desc';
    }

    protected function listColumns(): array
    {
        return [
            'title' => content('nav.projects'),
            'client' => content('projects.client'),
            'year' => content('projects.year'),
            'is_active' => __('admin.status'),
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'title', 'label' => content('nav.projects'), 'translatable' => true,
                'rules' => ['required', 'string', 'max:180']],

            ['name' => 'slug', 'label' => 'Slug', 'width' => 'half',
                'rules' => fn (?Model $r) => ['nullable', 'string', 'max:180', 'alpha_dash',
                    Rule::unique('projects', 'slug')->ignore($r)]],

            ['name' => 'year', 'label' => content('projects.year'), 'type' => 'number', 'width' => 'half',
                'rules' => ['nullable', 'integer', 'min:1900', 'max:2200']],

            ['name' => 'client', 'label' => content('projects.client'), 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:180']],

            ['name' => 'location', 'label' => content('projects.location'), 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:180']],

            ['name' => 'country_code', 'label' => 'ISO country code', 'width' => 'half',
                'rules' => ['nullable', 'string', 'size:2', 'alpha']],

            ['name' => 'volume_m3', 'label' => content('projects.volume'), 'type' => 'number', 'width' => 'half',
                'rules' => ['nullable', 'integer', 'min:0']],

            ['name' => 'summary', 'label' => __('admin.fields.summary'), 'type' => 'textarea', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:600']],

            ['name' => 'body', 'label' => __('admin.fields.description'), 'type' => 'textarea', 'translatable' => true,
                'rows' => 10, 'rules' => ['nullable', 'string', 'max:10000']],

            ['name' => 'scope', 'label' => content('projects.scope'), 'type' => 'list',
                'hint' => 'One line per scope item.'],

            ['name' => 'cover_image', 'label' => __('admin.fields.cover_path'), 'type' => 'image'],

            ['name' => 'gallery', 'label' => __('admin.fields.gallery'), 'type' => 'gallery',
                'hint' => __('admin.fields.gallery_hint')],

            ['name' => 'meta_title', 'label' => __('admin.fields.meta_title'), 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:70']],
            ['name' => 'meta_description', 'label' => __('admin.fields.meta_description'), 'type' => 'textarea', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:170']],

            ['name' => 'is_featured', 'label' => __('admin.featured'), 'type' => 'checkbox', 'width' => 'half'],
            ['name' => 'is_active', 'label' => __('admin.active'), 'type' => 'checkbox', 'width' => 'half'],
        ];
    }
}
