<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleRoutingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Note the explicit empty header: the test client's underlying
     * Request::create() supplies `en-us,en;q=0.5` of its own accord, so a test
     * that sent nothing would actually be testing English negotiation.
     */
    public function test_root_redirects_to_the_default_locale_when_no_language_is_requested(): void
    {
        $this->get('/', ['Accept-Language' => ''])->assertRedirect('/fa');
    }

    public function test_root_honours_the_accept_language_header(): void
    {
        $this->get('/', ['Accept-Language' => 'ar-SA,ar;q=0.9,en;q=0.5'])->assertRedirect('/ar');
        $this->get('/', ['Accept-Language' => 'en-GB,en;q=0.9'])->assertRedirect('/en');
    }

    public function test_an_unknown_language_falls_back_to_the_default(): void
    {
        $this->get('/', ['Accept-Language' => 'de-DE,de;q=0.9'])->assertRedirect('/fa');
    }

    /** A mixed header must pick the highest-quality *supported* language. */
    public function test_negotiation_ignores_unsupported_languages_ranked_higher(): void
    {
        $this->get('/', ['Accept-Language' => 'de;q=1.0,ar;q=0.9,en;q=0.8'])->assertRedirect('/ar');
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

        $this->get("/fa/products/{$product->category->slug}/{$product->slug}")
            ->assertOk()
            ->assertSee('لیکا ۴–۱۰');
    }
}
