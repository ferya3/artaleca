<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Shop\Cart;
use App\Shop\Shop;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Cart $cart): View
    {
        seo()
            ->title(__('shop.cart'))
            ->breadcrumbs($this->trail([
                ['label' => __('shop.title'), 'url' => route('shop.index')],
                ['label' => __('shop.cart'), 'url' => null],
            ]))
            ->noindex();

        $lines = $cart->lines();

        return view('pages.shop.cart', [
            'lines' => $lines,
            'totals' => Shop::totals((int) $lines->sum('line_total')),
        ]);
    }

    public function store(Request $request, Cart $cart): RedirectResponse
    {
        $validated = $request->validate([
            'variant' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $variant = ProductVariant::query()->purchasable()->with('product')->find($validated['variant']);

        if (! $variant || ! $variant->inStock()) {
            return back()->withErrors(['cart' => __('shop.unavailable')]);
        }

        $cart->add($variant, (int) $validated['quantity']);

        return redirect()->route('cart.index')->with('status', __('shop.added', ['item' => $variant->label()]));
    }

    public function update(Request $request, Cart $cart, int $variant): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        $model = ProductVariant::query()->purchasable()->find($variant);

        if (! $model || (int) $validated['quantity'] === 0) {
            $cart->remove($variant);
        } else {
            $cart->set($model, (int) $validated['quantity']);
        }

        return redirect()->route('cart.index');
    }

    public function destroy(Cart $cart, int $variant): RedirectResponse
    {
        $cart->remove($variant);

        return redirect()->route('cart.index');
    }
}
