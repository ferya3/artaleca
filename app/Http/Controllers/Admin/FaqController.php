<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Faq;
use Illuminate\Validation\Rule;

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
            'group' => __('product.filter_by_category'),
            'position' => __('admin.position'),
            'is_active' => __('admin.status'),
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'question', 'label' => __('admin.faqs'), 'translatable' => true,
                'rules' => ['required', 'string', 'max:300']],

            ['name' => 'answer', 'label' => __('product.description'), 'type' => 'textarea', 'translatable' => true,
                'rows' => 6, 'rules' => ['required', 'string', 'max:4000']],

            ['name' => 'group', 'label' => __('product.filter_by_category'), 'type' => 'select', 'width' => 'half',
                'options' => fn () => collect(Faq::GROUPS)->mapWithKeys(fn ($g) => [$g => __('faq.groups.'.$g)])->all(),
                'rules' => ['required', Rule::in(Faq::GROUPS)]],

            ['name' => 'position', 'label' => __('admin.position'), 'type' => 'number', 'width' => 'half',
                'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],

            ['name' => 'is_active', 'label' => __('admin.active'), 'type' => 'checkbox'],
        ];
    }
}
