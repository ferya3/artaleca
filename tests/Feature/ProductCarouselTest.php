<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The grades row on the products page.
 *
 * It is a scroller rather than a grid, and the thing that makes it read as one
 * is the peek: every card is a little narrower than an exact fit, so part of
 * the next is always on screen. That is a number in a class name, which is
 * exactly the kind of detail a later tidy-up rounds to something neat and
 * silently turns the carousel back into a row that looks like it ends.
 *
 * The scrolling itself is the browser's, so there is nothing to test in a
 * headless request beyond the two things that could break it: the markup that
 * opts into snapping, and the arrows staying hidden until a script arrives to
 * make them do something.
 */
class ProductCarouselTest extends TestCase
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
