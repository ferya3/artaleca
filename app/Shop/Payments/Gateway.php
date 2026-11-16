<?php

declare(strict_types=1);

namespace App\Shop\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * An online payment gateway.
 *
 * Two calls, the shape every Iranian PSP shares: ask for a payment and send
 * the customer to the bank page; then, when the bank sends them back, ask the
 * gateway — not the browser — whether the money really arrived. Adding Sep,
 * Mellat or IDPay is one more class with these two methods.
 */
interface Gateway
{
    public function name(): string;

    /**
     * Register the payment with the gateway.
     *
     * @return string the URL to send the customer to
     *
     * @throws GatewayException
     */
    public function start(Payment $payment, string $callbackUrl, string $description, ?string $mobile): string;

    /**
     * Confirm a payment the customer has come back from.
     *
     * @return array{reference: string, card_pan: ?string}
     *
     * @throws GatewayException when it was not paid
     */
    public function verify(Payment $payment, Request $callback): array;
}
