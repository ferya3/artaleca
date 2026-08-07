<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSpecTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_grain_range_is_formatted_without_trailing_zeros(): void
    {
        $product = $this->makeProduct(['grain_min_mm' => 4, 'grain_max_mm' => 10]);
        $this->assertSame('4–10', $product->grainRange());

        $product = $this->makeProduct(['slug' => 'b', 'sku' => 'B', 'grain_min_mm' => 0.5, 'grain_max_mm' => 3]);
        $this->assertSame('0.5–3', $product->grainRange());
    }

    public function test_an_open_ended_grain_range_reads_as_a_bound(): void
    {
        $product = $this->makeProduct(['grain_min_mm' => 10, 'grain_max_mm' => null]);
        $this->assertSame('≥ 10', $product->grainRange());

        $product = $this->makeProduct(['slug' => 'b', 'sku' => 'B', 'grain_min_mm' => null, 'grain_max_mm' => 3]);
        $this->assertSame('≤ 3', $product->grainRange());
    }

    public function test_a_product_with_no_grain_data_reports_nothing(): void
    {
        $product = $this->makeProduct(['grain_min_mm' => null, 'grain_max_mm' => null]);

        $this->assertNull($product->grainRange());
    }

    /**
     * A specifier asking for "something around 6 mm" should be shown a 4–10
     * grade, so the filter tests for overlap rather than containment.
     */
    public function test_the_grain_filter_matches_any_overlap(): void
    {
        $this->makeProduct(['grain_min_mm' => 4, 'grain_max_mm' => 10]);

        $matches = fn (?float $min, ?float $max) => Product::query()->grainBetween($min, $max)->count();

        $this->assertSame(1, $matches(6, 8), 'a window inside the range');
        $this->assertSame(1, $matches(1, 5), 'a window overlapping the low end');
        $this->assertSame(1, $matches(9, 30), 'a window overlapping the high end');
        $this->assertSame(1, $matches(null, null), 'no filter');
        $this->assertSame(0, $matches(20, 30), 'a window entirely above the range');
        $this->assertSame(0, $matches(0, 3), 'a window entirely below the range');
    }

    public function test_the_bulk_density_range_collapses_when_only_one_bound_is_set(): void
    {
        $this->assertSame('320–400', $this->makeProduct()->bulkDensityRange());

        $product = $this->makeProduct([
            'slug' => 'b', 'sku' => 'B', 'bulk_density_min' => 300, 'bulk_density_max' => null,
        ]);
        $this->assertSame('300', $product->bulkDensityRange());
    }

    public function test_the_search_scope_escapes_like_wildcards(): void
    {
        $this->makeProduct();

        // A bare "%" must not behave as "match everything".
        $this->assertSame(0, Product::query()->search('%')->count());
        $this->assertSame(1, Product::query()->search('ALS')->count());
    }

    public function test_the_primary_image_falls_back_to_the_gallery(): void
    {
        $product = $this->makeProduct([
            'hero_image' => null,
            'gallery' => ['/media/a.webp', '/media/b.webp'],
        ]);

        $this->assertSame('/media/a.webp', $product->primaryImage());
    }
}
