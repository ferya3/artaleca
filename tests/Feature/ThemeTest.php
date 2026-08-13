<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Day/night theming.
 *
 * The palette is CSS and the choice is one attribute on <html>; almost nothing
 * about it is PHP. What is worth testing is precisely the parts that a later
 * change could quietly remove without anything looking broken in the theme it
 * was being edited in.
 */
class ThemeTest extends TestCase
{
    use RefreshDatabase;

    private function css(): string
    {
        $files = glob(public_path('build/assets/app-*.css'));

        $this->assertNotEmpty($files, 'No built stylesheet — run `npm run build`.');

        return (string) file_get_contents($files[0]);
    }

    /**
     * The theme must work with the bundle blocked.
     *
     * `prefers-color-scheme` covers the visitor who has never chosen, so the
     * script is only ever needed to *override* the system — the same fail-open
     * shape as the header. Losing the media query would make the theme depend
     * on JavaScript arriving, and nobody testing in their own browser would
     * notice.
     */
    public function test_the_system_preference_is_honoured_without_javascript(): void
    {
        $css = $this->css();

        $this->assertMatchesRegularExpression(
            '/prefers-color-scheme\s*:\s*dark/',
            $css,
            'Without the media query the dark theme only exists once a script has run.',
        );

        $this->assertMatchesRegularExpression(
            '/:not\(\[data-theme=["\']?light["\']?\]\)/',
            $css,
            'The media query must yield to an explicit choice of light, or the toggle is one-way on a dark OS.',
        );

        $this->assertMatchesRegularExpression(
            '/\[data-theme=["\']?dark["\']?\]/',
            $css,
            'An explicit choice of dark has no rule to apply.',
        );
    }

    /** The choice is applied in the head, before anything is painted. */
    public function test_a_stored_choice_is_applied_before_first_paint(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $head = strstr($html, '</head>', true);

        $this->assertIsString($head);
        $this->assertStringContainsString("localStorage.getItem('theme')", $head);
        $this->assertStringContainsString("setAttribute('data-theme', choice)", $head);
    }

    /** Every inline script carries the nonce; the policy allows nothing else. */
    public function test_the_theme_script_carries_the_csp_nonce(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        preg_match_all('/<script(?![^>]*\ssrc=)([^>]*)>/i', $html, $matches);

        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $attributes) {
            $this->assertStringContainsString(
                'nonce=',
                $attributes,
                'An inline <script> without a nonce is dropped by the policy.',
            );
        }
    }

    /** A control that can be reached and operated without a pointer. */
    #[DataProvider('locales')]
    public function test_every_locale_offers_a_theme_toggle(string $locale): void
    {
        $html = $this->get('/'.$locale)->assertOk()->getContent();

        $this->assertStringContainsString('data-theme-toggle', $html);
        $this->assertStringContainsString(__('common.theme_toggle', [], $locale), $html);

        // A real <button>, not a div dressed as one.
        $this->assertMatchesRegularExpression('/<button[^>]*data-theme-toggle/', $html);
    }

    /** @return list<array{0:string}> */
    public static function locales(): array
    {
        return [['fa'], ['en'], ['ar']];
    }

    /**
     * The blocks that are dark in *both* themes must not be built from the ink
     * ramp, because that ramp inverts. Using `bg-ink-950` for the hero would
     * turn it white under the dark theme and leave white copy on it — which is
     * invisible to anyone who only ever looks at the light theme.
     */
    public function test_the_permanently_dark_blocks_do_not_use_the_inverting_ramp(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('bg-night-950', $html);

        $footer = strstr($html, '<footer');
        $this->assertIsString($footer);
        $this->assertStringNotContainsString('bg-ink-950', $footer);
        $this->assertStringNotContainsString('text-ink-300', $footer);
    }

    /**
     * A pointer click inside the header releases focus, so the bar can hide.
     *
     * The header reveals itself for anything focused inside it — an escape
     * hatch for a keyboard user tabbing in. A tap leaves focus behind as well,
     * and the bar then stayed pinned open at the top of the page for the rest
     * of the visit: `data-revealed` came off correctly and the CSS still would
     * not hide it, because the rule is `:not(:focus-within)`. It happened once
     * with the theme toggle and again with the menu button, so it is handled
     * once for the whole header.
     *
     * Asserted in the inline head script rather than the bundle, which is
     * where it lives: the reveal logic deliberately does not wait for
     * JavaScript to arrive. There is no JavaScript runner here, so this catches
     * the guard being removed — the regression that actually happened — while
     * the interaction itself is verified in a browser.
     *
     * `event.detail` is the part that matters: blurring unconditionally would
     * take the header away from a keyboard user in the middle of using it.
     */
    public function test_a_pointer_click_in_the_header_releases_focus(): void
    {
        $head = strstr($this->get('/fa')->assertOk()->getContent(), '</head>', true);

        $this->assertIsString($head);
        $this->assertStringContainsString('event.detail === 0', $head);
        $this->assertStringContainsString('active.blur()', $head);
        $this->assertStringContainsString('h.contains(event.target)', $head);
    }

    /**
     * The admin panel is pinned to light. It is full of literal `bg-white`
     * surfaces that would not invert with the ramp, so a dark operating system
     * would otherwise render near-white text on them.
     */
    public function test_the_admin_panel_is_pinned_to_the_light_theme(): void
    {
        $html = $this->get('/admin/login')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<html[^>]*data-theme="light"/', $html);
    }
}
