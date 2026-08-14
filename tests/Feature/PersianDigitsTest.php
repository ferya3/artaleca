<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\Digits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Numbers are typed on a Latin keyboard and read on a Persian page.
 *
 * The conversion happens once, on the response, so the guarantee these tests
 * defend is not "one template formats its numbers" but "nothing on a Persian
 * page shows 123" — and, just as importantly, that the machine-readable halves
 * of the page were left alone.
 */
class PersianDigitsTest extends TestCase
{
    use RefreshDatabase;

    // ── What the reader sees ────────────────────────────────────────────

    public function test_a_number_typed_in_the_admin_panel_is_shown_in_persian(): void
    {
        Setting::put('figures.annual_capacity_m3', 987654, 'figures', translatable: false);

        $this->get('/fa/about')->assertOk()->assertSee('۹۸۷٬۶۵۴');
    }

    public function test_the_other_languages_keep_western_digits(): void
    {
        Setting::put('figures.annual_capacity_m3', 987654, 'figures', translatable: false);

        $this->get('/en/about')->assertOk()->assertSee('987,654')->assertDontSee('۹۸۷');
        $this->get('/ar/about')->assertOk()->assertSee('987,654');
    }

    public function test_editor_copy_is_converted_along_with_everything_else(): void
    {
        Setting::put('content.home.intro_body', ['fa' => 'ظرفیت 250 هزار متر مکعب در سال.']);

        $this->get('/fa')->assertOk()->assertSee('ظرفیت ۲۵۰ هزار متر مکعب در سال.');
    }

    // ── What must survive untouched ─────────────────────────────────────

    /** A phone number a reader can see but not dial is worse than one in Latin. */
    public function test_links_and_attributes_keep_the_digits_a_machine_reads(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('href="tel:+982188880000"', $html);
        $this->assertStringContainsString('rel="canonical" href="http://localhost/fa"', $html);
    }

    /**
     * schema.org is read by crawlers, and its date and code fields mean Western
     * digits. Prose inside the graph may well be Persian — the description is
     * written that way in the language files — so the assertion is about the
     * machine-readable fields, not about the block as a whole.
     */
    public function test_structured_data_keeps_western_digits(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $ld = [];
        preg_match_all('#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $html, $ld);

        $this->assertNotEmpty($ld[1], 'The home page should publish structured data.');

        $graph = implode('', $ld[1]);

        $this->assertStringContainsString('"foundingDate":"1996"', $graph);
        $this->assertStringContainsString('"postalCode":"1969764514"', $graph);
    }

    /**
     * The admin panel is where the numbers are about to be typed back in. If a
     * textarea came back full of Persian digits, saving the form would write
     * them into the database.
     */
    public function test_the_admin_panel_is_left_alone(): void
    {
        Setting::put('figures.annual_capacity_m3', 987654, 'figures', translatable: false);

        $this->actingAs($this->makeAdmin())
            ->get('/admin/settings/figures')
            ->assertOk()
            ->assertSee('987654');
    }

    // ── The converter itself ────────────────────────────────────────────

    #[DataProvider('samples')]
    public function test_the_converter_handles_punctuation_around_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, Digits::text($input));
    }

    public static function samples(): array
    {
        return [
            'plain' => ['1996', '۱۹۹۶'],
            'thousands separator' => ['987,654', '۹۸۷٬۶۵۴'],
            'decimal point' => ['0.5', '۰٫۵'],
            'a full stop is not a decimal' => ['سال 2026.', 'سال ۲۰۲۶.'],
            'a list comma is not a separator' => ['3, 4', '۳, ۴'],
            'a range keeps its dash' => ['10-20', '۱۰-۲۰'],
            'entities are markup' => ['&#160;5', '&#160;۵'],
        ];
    }

    public function test_opaque_elements_are_skipped(): void
    {
        $html = '<p>12</p><script>var a = 12;</script><textarea>12</textarea><style>.a{width:12px}</style><p>12</p>';

        $this->assertSame(
            '<p>۱۲</p><script>var a = 12;</script><textarea>12</textarea><style>.a{width:12px}</style><p>۱۲</p>',
            Digits::html($html),
        );
    }

    public function test_attributes_are_skipped(): void
    {
        $this->assertSame(
            '<a href="/p/4-10" title="4-10">۴-۱۰</a>',
            Digits::html('<a href="/p/4-10" title="4-10">4-10</a>'),
        );
    }

    /** The escape hatch for a code that only looks like a number. */
    public function test_an_element_can_opt_out(): void
    {
        $this->assertSame(
            '<p>۱ <span data-latin-digits>EN 13055-1 <b>2</b></span> ۲</p>',
            Digits::html('<p>1 <span data-latin-digits>EN 13055-1 <b>2</b></span> 2</p>'),
        );
    }

    /** A nested element of the same name must not end the opt-out early. */
    public function test_the_opt_out_survives_nesting(): void
    {
        $this->assertSame(
            '<span data-latin-digits>1 <span>2</span> 3</span><span>۴</span>',
            Digits::html('<span data-latin-digits>1 <span>2</span> 3</span><span>4</span>'),
        );
    }

    public function test_comments_and_void_elements_do_not_confuse_the_walker(): void
    {
        $this->assertSame(
            '<!-- 12 --><img src="a-12.png" alt="12"><p>۱۲</p>',
            Digits::html('<!-- 12 --><img src="a-12.png" alt="12"><p>12</p>'),
        );
    }
}
