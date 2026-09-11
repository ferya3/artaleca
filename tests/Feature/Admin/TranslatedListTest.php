<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Application;
use App\Models\Product;
use App\Models\Project;
use Database\Seeders\ApplicationSeeder;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\ProjectSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bullet lists whose entries are translated.
 *
 * A product's features, an application's benefits, a project's scope: each is a
 * JSON list whose entries are per-locale maps, and the public pages have always
 * read them that way. The admin form did not — it treated the column as a list
 * of plain strings, so every seeded record died on "Array to string conversion"
 * the moment somebody opened it to edit. It was a 500 on a screen nobody had
 * opened until the content needed changing, which is the worst time to find it.
 *
 * These tests hold both halves: the form renders whatever is in the column, and
 * saving it back keeps all three languages.
 */
class TranslatedListTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{Model, string}> */
    public static function records(): array
    {
        return [
            'application benefits' => [Application::class, 'benefits'],
            'product features' => [Product::class, 'features'],
            'project scope' => [Project::class, 'scope'],
        ];
    }

    // ── Rendering ───────────────────────────────────────────────────────

    /**
     * The regression itself: seeded content is the shape the form has to cope
     * with, and it is the shape it used to crash on.
     */
    public function test_every_seeded_record_with_a_bullet_list_can_be_opened(): void
    {
        $this->seed(CatalogueSeeder::class);
        $this->seed(ApplicationSeeder::class);
        $this->seed(ProjectSeeder::class);

        $this->withoutExceptionHandling();
        $admin = $this->makeAdmin();

        $screens = [
            'products' => Product::all(),
            'applications' => Application::all(),
            'projects' => Project::all(),
        ];

        foreach ($screens as $uri => $records) {
            $this->assertNotEmpty($records, "Nothing seeded for {$uri}; this test would prove nothing.");

            foreach ($records as $record) {
                $this->actingAs($admin)
                    ->get("/admin/{$uri}/{$record->getRouteKey()}/edit")
                    ->assertOk();
            }
        }
    }

    /** Each language gets its own box, holding its own lines. */
    public function test_the_form_shows_one_box_per_language(): void
    {
        $application = Application::create([
            'slug' => 'structural',
            'name' => ['fa' => 'بتن سبک', 'en' => 'Light concrete', 'ar' => 'خرسانة'],
            'benefits' => [
                ['fa' => 'سبک‌تر', 'en' => 'Lighter', 'ar' => 'أخف'],
                ['fa' => 'عایق', 'en' => 'Insulating', 'ar' => 'عازل'],
            ],
            'is_active' => true,
        ]);

        $html = $this->actingAs($this->makeAdmin())
            ->get('/admin/applications/'.$application->getRouteKey().'/edit')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="benefits[fa]"', $html);
        $this->assertStringContainsString('name="benefits[en]"', $html);
        $this->assertStringContainsString('name="benefits[ar]"', $html);

        // One entry per line, in the box for its own language.
        $this->assertStringContainsString("سبک‌تر\nعایق", $html);
        $this->assertStringContainsString("Lighter\nInsulating", $html);
    }

    /**
     * A list of plain strings is language-independent and stays in one box —
     * nobody should type `EN 13055-1` three times.
     */
    public function test_a_language_independent_list_keeps_a_single_box(): void
    {
        $product = $this->makeProduct(['standards' => ['EN 13055-1', 'ASTM C330']]);

        $html = $this->actingAs($this->makeAdmin())
            ->get('/admin/products/'.$product->getRouteKey().'/edit')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="standards"', $html);
        $this->assertStringNotContainsString('name="standards[fa]"', $html);
        $this->assertStringContainsString("EN 13055-1\nASTM C330", $html);
    }

    // ── Saving ──────────────────────────────────────────────────────────

    /** Three boxes in, three languages out, zipped line by line. */
    public function test_saving_keeps_all_three_languages(): void
    {
        $application = Application::create([
            'slug' => 'structural',
            'name' => ['fa' => 'بتن سبک', 'en' => 'Light concrete', 'ar' => 'خرسانة'],
            'is_active' => true,
        ]);

        $this->actingAs($this->makeAdmin())->put('/admin/applications/'.$application->getRouteKey(), [
            'name' => ['fa' => 'بتن سبک', 'en' => 'Light concrete', 'ar' => 'خرسانة'],
            'slug' => 'structural',
            'benefits' => [
                'fa' => "سبک‌تر\nعایق",
                'en' => "Lighter\nInsulating",
                'ar' => "أخف\nعازل",
            ],
            'is_active' => '1',
        ])->assertRedirect();

        $benefits = $application->fresh()->benefits;

        $this->assertSame([
            ['fa' => 'سبک‌تر', 'en' => 'Lighter', 'ar' => 'أخف'],
            ['fa' => 'عایق', 'en' => 'Insulating', 'ar' => 'عازل'],
        ], $benefits);

        // And the public pages read it back per language.
        $this->assertSame(['سبک‌تر', 'عایق'], $application->fresh()->bullets('benefits', 'fa'));
        $this->assertSame(['Lighter', 'Insulating'], $application->fresh()->bullets('benefits', 'en'));
    }

    /**
     * A line added in one language only must survive rather than being dropped
     * for want of a translation — the reader already falls back.
     */
    public function test_an_entry_translated_into_one_language_is_still_kept(): void
    {
        $application = Application::create([
            'slug' => 'structural',
            'name' => ['fa' => 'بتن سبک', 'en' => 'Light concrete', 'ar' => 'خرسانة'],
            'is_active' => true,
        ]);

        $this->actingAs($this->makeAdmin())->put('/admin/applications/'.$application->getRouteKey(), [
            'name' => ['fa' => 'بتن سبک', 'en' => 'Light concrete', 'ar' => 'خرسانة'],
            'slug' => 'structural',
            'benefits' => ['fa' => "سبک‌تر\nمورد تازه", 'en' => 'Lighter'],
            'is_active' => '1',
        ])->assertRedirect();

        $fresh = $application->fresh();

        $this->assertCount(2, $fresh->benefits);
        $this->assertSame(['سبک‌تر', 'مورد تازه'], $fresh->bullets('benefits', 'fa'));

        // The untranslated one falls back to the default language.
        $this->assertSame(['Lighter', 'مورد تازه'], $fresh->bullets('benefits', 'en'));
    }

    public function test_clearing_the_boxes_empties_the_list(): void
    {
        $application = Application::create([
            'slug' => 'structural',
            'name' => ['fa' => 'بتن سبک', 'en' => 'Light concrete', 'ar' => 'خرسانة'],
            'benefits' => [['fa' => 'سبک‌تر', 'en' => 'Lighter']],
            'is_active' => true,
        ]);

        $this->actingAs($this->makeAdmin())->put('/admin/applications/'.$application->getRouteKey(), [
            'name' => ['fa' => 'بتن سبک', 'en' => 'Light concrete', 'ar' => 'خرسانة'],
            'slug' => 'structural',
            'benefits' => ['fa' => '', 'en' => '', 'ar' => ''],
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame([], $application->fresh()->benefits);
    }

    // ── The reader ──────────────────────────────────────────────────────

    /** Both stored shapes, read by one method. */
    public function test_bullets_reads_plain_strings_and_per_locale_entries_alike(): void
    {
        $product = $this->makeProduct([
            'standards' => ['EN 13055-1', 'ASTM C330'],
            'features' => [['fa' => 'ویژگی', 'en' => 'Feature']],
        ]);

        $this->assertSame(['EN 13055-1', 'ASTM C330'], $product->bullets('standards', 'fa'));
        $this->assertSame(['EN 13055-1', 'ASTM C330'], $product->bullets('standards', 'en'));
        $this->assertSame(['ویژگی'], $product->bullets('features', 'fa'));
        $this->assertSame(['Feature'], $product->bullets('features', 'en'));
    }

    public function test_bullets_is_empty_rather_than_null_for_an_unset_column(): void
    {
        $this->assertSame([], $this->makeProduct()->bullets('features'));
    }
}
