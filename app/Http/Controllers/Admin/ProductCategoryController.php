<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ProductCategoryController extends ResourceController
{
    public function model(): string
    {
        return ProductCategory::class;
    }

    protected function routeName(): string
    {
        return 'product-categories';
    }

    protected function title(): string
    {
        return __('admin.product_categories');
    }

    protected function searchable(): array
    {
        return ['slug', 'name'];
    }

    protected function listColumns(): array
    {
        return [
            'name' => __('product.filter_by_category'),
            'slug' => 'Slug',
            'position' => __('admin.position'),
            'is_active' => __('admin.status'),
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => __('product.filter_by_category'), 'translatable' => true,
                'rules' => ['required', 'string', 'max:180']],

            ['name' => 'slug', 'label' => 'Slug', 'width' => 'half',
                'rules' => fn (?Model $r) => ['nullable', 'string', 'max:180', 'alpha_dash',
                    Rule::unique('product_categories', 'slug')->ignore($r)]],

            ['name' => 'position', 'label' => __('admin.position'), 'type' => 'number', 'width' => 'half',
                'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],

            ['name' => 'summary', 'label' => __('product.description'), 'type' => 'textarea', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:600']],

            ['name' => 'description', 'label' => __('product.description'), 'type' => 'textarea', 'translatable' => true,
                'rows' => 8, 'rules' => ['nullable', 'string', 'max:6000']],

            ['name' => 'image', 'label' => 'Image path', 'rules' => ['nullable', 'string', 'max:255']],

            ['name' => 'meta_title', 'label' => 'Meta title', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:70']],
            ['name' => 'meta_description', 'label' => 'Meta description', 'type' => 'textarea', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:170']],

            ['name' => 'is_active', 'label' => __('admin.active'), 'type' => 'checkbox'],
        ];
    }
}
