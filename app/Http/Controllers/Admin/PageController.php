<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class PageController extends ResourceController
{
    public function model(): string
    {
        return Page::class;
    }

    protected function routeName(): string
    {
        return 'pages';
    }

    protected function title(): string
    {
        return __('admin.pages');
    }

    protected function searchable(): array
    {
        return ['slug', 'title'];
    }

    protected function listColumns(): array
    {
        return [
            'title' => __('admin.pages'),
            'slug' => 'Slug',
            'position' => __('admin.position'),
            'is_active' => __('admin.status'),
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'title', 'label' => __('admin.fields.name'), 'translatable' => true,
                'rules' => ['required', 'string', 'max:180']],

            ['name' => 'slug', 'label' => 'Slug', 'width' => 'half',
                'hint' => __('admin.fields.slug_hint'),
                'rules' => fn (?Model $r) => ['nullable', 'string', 'max:180', 'alpha_dash',
                    Rule::unique('pages', 'slug')->ignore($r),
                    // A page slug is served by a catch-all, so it must not
                    // collide with a section that already owns that path.
                    Rule::notIn(['products', 'applications', 'projects', 'articles', 'news',
                        'downloads', 'contact', 'quote', 'about', 'faq', 'search', 'admin']),
                ]],

            ['name' => 'position', 'label' => __('admin.position'), 'type' => 'number', 'width' => 'half',
                'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],

            ['name' => 'lead', 'label' => __('admin.fields.summary'), 'type' => 'textarea', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:600']],

            ['name' => 'body', 'label' => __('admin.fields.description'), 'type' => 'textarea', 'translatable' => true,
                'rows' => 16, 'rules' => ['nullable', 'string', 'max:40000']],

            ['name' => 'hero_image', 'label' => __('admin.fields.image_path'), 'type' => 'image'],

            ['name' => 'meta_title', 'label' => __('admin.fields.meta_title'), 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:70']],
            ['name' => 'meta_description', 'label' => __('admin.fields.meta_description'), 'type' => 'textarea', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:170']],

            ['name' => 'show_in_footer', 'label' => __('common.sitemap'), 'type' => 'checkbox', 'width' => 'half'],
            ['name' => 'is_active', 'label' => __('admin.active'), 'type' => 'checkbox', 'width' => 'half'],
        ];
    }
}
