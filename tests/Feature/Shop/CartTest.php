<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

class CartTest extends ShopTestCase
{
    public function test_a_quantity_is_rounded_to_what_can_be_sold(): void
    {
        $this->openShop();
        $variant = $this->variant(['stock' => 40]);   // min 10, step 5

        $this->fillCart($variant, 3);
        $this->assertSame([$variant->id => 10], session('shop.cart'));

        $this->patch("/fa/cart/{$variant->id}", ['quantity' => 12]);
        $this->assertSame([$variant->id => 15], session('shop.cart'));

        // Never more than is left, still on a step.
        $this->patch("/fa/cart/{$variant->id}", ['quantity' => 999]);
        $this->assertSame([$variant->id => 40], session('shop.cart'));
    }

    public function test_the_price_is_always_read_from_the_catalogue(): void
    {
        $this->openShop();
        $variant = $this->variant();
        $this->fillCart($variant, 10);

        $variant->update(['price' => 300000]);

        $this->get('/fa/cart')->assertOk()->assertSee('۳٬۰۰۰٬۰۰۰', false);   // 10 × 300,000
    }

    public function test_an_item_taken_off_sale_leaves_the_basket(): void
    {
        $this->openShop();
        $variant = $this->variant();
        $this->fillCart($variant, 10);

        $variant->update(['is_active' => false]);

        $this->get('/fa/cart')->assertOk()->assertSee(__('shop.empty', [], 'fa'));
        $this->assertSame([], session('shop.cart'));
    }

    public function test_zero_or_remove_takes_a_line_out(): void
    {
        $this->openShop();
        $a = $this->variant();
        $b = $this->variant(['sku' => 'B']);
        $this->fillCart($a, 10);
        $this->fillCart($b, 10);

        $this->patch("/fa/cart/{$a->id}", ['quantity' => 0]);
        $this->delete("/fa/cart/{$b->id}");

        $this->assertSame([], session('shop.cart'));
    }

    public function test_the_header_counts_the_lines(): void
    {
        $this->openShop();
        $this->fillCart($this->variant(), 10);

        $this->get('/fa')->assertSee(__('shop.cart_count', ['count' => 1], 'fa'));
    }
}
