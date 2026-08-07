<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CataloguePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_catalogue_lists_published_products(): void
    {
        $product = $this->makeProduct();

        $this->get('/fa/products')
            ->assertOk()
            ->assertSee('لیکا ۴–۱۰');
    }

    public function test_the_catalogue_hides_unpublished_products(): void
    {
        $product = $this->makeProduct();
        Product::create([
            'product_category_id' => $product->product_category_id,
            'slug' => 'draft-grade',
            'name' => ['fa' => 'پیش‌نویس', 'en' => 'Draft', 'ar' => 'مسودة'],
            'is_active' => false,
        ]);

        $this->get('/fa/products')->assertDontSee('پیش‌نویس');
    }

    public function test_an_unpublished_product_page_returns_not_found(): void
    {
        $product = $this->makeProduct(['is_active' => false]);

        $this->get("/fa/products/{$product->slug}")->assertNotFound();
    }

    /**
     * The nested URL must actually mean something: a product reached through
     * the wrong category is a 404, not a duplicate page under a second URL.
     */
    public function test_a_product_is_not_reachable_under_the_wrong_category(): void
    {
        $product = $this->makeProduct();

        ProductCategory::create([
            'slug' => 'horticulture',
            'name' => ['fa' => 'باغبانی', 'en' => 'Horticulture', 'ar' => 'بستنة'],
            'is_active' => true,
        ]);

        $this->get("/fa/products/horticulture/{$product->slug}")->assertNotFound();
    }

    /** Overlap, not containment — a 4–10 grade must answer a "6 mm" question. */
    public function test_the_grain_filter_matches_overlapping_ranges(): void
    {
        $this->makeProduct();

        $this->get('/fa/products?grain=6-8')->assertSee('لیکا ۴–۱۰');
        $this->get('/fa/products?grain=20-30')->assertDontSee('لیکا ۴–۱۰');
    }

    public function test_an_invalid_sort_parameter_is_rejected(): void
    {
        $this->makeProduct();

        $this->get('/fa/products?sort=drop_table')->assertSessionHasErrors('sort');
    }

    public function test_search_matches_across_languages(): void
    {
        $this->makeProduct();

        // Searching in the Persian UI still finds the Latin SKU and name,
        // because the LIKE runs against the raw JSON column.
        $this->get('/fa/search?q=Leca')->assertSee('لیکا ۴–۱۰');
        $this->get('/fa/search?q=ALS-0410')->assertSee('لیکا ۴–۱۰');
    }

    public function test_search_needs_at_least_two_characters(): void
    {
        $this->makeProduct();

        $this->get('/fa/search?q=l')->assertOk()->assertDontSee('لیکا ۴–۱۰');
    }

    public function test_the_product_page_shows_the_technical_data(): void
    {
        $product = $this->makeProduct();

        $this->get("/fa/products/{$product->slug}")
            ->assertOk()
            ->assertSee('4–10')
            ->assertSee('320–400')
            ->assertSee('ALS-0410');
    }

    public function test_every_public_page_responds(): void
    {
        $this->makeProduct();

        $paths = [
            '/fa', '/fa/about', '/fa/about/quality', '/fa/about/plant',
            '/fa/products', '/fa/applications', '/fa/projects', '/fa/articles',
            '/fa/downloads', '/fa/faq', '/fa/contact', '/fa/quote',
            '/fa/privacy', '/fa/terms', '/fa/search',
        ];

        foreach ($paths as $path) {
            $this->get($path)->assertOk();
        }
    }
}
