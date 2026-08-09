<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Image;
use App\Support\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImagePipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
    }

    /** A real JPEG on disk — `UploadedFile::fake()->image()` produces one GD can decode. */
    private function upload(int $width, int $height, string $name = 'photo.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($name, $width, $height);
    }

    public function test_an_oversized_upload_is_capped_to_the_maximum_edge(): void
    {
        $url = Media::storeImage($this->upload(4000, 3000), 'products');

        $this->assertSame([Image::MAX_EDGE, 1800], Image::dimensions($url));
    }

    public function test_an_image_within_the_cap_keeps_its_dimensions(): void
    {
        $url = Media::storeImage($this->upload(1200, 800), 'products');

        $this->assertSame([1200, 800], Image::dimensions($url));
    }

    /**
     * The whole responsive-image story rests on this: the template derives the
     * srcset from the filename, so every width it can name must exist on disk.
     */
    public function test_every_width_the_srcset_names_exists_on_disk(): void
    {
        $url = Media::storeImage($this->upload(2000, 1500), 'products');
        $srcset = Image::srcset($url);

        $this->assertNotNull($srcset);

        preg_match_all('/(\S+) \d+w/', $srcset, $matches);
        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $candidate) {
            $this->assertTrue(
                Storage::disk('media')->exists(Str::after($candidate, '/storage/media/')),
                "The srcset names {$candidate}, which was never written.",
            );
        }
    }

    /**
     * A `srcset` descriptor is a *width*. Every derivative must therefore be
     * exactly as wide as it claims, including for portrait images — scaling by
     * the longest edge made a portrait `-480.webp` 360px across while still
     * being advertised as `480w`, so the browser picked a candidate a quarter
     * narrower than it asked for and rendered it soft. Nothing looked broken,
     * which is precisely why it needs asserting.
     *
     * @return list<array{0:int,1:int}>
     */
    public static function orientations(): array
    {
        return [
            'landscape' => [2000, 1200],
            'portrait' => [1200, 1600],
            'square' => [1400, 1400],
            'panorama' => [2400, 800],
        ];
    }

    #[DataProvider('orientations')]
    public function test_each_derivative_is_exactly_as_wide_as_its_srcset_claims(int $width, int $height): void
    {
        $url = Media::storeImage($this->upload($width, $height), 'products');

        preg_match_all('/(\S+) (\d+)w/', (string) Image::srcset($url), $matches, PREG_SET_ORDER);
        $this->assertNotEmpty($matches);

        foreach ($matches as [, $path, $claimed]) {
            $real = getimagesizefromstring(
                Storage::disk('media')->get(Str::after($path, '/storage/media/'))
            );

            $this->assertSame(
                (int) $claimed,
                $real[0],
                "{$path} is advertised as {$claimed}w but is actually {$real[0]}px wide.",
            );
        }
    }

    public function test_derivatives_are_never_larger_than_the_master(): void
    {
        $url = Media::storeImage($this->upload(900, 600), 'products');

        // 1024, 1440 and 1920 would all be upscales of a 900px master, so the
        // widths offered stop at 768 plus the master itself.
        $this->assertSame([480, 768, 900], Image::variantWidths(900));
        $this->assertStringContainsString('900w', (string) Image::srcset($url));
        $this->assertStringNotContainsString('1024w', (string) Image::srcset($url));
    }

    public function test_deleting_an_image_removes_its_derivatives(): void
    {
        $url = Media::storeImage($this->upload(2000, 1500), 'products');
        $path = Str::after($url, '/storage/media/');

        Storage::disk('media')->assertExists(Image::variantPath($path, 480));

        Media::deleteImage($url);

        Storage::disk('media')->assertMissing($path);
        Storage::disk('media')->assertMissing(Image::variantPath($path, 480));
    }

    /**
     * Re-encoding is what strips EXIF. That matters beyond bytes: phone
     * cameras write GPS coordinates into it, and an editor uploading a plant
     * photograph should not be publishing their location.
     */
    public function test_re_encoding_strips_exif_metadata(): void
    {
        $file = $this->upload(1200, 800);

        // Append an APP1/EXIF marker so there is something to survive or not.
        $marker = "\xFF\xE1\x00\x16Exif\x00\x00GPSLatitudeRef";
        file_put_contents($file->getRealPath(), file_get_contents($file->getRealPath()).$marker);

        $url = Media::storeImage($file, 'products');
        $stored = Storage::disk('media')->get(Str::after($url, '/storage/media/'));

        $this->assertStringNotContainsString('GPSLatitudeRef', $stored);
    }

    /** An animation would lose its frames on re-encode, so it is stored untouched. */
    public function test_a_gif_is_stored_without_being_re_encoded(): void
    {
        $url = Media::storeImage(UploadedFile::fake()->create('spinner.gif', 4, 'image/gif'), 'products');

        $this->assertNull(Image::dimensions($url));
        Storage::disk('media')->assertExists(Str::after($url, '/storage/media/'));
    }

    /** The filename is generated and the extension sniffed, never client-supplied. */
    public function test_the_stored_filename_is_never_the_uploaded_one(): void
    {
        $url = Media::storeImage($this->upload(800, 600, 'shell.php.jpg'), 'products');

        $this->assertStringNotContainsString('shell', $url);
        $this->assertStringNotContainsString('.php', $url);
        $this->assertStringStartsWith('/storage/media/products/', $url);
    }

    /** Legacy paths without dimensions must degrade, not point at missing files. */
    public function test_an_unprocessed_path_produces_no_srcset(): void
    {
        $this->assertNull(Image::srcset('/storage/media/products/legacy.jpg'));
        $this->assertSame('/storage/media/products/legacy.jpg', Image::thumb('/storage/media/products/legacy.jpg', 480));
    }
}
