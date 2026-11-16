<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use App\Support\Navigation;

/**
 * The shop ships switched off, and off means absent: no page, no menu link,
 * no basket in the header, no buy box, no price in the structured data.
 */
class ShopVisibilityTest extends ShopTestCase
{
    public function test_a_closed_shop_is_not_there(): void
    {
        $this->variant();

        $this->get('/fa/shop')->assertNotFound();
        $this->get('/fa/cart')->assertNotFound();
        $this->get('/fa/checkout')->assertNotFound();

        $this->get('/fa')->assertOk()->assertDontSee('data-cart-link', false)->assertDontSee('/fa/shop', false);
        $this->get('/fa/products/leca-4-10')->assertOk()
            ->assertDontSee('id="buy"', false)
            ->assertDontSee('AggregateOffer', false);
    }

    public function test_an_open_shop_lists_what_is_on_sale(): void
    {
        $this->openShop();
        $this->variant();

        $this->get('/fa/shop')->assertOk()
            ->assertSee('کیسه ۵۰ لیتری')
            ->assertSee('۲۵۰٬۰۰۰', false);

        $this->get('/fa')->assertSee('data-cart-link', false)->assertSee('/fa/shop', false);
    }

    public function test_every_language_has_the_shop(): void
    {
        $this->openShop();
        $this->variant();

        foreach (['fa', 'en', 'ar', 'ru', 'ku'] as $locale) {
            $this->get("/{$locale}/shop")->assertOk()->assertSee(__('shop.add_to_cart', [], $locale));
        }

        // Iranian cards only — said to everyone who might not have one.
        $this->get('/en/shop')->assertSee(__('shop.foreign_note', [], 'en'));
        $this->get('/fa/shop')->assertDontSee(__('shop.foreign_note', [], 'fa'));
    }

    public function test_the_product_page_sells_and_still_quotes(): void
    {
        $this->openShop();
        $this->variant();

        $html = $this->get('/fa/products/leca-4-10')->assertOk()->getContent();

        $this->assertStringContainsString('id="buy"', $html);
        $this->assertStringContainsString('/fa/quote?product=leca-4-10', $html);

        // In rials, the ISO currency: 250,000 toman.
        $this->assertStringContainsString('"priceCurrency":"IRR"', $html);
        $this->assertStringContainsString('"lowPrice":"2500000"', $html);
    }

    public function test_what_is_not_for_sale_is_not_shown(): void
    {
        $this->openShop();
        $this->variant(['is_active' => false]);
        $this->variant(['price' => 0, 'sku' => 'zero']);
        $this->variant(['stock' => 5, 'sku' => 'short']);   // fewer than the minimum of 10

        $this->get('/fa/shop')->assertOk()->assertDontSee('name="variant"', false);
        $this->get('/fa/products/leca-4-10')->assertDontSee('id="buy"', false);
    }

    public function test_a_hidden_product_takes_its_variants_with_it(): void
    {
        $this->openShop();
        $variant = $this->variant();
        $variant->product->update(['is_active' => false]);

        $this->get('/fa/shop')->assertDontSee('name="variant"', false);
        $this->post('/fa/cart', ['variant' => $variant->id, 'quantity' => 10])->assertSessionHasErrors('cart');
    }

    public function test_the_sitemap_lists_the_shop_only_while_it_is_open(): void
    {
        $this->get('/sitemap.xml')->assertDontSee('/fa/shop');

        $this->openShop();
        Navigation::flush();

        $this->get('/sitemap.xml')->assertSee('/fa/shop');
    }

    /** Order pages carry a private token; nothing about them belongs in an index. */
    public function test_private_shop_pages_are_kept_out_of_crawlers(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->get('/robots.txt')
            ->assertSee('Disallow: /*/orders/')
            ->assertSee('Disallow: /*/checkout');
    }
}
