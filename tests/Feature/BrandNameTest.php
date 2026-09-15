<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Schema;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The company's name, in every spelling it is searched under.
 *
 * "لیکا" transliterates the Italian *Leca*, and Persian has no settled
 * spelling for it — so this firm's own name is written both with the ی and
 * without, by customers and on delivery notes alike. `آرتا لیکا` was indexed
 * and ranking; `آرتا لکا` appeared nowhere in the source at all, so the search
 * that matters most — somebody typing the company's name — could miss the site
 * entirely on one of its two spellings.
 *
 * Worse, and found while fixing it: the `<title>` suffix was the Latin
 * `ARTA LECA` on every page in all three languages. No Persian page carried
 * the company's name in Persian, and a title is the strongest single on-page
 * signal there is for a brand query.
 */
class BrandNameTest extends TestCase
{
    use RefreshDatabase;

    /** Both Persian spellings, as a customer writes them. */
    private const SPELLINGS = ['آرتا لیکا', 'آرتا لکا'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * The declared way to tell a search engine that one entity has several
     * names. Both Persian spellings and both Latin ones.
     */
    public function test_the_structured_data_declares_every_name(): void
    {
        $names = Schema::names();

        foreach ([...self::SPELLINGS, 'Arta Leca', 'Arta Leka', 'ARTA LECA'] as $name) {
            $this->assertContains($name, $names, "Schema::names() is missing \"{$name}\".");
        }
    }

    public function test_the_organization_node_carries_them(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        // The tag carries a CSP nonce, so the attribute list is not fixed.
        preg_match('~<script type="application/ld\+json"[^>]*>(.*?)</script>~s', $html, $m);
        $this->assertNotEmpty($m, 'No JSON-LD on the home page.');

        $json = json_decode($m[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($json);

        $organization = collect($json['@graph'] ?? [$json])
            ->firstWhere('@type', 'Organization');

        $this->assertNotNull($organization, 'No Organization node.');

        foreach (self::SPELLINGS as $name) {
            $this->assertContains(
                $name,
                (array) ($organization['alternateName'] ?? []),
                "The Organization node does not answer to \"{$name}\".",
            );
        }
    }

    /**
     * The title suffix is the company's name in the page's own language.
     *
     * This is the fix with the most weight behind it: before it, a Persian
     * brand query was being answered by a page whose title said `ARTA LECA`.
     */
    public function test_every_persian_title_carries_the_persian_name(): void
    {
        foreach (['/fa', '/fa/products', '/fa/applications', '/fa/about', '/fa/contact'] as $path) {
            preg_match('~<title>(.*?)</title>~s', $this->get($path)->assertOk()->getContent(), $m);

            $this->assertStringContainsString(
                'آرتا لیکا',
                $m[1] ?? '',
                "The <title> of {$path} does not contain the company's name in Persian.",
            );
        }
    }

    /** And it is not doubled on a page whose own title already says it. */
    public function test_the_name_is_not_repeated_in_a_title(): void
    {
        preg_match('~<title>(.*?)</title>~s', $this->get('/fa/about')->getContent(), $m);

        $this->assertSame(
            1,
            substr_count($m[1] ?? '', 'آرتا لیکا'),
            'The about title repeats the company name: '.($m[1] ?? ''),
        );
    }

    /** Each language gets its own, not the Latin one three times. */
    public function test_each_language_gets_its_own_name(): void
    {
        foreach (['/en' => 'ARTA LECA', '/ar' => 'آرتا ليكا'] as $path => $name) {
            $html = $this->get($path)->assertOk()->getContent();

            preg_match('~<title>(.*?)</title>~s', $html, $m);
            $this->assertStringContainsString($name, $m[1] ?? '', "The <title> of {$path} lost \"{$name}\".");

            $this->assertMatchesRegularExpression(
                '~<meta property="og:site_name" content="'.preg_quote($name, '~').'"~',
                $html,
                "og:site_name on {$path} is not \"{$name}\".",
            );
        }
    }

    /**
     * Structured data on its own will not rank a spelling that appears nowhere
     * in the text, so the variant is written into the copy too — once on the
     * home page and once in the company's own account of itself.
     */
    public function test_the_variant_spelling_is_in_the_visible_copy(): void
    {
        foreach (['/fa', '/fa/about'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertStringContainsString(
                'آرتا لکا',
                $html,
                "{$path} never writes the company's name as \"آرتا لکا\".",
            );

            $this->assertStringNotContainsString(
                'content="noindex',
                $html,
                "{$path} is noindex, so nothing on it can rank.",
            );
        }
    }

    /** The about page is where a brand query should land, so it says both. */
    public function test_the_about_page_metadata_names_both_spellings(): void
    {
        $html = $this->get('/fa/about')->assertOk()->getContent();

        preg_match('~<title>(.*?)</title>~s', $html, $title);
        preg_match('~<meta name="description" content="(.*?)"~s', $html, $description);

        foreach (self::SPELLINGS as $name) {
            $this->assertStringContainsString($name, $title[1] ?? '', "The about title is missing \"{$name}\".");
            $this->assertStringContainsString($name, $description[1] ?? '', "The about description is missing \"{$name}\".");
        }
    }

    /**
     * Neither the Persian nor the Arabic name may leak into the other
     * language's pages — they are different transliterations, and a page
     * carrying both reads as machine output to a reader and as a duplicate
     * signal to a crawler.
     */
    public function test_the_arabic_spelling_stays_on_the_arabic_site(): void
    {
        preg_match('~<title>(.*?)</title>~s', $this->get('/fa')->getContent(), $m);

        $this->assertStringNotContainsString('آرتا ليكا', $m[1] ?? '');
    }
}
