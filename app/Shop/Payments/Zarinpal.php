<?php

declare(strict_types=1);

namespace App\Shop\Payments;

use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Zarinpal, REST API v4.
 *
 *   request  POST /pg/v4/payment/request.json  → data.authority
 *   pay      GET  /pg/StartPay/{authority}      (the customer, in a browser)
 *   back     GET  callback?Authority=…&Status=OK|NOK
 *   verify   POST /pg/v4/payment/verify.json   → data.code 100, or 101 when
 *            this authority was already verified
 *
 * Amounts go in rials: the order is kept in tomans and multiplied by ten here,
 * at the one place that talks to the gateway.
 *
 * Sandbox mode is the same API on sandbox.zarinpal.com, takes any merchant id
 * of the right shape, and moves no money — which is how the whole flow is
 * tried end to end before the real merchant id goes in.
 */
final class Zarinpal implements Gateway
{
    public function __construct(
        private readonly string $merchantId,
        private readonly bool $sandbox = false,
    ) {}

    public function name(): string
    {
        return 'zarinpal';
    }

    private function host(): string
    {
        return $this->sandbox ? 'https://sandbox.zarinpal.com' : 'https://payment.zarinpal.com';
    }

    public function start(Payment $payment, string $callbackUrl, string $description, ?string $mobile): string
    {
        $data = $this->call('/pg/v4/payment/request.json', array_filter([
            'merchant_id' => $this->merchantId,
            'amount' => $payment->amount * 10,
            'currency' => 'IRR',
            'callback_url' => $callbackUrl,
            'description' => mb_substr($description, 0, 255),
            'metadata' => $mobile ? ['mobile' => $mobile] : null,
        ]));

        if ((int) ($data['code'] ?? 0) !== 100 || blank($data['authority'] ?? null)) {
            throw new GatewayException('Zarinpal refused the payment request (code '.($data['code'] ?? '?').').');
        }

        $payment->forceFill(['authority' => (string) $data['authority']])->save();

        return $this->host().'/pg/StartPay/'.rawurlencode((string) $data['authority']);
    }

    public function verify(Payment $payment, Request $callback): array
    {
        if ($callback->query('Status') !== 'OK') {
            throw new GatewayException('The customer cancelled, or the bank declined.');
        }

        // The authority in the query must be the one this payment was opened
        // with; a callback carrying somebody else's is not this payment's.
        if (! hash_equals((string) $payment->authority, (string) $callback->query('Authority'))) {
            throw new GatewayException('The callback does not belong to this payment.');
        }

        $data = $this->call('/pg/v4/payment/verify.json', [
            'merchant_id' => $this->merchantId,
            'amount' => $payment->amount * 10,
            'authority' => $payment->authority,
        ]);

        if (! in_array((int) ($data['code'] ?? 0), [100, 101], true)) {
            throw new GatewayException('Zarinpal did not confirm the payment (code '.($data['code'] ?? '?').').');
        }

        return [
            'reference' => (string) ($data['ref_id'] ?? ''),
            'card_pan' => isset($data['card_pan']) ? (string) $data['card_pan'] : null,
        ];
    }

    /** @return array<string, mixed> the `data` object */
    private function call(string $path, array $body): array
    {
        try {
            $response = Http::timeout(20)
                ->connectTimeout(8)
                ->acceptJson()
                ->asJson()
                ->post($this->host().$path, $body);
        } catch (ConnectionException $e) {
            throw new GatewayException('Zarinpal could not be reached: '.$e->getMessage(), 0, $e);
        }

        $json = $response->json();
        $data = is_array($json['data'] ?? null) ? $json['data'] : [];

        // Errors come back in `errors` with a negative code and an empty `data`.
        if ($data === [] && is_array($json['errors'] ?? null)) {
            $data = ['code' => $json['errors']['code'] ?? null];
        }

        return $data;
    }
}
