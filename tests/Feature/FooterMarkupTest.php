<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterMarkupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function footer(): string
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        preg_match('~<footer\b.*?</footer>~s', $html, $matches);
        $this->assertNotEmpty($matches, 'No <footer> was rendered.');

        return $matches[0];
    }

    /**
     * Stacked, the five blocks put the legal line about a screen and a half
     * below the last thing anyone came here to read — measured at 390px wide,
     * 1376px of footer. Two columns brings it to 786px.
     *
     * The grid is asserted rather than the height because the height depends on
     * how much address an editor has typed, and the layout should hold either
     * way.
     */
    public function test_the_footer_is_two_columns_on_a_phone(): void
    {
        $footer = $this->footer();

        preg_match('~<div class="container-page grid[^"]*"~', $footer, $grid);
        $this->assertNotEmpty($grid, 'The footer grid is not where this test expects it.');

        $this->assertStringContainsString(
            'grid-cols-2',
            $grid[0],
            'Without an unprefixed column count the grid falls back to one column on phones.',
        );

        // The brand block spans both, so the three link groups and the address
        // pair off into two even rows underneath rather than leaving an orphan.
        $this->assertStringContainsString('class="col-span-2 lg:col-span-4"', $footer);
    }

    /**
     * `Contact::social()` is empty until an editor fills the fields in, and an
     * empty flex row still charged its own top margin against the layout.
     */
    public function test_an_empty_social_row_is_not_rendered(): void
    {
        $this->assertStringNotContainsString('flex gap-3', $this->footer());
    }
}
