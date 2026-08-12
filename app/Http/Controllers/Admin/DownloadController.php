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
            'category' => content('product.filter_by_category'),
            'locale' => content('nav.language'),
            'download_count' => content('common.download'),
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

            ['name' => 'category', 'label' => content('product.filter_by_category'), 'type' => 'select', 'width' => 'half',
                'options' => fn () => collect(Download::CATEGORIES)
                    ->mapWithKeys(fn ($c) => [$c => __('downloads.categories.'.$c)])->all(),
                'rules' => ['required', Rule::in(Download::CATEGORIES)]],

            ['name' => 'description', 'label' => __('admin.fields.summary'), 'type' => 'textarea', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:600']],

            /*
             * Uploaded to the *private* documents disk, never to public/.
             * DownloadController streams it, so an inactive document stays
             * unreachable and downloads can be counted. Size and extension are
             * read from the upload rather than typed by the editor.
             */
            ['name' => 'file_path', 'label' => __('admin.fields.file'), 'type' => 'document',
                // Required when creating (the column is NOT NULL and a download
                // without a file is a 404), optional when editing so saving a
                // title change does not force a re-upload.
                'rules' => fn (?Model $r) => array_merge(
                    [$r?->exists ? 'nullable' : 'required'],
                    ['file', 'mimes:'.implode(',', config('site.uploads.document_mimes')),
                        'max:'.config('site.uploads.max_document_kb')],
                )],

            ['name' => 'locale', 'label' => content('nav.language'), 'type' => 'select', 'width' => 'half',
                'options' => fn () => collect(Locales::all())->map(fn ($m) => $m['native'])->all(),
                'hint' => 'Leave empty for a language-neutral document.',
                'rules' => ['nullable', Rule::in(Locales::codes())]],

            ['name' => 'position', 'label' => __('admin.position'), 'type' => 'number', 'width' => 'half',
                'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],

            ['name' => 'is_active', 'label' => __('admin.active'), 'type' => 'checkbox'],
        ];
    }
}
