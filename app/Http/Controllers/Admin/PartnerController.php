<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class PartnerController extends ResourceController
{
    public function model(): string
    {
        return Partner::class;
    }

    protected function routeName(): string
    {
        return 'partners';
    }

    protected function title(): string
    {
        return __('admin.partners');
    }

    protected function searchable(): array
    {
        return ['slug', 'name'];
    }

    protected function listColumns(): array
    {
        return [
            'name' => __('admin.fields.name'),
            'kind' => content('product.filter_by_category'),
            'position' => __('admin.position'),
            'is_active' => __('admin.status'),
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => __('admin.fields.name'), 'translatable' => true,
                'rules' => ['required', 'string', 'max:180']],

            ['name' => 'slug', 'label' => 'Slug', 'width' => 'half',
                'hint' => __('admin.fields.slug_hint'),
                'rules' => fn (?Model $r) => ['nullable', 'string', 'max:180', 'alpha_dash',
                    Rule::unique('partners', 'slug')->ignore($r)]],

            ['name' => 'kind', 'label' => content('product.filter_by_category'), 'type' => 'select', 'width' => 'half',
                'options' => fn () => collect(Partner::KINDS)
                    ->mapWithKeys(fn ($k) => [$k => __('partners.kinds.'.$k)])->all(),
                'rules' => ['required', Rule::in(Partner::KINDS)]],

            ['name' => 'summary', 'label' => __('admin.fields.summary'), 'type' => 'textarea', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:400']],

            ['name' => 'logo', 'label' => __('admin.fields.logo'), 'type' => 'image'],

            ['name' => 'website', 'label' => __('admin.fields.website'), 'width' => 'half',
                'rules' => ['nullable', 'url', 'max:255']],

            ['name' => 'position', 'label' => __('admin.position'), 'type' => 'number', 'width' => 'half',
                'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],

            ['name' => 'is_active', 'label' => __('admin.active'), 'type' => 'checkbox'],
        ];
    }
}
