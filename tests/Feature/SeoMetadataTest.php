<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SeoMetadataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The SEO title and description fields on every record the panel can edit.
 *
 * Nothing breaks when they are empty — each controller falls back to the
 * record's own name and summary — which is exactly why they stayed empty on
 * all thirty-one records without anyone noticing. A fallback title is the name
 * of the thing; a search result wants the phrase somebody would type.
 */
class SeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    private const MODELS = [
        Product::class,
        ProductCategory::class,
        Application::class,
        Project::class,
        Page::class,
        Post::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_every_record_carries_metadata_in_every_language(): void
    {
        foreach (self::MODELS as $class) {
            foreach ($class::all() as $record) {
                foreach (['fa', 'en', 'ar'] as $locale) {
                    foreach (['meta_title', 'meta_description'] as $field) {
                        $this->assertNotEmpty(
                            $record->getTranslation($field, $locale),
                            class_basename($class)." {$record->slug} has no {$field} in {$locale}.",
                        );
                    }
                }
            }
        }
    }

    /**
     * Seo truncates a description at 158 characters and search results cut a
     * title around 70. Both limits are invisible in the panel, so the text
     * looks finished in the form and arrives cut on the page.
     */
    public function test_nothing_is_written_past_the_cut(): void
    {
        foreach (self::MODELS as $class) {
            foreach ($class::all() as $record) {
                foreach (['fa', 'en', 'ar'] as $locale) {
                    foreach (['meta_title' => 70, 'meta_description' => 158] as $field => $max) {
                        $value = (string) $record->getTranslation($field, $locale);

                        $this->assertLessThanOrEqual(
                            $max,
                            mb_strlen($value),
                            class_basename($class)." {$record->slug} {$field} in {$locale} is "
                            .mb_strlen($value)." characters, over {$max}.",
                        );
                    }
                }
            }
        }
    }

    /**
     * Persian numerals are written literally in the seeder, because the digit
     * middleware rewrites text between tags and a meta description sits inside
     * one. A Latin digit typed there reaches Google as a Latin digit, on a page
     * where every other number is Persian.
     */
    public function test_persian_metadata_uses_persian_numerals(): void
    {
        foreach (self::MODELS as $class) {
            foreach ($class::all() as $record) {
                foreach (['meta_title', 'meta_description'] as $field) {
                    $this->assertDoesNotMatchRegularExpression(
                        '/\d/',
                        (string) $record->getTranslation($field, 'fa'),
                        class_basename($class)." {$record->slug} {$field} has a Latin digit in the Persian text; "
                        .'the digit middleware cannot reach inside a meta tag.',
                    );
                }
            }
        }
    }

    /** The written title is what the page serves, not the record's name. */
    public function test_the_metadata_is_what_the_page_renders(): void
    {
        $product = Product::where('slug', 'leca-structure-4-10')->firstOrFail();

        $html = $this->get('/fa/products/leca-structure-4-10')->assertOk()->getContent();

        $this->assertStringContainsString($product->getTranslation('meta_title', 'fa'), $html);
        $this->assertStringContainsString($product->getTranslation('meta_description', 'fa'), $html);
    }

    /** An editor's own wording survives a re-run. */
    public function test_reseeding_never_overwrites_what_an_editor_wrote(): void
    {
        $product = Product::where('slug', 'leca-structure-4-10')->firstOrFail();
        $product->setTranslation('meta_title', 'fa', 'عنوان دست‌نویس مدیر')->save();

        $this->seed(SeoMetadataSeeder::class);

        $this->assertSame('عنوان دست‌نویس مدیر', $product->fresh()->getTranslation('meta_title', 'fa'));
    }

    /** A record the seeder has no entry for must not stop the run. */
    public function test_an_unknown_slug_is_skipped_rather_than_fatal(): void
    {
        Product::where('slug', 'leca-structure-4-10')->firstOrFail()->delete();

        $this->seed(SeoMetadataSeeder::class);

        $this->assertTrue(true, 'The seeder ran to completion with a record missing.');
    }
}
