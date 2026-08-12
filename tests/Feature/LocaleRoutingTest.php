<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleRoutingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The root opens in Persian, whatever the browser asks for.
     *
     * It used to negotiate `Accept-Language`, and briefly picked a language
     * from the request's country. Both were removed: choosing a language from
     * anything about the visitor means Googlebot, crawling from the United
     * States, only ever sees the English pages. `RootLocaleTest` covers the
     * rest of that behaviour.
     */
    public function test_root_always_opens_in_the_default_locale(): void
    {
        foreach (['', 'ar-SA,ar;q=0.9,en;q=0.5', 'en-GB,en;q=0.9', 'de-DE,de;q=0.9'] as $header) {
            $this->get('/', ['Accept-Language' => $header])->assertRedirect('/fa');
        }
    }

    public function test_an_unsupported_locale_prefix_is_not_routable(): void
    {
        $this->get('/de')->assertNotFound();
    }

    /**
     * The direction attribute drives every logical-property style rule, so a
     * regression here silently breaks the whole Arabic and Persian layout.
     */
    public function test_each_locale_renders_its_own_language_and_direction(): void
    {
        $this->get('/fa')->assertOk()->assertSee('<html lang="fa" dir="rtl"', false);
        $this->get('/en')->assertOk()->assertSee('<html lang="en" dir="ltr"', false);
        $this->get('/ar')->assertOk()->assertSee('<html lang="ar" dir="rtl"', false);
    }

    public function test_the_content_language_header_matches_the_locale(): void
    {
        $this->get('/ar')->assertHeader('Content-Language', 'ar');
    }

    public function test_pages_are_translated_rather_than_repeated(): void
    {
        $this->get('/fa')->assertSee('مشاهده محصولات');
        $this->get('/en')->assertSee('View the range');
        $this->get('/ar')->assertSee('تصفّح المنتجات');
    }

    /**
     * Controller actions take route parameters positionally, so the `{locale}`
     * prefix must be removed from the route before dispatch or it lands in the
     * first model argument. This is the regression test for that.
     */
    public function test_the_locale_prefix_does_not_leak_into_controller_arguments(): void
    {
        $product = $this->makeProduct();

        $this->get("/fa/products/{$product->slug}")
            ->assertOk()
            ->assertSee('لیکا ۴–۱۰');
    }
}
