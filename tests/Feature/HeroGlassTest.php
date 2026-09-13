<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The desktop hero's glass panel and the photograph behind it.
 *
 * The effect only exists as a *contrast*: the picture frosted through the panel
 * and sharp around its edges. That makes three things load-bearing, and all
 * three are easy to lose in a later edit —
 *
 *  - the backdrop is rendered at all (it falls back to the hero photograph, so
 *    one upload is enough to see it),
 *  - it is decorative, because the framed copy of the same picture beside the
 *    copy already carries the description, and
 *  - it is not preloaded, because it is not what the visitor is waiting for.
 */
class HeroGlassTest extends TestCase
{
    use RefreshDatabase;

    private function hero(string $path): void
    {
        Setting::put('media.hero', ['fa' => ['light' => $path]], 'media', true);
    }

    private function desktopHero(string $html): string
    {
        // The two heroes are separate blocks. Everything from the desktop one's
        // opening tag to the end of the section is what this test is about.
        $start = strpos($html, 'md:block');
        $this->assertNotFalse($start, 'The desktop hero section is gone.');

        $start = strrpos(substr($html, 0, $start), '<section');
        $end = strpos($html, '</section>', (int) $start);

        return substr($html, (int) $start, $end - (int) $start);
    }

    public function test_the_copy_sits_on_a_glass_panel(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('hero-glass', $this->desktopHero($html));
    }

    /**
     * The one deliberate inconsistency in the site's typography, and the one
     * most likely to be tidied away by someone who has not measured it.
     *
     * Every other eyebrow is clay. Over a photograph that colour cannot hold
     * its contrast: at 11px it needs 4.5:1 and measures 1.5:1 on a bright
     * picture, and the only way to rescue it is to darken the band until the
     * photograph is gone. Both heroes therefore use white, which holds at 4.9:1
     * in that same worst case.
     */
    public function test_neither_hero_puts_the_clay_eyebrow_over_a_photograph(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertSame(
            2,
            substr_count($html, 'eyebrow text-white!'),
            'Both heroes sit over a photograph and both need the white eyebrow.',
        );

        $this->assertStringNotContainsString('eyebrow text-brand-400!', $html);
    }

    /** One upload is enough: the backdrop falls back to the hero photograph. */
    public function test_the_backdrop_falls_back_to_the_hero_photograph(): void
    {
        $this->hero('/storage/media/hero-1600x1000.jpg');

        $section = $this->desktopHero($this->get('/fa')->assertOk()->getContent());

        $this->assertSame(
            2,
            substr_count($section, 'src="/storage/media/hero-1600x1000.jpg"'),
            'The desktop hero should show the same photograph twice: framed, and frosted behind the glass.',
        );
    }

    /** And a different picture can be put back there when one is wanted. */
    public function test_a_backdrop_of_its_own_wins_over_the_fallback(): void
    {
        $this->hero('/storage/media/hero-1600x1000.jpg');
        Setting::put('media.hero_backdrop', ['fa' => ['light' => '/storage/media/behind-2560x1440.jpg']], 'media', true);

        $section = $this->desktopHero($this->get('/fa')->assertOk()->getContent());

        $this->assertStringContainsString('/storage/media/behind-2560x1440.jpg', $section);
        $this->assertStringContainsString('/storage/media/hero-1600x1000.jpg', $section);
    }

    /**
     * Decorative, and therefore silent. Both tags point at one photograph, and
     * a screen reader describing it twice is worse than not describing it.
     */
    public function test_the_backdrop_carries_no_alternative_text(): void
    {
        $this->hero('/storage/media/hero-1600x1000.jpg');

        $section = $this->desktopHero($this->get('/fa')->assertOk()->getContent());

        $this->assertStringContainsString('alt=""', $section);
    }

    /**
     * Fetched early, but the preload stays with the framed photograph. Two
     * preloads in one band do not conjure bandwidth, they split it, and the
     * picture the page is about should not be the half that waits.
     */
    public function test_the_backdrop_is_fetched_eagerly_but_never_preloaded(): void
    {
        $this->hero('/storage/media/hero-1600x1000.jpg');

        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertSame(
            1,
            substr_count($html, 'media="(min-width: 768px)"'),
            'The desktop hero should push exactly one preload — the framed photograph, not the backdrop.',
        );
    }

    /** An unset hero leaves the band as it was: motion, and no broken picture. */
    public function test_nothing_is_rendered_behind_the_glass_until_a_photograph_exists(): void
    {
        $section = $this->desktopHero($this->get('/fa')->assertOk()->getContent());

        $this->assertStringContainsString('hero-motion', $section);
        $this->assertStringNotContainsString('<img', $section);
    }

    /** The slot has to be uploadable, or the backdrop can only ever be the hero. */
    public function test_the_panel_offers_the_backdrop_as_its_own_upload(): void
    {
        $admin = \App\Models\User::factory()->create([
            'email' => 'backdrop@artaleca.com',
            'role' => \App\Models\User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $html = $this->actingAs($admin)->get('/admin/site-images')->assertOk()->getContent();

        $this->assertStringContainsString('media|hero_backdrop', $html);
        $this->assertStringContainsString(__('admin.settings_fields.media_hero_backdrop_hint'), $html);
    }
}
