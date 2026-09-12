<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Because slugs are shared across locales, every page's alternates differ
     * only by prefix — this asserts the whole reciprocal set is emitted.
     */
    public function test_every_page_declares_a_complete_hreflang_set(): void
    {
        $response = $this->get('/fa/about');

        $response
            ->assertSee('<link rel="alternate" hreflang="fa-IR" href="'.url('/fa/about').'">', false)
            ->assertSee('<link rel="alternate" hreflang="en" href="'.url('/en/about').'">', false)
            ->assertSee('<link rel="alternate" hreflang="ar" href="'.url('/ar/about').'">', false)
            ->assertSee('<link rel="alternate" hreflang="x-default" href="'.url('/fa/about').'">', false);
    }

    public function test_alternates_are_reciprocal_from_every_locale(): void
    {
        foreach (['fa', 'en', 'ar'] as $locale) {
            $this->get("/{$locale}/projects")
                ->assertSee('hreflang="fa-IR" href="'.url('/fa/projects').'"', false)
                ->assertSee('hreflang="en" href="'.url('/en/projects').'"', false)
                ->assertSee('hreflang="ar" href="'.url('/ar/projects').'"', false);
        }
    }

    public function test_the_canonical_url_drops_tracking_parameters(): void
    {
        $this->get('/fa/products?utm_source=newsletter&fbclid=abc')
            ->assertSee('<link rel="canonical" href="'.url('/fa/products').'">', false);
    }

    public function test_the_canonical_url_keeps_parameters_that_change_the_content(): void
    {
        $this->get('/fa/products?grain=3-10&utm_medium=email')
            ->assertSee('rel="canonical" href="'.url('/fa/products').'?grain=3-10"', false);
    }

    public function test_form_and_search_pages_are_excluded_from_the_index(): void
    {
        $this->get('/fa/quote')->assertSee('name="robots" content="noindex, follow"', false);
        $this->get('/fa/search?q=leca')->assertSee('name="robots" content="noindex, follow"', false);
    }

    public function test_content_pages_are_indexable(): void
    {
        $this->get('/fa/about')->assertSee('content="index, follow', false);
    }

    public function test_a_product_page_emits_product_and_breadcrumb_structured_data(): void
    {
        $product = $this->makeProduct();

        $response = $this->get("/fa/products/{$product->slug}");

        $response->assertSee('"@type":"Product"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
        $response->assertSee('"sku":"ALS-0410"', false);
    }

    public function test_the_homepage_emits_organization_and_website_structured_data(): void
    {
        $this->get('/fa')
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('"@type":"WebSite"', false);
    }

    public function test_the_sitemap_lists_every_locale_with_alternates(): void
    {
        $product = $this->makeProduct();

        $response = $this->get('/sitemap.xml');

        $response->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        foreach (['fa', 'en', 'ar'] as $locale) {
            $response->assertSee(
                '<loc>'.url("/{$locale}/products/{$product->slug}").'</loc>',
                false,
            );
        }

        $response->assertSee('hreflang="x-default"', false);
    }

    public function test_the_sitemap_omits_unpublished_records(): void
    {
        $product = $this->makeProduct();
        $hidden = Product::create([
            'product_category_id' => $product->product_category_id,
            'slug' => 'hidden-grade',
            'name' => ['fa' => 'پنهان', 'en' => 'Hidden', 'ar' => 'مخفي'],
            'is_active' => false,
        ]);

        $this->get('/sitemap.xml')->assertDontSee($hidden->slug);
    }

    public function test_robots_blocks_the_admin_and_points_at_the_sitemap(): void
    {
        $this->app['env'] = 'production';

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_robots_blocks_everything_outside_production(): void
    {
        $this->get('/robots.txt')->assertSee("User-agent: *\nDisallow: /");
    }

    /**
     * The two tests above pass whether or not the routes are the thing a
     * search engine actually reaches: they go through the kernel directly,
     * while nginx serves `try_files $uri` first. A file of either name sitting
     * in `public/` therefore shadows its route in production and nowhere else
     * — which is how the Laravel skeleton's own `robots.txt` ("Disallow:",
     * i.e. allow everything, no sitemap line) stayed in front of this
     * controller without a single test noticing.
     */
    public function test_no_static_file_shadows_the_generated_ones(): void
    {
        foreach (['robots.txt', 'sitemap.xml'] as $name) {
            $this->assertFileDoesNotExist(
                public_path($name),
                "public/$name would be served by the web server instead of the route that generates it.",
            );
        }
    }
}
