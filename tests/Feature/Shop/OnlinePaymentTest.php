<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use App\Jobs\SendOrderSms;
use App\Models\Order;
use App\Models\Payment;
use App\Shop\OrderStatus;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

/**
 * Zarinpal, faked at the HTTP layer. The point of these is the rule in
 * PaymentFlow: the browser coming back with Status=OK proves nothing; only the
 * gateway's own verify call, for this order's amount, marks an order paid.
 */
class OnlinePaymentTest extends ShopTestCase
{
    private const AUTHORITY = 'A00000000000000000000000000217885159';

    private function fakeZarinpal(int $verifyCode = 100): void
    {
        Http::fake([
            'payment.zarinpal.com/pg/v4/payment/request.json' => Http::response([
                'data' => ['code' => 100, 'message' => 'Success', 'authority' => self::AUTHORITY, 'fee_type' => 'Merchant', 'fee' => 100],
                'errors' => [],
            ]),
            'payment.zarinpal.com/pg/v4/payment/verify.json' => Http::response($verifyCode > 0
                ? ['data' => ['code' => $verifyCode, 'message' => 'Verified', 'card_pan' => '502229******5995', 'ref_id' => 201], 'errors' => []]
                : ['data' => [], 'errors' => ['code' => $verifyCode, 'message' => 'Session is not valid']]),
        ]);
    }

    private function placeOnlineOrder(): Order
    {
        $this->openShop();
        $this->fillCart($this->variant(), 10);

        $this->post('/fa/checkout', $this->checkoutPayload(['payment_method' => 'online']))
            ->assertRedirect('https://payment.zarinpal.com/pg/StartPay/'.self::AUTHORITY);

        return Order::sole();
    }

    public function test_the_customer_is_sent_to_the_bank_for_the_right_amount_in_rials(): void
    {
        Queue::fake();
        $this->fakeZarinpal();

        $order = $this->placeOnlineOrder();

        $this->assertSame(OrderStatus::Pending, $order->status);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/request.json')
            && $r['merchant_id'] === self::MERCHANT
            && $r['amount'] === 27500000                   // 2,750,000 toman incl. VAT, × 10
            && str_contains($r['callback_url'], '/fa/payment/'));

        // Nobody hears about an order that has not been paid for yet.
        Queue::assertNothingPushed();
    }

    public function test_a_verified_payment_marks_the_order_paid(): void
    {
        Mail::fake();
        Queue::fake();
        $this->fakeZarinpal();
        $order = $this->placeOnlineOrder();
        $payment = Payment::sole();

        $this->get("/fa/payment/{$payment->id}/callback?Authority=".self::AUTHORITY.'&Status=OK')
            ->assertRedirect("/fa/orders/{$order->token}")
            ->assertSessionHas('status', __('shop.payment_ok', [], 'fa'));

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame('201', $payment->fresh()->reference);
        $this->assertSame('502229******5995', $payment->fresh()->card_pan);

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/verify.json')
            && $r['amount'] === 27500000 && $r['authority'] === self::AUTHORITY);
        Queue::assertPushed(SendOrderSms::class, fn ($job) => $job->recipient === 'staff' && $job->event === 'paid');
    }

    public function test_coming_back_twice_does_not_pay_twice(): void
    {
        Queue::fake();
        Mail::fake();
        $this->fakeZarinpal();
        $this->placeOnlineOrder();
        $payment = Payment::sole();

        $url = "/fa/payment/{$payment->id}/callback?Authority=".self::AUTHORITY.'&Status=OK';
        $this->get($url);
        $this->get($url);

        Http::assertSentCount(2);   // one request, one verify — not a second verify
        Queue::assertPushed(SendOrderSms::class, 2);   // staff + customer, once
    }

    public function test_a_cancelled_payment_leaves_the_order_payable(): void
    {
        $this->fakeZarinpal();
        $order = $this->placeOnlineOrder();
        $payment = Payment::sole();

        $this->get("/fa/payment/{$payment->id}/callback?Authority=".self::AUTHORITY.'&Status=NOK')
            ->assertSessionHasErrors('payment');

        $this->assertSame(Payment::FAILED, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), '/verify.json'));

        $this->get("/fa/orders/{$order->token}")->assertSee(__('shop.pay_again', [], 'fa'));
    }

    /** Status=OK typed into an address bar, with the gateway saying no. */
    public function test_the_browser_is_not_believed(): void
    {
        $this->fakeZarinpal(-51);
        $order = $this->placeOnlineOrder();
        $payment = Payment::sole();

        $this->get("/fa/payment/{$payment->id}/callback?Authority=".self::AUTHORITY.'&Status=OK')
            ->assertSessionHasErrors('payment');

        $this->assertNull($order->fresh()->paid_at);
    }

    public function test_another_payments_authority_is_refused(): void
    {
        $this->fakeZarinpal();
        $order = $this->placeOnlineOrder();
        $payment = Payment::sole();

        $this->get("/fa/payment/{$payment->id}/callback?Authority=A0000000000000000000000000000000000X&Status=OK");

        $this->assertNull($order->fresh()->paid_at);
        Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), '/verify.json'));
    }

    public function test_a_gateway_that_is_down_keeps_the_order(): void
    {
        Http::fake(['*' => Http::response('', 502)]);
        $this->openShop();
        $this->fillCart($this->variant(), 10);

        $this->post('/fa/checkout', $this->checkoutPayload(['payment_method' => 'online']))
            ->assertSessionHasErrors('payment');

        $order = Order::sole();
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(Payment::FAILED, Payment::sole()->status);
    }

    public function test_paying_again_opens_a_new_attempt(): void
    {
        $second = 'A00000000000000000000000000217885160';
        Http::fake([
            'payment.zarinpal.com/pg/v4/payment/request.json' => Http::sequence()
                ->push(['data' => ['code' => 100, 'authority' => self::AUTHORITY], 'errors' => []])
                ->push(['data' => ['code' => 100, 'authority' => $second], 'errors' => []]),
        ]);
        $order = $this->placeOnlineOrder();

        $this->post("/fa/orders/{$order->token}/pay")
            ->assertRedirect('https://payment.zarinpal.com/pg/StartPay/'.$second);

        $this->assertSame(2, $order->payments()->count());
    }

    public function test_sandbox_mode_talks_to_the_sandbox(): void
    {
        Http::fake(['sandbox.zarinpal.com/*' => Http::response(['data' => ['code' => 100, 'authority' => self::AUTHORITY], 'errors' => []])]);
        $this->openShop(['shop.zarinpal_sandbox' => true]);
        $this->fillCart($this->variant(), 10);

        $this->post('/fa/checkout', $this->checkoutPayload(['payment_method' => 'online']))
            ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/'.self::AUTHORITY);
    }
}
