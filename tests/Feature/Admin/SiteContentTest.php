<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Support\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Page copy, editable without a deploy.
 *
 * The translation files stay the source of truth and the fallback; the admin
 * writes overrides on top. That arrangement is the whole design, so these tests
 * are mostly about it holding in both directions — an override reaching the
 * page, and clearing one putting the shipped text back.
 */
class SiteContentTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    private function admin(): User
    {
        return User::factory()->create([
            'email' => 'content'.(++self::$sequence).'@artaleca.com',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_the_hub_lists_every_area_of_the_site(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/content')->assertOk()->getContent();

        foreach (SiteContent::GROUPS as $group) {
            $this->assertStringContainsString(
                route('admin.content.edit', $group),
                $html,
                "The content hub is missing the {$group} area.",
            );
        }
    }

    public function test_an_area_offers_every_string_it_declares_in_all_three_languages(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/content/home')->assertOk()->getContent();

        foreach (array_keys(SiteContent::keys('home')) as $key) {
            $field = str_replace('.', '|', $key);

            foreach (['fa', 'en', 'ar'] as $locale) {
                $this->assertStringContainsString("{$field}[{$locale}]", $html);
            }
        }
    }

    /** The schema comes off the translation files, so it cannot go stale. */
    public function test_the_keys_are_read_from_the_translation_files(): void
    {
        $keys = SiteContent::keys('home');

        $this->assertArrayHasKey('intro_title', $keys);
        $this->assertSame(__('home.intro_title', [], 'fa'), $keys['intro_title']);

        $this->assertSame([], SiteContent::keys('validation'), 'validation is deliberately not editable here.');
    }

    /**
     * Each language renders what was written for it.
     *
     * All three are posted because that is what the form does — it arrives
     * prefilled with the current text, so every language comes back on save
     * whether or not it was touched.
     */
    public function test_an_override_reaches_the_page_in_the_language_it_was_written_for(): void
    {
        $this->actingAs($this->admin())->put('/admin/content/home', [
            'intro_title' => [
                'fa' => 'عنوان تازه',
                'en' => 'A fresh heading',
                'ar' => __('home.intro_title', [], 'ar'),
            ],
        ])->assertRedirect();

        $this->get('/fa')->assertOk()->assertSee('عنوان تازه');
        $this->get('/en')->assertOk()->assertSee('A fresh heading');
        $this->get('/ar')->assertOk()->assertSee(__('home.intro_title', [], 'ar'));
    }

    /**
     * Clearing a field removes the text from the page.
     *
     * It used to put the shipped wording back, which read as the save having
     * failed — the editor deleted a sentence, saved, and watched it return. The
     * form is prefilled with what the page renders, so an empty box is a
     * deliberate "remove this", and the resolver tells "no entry" apart from
     * "an entry that is empty".
     */
    public function test_clearing_a_field_removes_the_text_from_the_page(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put('/admin/content/home', ['intro_title' => ['fa' => 'عنوان تازه']]);
        $this->get('/fa')->assertSee('عنوان تازه');

        $this->actingAs($admin)->put('/admin/content/home', ['intro_title' => ['fa' => '']]);

        $this->get('/fa')
            ->assertOk()
            ->assertDontSee('عنوان تازه')
            ->assertDontSee(__('home.intro_title', [], 'fa'));
    }

    /** Reset is the way back, and the only one once a field has been saved. */
    public function test_reset_restores_the_shipped_wording(): void
    {
        $admin = $this->admin();
        $field = 'intro_title';

        $this->actingAs($admin)->put('/admin/content/home', [$field => ['fa' => '']]);
        $this->get('/fa')->assertDontSee(__('home.intro_title', [], 'fa'));

        $this->actingAs($admin)->put('/admin/content/home', [
            $field => ['fa' => ''],
            'reset' => [$field => '1'],
        ]);

        $this->assertNull(Setting::get('content.home.'.$field));
        $this->get('/fa')->assertOk()->assertSee(__('home.intro_title', [], 'fa'));
    }

    /**
     * A language left alone keeps rendering its own shipped text rather than
     * being blanked along with the one that was edited.
     */
    public function test_editing_one_language_does_not_blank_the_others(): void
    {
        $this->actingAs($this->admin())->put('/admin/content/home', [
            'intro_title' => ['fa' => 'عنوان تازه', 'en' => 'A fresh heading', 'ar' => __('home.intro_title', [], 'ar')],
        ]);

        $this->get('/ar')->assertOk()->assertSee(__('home.intro_title', [], 'ar'));
    }

    /** The form arrives holding the text the site is rendering right now. */
    public function test_the_form_is_prefilled_with_what_the_page_shows(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/content/home')->assertOk()->getContent();

        $this->assertStringContainsString(e(__('home.intro_title', [], 'fa')), $html);
        $this->assertStringContainsString('reset[intro_title]', $html);
    }

    /** Placeholders have to survive an override, or `:count` reaches the page. */
    public function test_replacements_still_work_inside_an_override(): void
    {
        Setting::put('content.articles.reading_time', ['fa' => ':minutes دقیقه خواندن'], 'content');

        $this->assertSame('7 دقیقه خواندن', content('articles.reading_time', ['minutes' => 7]));
    }

    /**
     * A paragraph typed with Enter has to arrive on the page with its breaks.
     *
     * `{{ }}` escapes, so a newline came through as one run-on line — the
     * editor could see their paragraphs in the textarea and nowhere else. The
     * override is escaped here first and only the breaks this code writes
     * survive, so an editor still cannot inject markup.
     */
    public function test_line_breaks_typed_by_an_editor_reach_the_page(): void
    {
        $this->actingAs($this->admin())->put('/admin/content/home', [
            'intro_body' => ['fa' => "خط نخست\nخط دوم"],
        ]);

        $this->get('/fa')->assertOk()->assertSee('خط نخست<br>خط دوم', false);
    }

    /** A line an editor opened with a dash is what they meant as a bullet. */
    public function test_a_dashed_line_becomes_a_bullet(): void
    {
        $this->actingAs($this->admin())->put('/admin/content/home', [
            'intro_body' => ['fa' => "سه مزیت:\n- سبکی\n- عایق حرارتی"],
        ]);

        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString('<span class="copy-bullet">سبکی</span>', $html);
        $this->assertStringContainsString('<span class="copy-bullet">عایق حرارتی</span>', $html);
    }

    /** Escaping still happens; only the breaks this code writes get through. */
    public function test_an_override_cannot_smuggle_markup_onto_the_page(): void
    {
        $this->actingAs($this->admin())->put('/admin/content/home', [
            'intro_body' => ['fa' => "سلام\n<script>alert(1)</script>"],
        ]);

        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** A single-line override stays a plain string, so attributes stay safe. */
    public function test_a_single_line_override_is_still_escaped_as_a_string(): void
    {
        $this->actingAs($this->admin())->put('/admin/content/home', [
            'intro_title' => ['fa' => '<b>bold</b>'],
        ]);

        $this->get('/fa')->assertOk()->assertDontSee('<b>bold</b>', false)->assertSee('&lt;b&gt;', false);
    }

    /** An area that does not exist must 404 rather than render an empty form. */
    public function test_an_unknown_area_is_not_found(): void
    {
        $this->actingAs($this->admin())->get('/admin/content/nonsense')->assertNotFound();
        $this->actingAs($this->admin())->put('/admin/content/validation', [])->assertNotFound();
    }

    /** A viewer must not be able to rewrite the site. */
    public function test_a_viewer_cannot_edit_the_content(): void
    {
        $viewer = User::factory()->create([
            'email' => 'viewer'.(++self::$sequence).'@artaleca.com',
            'role' => User::ROLE_VIEWER,
            'is_active' => true,
        ]);

        $this->actingAs($viewer)->get('/admin/content')->assertForbidden();
        $this->actingAs($viewer)->get('/admin/content/home')->assertForbidden();
    }

    /**
     * Navigation labels are in here and they are cached, so saving has to drop
     * that cache or the menu keeps the old wording until something else does.
     */
    public function test_saving_navigation_copy_flushes_the_navigation_cache(): void
    {
        $this->actingAs($this->admin())->put('/admin/content/nav', [
            'products' => ['fa' => 'کالاها'],
        ])->assertRedirect();

        $this->get('/fa')->assertOk()->assertSee('کالاها');
    }
}
