<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use App\Jobs\SendOrderSms;
use App\Mail\OrderReceived;
use App\Models\Order;
use App\Shop\OrderStatus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

class CheckoutTest extends ShopTestCase
{
    public function test_a_transfer_order_is_placed_with_everything_it_was_sold_with(): void
    {
        Mail::fake();
        Queue::fake();
        $this->openShop();
        $variant = $this->variant(['stock' => 100]);
        $this->fillCart($variant, 20);

        $response = $this->post('/fa/checkout', $this->checkoutPayload());

        $order = Order::with('items')->sole();
        $response->assertRedirect("/fa/orders/{$order->token}");

        $this->assertSame(OrderStatus::AwaitingTransfer, $order->status);
        $this->assertMatchesRegularExpression('/^AL-[ACDEFGHJKMNPQRTUVWXY]{7}$/', $order->number);
        $this->assertSame(5000000, $order->subtotal);        // 20 × 250,000
        $this->assertSame(500000, $order->tax);              // 10%
        $this->assertSame(5500000, $order->total);
        $this->assertSame('fa', $order->locale);

        $item = $order->items->sole();
        $this->assertSame(20, $item->quantity);
        $this->assertSame(250000, $item->unit_price);
        $this->assertSame('50-litre bag', $item->getTranslation('variant_name', 'en'));

        // Stock reserved, basket emptied.
        $this->assertSame(80, $variant->fresh()->stock);
        $this->assertSame([], session('shop.cart', []));

        // A transfer needs watching for, so staff hear now.
        Mail::assertSent(OrderReceived::class);
        Queue::assertPushed(SendOrderSms::class, fn ($job) => $job->recipient === 'staff' && $job->event === 'placed');
        Queue::assertPushed(SendOrderSms::class, fn ($job) => $job->recipient === 'customer');

        $this->get("/fa/orders/{$order->token}")->assertOk()
            ->assertSee('<bdi dir="ltr">6037-9911-2233-4455</bdi>', false)
            ->assertSee('<bdi dir="ltr">IR12 0170 0000 0012 3456 7890 01</bdi>', false)
            ->assertSee($order->number);
    }

    public function test_digits_typed_on_a_persian_keyboard_are_accepted(): void
    {
        $this->openShop();
        $this->fillCart($this->variant(), 10);

        $this->post('/fa/checkout', $this->checkoutPayload([
            'mobile' => '۰۹۱۲ ۱۲۳ ۴۵۶۷',
            'postal_code' => '۱۲۳۴۵-۶۷۸۹۰',
        ]))->assertSessionHasNoErrors();

        $order = Order::sole();
        $this->assertSame('09121234567', $order->mobile);
        $this->assertSame('1234567890', $order->postal_code);
    }

    public function test_the_province_is_stored_in_persian_from_any_language(): void
    {
        $this->openShop();
        $variant = $this->variant();
        $this->post('/en/cart', ['variant' => $variant->id, 'quantity' => 10]);

        $this->post('/en/checkout', $this->checkoutPayload(['province' => 'کرمانشاه']))->assertSessionHasNoErrors();
        $this->assertSame('کرمانشاه', Order::sole()->province);
        $this->assertSame('en', Order::sole()->locale);

        $this->fillCart($variant, 10);
        $this->post('/fa/checkout', $this->checkoutPayload(['province' => 'Narnia']))->assertSessionHasErrors('province');
    }

    public function test_a_payment_method_that_is_not_set_up_cannot_be_chosen(): void
    {
        $this->openShop(['shop.transfer_details' => '']);
        $this->fillCart($this->variant(), 10);

        $this->post('/fa/checkout', $this->checkoutPayload(['payment_method' => 'transfer']))
            ->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Order::count());
    }

    public function test_the_last_of_the_stock_goes_to_one_customer(): void
    {
        $this->openShop();
        $variant = $this->variant(['stock' => 10]);
        $this->fillCart($variant, 10);

        // Somebody else bought it between the basket and the checkout.
        $variant->update(['stock' => 0]);

        $this->post('/fa/checkout', $this->checkoutPayload())->assertRedirect('/fa/cart');
        $this->assertSame(0, Order::count());
    }

    public function test_the_spam_defences_apply(): void
    {
        $this->openShop();
        $this->fillCart($this->variant(), 10);

        $this->post('/fa/checkout', $this->checkoutPayload(['website' => 'http://spam']))->assertSessionHasErrors('website');
        $this->post('/fa/checkout', $this->checkoutPayload(['started_at' => $this->humanFormToken(0)]))->assertSessionHasErrors('started_at');
        $this->assertSame(0, Order::count());
    }

    public function test_an_empty_basket_goes_back_to_the_basket(): void
    {
        $this->openShop();

        $this->get('/fa/checkout')->assertRedirect('/fa/cart');
        $this->post('/fa/checkout', $this->checkoutPayload())->assertRedirect('/fa/cart');
    }

    public function test_without_tax_the_price_is_the_total(): void
    {
        $this->openShop(['shop.tax_percent' => 0]);
        $this->fillCart($this->variant(), 10);

        $this->post('/fa/checkout', $this->checkoutPayload());

        $this->assertSame(2500000, Order::sole()->total);
    }

    /** Whatever staff type into the transfer box is text, never markup. */
    public function test_the_transfer_details_are_escaped(): void
    {
        $this->openShop(['shop.transfer_details' => "<script>alert(1)</script>\nکارت: 6037-9911-2233-4455"]);
        $this->fillCart($this->variant(), 10);
        $this->post('/fa/checkout', $this->checkoutPayload());

        $this->get('/fa/orders/'.Order::sole()->token)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }
}
