<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The Persian search terms the site is meant to answer.
 *
 * Not a keyword-density check — the terms are in the copy because they are what
 * this material is actually called, and the same sentence that catches the
 * search tells a visitor who typed "پوکه لیکا" that they are in the right
 * place. What this guards is narrower and more fragile than the copy itself:
 * each phrase has to survive as an unbroken run of characters. Rewriting
 * "قیمت سبکدانه لیکا" as "قیمت و شرایط سبکدانه لیکا" reads the same to a
 * person and matches nothing.
 *
 * Every page checked has to be indexable, which is why the buying terms are
 * here rather than on the quote form: QuoteController sets noindex, so a
 * keyword placed there is invisible to the search that was supposed to find
 * it.
 */
class KeywordCoverageTest extends TestCase
{
    use RefreshDatabase;

    /** The indexable Persian pages, most important first. */
    private const PAGES = ['/fa', '/fa/products', '/fa/applications'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /** @return list<array{0: string}> */
    public static function keywords(): array
    {
        return array_map(fn (string $k) => [$k], [
            // The product, and every name it is bought under.
            'سبکدانه لیکا',
            'لیکا',
            'پوکه صنعتی لیکا',
            'دانه لیکا',
            'سبکدانه صنعتی',
            'پوکه لیکا',

            // Buying intent.
            'قیمت سبکدانه لیکا',
            'خرید سبکدانه لیکا',
            'فروش سبکدانه لیکا',
            'قیمت لیکا',
            'خرید لیکا',
            'فروش لیکا',

            // What it is used for.
            'سبکدانه بتن',
            'بتن سبک لیکا',
            'مصالح سبک ساختمانی',
        ]);
    }

    #[DataProvider('keywords')]
    public function test_the_term_is_on_an_indexable_page(string $keyword): void
    {
        $found = [];

        foreach (self::PAGES as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertStringNotContainsString(
                'content="noindex',
                $html,
                "{$path} is noindex, so nothing on it can rank.",
            );

            if (str_contains($html, $keyword)) {
                $found[] = $path;
            }
        }

        $this->assertNotEmpty($found, "\"{$keyword}\" appears on none of: ".implode(', ', self::PAGES));
    }

    /**
     * A term in the body is worth less than a term in the title, and the title
     * is the one thing a rewrite is most likely to shorten.
     */
    public function test_the_titles_carry_the_terms_each_page_competes_for(): void
    {
        $expected = [
            '/fa' => ['سبکدانه لیکا', 'پوکه صنعتی'],
            '/fa/products' => ['سبکدانه لیکا'],
            '/fa/applications' => ['سبکدانه لیکا', 'بتن سبک'],
        ];

        foreach ($expected as $path => $terms) {
            preg_match('~<title>(.*?)</title>~s', $this->get($path)->getContent(), $m);

            foreach ($terms as $term) {
                $this->assertStringContainsString($term, $m[1] ?? '', "The <title> of {$path} lost \"{$term}\".");
            }
        }
    }

    /**
     * Seo::description truncates at 158 characters, and the phrase at the end
     * of a long one simply vanishes — which is how "قیمت لیکا" was lost from
     * the home page description the first time it was written there. The
     * truncation is deliberate and right; writing past it is the mistake, and
     * it is invisible in the source file.
     */
    public function test_no_description_is_written_past_the_cut(): void
    {
        foreach (['fa', 'en', 'ar'] as $locale) {
            foreach (__('seo', [], $locale) as $key => $text) {
                if (! str_contains($key, 'description')) {
                    continue;
                }

                $this->assertLessThanOrEqual(
                    158,
                    mb_strlen($text),
                    "seo.{$key} in {$locale} is ".mb_strlen($text)." characters; everything after 158 is cut.",
                );
            }
        }
    }

    /**
     * The synonym sentence is the only place four of these terms appear, and
     * it is an editable string — so it is also the easiest one to delete by
     * accident from the admin panel.
     */
    public function test_the_synonym_sentence_names_every_alias(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        foreach (['سبکدانه لیکا', 'پوکه لیکا', 'پوکه صنعتی لیکا', 'دانه لیکا', 'سبکدانه صنعتی'] as $alias) {
            $this->assertStringContainsString($alias, $html, "The home page no longer calls it \"{$alias}\".");
        }
    }

    /** The other two languages must not inherit the Persian sentence. */
    public function test_the_other_languages_get_their_own_wording(): void
    {
        foreach (['/en', '/ar'] as $path) {
            $this->assertStringNotContainsString(
                'پوکه صنعتی لیکا',
                $this->get($path)->assertOk()->getContent(),
                "{$path} fell back to the Persian synonym sentence.",
            );
        }
    }
}
