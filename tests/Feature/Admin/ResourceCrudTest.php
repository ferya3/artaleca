<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ResourceCrudTest extends TestCase
{
    use RefreshDatabase;

    private function category(): ProductCategory
    {
        return ProductCategory::firstOrCreate(
            ['slug' => 'structural'],
            [
                'name' => ['fa' => 'سازه‌ای', 'en' => 'Structural', 'ar' => 'إنشائية'],
                'is_active' => true,
            ],
        );
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => ['fa' => 'گرید تازه', 'en' => 'New grade', 'ar' => 'درجة جديدة'],
            'slug' => 'new-grade',
            'product_category_id' => $this->category()->id,
            'is_active' => '1',
        ], $overrides);
    }

    public function test_a_product_is_created_with_all_three_translations(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/admin/products', $this->payload())
            ->assertRedirect();

        $product = Product::sole();

        $this->assertSame('گرید تازه', $product->getTranslation('name', 'fa'));
        $this->assertSame('New grade', $product->getTranslation('name', 'en'));
        $this->assertSame('درجة جديدة', $product->getTranslation('name', 'ar'));
    }

    /**
     * An editor working in one language must be able to save without wiping
     * the other two, so only the default locale is ever required.
     */
    public function test_only_the_default_locale_is_required(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/admin/products', $this->payload([
                'name' => ['fa' => 'فقط فارسی', 'en' => '', 'ar' => ''],
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('فقط فارسی', Product::sole()->getTranslation('name', 'fa'));
    }

    public function test_the_default_locale_is_required(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/admin/products', $this->payload([
                'name' => ['fa' => '', 'en' => 'English only', 'ar' => ''],
            ]))
            ->assertSessionHasErrors('name.fa');
    }

    /** A partly translated record should still render, not leave a hole. */
    public function test_a_missing_translation_falls_back_to_the_default_locale(): void
    {
        $this->actingAs($this->makeAdmin())->post('/admin/products', $this->payload([
            'name' => ['fa' => 'فقط فارسی', 'en' => '', 'ar' => ''],
        ]));

        $this->app->setLocale('en');
        $this->assertSame('فقط فارسی', Product::sole()->name);
    }

    public function test_a_blank_slug_is_generated_and_kept_unique(): void
    {
        $admin = $this->makeAdmin();
        $categoryId = $this->category()->id;

        $this->actingAs($admin)->post('/admin/products', [
            'name' => ['fa' => 'گرید', 'en' => 'Alpha Grade', 'ar' => 'درجة'],
            'slug' => '',
            'product_category_id' => $categoryId,
            'is_active' => '1',
        ]);

        $this->actingAs($admin)->post('/admin/products', [
            'name' => ['fa' => 'گرید', 'en' => 'Alpha Grade', 'ar' => 'درجة'],
            'slug' => '',
            'product_category_id' => $categoryId,
            'is_active' => '1',
        ]);

        $slugs = Product::pluck('slug')->all();

        $this->assertCount(2, array_unique($slugs));
    }

    public function test_a_duplicate_slug_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/admin/products', $this->payload());
        $this->actingAs($admin)->post('/admin/products', $this->payload())
            ->assertSessionHasErrors('slug');
    }

    /** Newline-separated textareas are the editor-facing form of a JSON list. */
    public function test_list_and_pair_fields_are_parsed_from_plain_text(): void
    {
        $this->actingAs($this->makeAdmin())->post('/admin/products', $this->payload([
            'standards' => "EN 13055-1\nASTM C330\n\n  \n",
            'specs' => "Sphericity | 0.85\nChloride | < 0.01%\nmalformed row",
        ]));

        $product = Product::sole();

        $this->assertSame(['EN 13055-1', 'ASTM C330'], $product->standards);
        $this->assertSame([
            ['label' => 'Sphericity', 'value' => '0.85'],
            ['label' => 'Chloride', 'value' => '< 0.01%'],
        ], $product->specs);
    }

    public function test_an_unchecked_checkbox_deactivates_the_record(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->makeAdmin())->put("/admin/products/{$product->slug}", [
            'name' => ['fa' => 'لیکا ۴–۱۰', 'en' => 'Leca 4–10', 'ar' => 'ليكا'],
            'slug' => $product->slug,
            'product_category_id' => $product->product_category_id,
        ]);

        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_saving_content_clears_the_navigation_cache(): void
    {
        $this->makeProduct();

        // Warm the cache, then confirm a save invalidates it.
        Navigation::productCategories();
        $this->assertTrue(Cache::has('nav.product-categories'));

        $this->actingAs($this->makeAdmin())->post('/admin/products', $this->payload(['slug' => 'another']));

        $this->assertFalse(Cache::has('nav.product-categories'));
    }

    /** A document path is an editor-supplied string, so it must not traverse. */
    public function test_a_traversing_document_path_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin())->post('/admin/downloads', [
            'title' => ['fa' => 'سند', 'en' => 'Doc', 'ar' => 'وثيقة'],
            'slug' => 'doc',
            'category' => 'catalogue',
            'file_path' => '../../../.env',
            'is_active' => '1',
        ])->assertSessionHasErrors('file_path');
    }
}
