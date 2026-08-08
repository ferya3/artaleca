<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderMarkupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The mobile menu regression, guarded at its actual cause.
     *
     * `backdrop-filter` makes an element a containing block for its
     * `position: fixed` descendants. With it on the header, the full-screen
     * menu inside resolved `top-16 bottom-0` against a 64px-tall header and
     * collapsed to zero height — the <details> opened correctly and reported
     * `open`, but nothing was visible. Nothing about the failure looked like a
     * CSS bug, which is exactly why it needs a test rather than a comment.
     */
    public function test_the_header_carries_no_backdrop_filter(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        preg_match('/<header\b[^>]*>/', $html, $matches);
        $this->assertNotEmpty($matches, 'No <header> was rendered.');

        $this->assertStringNotContainsString(
            'backdrop-blur',
            $matches[0],
            'A backdrop-filter on the header makes it the containing block for the '
            .'fixed mobile menu, which collapses the menu to zero height.',
        );
    }

    /** The menu panel is fixed to the viewport and must stay that way. */
    public function test_the_mobile_menu_panel_is_viewport_fixed(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<details[^>]*data-mobile-menu.*?<div class="fixed inset-x-0 bottom-0 top-16/s',
            $html,
        );
    }

    /** Every primary destination is reachable without opening a submenu. */
    public function test_the_mobile_menu_lists_the_primary_navigation(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $menu = substr($html, (int) strpos($html, 'data-mobile-menu'));

        foreach ([__('nav.projects'), __('nav.about'), __('nav.contact')] as $label) {
            $this->assertStringContainsString($label, $menu);
        }
    }

    /** The hero's motion layer is decorative and must not reach the a11y tree. */
    public function test_the_hero_motion_layer_is_hidden_from_assistive_technology(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('<div class="hero-motion" aria-hidden="true">', $html);
    }
}
