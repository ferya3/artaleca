<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The use and project cards open a panel instead of a page.
 *
 * Someone scanning seven uses or a row of finished projects is comparing them,
 * and a page load per card turns comparing into a sequence of back buttons. The
 * panel carries the part being compared — what the material does there and the
 * engineering case, or where a project was and what the work involved — while
 * the pages keep the photographs, the recommended grades and the reference
 * projects, and stay where they are for search traffic.
 *
 * Which is the whole reason each trigger is still an `<a href>` rather than a
 * button: the panel is an enhancement over a link that works without it. These
 * tests are server-side, so they check exactly that layer — the markup a
 * browser with no JavaScript, and a crawler, actually get.
 */
class CardDialogTest extends TestCase
{
    use RefreshDatabase;

    private function use(string $slug = 'lightweight-concrete'): Application
    {
        return Application::create([
            'slug' => $slug,
            'name' => ['fa' => 'بتن سبک', 'en' => 'Lightweight concrete', 'ar' => 'خرسانة خفيفة'],
            'summary' => ['fa' => 'خلاصه‌ی کاربرد', 'en' => 'The summary', 'ar' => 'الملخص'],
            'description' => ['fa' => 'شرح کامل کاربرد در سازه.', 'en' => 'The full description.', 'ar' => 'الوصف'],
            'benefits' => [
                ['fa' => 'کاهش وزن مرده', 'en' => 'Less dead load', 'ar' => 'وزن أقل'],
                ['fa' => 'عایق حرارتی بهتر', 'en' => 'Better insulation', 'ar' => 'عزل أفضل'],
            ],
            'position' => 1,
            'is_active' => true,
        ]);
    }

    private function project(string $slug = 'tehran-tower'): Project
    {
        return Project::create([
            'slug' => $slug,
            'title' => ['fa' => 'برج تهران', 'en' => 'Tehran tower', 'ar' => 'برج طهران'],
            'client' => ['fa' => 'کارفرمای نمونه', 'en' => 'A client', 'ar' => 'عميل'],
            'location' => ['fa' => 'تهران', 'en' => 'Tehran', 'ar' => 'طهران'],
            'year' => 1402,
            'volume_m3' => 4200,
            'summary' => ['fa' => 'خلاصه‌ی پروژه', 'en' => 'The summary', 'ar' => 'الملخص'],
            'body' => ['fa' => 'شرح کامل اجرای پروژه.', 'en' => 'The full account.', 'ar' => 'الوصف'],
            'scope' => [
                ['fa' => 'شیب‌بندی بام', 'en' => 'Roof screed', 'ar' => 'ميول السطح'],
                ['fa' => 'پرکردن حفرات', 'en' => 'Void fill', 'ar' => 'ردم الفراغات'],
            ],
            'position' => 1,
            'is_featured' => true,
            'is_active' => true,
        ]);
    }

    /** @return list<array{0: string, 1: string}> */
    public static function pagesWithCards(): array
    {
        return [
            'uses on the home page' => ['/fa', 'use-lightweight-concrete'],
            'uses on their index' => ['/fa/applications', 'use-lightweight-concrete'],
            'projects on the home page' => ['/fa', 'project-tehran-tower'],
            'projects on their index' => ['/fa/projects', 'project-tehran-tower'],
        ];
    }

    /**
     * Wherever a use card appears, the panel it opens appears with it. They are
     * one component for exactly this reason — a card rendered on a page whose
     * panel was left behind is a card that silently does nothing.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('pagesWithCards')]
    public function test_every_card_is_paired_with_the_panel_it_opens(string $path, string $id): void
    {
        $this->use();
        $this->project();

        $html = $this->get($path)->assertOk()->getContent();

        $this->assertStringContainsString('data-dialog="'.$id.'"', $html);

        $this->assertSame(
            substr_count($html, 'data-dialog="'.$id.'"'),
            substr_count($html, 'id="'.$id.'"'),
            "{$path} renders a different number of triggers and panels for {$id}.",
        );
    }

    /** The panel carries the page's own material: the description and the case. */
    public function test_the_panel_holds_the_description_and_the_engineering_case(): void
    {
        $this->use();

        $html = $this->get('/fa/applications')->assertOk()->getContent();

        $this->assertStringContainsString('شرح کامل کاربرد در سازه.', $html);
        $this->assertStringContainsString(content('applications.benefits'), $html);
        $this->assertStringContainsString('کاهش وزن مرده', $html);
        $this->assertStringContainsString('عایق حرارتی بهتر', $html);
    }

    /** And the project panel carries what a visitor is weighing on a project. */
    public function test_the_project_panel_holds_the_facts_the_account_and_the_scope(): void
    {
        $this->project();

        $html = $this->get('/fa/projects')->assertOk()->getContent();

        $this->assertStringContainsString('شرح کامل اجرای پروژه.', $html);
        $this->assertStringContainsString(content('projects.scope'), $html);
        $this->assertStringContainsString('شیب‌بندی بام', $html);

        // The four facts, labelled. `4,200` is the formatted volume; the digits
        // are rewritten to Persian on the way out, so the label is what to look
        // for rather than the number.
        foreach (['client', 'location', 'year', 'volume'] as $fact) {
            $this->assertStringContainsString(content('projects.'.$fact), $html);
        }
    }

    /**
     * The trigger stays a link to a page that still exists and still answers.
     *
     * This is the half that survives without JavaScript, and the half a crawler
     * follows — the pages were kept precisely so the search traffic they earn
     * keeps landing somewhere real.
     */
    public function test_the_cards_still_link_to_pages_that_work(): void
    {
        $application = $this->use();
        $project = $this->project();

        $this->get('/fa/applications')
            ->assertOk()
            ->assertSee(route('applications.show', ['application' => $application]), false);

        $this->get('/fa/projects')
            ->assertOk()
            ->assertSee(route('projects.show', ['project' => $project]), false);

        $this->get(route('applications.show', ['application' => $application]))->assertOk();
        $this->get(route('projects.show', ['project' => $project]))->assertOk();
    }

    /** Closing needs no script: the form is the browser's own way out. */
    public function test_the_panel_can_be_closed_without_javascript(): void
    {
        $this->use();

        $html = $this->get('/fa/applications')->assertOk()->getContent();

        $this->assertStringContainsString('<form method="dialog"', $html);
        $this->assertStringContainsString(content('common.close'), $html);
    }

    /**
     * The display rule has to stay scoped to `[open]`.
     *
     * A closed <dialog> is `display: none` from the user-agent stylesheet, so a
     * bare `.dialog-panel { display: flex }` — the obvious way to write the column
     * inside it — leaves every panel on the page open at once, stacked down the
     * middle of it. Checked in the built stylesheet, because that is the file
     * the browser reads.
     */
    public function test_the_panel_is_only_a_flex_column_when_it_is_open(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('build/manifest.json')), true);
        $css = (string) file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));

        $this->assertStringContainsString('.dialog-panel[open]', $css, 'Run `npm run build` before this test.');

        $this->assertDoesNotMatchRegularExpression(
            '/\.dialog-panel\{[^}]*display:\s*flex/',
            $css,
            'The flex column escaped the [open] guard, so every panel is open at once.',
        );

        // And the centring, which Tailwind's preflight would otherwise zero.
        $this->assertMatchesRegularExpression('/\.dialog-panel\{[^}]*margin:\s*auto/', $css);
    }
}
