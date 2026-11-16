<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;

class ShopController extends Controller
{
    public function index(): View
    {
        // Products, each with what of it is on sale. A product with nothing on
        // sale is left out here; it is still in the catalogue with its quote
        // button.
        $products = Product::query()
            ->active()->ordered()
            ->with(['category', 'variants' => fn ($q) => $q->purchasable()])
            ->whereHas('variants', fn ($q) => $q->purchasable())
            ->get()
            ->each(fn (Product $p) => $p->setRelation('variants', $p->variants->filter(fn (ProductVariant $v) => $v->inStock())->values()))
            ->filter(fn (Product $p) => $p->variants->isNotEmpty())
            ->values();

        seo()
            ->title(__('shop.meta_title'))
            ->description(__('shop.meta_description'))
            ->breadcrumbs($this->trail([
                ['label' => __('shop.title'), 'url' => null],
            ]));

        return view('pages.shop.index', ['products' => $products]);
    }
}
