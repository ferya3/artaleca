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
     * Stacked one block per row, this footer measured 1376px tall at 390px
     * wide — the legal line sat about a screen and a half below the last thing
     * anyone came here to read. Two columns of five links, with the contact
     * details a line each underneath, brings it to 650px.
     *
     * The markup is asserted rather than the height, because the height also
     * depends on how much address an editor has typed and the layout should
     * hold either way.
     */
    public function test_the_links_sit_in_two_columns_on_a_phone(): void
    {
        $footer = $this->footer();

        preg_match('~<div class="mt-8 grid[^"]*"~', $footer, $grid);
        $this->assertNotEmpty($grid, 'The footer link grid is not where this test expects it.');

        $this->assertStringContainsString(
            'grid-cols-2',
            $grid[0],
            'Without an unprefixed column count the links fall back to one column on phones.',
        );

        $this->assertSame(
            2,
            substr_count($footer, '<nav aria-label='),
            'The footer is meant to be two columns of links, not three or more.',
        );
    }

    /**
     * Each group heading is the accessible name of its <nav> and nothing more.
     * Drawn, a heading costs as much vertical space as a link does, which is a
     * poor trade for two words on a phone.
     */
    public function test_the_group_headings_are_not_drawn(): void
    {
        $this->assertStringNotContainsString('eyebrow', $this->footer());
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
