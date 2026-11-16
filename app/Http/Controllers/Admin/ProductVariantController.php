<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Prices: one row per way a product is sold.
 *
 * Its own list rather than a block inside the product form, because it is the
 * screen somebody opens every time the price list changes — and on that day
 * they want every price on one page, not seven product forms to scroll.
 */
class ProductVariantController extends ResourceController
{
    public function model(): string
    {
        return ProductVariant::class;
    }

    protected function routeName(): string
    {
        return 'product-variants';
    }

    protected function title(): string
    {
        return __('admin.orders.prices');
    }

    protected function listWith(): array
    {
        return ['product'];
    }

    protected function searchable(): array
    {
        return ['sku', 'name'];
    }

    protected function listColumns(): array
    {
        return [
            'product.name' => __('admin.products'),
            'name' => __('admin.fields.name'),
            'price' => __('admin.orders.price'),
            'unit' => __('admin.orders.unit'),
            'stock' => __('admin.orders.stock'),
            'is_active' => __('admin.status'),
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'product_id', 'label' => __('admin.products'), 'type' => 'select', 'width' => 'half',
                'options' => fn () => Product::query()->ordered()->get()->mapWithKeys(fn (Product $p) => [$p->id => $p->name])->all(),
                'rules' => ['required', 'integer', Rule::exists('products', 'id')]],

            ['name' => 'sku', 'label' => content('product.sku'), 'width' => 'half',
                'rules' => fn (?Model $r) => ['nullable', 'string', 'max:40', Rule::unique('product_variants', 'sku')->ignore($r)]],

            ['name' => 'name', 'label' => __('admin.orders.variant_name'), 'translatable' => true,
                'hint' => __('admin.orders.variant_name_hint'),
                'rules' => ['required', 'string', 'max:120']],

            ['name' => 'unit', 'label' => __('admin.orders.unit'), 'translatable' => true,
                'hint' => __('admin.orders.unit_hint'),
                'rules' => ['required', 'string', 'max:40']],

            ['name' => 'price', 'label' => __('admin.orders.price'), 'type' => 'number', 'width' => 'half',
                'hint' => __('admin.orders.price_hint'),
                'rules' => ['required', 'integer', 'min:1', 'max:1000000000000']],

            ['name' => 'stock', 'label' => __('admin.orders.stock'), 'type' => 'number', 'width' => 'half',
                'hint' => __('admin.orders.stock_hint'),
                'rules' => ['nullable', 'integer', 'min:0', 'max:100000000']],

            ['name' => 'min_quantity', 'label' => __('admin.orders.min_quantity'), 'type' => 'number', 'width' => 'half',
                'rules' => ['required', 'integer', 'min:1', 'max:100000']],

            ['name' => 'step', 'label' => __('admin.orders.step'), 'type' => 'number', 'width' => 'half',
                'hint' => __('admin.orders.step_hint'),
                'rules' => ['required', 'integer', 'min:1', 'max:100000']],

            ['name' => 'max_quantity', 'label' => __('admin.orders.max_quantity'), 'type' => 'number', 'width' => 'half',
                'hint' => __('admin.orders.max_quantity_hint'),
                'rules' => ['nullable', 'integer', 'min:1', 'max:100000000', 'gte:min_quantity']],

            ['name' => 'position', 'label' => __('admin.position'), 'type' => 'number', 'width' => 'half',
                'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],

            ['name' => 'is_active', 'label' => __('admin.orders.on_sale'), 'type' => 'checkbox', 'width' => 'half'],
        ];
    }
}
