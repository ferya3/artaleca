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

        foreach (['media|hero', 'media|quality_lab', 'media|plant_exterior', 'media|kiln', 'media|screening'] as $field) {
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
