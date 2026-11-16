<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Shop\PaymentFlow;
use App\Shop\Payments\GatewayException;
use App\Shop\Shop;
use App\Support\Mobile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The customer's side of an order.
 *
 * There are no customer accounts. An order is reached by its token — a forty-
 * character link the customer is sent to straight after checkout — or by its
 * number together with the mobile it was placed with. Either is enough to see
 * and pay the order and neither is enough to change it.
 */
class OrderController extends Controller
{
    public function show(string $token): View
    {
        $order = $this->find($token);

        seo()->title(__('shop.order_title', ['number' => $order->number]))->noindex();

        return view('pages.shop.order', [
            'order' => $order->load(['items', 'payments']),
            'transferDetails' => Shop::transferDetails(),
            'canPayOnline' => $order->canPayOnline() && Shop::gateway() !== null,
        ]);
    }

    /** Try the bank again — a customer who closed the page, or was declined. */
    public function pay(string $token): Response
    {
        $order = $this->find($token);

        if (! $order->canPayOnline() || Shop::gateway() === null) {
            return redirect()->route('orders.show', $order->token);
        }

        try {
            return redirect()->away(PaymentFlow::start($order));
        } catch (GatewayException) {
            return redirect()->route('orders.show', $order->token)
                ->withErrors(['payment' => __('shop.gateway_unavailable')]);
        }
    }

    /** Where the bank sends the customer back to. */
    public function callback(Request $request, Payment $payment): RedirectResponse
    {
        $paid = PaymentFlow::complete($payment, $request);
        $order = $payment->order;

        return $paid
            ? redirect()->route('orders.show', $order->token)->with('status', __('shop.payment_ok'))
            : redirect()->route('orders.show', $order->token)->withErrors(['payment' => __('shop.payment_failed')]);
    }

    public function track(): View
    {
        seo()
            ->title(__('shop.track_title'))
            ->breadcrumbs($this->trail([['label' => __('shop.track_title'), 'url' => null]]))
            ->noindex();

        return view('pages.shop.track');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:20'],
            'mobile' => ['required', 'string', 'max:20'],
        ]);

        $order = Order::query()
            ->where('number', strtoupper(trim($validated['number'])))
            ->where('mobile', Mobile::normalize($validated['mobile']) ?? '-')
            ->first();

        // One message for "no such order" and "wrong mobile", so the form
        // cannot be used to find out which order numbers exist.
        if (! $order) {
            return back()->withInput()->withErrors(['number' => __('shop.track_not_found')]);
        }

        return redirect()->route('orders.show', $order->token);
    }

    private function find(string $token): Order
    {
        abort_unless(strlen($token) === 40 && ctype_alnum($token), 404);

        return Order::query()->where('token', $token)->firstOrFail();
    }
}
