<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Office;
use App\Support\Contact;
use App\Support\Digits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A company with two sales offices.
 *
 * The site began with exactly two addresses fixed in a config file — one head
 * office, one plant — which models a company right up until it opens a second
 * office. These tests hold the whole path open: the two original addresses
 * survived the move into the table, a third can be added from the panel, and
 * everything that shows an address reads the same list.
 */
class OfficeTest extends TestCase
{
    use RefreshDatabase;

    private function office(array $overrides = []): Office
    {
        return Office::create(array_merge([
            'name' => ['fa' => 'دفتر فروش اردبیل', 'en' => 'Ardabil sales office', 'ar' => 'مكتب مبيعات أردبيل'],
            'kind' => Office::KIND_OFFICE,
            'address' => [
                'fa' => 'اردبیل، خیابان دانشگاه، پلاک ۱۲',
                'en' => '12 Daneshgah St., Ardabil',
                'ar' => '١٢ شارع دانشغاه، أردبيل',
            ],
            'phone' => '+98 45 3333 1111',
            'position' => 2,
            'is_active' => true,
        ], $overrides));
    }

    // ── What the move brought across ────────────────────────────────────

    public function test_the_original_two_addresses_survived_the_migration(): void
    {
        $this->assertSame(2, Office::count());

        $this->assertNotNull(Contact::headOffice());
        $this->assertNotNull(Contact::plant());

        $this->assertSame(config('site.contact.plant.lines.fa'), Contact::plant()->address);
        $this->assertSame(config('site.contact.hq.postal_code'), Contact::headOffice()->postal_code);
    }

    // ── Adding one ──────────────────────────────────────────────────────

    public function test_an_editor_can_add_an_office_and_it_reaches_the_contact_page(): void
    {
        $this->actingAs($this->makeAdmin())->post('/admin/offices', [
            'name' => ['fa' => 'دفتر فروش اردبیل', 'en' => 'Ardabil sales office', 'ar' => 'مكتب أردبيل'],
            'kind' => Office::KIND_OFFICE,
            'address' => [
                'fa' => 'اردبیل، خیابان دانشگاه، پلاک ۱۲',
                'en' => '12 Daneshgah St., Ardabil',
                'ar' => '١٢ شارع دانشغاه، أردبيل',
            ],
            'phone' => '+98 45 3333 1111',
            'position' => 2,
            'is_active' => '1',
        ])->assertRedirect();

        $this->get('/fa/contact')->assertOk()
            ->assertSee('دفتر فروش اردبیل')
            ->assertSee('اردبیل، خیابان دانشگاه، پلاک ۱۲');

        $this->get('/en/contact')->assertOk()->assertSee('12 Daneshgah St., Ardabil');
        $this->get('/ar/contact')->assertOk()->assertSee('١٢ شارع دانشغاه، أردبيل');
    }

    public function test_every_office_appears_alongside_the_others(): void
    {
        $this->office();

        $html = $this->get('/fa/contact')->assertOk()->getContent();

        foreach (Office::all() as $office) {
            $this->assertStringContainsString(
                $office->getTranslation('address', 'fa'),
                $html,
                'An office is missing from the contact page.',
            );
        }
    }

    public function test_an_editor_can_delete_an_office(): void
    {
        $office = $this->office();

        $this->actingAs($this->makeAdmin())
            ->delete('/admin/offices/'.$office->id)
            ->assertRedirect();

        $this->get('/fa/contact')->assertOk()->assertDontSee('دفتر فروش اردبیل');
    }

    public function test_an_unpublished_office_stays_off_the_page(): void
    {
        $this->office(['is_active' => false]);

        $this->get('/fa/contact')->assertOk()->assertDontSee('دفتر فروش اردبیل');
    }

    // ── Order ───────────────────────────────────────────────────────────

    /** Offices in the editor's order, and the plant after all of them. */
    public function test_the_plant_comes_after_the_offices(): void
    {
        $this->office(['position' => 99]);

        $kinds = Contact::offices()->pluck('kind')->all();

        $this->assertSame(Office::KIND_PLANT, end($kinds));
        $this->assertSame(3, count($kinds));
    }

    public function test_the_head_office_is_the_first_office_not_the_plant(): void
    {
        $this->office(['position' => 0]);

        $this->assertSame(Office::KIND_OFFICE, Contact::headOffice()->kind);
        $this->assertSame('دفتر فروش اردبیل', Contact::headOffice()->name);
    }

    // ── Everywhere else that shows an address ───────────────────────────

    public function test_the_footer_shows_the_head_office_and_the_plant(): void
    {
        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertStringContainsString(Contact::headOffice()->address, $html);
        $this->assertStringContainsString(Contact::plant()->address, $html);
    }

    public function test_the_organization_schema_follows_the_head_office(): void
    {
        $this->actingAs($this->makeAdmin())
            ->put('/admin/offices/'.Contact::headOffice()->id, [
                'name' => ['fa' => 'دفتر تهران', 'en' => 'Tehran office', 'ar' => 'مكتب طهران'],
                'kind' => Office::KIND_OFFICE,
                'address' => ['fa' => 'نشانی تازه', 'en' => 'New address', 'ar' => 'عنوان جديد'],
                'is_active' => '1',
            ])->assertRedirect();

        $this->get('/en')->assertOk()->assertSee('"streetAddress":"New address"', false);
    }

    /** The map link only appears when there are coordinates to point at. */
    public function test_the_map_link_is_only_offered_where_it_can_work(): void
    {
        $this->office(['latitude' => null, 'longitude' => null]);

        $this->assertFalse($this->office(['latitude' => null])->hasMap());
        $this->assertTrue($this->office(['latitude' => '38.24', 'longitude' => '48.29'])->hasMap());
    }

    /**
     * The coordinates build the link; they are not something a visitor reads.
     * A pair of decimals printed beside the address answers no question anyone
     * arrived with, and it was on the page for a while.
     */
    public function test_the_coordinates_are_not_printed_on_the_page(): void
    {
        $office = $this->office(['latitude' => '38.2498', 'longitude' => '48.2933']);

        $html = $this->get('/fa/contact')->assertOk()->getContent();

        // `e()`, because Blade escapes the `&` joining the two query parameters.
        $this->assertStringContainsString(e($office->mapUrl()), $html, 'The map link should still be there.');

        // The href carries them; nothing between the tags should.
        $text = strip_tags(preg_replace('/<a\b[^>]*>/', '<a>', $html) ?? $html);

        $this->assertStringNotContainsString('38.2498', $text);
        $this->assertStringNotContainsString('48.2933', $text);
        $this->assertStringNotContainsString(Digits::text('38.2498'), $text);
    }

    public function test_an_office_needs_a_name_and_an_address(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/admin/offices', ['kind' => Office::KIND_OFFICE, 'is_active' => '1'])
            ->assertSessionHasErrors(['name.fa', 'address.fa']);
    }
}
