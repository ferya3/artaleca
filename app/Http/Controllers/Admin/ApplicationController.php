<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ApplicationController extends ResourceController
{
    public function model(): string
    {
        return Application::class;
    }

    protected function routeName(): string
    {
        return 'applications';
    }

    protected function title(): string
    {
        return __('admin.applications');
    }

    protected function searchable(): array
    {
        return ['slug', 'name'];
    }

    protected function listColumns(): array
    {
        return [
            'name' => __('nav.applications'),
            'slug' => 'Slug',
            'position' => __('admin.position'),
            'is_active' => __('admin.status'),
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => __('nav.applications'), 'translatable' => true,
                'rules' => ['required', 'string', 'max:180']],

            ['name' => 'slug', 'label' => 'Slug', 'width' => 'half',
                'rules' => fn (?Model $r) => ['nullable', 'string', 'max:180', 'alpha_dash',
                    Rule::unique('applications', 'slug')->ignore($r)]],

            ['name' => 'position', 'label' => __('admin.position'), 'type' => 'number', 'width' => 'half',
                'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],

            ['name' => 'summary', 'label' => __('admin.fields.summary'), 'type' => 'textarea', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:600']],

            ['name' => 'description', 'label' => __('admin.fields.description'), 'type' => 'textarea', 'translatable' => true,
                'rows' => 10, 'rules' => ['nullable', 'string', 'max:8000']],

            ['name' => 'benefits', 'label' => __('applications.benefits'), 'type' => 'list',
                'hint' => 'One benefit per line (single language).'],

            ['name' => 'image', 'label' => __('admin.fields.image_path'), 'type' => 'image'],

            ['name' => 'meta_title', 'label' => __('admin.fields.meta_title'), 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:70']],
            ['name' => 'meta_description', 'label' => __('admin.fields.meta_description'), 'type' => 'textarea', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:170']],

            ['name' => 'is_active', 'label' => __('admin.active'), 'type' => 'checkbox'],
        ];
    }
}
