<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Download;
use App\Models\GalleryImage;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Media::DISK);
        Storage::fake(Media::DOCUMENT_DISK);
    }

    private function category(): ProductCategory
    {
        return ProductCategory::firstOrCreate(
            ['slug' => 'structural'],
            ['name' => ['fa' => 'سازه‌ای', 'en' => 'Structural', 'ar' => 'إنشائية'], 'is_active' => true],
        );
    }

    public function test_an_uploaded_hero_image_is_stored_and_linked(): void
    {
        $this->actingAs($this->makeAdmin())->post('/admin/products', [
            'name' => ['fa' => 'گرید', 'en' => 'Grade', 'ar' => 'درجة'],
            'slug' => 'graded',
            'product_category_id' => $this->category()->id,
            'hero_image' => UploadedFile::fake()->image('photo.jpg', 800, 600),
            'is_active' => '1',
        ])->assertRedirect();

        $product = Product::sole();

        $this->assertStringStartsWith('/storage/media/products/', $product->hero_image);
        Storage::disk(Media::DISK)->assertExists(str_replace('/storage/media/', '', $product->hero_image));
    }

    /**
     * The stored name is generated, never the client's — otherwise an editor
     * could overwrite an existing asset just by naming a file the same thing.
     */
    public function test_the_stored_filename_is_generated_not_taken_from_the_upload(): void
    {
        $this->actingAs($this->makeAdmin())->post('/admin/products', [
            'name' => ['fa' => 'گرید', 'en' => 'Grade', 'ar' => 'درجة'],
            'slug' => 'graded',
            'product_category_id' => $this->category()->id,
            'hero_image' => UploadedFile::fake()->image('../../evil name.jpg'),
            'is_active' => '1',
        ]);

        $stored = Product::sole()->hero_image;

        $this->assertStringNotContainsString('evil', $stored);
        $this->assertStringNotContainsString('..', $stored);
        // `<32 random chars>-<width>x<height>.<sniffed ext>`. The dimensions are
        // written by the optimisation pass and read back by the templates.
        $this->assertMatchesRegularExpression('#^/storage/media/products/[A-Za-z0-9]{32}-\d+x\d+\.jpg$#', $stored);
    }

    public function test_a_non_image_upload_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin())->post('/admin/products', [
            'name' => ['fa' => 'گرید', 'en' => 'Grade', 'ar' => 'درجة'],
            'slug' => 'graded',
            'product_category_id' => $this->category()->id,
            'hero_image' => UploadedFile::fake()->create('payload.php', 8, 'application/x-php'),
            'is_active' => '1',
        ])->assertSessionHasErrors('hero_image');

        $this->assertDatabaseCount('products', 0);
    }

    /** Saving a form without touching the file input must not clear the file. */
    public function test_saving_without_a_new_file_keeps_the_existing_one(): void
    {
        $product = $this->makeProduct(['hero_image' => '/storage/media/products/existing.webp']);

        $this->actingAs($this->makeAdmin())->put("/admin/products/{$product->slug}", [
            'name' => ['fa' => 'لیکا ۴–۱۰', 'en' => 'Leca 4–10', 'ar' => 'ليكا'],
            'slug' => $product->slug,
            'product_category_id' => $product->product_category_id,
            'is_active' => '1',
        ]);

        $this->assertSame('/storage/media/products/existing.webp', $product->fresh()->hero_image);
    }

    public function test_the_clear_checkbox_removes_the_file(): void
    {
        $product = $this->makeProduct(['hero_image' => '/storage/media/products/existing.webp']);

        $this->actingAs($this->makeAdmin())->put("/admin/products/{$product->slug}", [
            'name' => ['fa' => 'لیکا ۴–۱۰', 'en' => 'Leca 4–10', 'ar' => 'ليكا'],
            'slug' => $product->slug,
            'product_category_id' => $product->product_category_id,
            'hero_image_clear' => '1',
            'is_active' => '1',
        ]);

        $this->assertNull($product->fresh()->hero_image);
    }

    public function test_gallery_uploads_append_and_can_be_removed_individually(): void
    {
        $product = $this->makeProduct(['gallery' => ['/storage/media/products/one.webp']]);
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put("/admin/products/{$product->slug}", [
            'name' => ['fa' => 'لیکا ۴–۱۰', 'en' => 'Leca 4–10', 'ar' => 'ليكا'],
            'slug' => $product->slug,
            'product_category_id' => $product->product_category_id,
            'gallery' => [UploadedFile::fake()->image('two.jpg')],
            'is_active' => '1',
        ]);

        $this->assertCount(2, $product->fresh()->gallery);

        $this->actingAs($admin)->put("/admin/products/{$product->slug}", [
            'name' => ['fa' => 'لیکا ۴–۱۰', 'en' => 'Leca 4–10', 'ar' => 'ليكا'],
            'slug' => $product->slug,
            'product_category_id' => $product->product_category_id,
            'gallery_remove' => ['/storage/media/products/one.webp'],
            'is_active' => '1',
        ]);

        $gallery = $product->fresh()->gallery;

        $this->assertCount(1, $gallery);
        $this->assertNotContains('/storage/media/products/one.webp', $gallery);
    }

    /** Documents belong on the private disk, never under public/. */
    public function test_a_document_is_stored_privately_with_its_size_recorded(): void
    {
        $this->actingAs($this->makeAdmin())->post('/admin/downloads', [
            'title' => ['fa' => 'کاتالوگ', 'en' => 'Catalogue', 'ar' => 'كتالوج'],
            'slug' => 'catalogue',
            'category' => 'catalogue',
            'file_path' => UploadedFile::fake()->create('catalogue.pdf', 120, 'application/pdf'),
            'is_active' => '1',
        ])->assertRedirect();

        $download = Download::sole();

        $this->assertStringNotContainsString('/storage/', $download->file_path);
        $this->assertSame('pdf', $download->file_extension);
        $this->assertGreaterThan(0, $download->file_size);

        Storage::disk(Media::DOCUMENT_DISK)->assertExists($download->file_path);
        Storage::disk(Media::DISK)->assertMissing($download->file_path);
    }

    public function test_a_download_cannot_be_created_without_a_file(): void
    {
        $this->actingAs($this->makeAdmin())->post('/admin/downloads', [
            'title' => ['fa' => 'کاتالوگ', 'en' => 'Catalogue', 'ar' => 'كتالوج'],
            'slug' => 'catalogue',
            'category' => 'catalogue',
            'is_active' => '1',
        ])->assertSessionHasErrors('file_path');
    }

    public function test_a_gallery_image_requires_alt_text(): void
    {
        $this->actingAs($this->makeAdmin())->post('/admin/gallery', [
            'path' => UploadedFile::fake()->image('kiln.jpg'),
            'alt' => ['fa' => '', 'en' => '', 'ar' => ''],
            'album' => 'plant',
            'is_active' => '1',
        ])->assertSessionHasErrors('alt.fa');

        $this->assertDatabaseCount('gallery_images', 0);
    }

    public function test_a_gallery_image_is_created_with_alt_text(): void
    {
        $this->actingAs($this->makeAdmin())->post('/admin/gallery', [
            'path' => UploadedFile::fake()->image('kiln.jpg'),
            'alt' => ['fa' => 'کوره دوار', 'en' => 'Rotary kiln', 'ar' => 'فرن'],
            'album' => 'plant',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertStringStartsWith('/storage/media/gallery/', GalleryImage::sole()->path);
    }
}
