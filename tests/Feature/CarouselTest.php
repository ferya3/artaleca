<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Application;
use App\Support\Digits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The three card rows that scroll: the grades on the catalogue page, and the
 * products and the uses on the home page.
 *
 * What makes them read as rows rather than blocks that end is the peek — every
 * card is a little narrower than an exact fit, so part of the next is always on
 * screen. That is a number in a class name, which is exactly the kind of detail
 * a later tidy-up rounds to something neat and silently turns a carousel back
 * into a row that looks finished.
 *
 * The scrolling itself is the browser's, so there is nothing to test in a
 * headless request beyond what could actually break it: the markup that opts
 * into snapping, the widths that produce the peek, the arrows staying hidden
 * until a script arrives to make them do something, and the two home rows
 * turning back into the grids they were above a phone.
 */
class CarouselTest extends TestCase
{
    use RefreshDatabase;

    private function catalogue(int $count = 6): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->makeProduct([
                'slug' => 'grade-'.$i,
                'sku' => 'ALS-'.$i,
                'name' => ['fa' => 'گرید '.$i, 'en' => 'Grade '.$i, 'ar' => 'درجة '.$i],
            ]);
        }
    }

    /** N published uses, in the order they were made. */
    private function uses(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Application::create([
                'slug' => 'use-'.$i,
                'name' => ['fa' => 'کاربرد '.$i, 'en' => 'Use '.$i, 'ar' => 'استخدام '.$i],
                'summary' => ['fa' => 'خلاصه '.$i, 'en' => 'Summary '.$i, 'ar' => 'ملخص '.$i],
                'position' => $i,
                'is_active' => true,
            ]);
        }
    }

    public function test_the_grades_are_a_snapping_scroller(): void
    {
        $this->catalogue();

        $html = $this->get('/fa/products')->assertOk()->getContent();

        $this->assertStringContainsString('data-carousel', $html);
        $this->assertStringContainsString('no-scrollbar carousel', $html);
    }

    /**
     * The peek, breakpoint by breakpoint.
     *
     * Each basis is under the exact fit for the number of cards at that width —
     * 78% of one, 42% of two, 29% of three, 22% of four. They were measured
     * rather than guessed (390, 768, 1100 and 1440 all land on 28px of the card
     * behind and 40-75px of the one ahead), and rounding any of them up to
     * something neater closes the gap the next card shows through.
     */
    public function test_every_breakpoint_leaves_the_next_card_showing(): void
    {
        $this->catalogue();

        $html = $this->get('/fa/products')->assertOk()->getContent();

        foreach (['basis-[78%]', 'sm:basis-[42%]', 'lg:basis-[29%]', 'xl:basis-[22%]'] as $basis) {
            $this->assertStringContainsString($basis, $html, "The carousel lost its {$basis} peek.");
        }
    }

    /** The mechanics live in one place, and the peek depends on them. */
    public function test_the_carousel_rules_are_declared(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        foreach ([
            'scroll-snap-type: x mandatory',
            'scroll-snap-align: start',
            // Without this a swipe becomes the browser's back gesture.
            'overscroll-behavior-x: contain',
            // What leaves the *previous* card's edge showing once you scroll.
            'scroll-padding-inline',
        ] as $rule) {
            $this->assertStringContainsString($rule, $css, "The carousel is missing `{$rule}`.");
        }
    }

    /** An arrow that scrolls nothing is worse than no arrow. */
    public function test_the_arrows_are_hidden_until_a_script_claims_them(): void
    {
        $this->catalogue();

        $html = $this->get('/fa/products')->assertOk()->getContent();
        $css = (string) file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('data-carousel-prev', $html);
        $this->assertStringContainsString('data-carousel-next', $html);

        // Shipped without the attribute app.js adds, and hidden without it.
        $this->assertStringNotContainsString('data-ready', $html);
        $this->assertMatchesRegularExpression(
            '/\[data-carousel-controls\]\s*\{[^}]*display:\s*none/',
            $css,
        );
    }

    /** Scrolling a row of links with the keyboard needs somewhere to focus. */
    public function test_the_scroller_is_reachable_by_keyboard(): void
    {
        $this->catalogue();

        $this->get('/fa/products')
            ->assertOk()
            ->assertSee('tabindex="0"', false)
            ->assertSee('aria-label="'.content('product.grades_title').'"', false);
    }

    // ── The home page: a carousel on a phone only ───────────────────────

    /**
     * One element that is a carousel on a phone and the grid it always was
     * above that.
     *
     * Rendering the cards twice and hiding a copy would be the obvious way to
     * do it and the wrong one — double the markup and double the image URLs for
     * a section that shows the same four products either way. So the test is
     * that there is one row carrying both behaviours, not two rows.
     */
    public function test_the_home_page_row_is_a_carousel_and_a_grid_at_once(): void
    {
        $this->catalogue(4);

        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('carousel carousel-phone', $html);

        // The grid it becomes at `sm`, on the same element.
        $this->assertStringContainsString('sm:grid sm:grid-cols-2', $html);
        $this->assertStringContainsString('lg:grid-cols-4', $html);

        // And the card sizing that reverts with it.
        $this->assertStringContainsString('basis-[78%] sm:basis-auto', $html);
    }

    /** The three properties no utility can cancel, cancelled at `sm`. */
    public function test_the_phone_only_carousel_switches_off_above_a_phone(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 640px\) \{\s*\.carousel-phone \{[^}]*overflow-x: visible;[^}]*scroll-snap-type: none;[^}]*scroll-padding-inline: 0;/',
            $css,
            'The home row must stop being a scroller once it is a grid.',
        );
    }

    /** The catalogue row is a carousel at every width, and keeps its arrows. */
    public function test_the_catalogue_row_is_not_the_phone_only_kind(): void
    {
        $this->catalogue();

        $html = $this->get('/fa/products')->assertOk()->getContent();

        $this->assertStringNotContainsString('carousel-phone', $html);
        $this->assertStringContainsString('data-carousel-next', $html);
    }

    // ── The home page: the seven uses ───────────────────────────────────

    /**
     * The uses get the same treatment as the products above them, so the two
     * sections behave the same way under the same thumb.
     */
    public function test_the_uses_row_is_a_carousel_on_a_phone_too(): void
    {
        $this->catalogue(4);
        $this->uses(7);

        $html = $this->get('/fa')->assertOk()->getContent();

        // Two rows carrying the phone-only behaviour: products, then uses.
        $this->assertSame(2, substr_count($html, 'carousel carousel-phone'));

        // Digits::text, because the Persian pages are rewritten on the way
        // out and "1" reaches the browser as "۱" — see LocaliseDigits.
        foreach (range(1, 7) as $i) {
            $this->assertStringContainsString(
                'کاربرد '.Digits::text((string) $i),
                $html,
                "Use {$i} is missing from the home row.",
            );
        }
    }

    /**
     * Seven, and seven is a cap rather than a coincidence — an eighth use
     * should extend the page it has of its own, not this row.
     */
    public function test_the_uses_row_is_capped_at_seven(): void
    {
        $this->uses(10);

        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('کاربرد ۷', $html, 'The seventh use belongs in the row.');

        foreach ([8, 9, 10] as $beyond) {
            $this->assertStringNotContainsString(
                'کاربرد '.Digits::text((string) $beyond),
                $html,
                "Use {$beyond} should stay on the applications page, not the home row.",
            );
        }
    }

    /** Whatever the row does, the products are still in it. */
    public function test_every_product_is_still_on_the_page(): void
    {
        $this->catalogue(4);

        $response = $this->get('/en/products')->assertOk();

        foreach (range(1, 4) as $i) {
            $response->assertSee('Grade '.$i);
        }
    }
}
