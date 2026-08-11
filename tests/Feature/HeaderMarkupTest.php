<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Locales;
use App\Support\Navigation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $html = $this->get('/fa/about')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<dl class="hidden grid-cols-2[^"]*md:grid\b/',
            $html,
            'The figures strip must be display:none below md, not merely visually shrunk.',
        );
    }

    /**
     * The hero carries the headline and nothing that competes with it.
     *
     * The figures strip is off the home page entirely — it sat under the
     * desktop hero and was the second thing a visitor read — and the phone hero
     * has no button, because over a photograph a call to action fights the
     * headline for the same glance. Quoting is still one tap away in the menu,
     * which the next assertion holds to.
     */
    public function test_the_home_hero_carries_no_figures_strip_and_no_button_on_phones(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<dl class="hidden grid-cols-2[^"]*md:grid\b/',
            $html,
            'The figures strip is no longer part of the home page.',
        );

        // Between where the phone block opens and where the desktop one does.
        // Sliced rather than searched whole, because the header above it holds
        // a quote link of its own.
        $phoneHero = strstr($html, 'relative overflow-hidden bg-ink-950 text-white md:hidden');
        $phoneHero = is_string($phoneHero)
            ? strstr($phoneHero, 'hidden overflow-hidden bg-ink-950 text-white md:block', true)
            : false;

        $this->assertIsString($phoneHero, 'The two hero blocks should both be present.');
        $this->assertStringNotContainsString(route('quote'), $phoneHero);

        // …but the action itself is still reachable on a phone.
        $this->assertStringContainsString(route('quote'), $html);
    }

    /**
     * No `style` attributes anywhere in a rendered page.
     *
     * This is a CSP guard, not a style preference. A nonce authorises <style>
     * *elements*; it does nothing for style *attributes*, which `style-src-attr`
     * blocks outright. An inline `style="aspect-ratio: 4/3"` on the media
     * component was therefore being discarded on every image on the site, and
     * nothing looked wrong because the placeholder SVG brings its own intrinsic
     * size — a real uploaded photograph would have collapsed to nothing.
     *
     * Silent is the operative word: the browser reports it to the console and
     * renders on, so only a test catches it.
     */
    #[DataProvider('pagesWithMedia')]
    public function test_a_page_renders_no_inline_style_attributes(string $path): void
    {
        $html = $this->get($path)->assertOk()->getContent();

        // The <noscript> block is a <style> element carrying the nonce, which
        // CSP does allow; attributes are what must not appear.
        preg_match_all('/<[a-z][^>]*\sstyle=(["\'])(?!\1)/i', $html, $matches);

        $this->assertSame(
            [],
            $matches[0],
            $path.' renders '.count($matches[0]).' inline style attribute(s); '
            .'style-src-attr blocks them and the declaration is silently dropped.',
        );
    }

    /** @return list<array{0:string}> */
    public static function pagesWithMedia(): array
    {
        return [
            'home' => ['/fa'],
            'catalogue' => ['/fa/products'],
            'projects' => ['/fa/projects'],
            'articles' => ['/fa/articles'],
            'gallery' => ['/fa/projects-gallery'],
        ];
    }

    /** The ratio has to survive as a class, since it can no longer be a style. */
    public function test_the_media_ratio_is_expressed_as_a_class(): void
    {
        // Cards only render where there is content to put in them.
        $this->seed(DatabaseSeeder::class);

        $this->get('/fa/projects')->assertOk()->assertSee('aspect-[3/2]', false);
        $this->get('/fa/articles')->assertOk()->assertSee('aspect-[16/9]', false);
    }

    /**
     * The header must fail *open*.
     *
     * It is the only navigation on a phone, and it is hidden until scrolled —
     * so if the thing that reveals it can fail, the navigation disappears for
     * good and no amount of scrolling brings it back. That is exactly what
     * happened: the hiding rule applied unconditionally and only the bundle
     * could undo it.
     *
     * The hinge is that the hidden state is gated on `data-autohide`, which is
     * set by the inline head script and by nothing else. No script, no
     * attribute, no hiding.
     */
    public function test_the_hidden_header_state_is_gated_on_the_script_having_run(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString(
            '[data-autohide] [data-site-header]:not([data-revealed])',
            $css,
            'The hiding rule must be gated on the marker the script sets, or a '
            .'script failure removes the navigation permanently.',
        );

        $this->assertStringNotContainsString(
            "\n    [data-site-header]:not([data-revealed])",
            $css,
            'An ungated hiding rule hides the header whether or not the script ran.',
        );
    }

    /** And the reveal must not live in the bundle, which can fail to arrive. */
    public function test_the_header_behaviour_does_not_depend_on_the_bundle(): void
    {
        $bundle = file_get_contents(resource_path('js/app.js'));
        $head = file_get_contents(resource_path('views/partials/head.blade.php'));

        $this->assertStringNotContainsString(
            'data-site-header',
            $bundle,
            'Header behaviour in the bundle means a blocked or slow bundle costs '
            .'the visitor their navigation.',
        );

        $this->assertStringContainsString('data-autohide', $head);
        $this->assertStringContainsString('data-site-header', $head);
    }

    /** The marker reaches the browser on a real response, not just in source. */
    public function test_the_page_ships_the_inline_header_script_with_a_nonce(): void
    {
        $response = $this->get('/fa')->assertOk();
        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/<script nonce="[^"]+">\s*\(function \(\) \{/',
            $html,
            'The inline header script must carry the CSP nonce or it will not execute.',
        );
        $this->assertStringContainsString("setAttribute('data-autohide'", $html);
    }

    /**
     * Every page-grid column must carry `min-w-0`.
     *
     * A grid item's automatic minimum size is its content, so it refuses to
     * shrink below it. One wide table inside a column therefore stretches the
     * whole track past the viewport instead of scrolling inside its own
     * `overflow-x-auto` wrapper — and because the track is shared, the *text*
     * in the neighbouring column gets dragged off the screen with it.
     *
     * That is invisible on a desktop and obvious on a phone, which is exactly
     * the combination that needs a test rather than an eye.
     */
    public function test_every_page_grid_column_can_shrink_below_its_content(): void
    {
        $offenders = [];

        foreach (glob(resource_path('views/pages').'/{,*/}*.blade.php', GLOB_BRACE) as $file) {
            foreach (file($file) as $number => $line) {
                if (preg_match('/class="([^"]*\blg:col-span-\d[^"]*)"/', $line, $m)
                    && ! str_contains($m[1], 'min-w-0')) {
                    $offenders[] = basename($file).':'.($number + 1);
                }
            }
        }

        $this->assertSame([], $offenders, 'Grid columns without min-w-0: '.implode(', ', $offenders));
    }

    /** The primary navigation is a deliberate order, not an accident of code order. */
    public function test_the_primary_navigation_is_in_the_agreed_order(): void
    {
        $labels = array_column(Navigation::primary(), 'label');

        $this->assertSame([
            __('nav.home'),
            __('nav.products'),
            __('nav.downloads'),
            __('nav.representatives'),
            __('nav.projects'),
            __('nav.contact'),
        ], $labels);
    }

    /**
     * The language switcher has to be reachable on a phone without opening the
     * menu and scrolling to its foot, which is where it used to hide — the
     * desktop utility strip that carries it is hidden below `lg`.
     */
    public function test_a_phone_gets_a_language_switcher_in_the_header_itself(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('data-language-switcher', $html);

        // It sits outside the mobile menu, or it would be just as buried.
        $switcher = (int) strpos($html, 'data-language-switcher');
        $menu = (int) strpos($html, 'data-mobile-menu');
        $this->assertNotSame(0, $switcher);
        $this->assertGreaterThan($menu, $switcher);

        // Every language is offered, and switching keeps the header open.
        foreach (Locales::all() as $meta) {
            $this->assertStringContainsString($meta['native'], $html);
        }
    }

    /** The hero's motion layer is decorative and must not reach the a11y tree. */
    public function test_the_hero_motion_layer_is_hidden_from_assistive_technology(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('<div class="hero-motion" aria-hidden="true">', $html);
    }
}
