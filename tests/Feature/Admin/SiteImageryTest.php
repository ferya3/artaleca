<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The photographs on the designed pages — hero, plant, kiln — used to be fixed
 * in the templates, so changing one meant a deploy. They are settings now.
 */
class SiteImageryTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    private function admin(): User
    {
        return User::factory()->create([
            'email' => 'imagery'.(++self::$sequence).'@artaleca.com',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_the_settings_screen_offers_every_site_image(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/settings')->assertOk()->getContent();

        foreach (['media|hero', 'media|applications_infographic', 'media|quality_lab', 'media|plant_exterior', 'media|kiln', 'media|screening'] as $field) {
            $this->assertStringContainsString($field, $html, "The settings form is missing {$field}.");
        }
    }

    public function test_an_administrator_can_upload_the_hero_image(): void
    {
        Storage::fake('media');

        $this->actingAs($this->admin())
            ->from('/admin/settings')
            ->put('/admin/settings', ['media|hero' => UploadedFile::fake()->image('hero.jpg', 2000, 1500)])
            ->assertRedirect('/admin/settings');

        $stored = Setting::get('media.hero');

        $this->assertNotNull($stored, 'The uploaded hero image was not stored as a setting.');
        $this->assertStringStartsWith('/storage/media/', $stored);
    }

    /**
     * The point of the whole exercise: an uploaded image has to reach the page,
     * carrying the responsive set and the intrinsic size the pipeline gives it.
     */
    public function test_an_uploaded_hero_reaches_the_home_page(): void
    {
        Storage::fake('media');

        $this->actingAs($this->admin())
            ->put('/admin/settings', ['media|hero' => UploadedFile::fake()->image('hero.jpg', 2000, 1500)]);

        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('-2000x1500.jpg', $html);
        $this->assertStringContainsString('-2000x1500-768.webp', $html);
        $this->assertStringContainsString('width="2000" height="1500"', $html);
        $this->assertStringContainsString('rel="preload" as="image"', $html);
    }

    /**
     * The two heroes are separate blocks, not one responsive element.
     *
     * They want opposite things — a full-bleed backdrop with the copy on top,
     * against a photograph beside the copy — and every attempt to make one
     * element do both ended with the image's own aspect ratio deciding the
     * layout's width. Only one block is ever displayed, so each carries its own
     * preload scoped to the viewport it serves.
     */
    public function test_the_two_heroes_are_separate_blocks_each_preloading_its_own_image(): void
    {
        Storage::fake('media');

        $this->actingAs($this->admin())->put('/admin/settings', [
            'media|hero' => UploadedFile::fake()->image('desktop.jpg', 2400, 1600),
            'media|hero_mobile' => UploadedFile::fake()->image('mobile.jpg', 1200, 1600),
        ]);

        $html = $this->get('/fa')->assertOk()->getContent();

        // One block for phones, one for everything above `md`.
        $this->assertStringContainsString('md:hidden', $html);
        $this->assertStringContainsString('hidden overflow-hidden bg-ink-950 text-white md:block', $html);

        $this->assertStringContainsString('-1200x1600-', $html);
        $this->assertStringContainsString('-2400x1600-', $html);

        // Two preloads, each gated, so neither viewport fetches the other's.
        $this->assertSame(2, substr_count($html, 'rel="preload" as="image"'));
        $this->assertStringContainsString('media="(max-width: 767.98px)"', $html);
        $this->assertStringContainsString('media="(min-width: 768px)"', $html);
    }

    /** With no mobile image set, the phone block falls back to the desktop one. */
    public function test_the_phone_hero_falls_back_to_the_desktop_image(): void
    {
        Storage::fake('media');

        $this->actingAs($this->admin())->put('/admin/settings', [
            'media|hero' => UploadedFile::fake()->image('desktop.jpg', 2400, 1600),
        ]);

        $html = $this->get('/fa')->assertOk()->getContent();

        // The same photograph is used by both blocks, so its srcset appears in
        // each of them rather than the phone falling back to the placeholder.
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($html, '-2400x1600-480.webp'),
            'The phone hero should reuse the desktop image when no mobile one is set.',
        );
        $this->assertSame(2, substr_count($html, 'rel="preload" as="image"'));
    }

    /**
     * The applications infographic must reach the page *uncropped*.
     *
     * Every other photograph on the site is `object-cover` inside a fixed
     * ratio; an infographic carries text, so cropping it would cut the content
     * off. It keeps its own ratio and takes the height the artwork asks for —
     * with `width`/`height` still on the tag so nothing below it shifts.
     */
    public function test_the_applications_infographic_reaches_the_home_page_uncropped(): void
    {
        Storage::fake('media');

        $this->actingAs($this->admin())->put('/admin/settings', [
            'media|applications_infographic' => UploadedFile::fake()->image('info.jpg', 1600, 2200),
        ]);

        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('-1600x2200.jpg', $html);
        $this->assertStringContainsString('-1600x2200-1024.webp', $html);
        $this->assertStringContainsString('width="1600" height="2200"', $html);
        // `h-auto w-full`, not `object-cover` inside a ratio box: the artwork
        // decides the height, so none of it is cut off.
        $this->assertStringContainsString('class="h-auto w-full"', $html);
    }

    /** A phone can be given its own, taller version of the same infographic. */
    public function test_the_infographic_takes_a_separate_mobile_version(): void
    {
        Storage::fake('media');

        $this->actingAs($this->admin())->put('/admin/settings', [
            'media|applications_infographic' => UploadedFile::fake()->image('wide.jpg', 1600, 900),
            'media|applications_infographic_mobile' => UploadedFile::fake()->image('tall.jpg', 800, 1800),
        ]);

        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('-800x1800-', $html);
        $this->assertStringContainsString('-1600x900-', $html);

        // Art direction, not resolution switching: the phone must be able to
        // fetch the tall version and nothing else.
        $this->assertMatchesRegularExpression(
            '/<source media="\(max-width: 767\.98px\)"[^>]*-800x1800-/',
            $html,
        );

        /*
         * And the art-directed source must declare its own intrinsic size.
         *
         * `width`/`height` on the <img> describe the desktop photograph, and
         * the browser reserves that ratio before it knows which source it will
         * take — so the taller phone crop pushed the whole page down when it
         * arrived. Measured on a 390px screen: a box reserved at 348x218 that
         * settled at 348x618.
         */
        $this->assertMatchesRegularExpression(
            '/<source media="\(max-width: 767\.98px\)"[^>]*width="800" height="1800"/',
            $html,
            'The phone source must carry its own dimensions, or the page shifts when it loads.',
        );
    }

    /** The heading stands whether or not an infographic has been uploaded. */
    public function test_the_applications_section_holds_its_space_before_an_upload(): void
    {
        $this->assertNull(Setting::get('media.applications_infographic'));

        $this->get('/fa')
            ->assertOk()
            ->assertSee(__('home.applications_title'))
            ->assertSee('<svg viewBox="0 0 400 300"', false);
    }

    /** The cue is a real link, so it works with no script and can be tabbed to. */
    public function test_the_scroll_cue_links_to_the_section_below_the_hero(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('class="scroll-cue', $html);
        $this->assertStringContainsString('href="#intro"', $html);
        $this->assertStringContainsString('id="intro"', $html);
    }

    /** An unset image must fall back to the placeholder, never to a broken one. */
    public function test_an_unset_image_falls_back_to_the_generated_placeholder(): void
    {
        $this->assertNull(Setting::get('media.kiln'));

        $this->get('/fa/about/plant')
            ->assertOk()
            // The deterministic granule field, not an <img> with an empty src.
            ->assertSee('<svg viewBox="0 0 400 300"', false)
            ->assertDontSee('<img src=""', false);
    }

    /** A viewer must not be able to replace the site's imagery. */
    public function test_a_viewer_cannot_change_site_imagery(): void
    {
        $viewer = User::factory()->create([
            'email' => 'viewer'.(++self::$sequence).'@artaleca.com',
            'role' => User::ROLE_VIEWER,
            'is_active' => true,
        ]);

        $this->actingAs($viewer)->get('/admin/settings')->assertForbidden();
    }
}
