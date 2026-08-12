<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Download;
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
            'sku' => content('product.sku'),
            'category.name' => content('product.filter_by_category'),
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

            ['name' => 'sku', 'label' => content('product.sku'), 'width' => 'half',
                'rules' => fn (?Model $r) => ['nullable', 'string', 'max:40',
                    Rule::unique('products', 'sku')->ignore($r)]],

            ['name' => 'product_category_id', 'label' => content('product.filter_by_category'), 'type' => 'select',
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

            ['name' => 'features', 'label' => __('admin.fields.features'), 'type' => 'list',
                'hint' => 'One feature per line.'],

            ['name' => 'advantages', 'label' => __('admin.fields.advantages'), 'type' => 'list',
                'hint' => 'One advantage per line.'],

            // ── Technical data ──────────────────────────────────────────
            ['name' => 'grain_min_mm', 'label' => content('product.grain_size').' — min (mm)', 'type' => 'number',
                'step' => '0.1', 'width' => 'half', 'rules' => ['nullable', 'numeric', 'min:0', 'max:999']],
            ['name' => 'grain_max_mm', 'label' => content('product.grain_size').' — max (mm)', 'type' => 'number',
                'step' => '0.1', 'width' => 'half', 'rules' => ['nullable', 'numeric', 'min:0', 'max:999']],
            ['name' => 'bulk_density_min', 'label' => content('product.bulk_density').' — min', 'type' => 'number',
                'width' => 'half', 'rules' => ['nullable', 'integer', 'min:0', 'max:5000']],
            ['name' => 'bulk_density_max', 'label' => content('product.bulk_density').' — max', 'type' => 'number',
                'width' => 'half', 'rules' => ['nullable', 'integer', 'min:0', 'max:5000']],
            ['name' => 'particle_density', 'label' => content('product.particle_density'), 'type' => 'number',
                'width' => 'half', 'rules' => ['nullable', 'integer', 'min:0', 'max:5000']],
            ['name' => 'crushing_strength', 'label' => content('product.crushing_strength'), 'type' => 'number',
                'step' => '0.01', 'width' => 'half', 'rules' => ['nullable', 'numeric', 'min:0', 'max:999']],
            ['name' => 'thermal_conductivity', 'label' => content('product.thermal_conductivity'), 'type' => 'number',
                'step' => '0.001', 'width' => 'half', 'rules' => ['nullable', 'numeric', 'min:0', 'max:99']],
            ['name' => 'water_absorption_24h', 'label' => content('product.water_absorption'), 'type' => 'number',
                'step' => '0.1', 'width' => 'half', 'rules' => ['nullable', 'numeric', 'min:0', 'max:100']],
            ['name' => 'ph_value', 'label' => content('product.ph_value'), 'type' => 'number',
                'step' => '0.1', 'width' => 'half', 'rules' => ['nullable', 'numeric', 'min:0', 'max:14']],
            ['name' => 'fire_resistance_c', 'label' => content('product.fire_resistance'), 'type' => 'number',
                'step' => '1', 'width' => 'half', 'rules' => ['nullable', 'numeric', 'min:0', 'max:5000']],

            ['name' => 'standards', 'label' => content('product.standards'), 'type' => 'list',
                'hint' => 'One standard per line, e.g. EN 13055-1'],

            ['name' => 'specs', 'label' => content('product.properties'), 'type' => 'pairs',
                'hint' => 'One row per line: label | value'],

            ['name' => 'hero_image', 'label' => __('admin.fields.image_path'), 'type' => 'image', 'width' => 'half'],

            ['name' => 'datasheet_path', 'label' => __('admin.fields.datasheet_path'), 'type' => 'document', 'width' => 'half'],

            ['name' => 'gallery', 'label' => __('admin.fields.gallery'), 'type' => 'gallery',
                'hint' => __('admin.fields.gallery_hint')],

            ['name' => 'downloads', 'label' => __('admin.fields.related_downloads'), 'type' => 'relation',
                'relation' => 'downloads',
                'options' => fn () => Download::query()->where('is_active', true)->orderBy('position')
                    ->pluck('title', 'id')
                    ->map(fn ($t) => is_array($t) ? ($t[app()->getLocale()] ?? reset($t)) : $t)->all()],

            ['name' => 'meta_title', 'label' => __('admin.fields.meta_title'), 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:70']],
            ['name' => 'meta_description', 'label' => __('admin.fields.meta_description'), 'type' => 'textarea', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:170']],

            ['name' => 'is_featured', 'label' => __('admin.featured'), 'type' => 'checkbox', 'width' => 'half'],
            ['name' => 'is_active', 'label' => __('admin.active'), 'type' => 'checkbox', 'width' => 'half'],
        ];
    }
}
