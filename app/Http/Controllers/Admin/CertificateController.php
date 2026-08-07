<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Certificate;

class CertificateController extends ResourceController
{
    public function model(): string
    {
        return Certificate::class;
    }

    protected function routeName(): string
    {
        return 'certificates';
    }

    protected function title(): string
    {
        return __('admin.certificates');
    }

    protected function searchable(): array
    {
        return ['title'];
    }

    protected function listColumns(): array
    {
        return [
            'title' => __('admin.certificates'),
            'issuer' => __('common.certificates'),
            'year' => __('projects.year'),
            'is_active' => __('admin.status'),
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'title', 'label' => __('admin.certificates'), 'translatable' => true,
                'rules' => ['required', 'string', 'max:180']],

            ['name' => 'issuer', 'label' => __('common.certificates'), 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:180']],

            ['name' => 'reference', 'label' => 'Reference no.', 'width' => 'half',
                'rules' => ['nullable', 'string', 'max:60']],

            ['name' => 'year', 'label' => __('projects.year'), 'type' => 'number', 'width' => 'half',
                'rules' => ['nullable', 'integer', 'min:1900', 'max:2200']],

            ['name' => 'image', 'label' => __('admin.fields.image_path'), 'rules' => ['nullable', 'string', 'max:255']],

            ['name' => 'position', 'label' => __('admin.position'), 'type' => 'number', 'width' => 'half',
                'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],

            ['name' => 'is_active', 'label' => __('admin.active'), 'type' => 'checkbox', 'width' => 'half'],
        ];
    }
}
