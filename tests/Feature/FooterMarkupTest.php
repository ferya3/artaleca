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

    /**
     * The brand is centred on a phone and heads the row on a desktop.
     *
     * On a phone it shared a `justify-between` row with the social icons, which
     * with no icons configured left it hard against one edge — measured at 390px
     * and 768px it is now centred to the pixel.
     */
    public function test_the_brand_is_centred_on_a_phone_and_starts_the_row_on_a_desktop(): void
    {
        $footer = $this->footer();

        preg_match('~<div class="flex flex-col items-center[^"]*"~', $footer, $brand);
        $this->assertNotEmpty($brand, 'The footer brand block is not where this test expects it.');

        $this->assertStringContainsString('items-center', $brand[0]);
        $this->assertStringContainsString('lg:items-start', $brand[0]);
    }

    /**
     * The row is sized by its content, not by twelve columns.
     *
     * A third of the width for a block as wide as a logo is what put 300px of
     * nothing between the mark and the first link. `flex-1` on the address is
     * the half that matters: it is the only block with a reason to take the
     * slack.
     */
    public function test_the_footer_row_is_laid_out_by_content(): void
    {
        $footer = $this->footer();

        $this->assertStringNotContainsString('lg:col-span-4', $footer, 'The twelve-column grid is back.');
        $this->assertStringContainsString('lg:flex lg:items-start', $footer);
        $this->assertStringContainsString('lg:flex-1', $footer);
    }

    /** The mark is the last branding on the page, so it takes the largest size. */
    public function test_the_footer_logo_takes_the_largest_size(): void
    {
        // The literal class string the `xl` size emits, which is the only form
        // Tailwind's scanner ever sees.
        $this->assertStringContainsString('h-20 w-auto shrink-0 lg:h-24', $this->footer());
    }

    /**
     * An uploaded logo in the footer always takes the dark-ground file.
     *
     * This band is night-palette in both themes. Picking between the two files
     * by *theme* is right in the header and wrong here: in the light theme it
     * handed the footer the dark-lettered file over a near-black ground, and
     * the logo was simply not visible — which is exactly how it was reported.
     */
    public function test_the_footer_logo_uses_the_dark_ground_file_in_both_themes(): void
    {
        \App\Models\Setting::put('media.logo', ['fa' => [
            'light' => '/storage/media/logo-dark-lettering.png',
            'dark' => '/storage/media/logo-light-lettering.png',
        ]], 'media', true);

        $footer = $this->footer();

        $this->assertStringContainsString('/storage/media/logo-light-lettering.png', $footer);
        $this->assertStringNotContainsString('/storage/media/logo-dark-lettering.png', $footer);

        // One tag, not a themed pair: there is nothing here for a theme to
        // switch between.
        $this->assertStringNotContainsString('theme-only-', $footer);
    }
}
