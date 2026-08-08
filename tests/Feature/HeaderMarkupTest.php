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

    /**
     * The mobile row is a three-column grid so the logo is optically centred
     * rather than merely placed after the menu button. Grid columns follow the
     * writing direction on their own, which is what puts the button on the
     * right in Persian and Arabic and on the left in English without a single
     * direction-specific class — so the guard is that no such class appears.
     */
    public function test_the_mobile_header_centres_the_logo_without_direction_specific_classes(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('grid-cols-[2.75rem_1fr_2.75rem]', $html);
        $this->assertStringContainsString('justify-self-center', $html);

        // The menu button must precede the logo in source order, or the grid
        // places it in the centre column.
        $row = substr($html, (int) strpos($html, 'grid-cols-[2.75rem_1fr_2.75rem]'));
        $this->assertLessThan(
            (int) strpos($row, 'justify-self-center'),
            (int) strpos($row, 'data-mobile-menu'),
            'The menu button must come before the logo for the grid to centre it.',
        );
    }

    /**
     * Switching language navigates to a new URL that starts at the top, where
     * the header would hide itself — from inside the header the visitor just
     * clicked. The links carry the marker app.js uses to keep it open.
     */
    public function test_language_links_are_marked_to_keep_the_header_open(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        // Once in the desktop utility strip, once in the mobile menu, for each
        // of the two languages that are not the current one, plus the current.
        $this->assertGreaterThanOrEqual(
            6,
            substr_count($html, 'data-keep-header'),
            'Every language link in both the desktop strip and the mobile menu needs the marker.',
        );
    }

    /** Company statistics are desktop-only; a phone gets the product instead. */
    public function test_the_figures_strip_is_hidden_on_phones(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<dl class="hidden grid-cols-2[^"]*md:grid\b/',
            $html,
            'The figures strip must be display:none below md, not merely visually shrunk.',
        );
    }

    /** The hero's motion layer is decorative and must not reach the a11y tree. */
    public function test_the_hero_motion_layer_is_hidden_from_assistive_technology(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('<div class="hero-motion" aria-hidden="true">', $html);
    }
}
