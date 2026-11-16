<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use App\Jobs\SendOrderSms;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Shop\OrderStatus;
use Illuminate\Support\Facades\Queue;

class AdminShopTest extends ShopTestCase
{
    private static int $sequence = 0;

    private function staff(string $role = User::ROLE_ADMIN): User
    {
        return User::factory()->create([
            'email' => 'shop'.(++self::$sequence).'@artaleca.com',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function transferOrder(?ProductVariant $variant = null): Order
    {
        $this->openShop();
        $variant ??= $this->variant(['stock' => 100]);
        $this->fillCart($variant, 20);
        $this->post('/fa/checkout', $this->checkoutPayload());

        return Order::sole();
    }

    public function test_orders_are_listed_and_searchable(): void
    {
        $order = $this->transferOrder();
        $admin = $this->staff();

        $this->actingAs($admin)->get('/admin/orders')->assertOk()->assertSee($order->number);
        $this->actingAs($admin)->get('/admin/orders?q=09121234567')->assertSee($order->number);
        $this->actingAs($admin)->get('/admin/orders?q=nobody')->assertDontSee($order->number);
        $this->actingAs($admin)->get("/admin/orders/{$order->number}")->assertOk()->assertSee('سعید رامین');
    }

    public function test_a_confirmed_transfer_is_marked_paid_and_the_customer_told(): void
    {
        $order = $this->transferOrder();
        Queue::fake();

        $this->actingAs($this->staff(User::ROLE_VIEWER))
            ->patch("/admin/orders/{$order->number}", ['status' => 'paid'])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
        Queue::assertPushed(SendOrderSms::class, fn ($job) => $job->recipient === 'customer' && $job->event === 'paid');
    }

    public function test_only_the_next_steps_are_accepted(): void
    {
        $order = $this->transferOrder();

        $this->actingAs($this->staff())
            ->patch("/admin/orders/{$order->number}", ['status' => 'delivered'])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::AwaitingTransfer, $order->fresh()->status);
    }

    public function test_cancelling_gives_the_stock_back(): void
    {
        $variant = $this->variant(['stock' => 100]);
        $order = $this->transferOrder($variant);
        $this->assertSame(80, $variant->fresh()->stock);

        $this->actingAs($this->staff())->patch("/admin/orders/{$order->number}", ['status' => 'cancelled']);

        $this->assertSame(100, $variant->fresh()->stock);
        $this->assertNotNull($order->fresh()->cancelled_at);
    }

    public function test_an_order_cannot_be_deleted_or_created_by_hand(): void
    {
        $order = $this->transferOrder();

        $this->actingAs($this->staff())->delete("/admin/orders/{$order->number}")->assertStatus(405);
        $this->assertSame(1, Order::count());
    }

    public function test_prices_are_managed_in_the_panel(): void
    {
        $product = $this->makeProduct();
        $admin = $this->staff();

        $this->actingAs($admin)->get('/admin/product-variants/create')->assertOk();

        $this->actingAs($admin)->post('/admin/product-variants', [
            'product_id' => $product->id,
            'name' => ['fa' => 'فله — مترمکعب', 'en' => 'Loose — cubic metre'],
            'unit' => ['fa' => 'مترمکعب', 'en' => 'm³'],
            'price' => 1850000,
            'min_quantity' => 10,
            'step' => 1,
            'is_active' => '1',
        ])->assertRedirect();

        $variant = ProductVariant::sole();
        $this->assertSame(1850000, $variant->price);
        $this->assertSame('m³', $variant->getTranslation('unit', 'en'));

        $this->actingAs($admin)->get('/admin/product-variants')->assertOk()->assertSee('فله — مترمکعب');
    }

    public function test_the_shop_settings_screen_saves(): void
    {
        $admin = $this->staff();

        $this->actingAs($admin)->get('/admin/settings/shop')->assertOk()->assertSee(__('admin.orders.settings.zarinpal_merchant'));

        $this->actingAs($admin)->put('/admin/settings/shop', [
            'shop|enabled' => '1',
            'shop|tax_percent' => 10,
            'shop|zarinpal_merchant' => self::MERCHANT,
            'shop|transfer_details' => 'IR12 0170 0000 0012 3456 7890 01',
        ])->assertRedirect();

        $this->assertTrue((bool) Setting::get('shop.enabled'));
        $this->assertSame(self::MERCHANT, Setting::get('shop.zarinpal_merchant'));
        $this->get('/fa/shop')->assertOk();
    }

    public function test_the_sidebar_flags_orders_that_need_doing(): void
    {
        $this->transferOrder();

        $this->actingAs($this->staff())->get('/admin')->assertOk()
            ->assertSee(route('admin.orders.index'), false)
            ->assertSee(route('admin.product-variants.index'), false);
    }
}
