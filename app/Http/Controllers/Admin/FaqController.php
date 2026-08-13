<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Faq;

class FaqController extends ResourceController
{
    public function model(): string
    {
        return Faq::class;
    }

    protected function routeName(): string
    {
        return 'faqs';
    }

    protected function title(): string
    {
        return __('admin.faqs');
    }

    protected function searchable(): array
    {
        return ['question'];
    }

    protected function listColumns(): array
    {
        return [
            'question' => __('admin.faqs'),
            'position' => __('admin.position'),
            'is_active' => __('admin.status'),
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'question', 'label' => __('admin.faqs'), 'translatable' => true,
                'rules' => ['required', 'string', 'max:300']],

            ['name' => 'answer', 'label' => __('admin.fields.description'), 'type' => 'textarea', 'translatable' => true,
                'rows' => 6, 'rules' => ['required', 'string', 'max:4000']],

            ['name' => 'position', 'label' => __('admin.position'), 'type' => 'number', 'width' => 'half',
                'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],

            ['name' => 'is_active', 'label' => __('admin.active'), 'type' => 'checkbox'],
        ];
    }
}
