<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The article reading page.
 *
 * What it used to be, measured on `/fa/articles/leca-vs-natural-pumice` at
 * 1440px wide: a cover picture 1216 × 684 — a screen and a half of photograph
 * before the first sentence — with the text in a 611px column centred
 * underneath it, so the title, the picture and the body each had their own
 * edge. The body itself was `nl2br(e($body))`: one element holding twelve
 * `<br>`s, no paragraphs at all, which is why the stylesheet's paragraph
 * spacing had nothing to apply to and a screen reader was read one long run.
 *
 * These tests hold the two decisions that fixed it — one measure for the whole
 * column, and real paragraph markup — because both are invisible in a
 * screenshot once they are right.
 */
class ArticleLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function article(): Post
    {
        return Post::create([
            'slug' => 'layout-under-test',
            'type' => 'article',
            'title' => ['fa' => 'عنوان مقاله'],
            'excerpt' => ['fa' => 'خلاصهٔ مقاله برای آزمون چیدمان.'],
            'body' => ['fa' => "پاراگراف اول.\n\n## یک تیتر\n\nپاراگراف دوم.\n\n- نکتهٔ اول\n- نکتهٔ دوم"],
            'reading_minutes' => 7,
            'published_at' => now()->subDay(),
        ]);
    }

    private function html(): string
    {
        return $this->get('/fa/articles/'.$this->article()->slug)
            ->assertOk()
            ->getContent();
    }

    /** The body is paragraphs, headings and a list — not a run of `<br>`s. */
    public function test_the_body_is_rendered_as_real_prose(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('<p>پاراگراف اول.</p>', $html);
        $this->assertStringContainsString('<h2>یک تیتر</h2>', $html);
        $this->assertStringContainsString('<li>نکتهٔ اول</li>', $html);
    }

    /**
     * The specific regression: `nl2br` back in the body would restore the
     * twelve-`<br>` single element and silently undo every spacing rule.
     */
    public function test_the_body_is_not_a_run_of_line_breaks(): void
    {
        $body = $this->body($this->html());

        $this->assertSame(
            0,
            preg_match_all('/<br\s*\/?>/', $body),
            'The article body is being rendered with nl2br again.',
        );
    }

    /**
     * One measure, two wrappers, everything inside them. The header copy and
     * the body column both hang off `.article-measure`, which is the only
     * reason the title, the picture and the first paragraph share an edge —
     * and the reason it is a class rather than a `max-w-*` utility written out
     * in each place.
     */
    public function test_the_column_carries_one_shared_measure(): void
    {
        $html = $this->html();

        // The header copy is inside the measure, not spanning the container.
        $this->assertMatchesRegularExpression(
            '/article-measure[^>]*>.*?<h1\b/s',
            $html,
            'The article title is outside the measured column.',
        );

        // So is the body — it hands its width back to the column (see
        // `.prose-long`) instead of setting a second, narrower one.
        $this->assertMatchesRegularExpression(
            '/article-measure[^>]*>.*?prose-long/s',
            $html,
            'The article body is outside the measured column.',
        );

        $this->assertSame(
            2,
            substr_count($html, 'article-measure'),
            'The measure should be declared once per wrapper and nowhere else — '
            .'a third copy means an element is setting its own width again.',
        );
    }

    /** The long-form type size is what `long` on the component buys. */
    public function test_the_body_asks_for_the_long_form_setting(): void
    {
        $this->assertStringContainsString('prose-industrial prose-long', $this->html());
    }

    /**
     * The cover stays 16:9 at the head of the article — the card is 1:1, and
     * the brief promises one upload cropped both ways.
     */
    public function test_the_cover_keeps_the_wide_frame(): void
    {
        $this->assertStringContainsString('aspect-[16/9]', $this->html());
    }

    /**
     * `alt=""` on purpose. The picture sits directly under a heading that
     * already says what the article is, so a second reading of the same words
     * is noise to anyone listening rather than looking.
     */
    public function test_the_cover_is_decorative(): void
    {
        $html = $this->html();

        preg_match('/<figure\b.*?<\/figure>/s', $html, $figure);
        $this->assertNotEmpty($figure, 'The cover is not in a <figure>.');

        // Either an empty alt on a real picture or `aria-hidden` on the
        // generated placeholder — both say "skip this", which is the point.
        $this->assertTrue(
            str_contains($figure[0], 'alt=""') || str_contains($figure[0], 'aria-hidden="true"'),
            'The cover is being announced as content.',
        );

        $this->assertStringNotContainsString('عنوان مقاله', $figure[0]);
    }

    /** Kind, date and reading time: what somebody checks before reading. */
    public function test_the_header_states_the_kind_the_date_and_the_reading_time(): void
    {
        // The article's own header, not the site's — the page has both, and
        // the site's comes first.
        preg_match('/<article\b.*?<\/header>/s', $this->html(), $matches);
        $header = $matches[0] ?? '';
        $this->assertNotEmpty($header, 'The article has no header of its own.');

        $this->assertStringContainsString('<time datetime="', $header);
        $this->assertStringContainsString('۷', $header, 'The reading time is missing from the header.');
        $this->assertStringContainsString('<h1', $header);
    }

    /** An article with no excerpt and no picture must not render an empty one. */
    public function test_a_bare_article_renders_nothing_empty(): void
    {
        $post = Post::create([
            'slug' => 'bare-article',
            'type' => 'article',
            'title' => ['fa' => 'مقالهٔ خالی'],
            'excerpt' => ['fa' => ''],
            'body' => ['fa' => ''],
            'published_at' => now()->subDay(),
        ]);

        $html = $this->get('/fa/articles/'.$post->slug)->assertOk()->getContent();

        $this->assertStringNotContainsString('prose-long', $html);
        $this->assertStringNotContainsString('<p></p>', $html);
    }

    private function body(string $html): string
    {
        preg_match('/<div class="prose-industrial prose-long.*?<\/div>/s', $html, $matches);

        return $matches[0] ?? '';
    }
}
