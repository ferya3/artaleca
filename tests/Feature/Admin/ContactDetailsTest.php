<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The addresses, from the panel to the page.
 *
 * They lived in `config/site.php`, which meant moving an office was a code
 * change — and, more to the point, that an editor looking for the plant address
 * in the panel would never find it, because it was not there. These tests hold
 * the whole path open: the screen exists, saving works, the site changes, and
 * an untouched field still shows what the site shipped with.
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

    public function test_the_contact_screen_offers_every_field(): void
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
            ->assertSee(config('site.contact.plant.lines.fa'), false)
            ->assertSee(config('site.contact.sales_email'), false);
    }

    public function test_a_saved_address_reaches_the_site_in_every_language(): void
    {
        $this->actingAs($this->makeAdmin())
            ->put('/admin/settings/contact', [
                'contact|plant_lines' => [
                    'fa' => 'اردبیل، شهرک صنعتی شماره ۲',
                    'en' => 'Industrial Zone 2, Ardabil',
                    'ar' => 'المنطقة الصناعية ٢، أردبيل',
                ],
                'contact|sales_phone' => '+98 45 3333 1111',
            ])
            ->assertRedirect('/admin/settings/contact');

        $this->get('/fa/contact')->assertOk()->assertSee('اردبیل، شهرک صنعتی شماره ۲');
        $this->get('/en/contact')->assertOk()->assertSee('Industrial Zone 2, Ardabil');
        $this->get('/ar/contact')->assertOk()->assertSee('المنطقة الصناعية ٢، أردبيل');

        // The footer shows the same address on every page, from the same place.
        $this->get('/fa')->assertOk()->assertSee('اردبیل، شهرک صنعتی شماره ۲');
    }

    /** A field nobody has filled in must not blank the site. */
    public function test_an_untouched_field_still_falls_back_to_the_shipped_value(): void
    {
        $this->assertSame(config('site.contact.sales_email'), Contact::value('sales_email'));
        $this->assertSame(config('site.contact.hq.lines.fa'), Contact::lines('hq_lines'));
    }

    /** The structured data reads the same source as the page. */
    public function test_the_organization_schema_follows_the_edited_address(): void
    {
        $this->actingAs($this->makeAdmin())
            ->put('/admin/settings/contact', [
                'contact|hq_lines' => ['fa' => 'دفتر تازه', 'en' => 'New office', 'ar' => 'مكتب جديد'],
            ]);

        $this->get('/en')->assertOk()->assertSee('"streetAddress":"New office"', false);
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
