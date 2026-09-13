<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Post;
use Database\Seeders\TechnicalArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicalArticleTest extends TestCase
{
    use RefreshDatabase;

    private const SLUGS = [
        'leca-vs-natural-pumice',
        'screed-volume-per-square-metre',
        'what-drives-the-price-of-leca',
        'leca-infill-concrete-grades',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TechnicalArticleSeeder::class);
    }

    public function test_each_article_is_published_in_all_three_languages(): void
    {
        foreach (self::SLUGS as $slug) {
            foreach (['fa', 'en', 'ar'] as $locale) {
                $this->get("/{$locale}/articles/{$slug}")
                    ->assertOk()
                    ->assertDontSee('noindex, follow', false);
            }
        }
    }

    /**
     * Seo::description truncates at 158 and the title bar of a search result at
     * roughly 70 — and a post carries its own overrides, which the checks on
     * the lang files never see.
     */
    public function test_no_post_metadata_is_written_past_the_cut(): void
    {
        foreach (Post::all() as $post) {
            foreach (['fa', 'en', 'ar'] as $locale) {
                foreach (['meta_description' => 158, 'meta_title' => 70] as $field => $max) {
                    $value = $post->getTranslation($field, $locale);

                    if (blank($value)) {
                        continue;
                    }

                    $this->assertLessThanOrEqual(
                        $max,
                        mb_strlen($value),
                        "{$post->slug} {$field} in {$locale} is ".mb_strlen($value)." characters, over {$max}.",
                    );
                }
            }
        }
    }

    /**
     * A missing translation falls back to Persian rather than failing, so an
     * English article with an untranslated body renders as a page of Persian
     * and nobody notices until a reader does.
     */
    public function test_the_translations_are_real_translations(): void
    {
        foreach (self::SLUGS as $slug) {
            $post = Post::where('slug', $slug)->firstOrFail();

            foreach (['title', 'excerpt', 'body', 'meta_title', 'meta_description'] as $field) {
                $this->assertDoesNotMatchRegularExpression(
                    '/\p{Arabic}/u',
                    (string) $post->getTranslation($field, 'en'),
                    "{$slug}: the English {$field} still contains Persian or Arabic script.",
                );

                $this->assertNotSame(
                    $post->getTranslation($field, 'fa'),
                    $post->getTranslation($field, 'ar'),
                    "{$slug}: the Arabic {$field} is a copy of the Persian one.",
                );
            }
        }
    }

    /**
     * The sitemap is cached for six hours, and saving from the panel is what
     * normally clears it. A seeder does not go through the panel, so without
     * an explicit flush the articles would be missing from the sitemap until
     * long after the run that published them.
     */
    public function test_the_articles_reach_the_sitemap_immediately(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        foreach (self::SLUGS as $slug) {
            // <loc> only: each entry also repeats the slug in its four
            // xhtml:link alternates, so a plain substring count returns 15.
            $n = preg_match_all('~<loc>[^<]*'.preg_quote($slug, '~').'[^<]*</loc>~', $xml);

            $this->assertSame(3, $n, "{$slug} should be in the sitemap once per language, found {$n}.");
        }
    }

    /**
     * `firstOrCreate`, so that re-running the seeder cannot overwrite whatever
     * an editor has rewritten in the panel.
     */
    public function test_reseeding_leaves_an_edited_article_alone(): void
    {
        $post = Post::where('slug', 'what-drives-the-price-of-leca')->firstOrFail();
        $post->setTranslation('title', 'fa', 'عنوان بازنویسی‌شده')->save();

        $this->seed(TechnicalArticleSeeder::class);

        $this->assertSame('عنوان بازنویسی‌شده', $post->fresh()->getTranslation('title', 'fa'));
        $this->assertSame(count(self::SLUGS), Post::whereIn('slug', self::SLUGS)->count());
    }
}
