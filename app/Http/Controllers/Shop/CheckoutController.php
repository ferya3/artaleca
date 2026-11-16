<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\ContactMessage;
use App\Shop\Cart;
use App\Shop\Checkout;
use App\Shop\OrderNotifier;
use App\Shop\PaymentFlow;
use App\Shop\Payments\GatewayException;
use App\Shop\Shop;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    public function create(Cart $cart): View|RedirectResponse
    {
        $lines = $cart->lines();

        if ($lines->isEmpty()) {
            return redirect()->route('cart.index');
        }

        seo()
            ->title(__('shop.checkout'))
            ->breadcrumbs($this->trail([
                ['label' => __('shop.cart'), 'url' => route('cart.index')],
                ['label' => __('shop.checkout'), 'url' => null],
            ]))
            ->noindex();

        return view('pages.shop.checkout', [
            'lines' => $lines,
            'totals' => Shop::totals((int) $lines->sum('line_total')),
            'paymentMethods' => Shop::paymentMethods(),
        ]);
    }

    public function store(CheckoutRequest $request, Cart $cart): Response
    {
        $lines = $cart->lines();

        if ($lines->isEmpty()) {
            return redirect()->route('cart.index');
        }

        try {
            $order = Checkout::place($lines, $request->validated(), ContactMessage::hashIp($request->ip()));
        } catch (ValidationException $e) {
            return redirect()->route('cart.index')->withErrors($e->errors());
        }

        $cart->clear();
        OrderNotifier::placed($order);

        if ($order->payment_method === Shop::PAYMENT_ONLINE) {
            try {
                // `away` and not `redirect()->to()`: the bank page is another
                // site, and Inertia-style same-origin assumptions do not apply.
                return redirect()->away(PaymentFlow::start($order));
            } catch (GatewayException) {
                return redirect()
                    ->route('orders.show', $order->token)
                    ->withErrors(['payment' => __('shop.gateway_unavailable')]);
            }
        }

        return redirect()->route('orders.show', $order->token)->with('status', __('shop.placed'));
    }
}
