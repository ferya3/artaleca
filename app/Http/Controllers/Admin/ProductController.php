<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ProductController extends ResourceController
{
    public function model(): string
    {
        return Product::class;
    }

    protected function routeName(): string
    {
        return 'products';
    }

    protected function title(): string
    {
        return __('admin.products');
    }

    protected function listWith(): array
    {
        return ['category'];
    }

    protected function searchable(): array
    {
        return ['slug', 'sku', 'name'];
    }

    protected function listColumns(): array
    {
        return [
            'name' => __('admin.fields.name'),
            'sku' => __('product.sku'),
            'category.name' => __('product.filter_by_category'),
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
                    Rule::unique('products', 'slug')->ignore($r)]],

            ['name' => 'sku', 'label' => __('product.sku'), 'width' => 'half',
                'rules' => fn (?Model $r) => ['nullable', 'string', 'max:40',
                    Rule::unique('products', 'sku')->ignore($r)]],

            ['name' => 'product_category_id', 'label' => __('product.filter_by_category'), 'type' => 'select',
                'width' => 'half',
                'options' => fn () => ProductCategory::query()->ordered()->pluck('name', 'id')
                    ->map(fn ($name) => is_array($name) ? ($name[app()->getLocale()] ?? reset($name)) : $name)->all(),
                'rules' => ['required', 'integer', Rule::exists('product_categories', 'id')]],

            ['name' => 'position', 'label' => __('admin.position'), 'type' => 'number', 'width' => 'half',
                'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],

            ['name' => 'tagline', 'label' => __('admin.fields.tagline'), 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:255']],

            ['name' => 'summary', 'label' => __('admin.fields.summary'), 'type' => 'textarea', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:600']],

            ['name' => 'description', 'label' => __('admin.fields.description'), 'type' => 'textarea', 'translatable' => true,
                'rows' => 10, 'rules' => ['nullable', 'string', 'max:8000']],

            // ── Technical data ──────────────────────────────────────────
            ['name' => 'grain_min_mm', 'label' => __('product.grain_size').' — min (mm)', 'type' => 'number',
                'step' => '0.1', 'width' => 'half', 'rules' => ['nullable', 'numeric', 'min:0', 'max:999']],
            ['name' => 'grain_max_mm', 'label' => __('product.grain_size').' — max (mm)', 'type' => 'number',
                'step' => '0.1', 'width' => 'half', 'rules' => ['nullable', 'numeric', 'min:0', 'max:999']],
            ['name' => 'bulk_density_min', 'label' => __('product.bulk_density').' — min', 'type' => 'number',
                'width' => 'half', 'rules' => ['nullable', 'integer', 'min:0', 'max:5000']],
            ['name' => 'bulk_density_max', 'label' => __('product.bulk_density').' — max', 'type' => 'number',
                'width' => 'half', 'rules' => ['nullable', 'integer', 'min:0', 'max:5000']],
            ['name' => 'particle_density', 'label' => __('product.particle_density'), 'type' => 'number',
                'width' => 'half', 'rules' => ['nullable', 'integer', 'min:0', 'max:5000']],
            ['name' => 'crushing_strength', 'label' => __('product.crushing_strength'), 'type' => 'number',
                'step' => '0.01', 'width' => 'half', 'rules' => ['nullable', 'numeric', 'min:0', 'max:999']],
            ['name' => 'thermal_conductivity', 'label' => __('product.thermal_conductivity'), 'type' => 'number',
                'step' => '0.001', 'width' => 'half', 'rules' => ['nullable', 'numeric', 'min:0', 'max:99']],
            ['name' => 'water_absorption_24h', 'label' => __('product.water_absorption'), 'type' => 'number',
                'step' => '0.1', 'width' => 'half', 'rules' => ['nullable', 'numeric', 'min:0', 'max:100']],
            ['name' => 'ph_value', 'label' => __('product.ph_value'), 'type' => 'number',
                'step' => '0.1', 'width' => 'half', 'rules' => ['nullable', 'numeric', 'min:0', 'max:14']],
            ['name' => 'fire_resistance_c', 'label' => __('product.fire_resistance'), 'type' => 'number',
                'step' => '1', 'width' => 'half', 'rules' => ['nullable', 'numeric', 'min:0', 'max:5000']],

            ['name' => 'standards', 'label' => __('product.standards'), 'type' => 'list',
                'hint' => 'One standard per line, e.g. EN 13055-1'],

            ['name' => 'specs', 'label' => __('product.properties'), 'type' => 'pairs',
                'hint' => 'One row per line: label | value'],

            ['name' => 'hero_image', 'label' => __('admin.fields.image_path'), 'width' => 'half',
                'rules' => ['nullable', 'string', 'max:255']],
            ['name' => 'datasheet_path', 'label' => __('admin.fields.datasheet_path'), 'width' => 'half',
                'rules' => ['nullable', 'string', 'max:255']],

            ['name' => 'meta_title', 'label' => __('admin.fields.meta_title'), 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:70']],
            ['name' => 'meta_description', 'label' => __('admin.fields.meta_description'), 'type' => 'textarea', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:170']],

            ['name' => 'is_featured', 'label' => __('admin.featured'), 'type' => 'checkbox', 'width' => 'half'],
            ['name' => 'is_active', 'label' => __('admin.active'), 'type' => 'checkbox', 'width' => 'half'],
        ];
    }
}
