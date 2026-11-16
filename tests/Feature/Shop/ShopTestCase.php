<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ShopTestCase extends TestCase
{
    use RefreshDatabase;

    protected const MERCHANT = '00000000-1111-2222-3333-444444444444';

    protected function openShop(array $settings = []): void
    {
        $settings += [
            'shop.enabled' => true,
            'shop.tax_percent' => 10,
            'shop.zarinpal_merchant' => self::MERCHANT,
            'shop.transfer_details' => "6037-9911-2233-4455\nIR12 0170 0000 0012 3456 7890 01\nArta Leca Industrial Co.",
        ];

        foreach ($settings as $key => $value) {
            Setting::put($key, $value, 'shop', false);
        }
    }

    protected function variant(array $overrides = [], ?Product $product = null): ProductVariant
    {
        $product ??= Product::query()->where('slug', 'leca-4-10')->first() ?? $this->makeProduct();

        return ProductVariant::create(array_merge([
            'product_id' => $product->id,
            'name' => ['fa' => 'کیسه ۵۰ لیتری', 'en' => '50-litre bag', 'ar' => 'كيس ٥٠ لتر', 'ru' => 'Мешок 50 л', 'ku' => 'کیسەی ٥٠ لیتری'],
            'unit' => ['fa' => 'کیسه', 'en' => 'bag', 'ar' => 'كيس', 'ru' => 'мешок', 'ku' => 'کیسە'],
            'price' => 250000,
            'min_quantity' => 10,
            'step' => 5,
            'is_active' => true,
        ], $overrides));
    }

    /** @return array<string, mixed> */
    protected function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'سعید رامین',
            'mobile' => '09121234567',
            'email' => 'buyer@example.com',
            'province' => 'تهران',
            'city' => 'تهران',
            'address' => 'خیابان آزادی، کوچه ۱۲، پلاک ۴',
            'postal_code' => '1234567890',
            'payment_method' => 'transfer',
            'shipping_method' => 'freight',
            'consent' => '1',
            'website' => '',
            'started_at' => $this->humanFormToken(),
        ], $overrides);
    }

    protected function fillCart(ProductVariant $variant, int $quantity): void
    {
        $this->post('/fa/cart', ['variant' => $variant->id, 'quantity' => $quantity])->assertRedirect();
    }
}
