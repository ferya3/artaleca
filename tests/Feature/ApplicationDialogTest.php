<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The use cards open a panel instead of a page.
 *
 * Someone scanning seven uses is comparing them, and a page load per card turns
 * that into a sequence of back buttons. The panel carries the part being
 * compared — what the material does there, and the engineering case — while the
 * page keeps the recommended grades and the reference projects and stays where
 * it is for search traffic.
 *
 * Which is the whole reason the trigger is still an `<a href>` rather than a
 * button: the panel is an enhancement over a link that works without it. These
 * tests are server-side, so they check exactly that layer — the markup a
 * browser with no JavaScript, and a crawler, actually get.
 */
class ApplicationDialogTest extends TestCase
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

    /** @return list<array{0: string}> */
    public static function pagesWithUseCards(): array
    {
        return [['/fa'], ['/fa/applications']];
    }

    /**
     * Wherever a use card appears, the panel it opens appears with it. They are
     * one component for exactly this reason — a card rendered on a page whose
     * panel was left behind is a card that silently does nothing.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('pagesWithUseCards')]
    public function test_every_card_is_paired_with_the_panel_it_opens(string $path): void
    {
        $this->use();

        $html = $this->get($path)->assertOk()->getContent();

        $this->assertSame(
            substr_count($html, 'data-dialog="use-lightweight-concrete"'),
            substr_count($html, 'id="use-lightweight-concrete"'),
            "{$path} renders a different number of triggers and panels.",
        );

        $this->assertStringContainsString('data-dialog="use-lightweight-concrete"', $html);
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

    /**
     * The trigger stays a link to a page that still exists and still answers.
     *
     * This is the half that survives without JavaScript, and the half a crawler
     * follows — the pages were kept precisely so the search traffic they earn
     * keeps landing somewhere real.
     */
    public function test_the_card_still_links_to_a_page_that_works(): void
    {
        $application = $this->use();

        $this->get('/fa/applications')
            ->assertOk()
            ->assertSee(route('applications.show', ['application' => $application]), false);

        $this->get(route('applications.show', ['application' => $application]))->assertOk();
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
     * bare `.use-dialog { display: flex }` — the obvious way to write the column
     * inside it — leaves every panel on the page open at once, stacked down the
     * middle of it. Checked in the built stylesheet, because that is the file
     * the browser reads.
     */
    public function test_the_panel_is_only_a_flex_column_when_it_is_open(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('build/manifest.json')), true);
        $css = (string) file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));

        $this->assertStringContainsString('.use-dialog[open]', $css, 'Run `npm run build` before this test.');

        $this->assertDoesNotMatchRegularExpression(
            '/\.use-dialog\{[^}]*display:\s*flex/',
            $css,
            'The flex column escaped the [open] guard, so every panel is open at once.',
        );

        // And the centring, which Tailwind's preflight would otherwise zero.
        $this->assertMatchesRegularExpression('/\.use-dialog\{[^}]*margin:\s*auto/', $css);
    }
}
