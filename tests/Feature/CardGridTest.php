<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The card index pages, and the hole a plain grid leaves in them.
 *
 * Seven uses in three columns put one card on the last row with two empty
 * columns beside it, and an empty column reads as something that failed to
 * load rather than as the end of a list. `.card-grid` wraps with flex and
 * centres the line, so the remainder sits in the middle instead; a full row
 * fills the width exactly and is unaffected.
 *
 * There is no column count that fixes this — an editor adds the eighth record
 * and the hole moves — so the guard is on the mechanism rather than on any
 * particular page's count.
 */
class CardGridTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /** @return list<array{0: string}> */
    public static function indexPages(): array
    {
        return [
            ['/fa/applications'],
            ['/fa/products'],
            ['/fa/projects'],
            ['/fa/articles'],
            ['/fa/representatives'],
        ];
    }

    #[DataProvider('indexPages')]
    public function test_the_card_index_centres_a_short_last_row(string $path): void
    {
        $html = $this->get($path)->assertOk()->getContent();

        $this->assertStringContainsString(
            'card-grid',
            $html,
            "{$path} is not using the grid that centres a short last row.",
        );

        // The utilities it replaced. Left in place they would win over
        // `.card-grid`'s `display: flex`, and the hole would be back with the
        // class still in the markup saying otherwise.
        $this->assertStringNotContainsString(
            'grid gap-6 sm:grid-cols-2 lg:grid-cols-3',
            $html,
            "{$path} still carries the grid utilities that leave the hole.",
        );
    }

    /**
     * The rule itself, since every page above only asserts that it is asked
     * for. Checked in the built stylesheet rather than the source, because a
     * class Tailwind never emitted is a class the browser never sees.
     */
    public function test_the_rule_is_in_the_built_stylesheet(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('build/manifest.json')), true);
        $css = public_path('build/'.$manifest['resources/css/app.css']['file']);

        $this->assertFileExists($css, 'Run `npm run build` before this test.');

        $built = (string) file_get_contents($css);

        $this->assertStringContainsString('.card-grid', $built);
        $this->assertMatchesRegularExpression(
            '/\.card-grid\s*\{[^}]*justify-content:\s*center/',
            $built,
            'Without the centring, .card-grid is just a grid by another name.',
        );
    }
}
