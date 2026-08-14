<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

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

    public function test_the_contact_screen_offers_every_desk(): void
    {
        $html = $this->actingAs($this->makeAdmin())
            ->get('/admin/settings/contact')
            ->assertOk()
            ->getContent();

        foreach (Contact::fields() as $field) {
            $this->assertStringContainsString(
                'contact|'.$field,
                $html,
                "The contact screen is missing {$field}.",
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
