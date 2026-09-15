<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Support\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The desks — the sales line, the export mailbox, the general address.
 *
 * These belong to no particular building, so they stay settings while the
 * addresses live in `offices`. What these tests hold is that the screen exists,
 * that a blank field still shows what the site ships with, and that nothing an
 * editor has not touched can quietly become empty.
 */
class ContactDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_settings_hub_lists_the_areas(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee(__('admin.settings_groups.contact'))
            ->assertSee(__('admin.settings_groups.figures'))
            ->assertSee('/admin/settings/contact', false);
    }

    /**
     * Every desk is editable somewhere.
     *
     * Two screens, not one: the messengers moved to their own page, because
     * they are the setting somebody comes back to change — the account that
     * answers moves between people far more often than the plant's address
     * does — while the emails and the phone lines stay with the addresses.
     * What must not happen is a desk that exists in `Contact::fields()` and on
     * no screen at all, which is a setting the site reads and nobody can set.
     */
    public function test_every_desk_is_editable_on_one_screen_or_the_other(): void
    {
        $admin = $this->makeAdmin();

        $screens = '';

        foreach (['contact', 'support'] as $group) {
            $screens .= $this->actingAs($admin)
                ->get('/admin/settings/'.$group)
                ->assertOk()
                ->getContent();
        }

        foreach (Contact::fields() as $field) {
            $this->assertStringContainsString(
                'contact|'.$field,
                $screens,
                "No settings screen offers the {$field} desk.",
            );
        }
    }

    /** An empty box shows what the site is using today, not nothing. */
    public function test_untouched_fields_show_the_shipped_value_as_a_placeholder(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin/settings/contact')
            ->assertOk()
            ->assertSee(config('site.contact.sales_email'), false)
            ->assertSee(config('site.contact.export_email'), false);
    }

    public function test_a_saved_desk_reaches_the_site(): void
    {
        $this->actingAs($this->makeAdmin())
            ->put('/admin/settings/contact', ['contact|sales_phone' => '+98 45 3333 1111'])
            ->assertRedirect('/admin/settings/contact');

        $this->get('/fa/contact')->assertOk()->assertSee('+۹۸ ۴۵ ۳۳۳۳ ۱۱۱۱');
        $this->get('/en/contact')->assertOk()->assertSee('+98 45 3333 1111');
    }

    /**
     * The nationwide line, in the three places that show it.
     *
     * It is the number to try first — one number that works from anywhere in
     * the country, where the sales line is a city code somebody has to be
     * willing to dial — so it sits ahead of the sales line in the top strip.
     * The strip is desktop-only, which is why the mobile menu carries it too:
     * without that it would be invisible to exactly the visitors most likely
     * to ring it.
     */
    public function test_the_national_line_is_shown_ahead_of_the_sales_line(): void
    {
        $this->actingAs($this->makeAdmin())
            ->put('/admin/settings/contact', ['contact|national_phone' => '+98-45-3182'])
            ->assertRedirect();

        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertStringContainsString('tel:+98-45-3182', $html);
        $this->assertStringContainsString(__('common.national_phone', [], 'en'), $html);

        /*
         * Ahead of the sales line in the strip, and in the mobile menu.
         *
         * Split on the menu's opening tag rather than on `data-mobile-menu`:
         * the head carries a critical-CSS block that names the same attribute,
         * so the bare string first matches inside <head> and would put the
         * whole header on the wrong side of the split.
         */
        $strip = substr($html, 0, (int) strpos($html, '<details data-mobile-menu'));

        $this->assertLessThan(
            strpos($strip, 'tel:'.str_replace(' ', '', config('site.contact.sales_phone'))),
            strpos($strip, 'tel:+98-45-3182'),
            'The nationwide number is not ahead of the sales line.',
        );

        $menu = substr($html, (int) strpos($html, '<details data-mobile-menu'));
        $this->assertStringContainsString('tel:+98-45-3182', $menu, 'The mobile menu does not carry it.');

        $this->get('/en/contact')->assertOk()->assertSee('+98-45-3182', false);
    }

    /** Emptied, it disappears rather than rendering a label with no number. */
    public function test_an_empty_national_line_is_not_rendered(): void
    {
        Setting::put('contact.national_phone', '', 'contact', false);
        config()->set('site.contact.national_phone', '');

        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertStringNotContainsString(__('common.national_phone', [], 'en'), $html);
    }

    /** A field nobody has filled in must not blank the site. */
    public function test_an_untouched_field_still_falls_back_to_the_shipped_value(): void
    {
        $this->assertSame(config('site.contact.sales_email'), Contact::value('sales_email'));
        $this->assertSame(config('site.contact.export_email'), Contact::value('export_email'));
    }

    public function test_an_unknown_settings_group_is_not_found(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin/settings/nonsense')
            ->assertNotFound();
    }

    public function test_an_editor_without_the_settings_gate_cannot_reach_it(): void
    {
        $this->actingAs($this->makeAdmin(User::ROLE_EDITOR))
            ->get('/admin/settings/contact')
            ->assertForbidden();
    }
}
