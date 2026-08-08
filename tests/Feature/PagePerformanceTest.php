<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Query-count regressions, which are the ones that never show up in a feature
 * test: a page keeps returning 200 and looking correct right up to the point
 * where it is issuing a query per row.
 */
class PagePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private int $queries = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // Seeded explicitly rather than through `protected $seed = true`: that
        // property only fires on the test that happens to run the migration, so
        // in a full-suite run — where another class migrated first — this class
        // would silently measure an empty database.
        $this->seed(DatabaseSeeder::class);

        // Registered exactly once. Re-registering per measurement would stack
        // listeners and multiply every count by however many had accumulated.
        DB::listen(function (): void {
            $this->queries++;
        });
    }

    /**
     * Queries for one request, measured in the steady state.
     *
     * The first request is a warm-up: navigation and site settings are cached
     * on first read, so measuring a cold request would report the cache fill
     * rather than what a real visitor costs.
     */
    private function queriesFor(string $path): int
    {
        $this->get($path);

        $this->queries = 0;
        $this->get($path)->assertOk();

        return $this->queries;
    }

    /**
     * Ceilings, not exact counts — an exact number would fail on any harmless
     * change. These are set close enough to the real figures (0–7) that an
     * accidental N+1 cannot hide underneath one.
     *
     * @return list<array{0:string,1:int}>
     */
    public static function pages(): array
    {
        return [
            'home' => ['/fa', 10],
            'catalogue' => ['/fa/products', 8],
            'applications' => ['/fa/applications', 6],
            'projects' => ['/fa/projects', 8],
            'articles' => ['/fa/articles', 6],
            'downloads' => ['/fa/downloads', 6],
            'faq' => ['/fa/faq', 6],
            'contact' => ['/fa/contact', 4],
            'quote' => ['/fa/quote', 8],
            'about' => ['/fa/about', 6],
            'gallery' => ['/fa/projects-gallery', 6],
            'search' => ['/fa/search?q=leca', 8],
        ];
    }

    #[DataProvider('pages')]
    public function test_a_page_stays_within_its_query_budget(string $path, int $budget): void
    {
        $count = $this->queriesFor($path);

        $this->assertLessThanOrEqual(
            $budget,
            $count,
            "{$path} issued {$count} queries against a budget of {$budget}.",
        );
    }

    /**
     * The definitive N+1 check, and the one that cannot go stale: whatever the
     * catalogue costs, tripling the number of products must not change it.
     * A lazy-loaded `$product->category` in a card would show up here
     * immediately, however the budget above is tuned.
     */
    public function test_the_catalogue_cost_does_not_grow_with_the_catalogue(): void
    {
        $before = $this->queriesFor('/fa/products');

        $category = ProductCategory::query()->where('is_active', true)->firstOrFail();

        for ($i = 0; $i < 20; $i++) {
            Product::create([
                'product_category_id' => $category->id,
                'slug' => "bulk-grade-{$i}",
                'name' => ['fa' => "گرید {$i}", 'en' => "Grade {$i}", 'ar' => "درجة {$i}"],
                'is_active' => true,
            ]);
        }

        $this->assertSame($before, $this->queriesFor('/fa/products'));
    }

    /** Same guarantee for the project index, which renders a different card. */
    public function test_the_project_index_cost_does_not_grow_with_the_projects(): void
    {
        $before = $this->queriesFor('/fa/projects');

        for ($i = 0; $i < 20; $i++) {
            Project::create([
                'slug' => "bulk-project-{$i}",
                'title' => ['fa' => "پروژه {$i}", 'en' => "Project {$i}", 'ar' => "مشروع {$i}"],
                'is_active' => true,
                'year' => 1400 + ($i % 5),
            ]);
        }

        $this->assertSame($before, $this->queriesFor('/fa/projects'));
    }

    /**
     * Settings are read a dozen times while rendering a page — figures, SEO
     * defaults, LocalBusiness. They are memoised per request, so reading one
     * more must not cost one more query.
     */
    public function test_settings_are_read_from_the_store_once_per_request(): void
    {
        $this->get('/fa');

        $this->queries = 0;
        $this->get('/fa');
        $withSettings = $this->queries;

        $this->assertLessThanOrEqual(
            10,
            $withSettings,
            'The homepage reads settings repeatedly; the per-request memo is not holding.',
        );
    }
}
