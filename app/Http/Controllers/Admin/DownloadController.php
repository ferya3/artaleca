<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Download;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class DownloadController extends ResourceController
{
    public function model(): string
    {
        return Download::class;
    }

    protected function routeName(): string
    {
        return 'downloads';
    }

    protected function title(): string
    {
        return __('admin.downloads');
    }

    protected function searchable(): array
    {
        return ['slug', 'title'];
    }

    protected function listColumns(): array
    {
        return [
            'title' => __('admin.downloads'),
            'category' => __('product.filter_by_category'),
            'locale' => __('nav.language'),
            'download_count' => __('common.download'),
            'is_active' => __('admin.status'),
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'title', 'label' => __('admin.downloads'), 'translatable' => true,
                'rules' => ['required', 'string', 'max:180']],

            ['name' => 'slug', 'label' => 'Slug', 'width' => 'half',
                'rules' => fn (?Model $r) => ['nullable', 'string', 'max:180', 'alpha_dash',
                    Rule::unique('downloads', 'slug')->ignore($r)]],

            ['name' => 'category', 'label' => __('product.filter_by_category'), 'type' => 'select', 'width' => 'half',
                'options' => fn () => collect(Download::CATEGORIES)
                    ->mapWithKeys(fn ($c) => [$c => __('downloads.categories.'.$c)])->all(),
                'rules' => ['required', Rule::in(Download::CATEGORIES)]],

            ['name' => 'description', 'label' => __('admin.fields.summary'), 'type' => 'textarea', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:600']],

            /*
             * Path relative to the private `documents` disk, not a public URL.
             * DownloadController streams it, so an inactive document stays
             * unreachable and downloads can be counted.
             */
            ['name' => 'file_path', 'label' => 'File path (documents disk)',
                'hint' => 'e.g. catalogues/arta-leca-catalogue-fa.pdf',
                'rules' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._\/-]+$/', 'not_regex:/\.\./']],

            ['name' => 'file_extension', 'label' => 'Extension', 'width' => 'half',
                'rules' => ['nullable', 'string', 'max:10', 'alpha_num']],

            ['name' => 'file_size', 'label' => 'Size (bytes)', 'type' => 'number', 'width' => 'half',
                'rules' => ['nullable', 'integer', 'min:0']],

            ['name' => 'locale', 'label' => __('nav.language'), 'type' => 'select', 'width' => 'half',
                'options' => fn () => collect(Locales::all())->map(fn ($m) => $m['native'])->all(),
                'hint' => 'Leave empty for a language-neutral document.',
                'rules' => ['nullable', Rule::in(Locales::codes())]],

            ['name' => 'position', 'label' => __('admin.position'), 'type' => 'number', 'width' => 'half',
                'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],

            ['name' => 'is_active', 'label' => __('admin.active'), 'type' => 'checkbox'],
        ];
    }
}
