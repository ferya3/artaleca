<?php

declare(strict_types=1);

namespace App\Shop;

use App\Models\Order;
use App\Models\Payment;
use App\Shop\Payments\GatewayException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Taking an order through the gateway and back.
 *
 * The browser is never believed. Coming back with `Status=OK` proves nothing —
 * anybody can type that into an address bar — so an order is marked paid only
 * after the gateway itself confirms the payment, for this order's amount, with
 * the merchant id only the server holds.
 */
final class PaymentFlow
{
    /**
     * @return string where to send the customer
     *
     * @throws GatewayException
     */
    public static function start(Order $order): string
    {
        $gateway = Shop::gateway() ?? throw new GatewayException('No online gateway is configured.');

        $payment = $order->payments()->create([
            'gateway' => $gateway->name(),
            'amount' => $order->total,
            'status' => Payment::PENDING,
        ]);

        try {
            return $gateway->start(
                $payment,
                route('payment.callback', ['payment' => $payment->id, 'locale' => $order->locale]),
                __('shop.payment_description', ['number' => $order->number]),
                $order->mobile,
            );
        } catch (GatewayException $e) {
            $payment->forceFill(['status' => Payment::FAILED, 'failure' => mb_substr($e->getMessage(), 0, 255)])->save();
            Log::warning('Payment could not be started', ['order' => $order->number, 'reason' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * Settle a payment the customer has come back from. True when the order
     * is paid — now, or already.
     */
    public static function complete(Payment $payment, Request $callback): bool
    {
        $gateway = Shop::gateway();

        return DB::transaction(function () use ($payment, $callback, $gateway) {
            /** @var Payment $payment */
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);

            // A refresh of the callback page, or the gateway calling twice.
            if ($payment->status === Payment::PAID) {
                return true;
            }

            if ($payment->status !== Payment::PENDING || ! $gateway || $order->status === OrderStatus::Cancelled) {
                return $order->isPaid();
            }

            try {
                $result = $gateway->verify($payment, $callback);
            } catch (GatewayException $e) {
                $payment->forceFill(['status' => Payment::FAILED, 'failure' => mb_substr($e->getMessage(), 0, 255)])->save();

                return $order->isPaid();
            }

            $payment->forceFill([
                'status' => Payment::PAID,
                'reference' => $result['reference'],
                'card_pan' => $result['card_pan'],
                'verified_at' => now(),
            ])->save();

            if (! $order->isPaid()) {
                $order->forceFill(['status' => OrderStatus::Paid, 'paid_at' => now()])->save();
                DB::afterCommit(fn () => OrderNotifier::paid($order));
            }

            return true;
        });
    }
}
