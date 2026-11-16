<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use App\Models\Order;

class OrderPageTest extends ShopTestCase
{
    private function order(): Order
    {
        $this->openShop();
        $this->fillCart($this->variant(), 10);
        $this->post('/fa/checkout', $this->checkoutPayload());

        return Order::sole();
    }

    public function test_the_order_page_is_reached_by_its_token_only(): void
    {
        $order = $this->order();

        $this->get("/fa/orders/{$order->token}")->assertOk()->assertSee('noindex', false);
        $this->get('/fa/orders/'.str_repeat('a', 40))->assertNotFound();
        $this->get("/fa/orders/{$order->number}")->assertNotFound();
    }

    public function test_an_order_is_found_by_number_and_mobile(): void
    {
        $order = $this->order();

        $this->post('/fa/orders/track', ['number' => strtolower($order->number), 'mobile' => '۰۹۱۲۱۲۳۴۵۶۷'])
            ->assertRedirect("/fa/orders/{$order->token}");
    }

    /** Same answer for "no such order" and "not your mobile". */
    public function test_a_wrong_mobile_says_no_more_than_a_wrong_number(): void
    {
        $order = $this->order();

        $wrongMobile = $this->post('/fa/orders/track', ['number' => $order->number, 'mobile' => '09350000000']);
        $wrongNumber = $this->post('/fa/orders/track', ['number' => 'AL-XXXXXXX', 'mobile' => '09121234567']);

        $wrongMobile->assertSessionHasErrors(['number' => __('shop.track_not_found', [], 'fa')]);
        $wrongNumber->assertSessionHasErrors(['number' => __('shop.track_not_found', [], 'fa')]);
    }

    /** Closing the shop must not strand an order that is waiting for its money. */
    public function test_order_pages_outlive_a_closed_shop(): void
    {
        $order = $this->order();
        $this->openShop(['shop.enabled' => false]);

        $this->get("/fa/orders/{$order->token}")->assertOk();
        $this->get('/fa/orders/track')->assertOk();
    }

    public function test_the_order_number_is_not_turned_into_persian_digits(): void
    {
        $order = $this->order();

        $this->get("/fa/orders/{$order->token}")->assertSee('data-latin-digits>'.$order->number, false);
    }
}
