<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Prose;
use PHPUnit\Framework\TestCase;

class ProseTest extends TestCase
{
    /** The whole point: a blank line is a paragraph, not two `<br>`s. */
    public function test_a_blank_line_starts_a_new_paragraph(): void
    {
        $html = Prose::toHtml("First paragraph.\n\nSecond paragraph.");

        $this->assertSame("<p>First paragraph.</p>\n<p>Second paragraph.</p>", $html);
    }

    /** Any number of blank lines is still one break — editors leave extras. */
    public function test_extra_blank_lines_do_not_make_empty_paragraphs(): void
    {
        $html = Prose::toHtml("One.\n\n\n\n\nTwo.");

        $this->assertSame("<p>One.</p>\n<p>Two.</p>", $html);
        $this->assertStringNotContainsString('<p></p>', $html);
    }

    /**
     * A single newline inside a paragraph stays a line break. It is how an
     * address or a short sequence is written, and reflowing it would be a
     * silent change to what the editor typed.
     */
    public function test_a_single_newline_stays_a_line_break(): void
    {
        $html = Prose::toHtml("Line one\nLine two");

        $this->assertSame('<p>Line one<br>Line two</p>', $html);
    }

    public function test_a_block_of_dashes_becomes_a_list(): void
    {
        $html = Prose::toHtml("- first\n- second\n* third");

        $this->assertSame('<ul><li>first</li><li>second</li><li>third</li></ul>', $html);
    }

    /**
     * "Some lines start with a dash" is not a list. A paragraph that happens
     * to contain a dashed clause has to survive as a paragraph, or ordinary
     * prose starts turning into bullets on its own.
     */
    public function test_a_paragraph_containing_a_dash_is_not_a_list(): void
    {
        $html = Prose::toHtml("The density — 300 kg/m³ —\n- is measured dry");

        $this->assertStringStartsWith('<p>', $html);
        $this->assertStringNotContainsString('<ul>', $html);
    }

    public function test_hash_markers_become_headings(): void
    {
        $this->assertSame('<h2>Grading</h2>', Prose::toHtml('## Grading'));
        $this->assertSame('<h3>Grading</h3>', Prose::toHtml('### Grading'));
    }

    /** A heading and the paragraph under it are usually typed in one block. */
    public function test_text_after_a_heading_in_the_same_block_is_a_paragraph(): void
    {
        $html = Prose::toHtml("## Grading\nThe fraction is 4–10 mm.");

        $this->assertSame('<h2>Grading</h2><p>The fraction is 4–10 mm.</p>', $html);
    }

    /**
     * A single `#` is not a heading. The page already has one `<h1>`, and an
     * editor who types `#1` in a sentence means "number one".
     */
    public function test_a_single_hash_is_not_a_heading(): void
    {
        $html = Prose::toHtml('# Not a heading');

        $this->assertStringNotContainsString('<h1>', $html);
        $this->assertStringContainsString('# Not a heading', $html);
    }

    /**
     * The security property this class exists to hold: editor text can never
     * become markup. Everything is escaped, and the only tags on the page are
     * the ones the class writes itself.
     */
    public function test_editor_text_cannot_become_markup(): void
    {
        foreach ([
            '<script>alert(1)</script>',
            '<img src=x onerror=alert(1)>',
            '<a href="https://example.com">link</a>',
            '## <script>alert(1)</script>',
            '- <script>alert(1)</script>',
        ] as $attack) {
            $html = Prose::toHtml($attack);

            // No opening angle bracket survives except the ones this class
            // writes, so there is no tag for a browser to act on. The payload
            // is still there — as text, escaped, which is the correct outcome
            // rather than a silent deletion.
            $this->assertStringNotContainsString('<script', $html);
            $this->assertStringNotContainsString('<img', $html);
            $this->assertStringNotContainsString('<a ', $html);
            $this->assertStringContainsString('&lt;', $html);
        }
    }

    public function test_nothing_in_means_nothing_out(): void
    {
        foreach ([null, '', '   ', "\n\n", "\r\n"] as $empty) {
            $this->assertSame('', Prose::toHtml($empty));
            $this->assertFalse(Prose::filled($empty));
        }

        $this->assertTrue(Prose::filled('a'));
    }

    /** Windows and old Mac line endings have to paragraph like Unix ones. */
    public function test_carriage_returns_are_normalised(): void
    {
        $this->assertSame(
            "<p>One.</p>\n<p>Two.</p>",
            Prose::toHtml("One.\r\n\r\nTwo."),
        );

        $this->assertSame(
            "<p>One.</p>\n<p>Two.</p>",
            Prose::toHtml("One.\r\rTwo."),
        );
    }

    /** Persian text with an RTL body is the common case, not the exotic one. */
    public function test_persian_bodies_paragraph_and_bullet(): void
    {
        $html = Prose::toHtml("## مشخصات فنی\n\n- دانه‌بندی ۴ تا ۱۰ میلی‌متر\n- چگالی ۳۰۰ کیلوگرم");

        $this->assertSame(
            '<h2>مشخصات فنی</h2>'."\n"
            .'<ul><li>دانه‌بندی ۴ تا ۱۰ میلی‌متر</li><li>چگالی ۳۰۰ کیلوگرم</li></ul>',
            $html,
        );
    }
}
