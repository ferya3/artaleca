<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Support\SiteImage;
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

    public function test_the_imagery_screen_offers_every_site_image(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/site-images')->assertOk()->getContent();

        foreach (['media|hero', 'media|applications_infographic', 'media|quality_lab', 'media|plant_exterior', 'media|kiln', 'media|screening'] as $field) {
            $this->assertStringContainsString($field, $html, "The imagery screen is missing {$field}.");
        }
    }

    public function test_an_administrator_can_upload_the_hero_image(): void
    {
        Storage::fake('media');

        $this->actingAs($this->admin())
            ->from('/admin/site-images')
            ->put('/admin/site-images', ['media|hero' => ['fa' => ['light' => UploadedFile::fake()->image('hero.jpg', 2000, 1500)]]])
            ->assertRedirect('/admin/site-images');

        $stored = SiteImage::get('media.hero');

        $this->assertNotNull($stored['light'], 'The uploaded hero image was not stored as a setting.');
        $this->assertStringStartsWith('/storage/media/', $stored['light']);

        // Night falls back to day, so one upload still covers both themes.
        $this->assertSame($stored['light'], $stored['dark']);
    }

    /**
     * The point of the whole exercise: an uploaded image has to reach the page,
     * carrying the responsive set and the intrinsic size the pipeline gives it.
     */
    public function test_an_uploaded_hero_reaches_the_home_page(): void
    {
        Storage::fake('media');

        $this->actingAs($this->admin())
            ->put('/admin/site-images', ['media|hero' => ['fa' => ['light' => UploadedFile::fake()->image('hero.jpg', 2000, 1500)]]]);

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

        $this->actingAs($this->admin())->put('/admin/site-images', [
            'media|hero' => ['fa' => ['light' => UploadedFile::fake()->image('desktop.jpg', 2400, 1600)]],
            'media|hero_mobile' => ['fa' => ['light' => UploadedFile::fake()->image('mobile.jpg', 1200, 1600)]],
        ]);

        $html = $this->get('/fa')->assertOk()->getContent();

        // One block for phones, one for everything above `md`.
        $this->assertStringContainsString('md:hidden', $html);
        $this->assertStringContainsString('hidden overflow-hidden bg-night-950 text-white md:block', $html);

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

        $this->actingAs($this->admin())->put('/admin/site-images', [
            'media|hero' => ['fa' => ['light' => UploadedFile::fake()->image('desktop.jpg', 2400, 1600)]],
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

        $this->actingAs($this->admin())->put('/admin/site-images', [
            'media|applications_infographic' => ['fa' => ['light' => UploadedFile::fake()->image('info.jpg', 1600, 2200)]],
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

        $this->actingAs($this->admin())->put('/admin/site-images', [
            'media|applications_infographic' => ['fa' => ['light' => UploadedFile::fake()->image('wide.jpg', 1600, 900)]],
            'media|applications_infographic_mobile' => ['fa' => ['light' => UploadedFile::fake()->image('tall.jpg', 800, 1800)]],
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

    /**
     * An image can carry text, so it is uploaded per language.
     *
     * This was invisible because nothing in the pipeline treats a picture as
     * copy: an infographic lettered in Persian was served to the English site
     * exactly as a Persian paragraph would have been, and no test or type
     * could tell the difference.
     */
    public function test_each_language_serves_its_own_artwork(): void
    {
        Storage::fake('media');

        $this->actingAs($this->admin())->put('/admin/site-images', [
            'media|applications_infographic' => [
                'fa' => ['light' => UploadedFile::fake()->image('fa.jpg', 1600, 1000)],
                'en' => ['light' => UploadedFile::fake()->image('en.jpg', 1500, 1000)],
                'ar' => ['light' => UploadedFile::fake()->image('ar.jpg', 1400, 1000)],
            ],
        ]);

        $this->get('/fa')->assertOk()->assertSee('-1600x1000-', false)->assertDontSee('-1500x1000-', false);
        $this->get('/en')->assertOk()->assertSee('-1500x1000-', false)->assertDontSee('-1600x1000-', false);
        $this->get('/ar')->assertOk()->assertSee('-1400x1000-', false)->assertDontSee('-1600x1000-', false);
    }

    /**
     * A language with no artwork of its own shows the default one.
     *
     * A photograph that says nothing needs one upload, not three, and a missing
     * image is a worse outcome than a shared one.
     */
    public function test_a_language_without_its_own_image_falls_back_to_the_default_one(): void
    {
        Storage::fake('media');

        $this->actingAs($this->admin())->put('/admin/site-images', [
            'media|applications_infographic' => ['fa' => ['light' => UploadedFile::fake()->image('fa.jpg', 1600, 1000)]],
        ]);

        $this->get('/en')->assertOk()->assertSee('-1600x1000-', false);
    }

    /** Uploading for one language must not disturb the others. */
    public function test_uploading_one_language_leaves_the_rest_alone(): void
    {
        Storage::fake('media');
        $admin = $this->admin();

        $this->actingAs($admin)->put('/admin/site-images', [
            'media|applications_infographic' => ['fa' => ['light' => UploadedFile::fake()->image('fa.jpg', 1600, 1000)]],
        ]);

        $this->actingAs($admin)->put('/admin/site-images', [
            'media|applications_infographic' => ['en' => ['light' => UploadedFile::fake()->image('en.jpg', 1500, 1000)]],
        ]);

        $stored = SiteImage::get('media.applications_infographic', 'fa');

        $this->assertStringContainsString('-1600x1000.jpg', (string) $stored['light']);
        $this->get('/en')->assertOk()->assertSee('-1500x1000-', false);
    }

    /**
     * A value stored before images were per-language stood for every language,
     * so it must keep doing so rather than vanishing from the site.
     */
    public function test_an_image_stored_as_a_plain_string_still_serves_every_language(): void
    {
        Setting::put('media.applications_infographic', '/storage/media/settings/legacy-1600x1000.jpg', 'media', false);

        foreach (['fa', 'en', 'ar'] as $locale) {
            $this->get('/'.$locale)->assertOk()->assertSee('legacy-1600x1000.jpg', false);
        }
    }

    /**
     * Artwork with a background of its own needs one file per theme.
     *
     * An infographic lettered on white is unreadable on a dark page for the
     * same reason a Persian one is unreadable in English: the picture carries
     * something the theme has an opinion about. Both files go into the page and
     * CSS shows one — not `<source media="(prefers-color-scheme: dark)">`,
     * which can only see the operating system and would ignore the site's own
     * toggle.
     */
    public function test_a_night_version_reaches_the_page_alongside_the_day_one(): void
    {
        Storage::fake('media');

        $this->actingAs($this->admin())->put('/admin/site-images', [
            'media|applications_infographic' => ['fa' => [
                'light' => UploadedFile::fake()->image('day.jpg', 1600, 1000),
                'dark' => UploadedFile::fake()->image('night.jpg', 1500, 1000),
            ]],
        ]);

        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('-1600x1000-', $html);
        $this->assertStringContainsString('-1500x1000-', $html);
        $this->assertStringContainsString('theme-only-light', $html);
        $this->assertStringContainsString('theme-only-dark', $html);
    }

    /**
     * One upload still means one image on the page.
     *
     * Most of these are photographs, which have no opinion about the theme.
     * Rendering both halves for them would double the markup and the bytes for
     * nothing, so the pair only appears when the two files actually differ.
     */
    public function test_a_single_upload_renders_one_image_and_no_theme_pair(): void
    {
        Storage::fake('media');

        $this->actingAs($this->admin())->put('/admin/site-images', [
            'media|hero' => ['fa' => ['light' => UploadedFile::fake()->image('hero.jpg', 2400, 1600)]],
        ]);

        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringNotContainsString('theme-only-', $html);
        $this->assertSame(2, substr_count($html, 'rel="preload" as="image"'));
    }

    /** Each half of a themed hero preloads only for the scheme that shows it. */
    public function test_a_themed_hero_scopes_its_preloads_by_colour_scheme(): void
    {
        Storage::fake('media');

        $this->actingAs($this->admin())->put('/admin/site-images', [
            'media|hero' => ['fa' => [
                'light' => UploadedFile::fake()->image('day.jpg', 2400, 1600),
                'dark' => UploadedFile::fake()->image('night.jpg', 2200, 1600),
            ]],
        ]);

        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('(min-width: 768px) and (prefers-color-scheme: light)', $html);
        $this->assertStringContainsString('(min-width: 768px) and (prefers-color-scheme: dark)', $html);
    }

    /** Uploading a night version must not disturb the day one. */
    public function test_uploading_one_theme_leaves_the_other_alone(): void
    {
        Storage::fake('media');
        $admin = $this->admin();

        $this->actingAs($admin)->put('/admin/site-images', [
            'media|applications_infographic' => ['fa' => ['light' => UploadedFile::fake()->image('day.jpg', 1600, 1000)]],
        ]);

        $this->actingAs($admin)->put('/admin/site-images', [
            'media|applications_infographic' => ['fa' => ['dark' => UploadedFile::fake()->image('night.jpg', 1500, 1000)]],
        ]);

        $stored = SiteImage::get('media.applications_infographic', 'fa');

        $this->assertStringContainsString('-1600x1000.jpg', (string) $stored['light']);
        $this->assertStringContainsString('-1500x1000.jpg', (string) $stored['dark']);
    }

    /** Site imagery is off the settings screen entirely, not duplicated on it. */
    public function test_the_settings_screen_no_longer_carries_the_imagery(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/settings')->assertOk()->getContent();

        $this->assertStringNotContainsString('media|hero', $html);
        $this->assertStringNotContainsString('type="file"', $html);
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

        $this->actingAs($viewer)->get('/admin/site-images')->assertForbidden();
    }
}
