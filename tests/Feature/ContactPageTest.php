<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Digits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The contact page, checked for the two things that made it unreadable.
 *
 * Persian body copy is set justified, which is right for a paragraph and wrong
 * for an address: in a narrow column it stretches four words across the full
 * measure, and a label with its value on one line ends up with the two on
 * opposite edges — so "تلفن" sat a hand's width from the number it belonged to.
 * Both are structural, both are invisible in a passing render, so both get an
 * assertion rather than an eye.
 */
class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    private function css(): string
    {
        return (string) file_get_contents(resource_path('css/app.css'));
    }

    public function test_addresses_are_not_justified(): void
    {
        $css = $this->css();

        $this->assertMatchesRegularExpression(
            '/address,\s*\n\s*address p,/',
            $css,
            'A postal address must opt out of the justified body rule.',
        );

        $this->assertStringContainsString(
            'footer address,',
            $css,
            'The footer address column is the narrowest on the site and the first to break.',
        );
    }

    /**
     * A label and its value belong to each other. In a <dl> they are a pair
     * whatever the line does; in one justified <p> they are two runs of text
     * the browser is free to push apart.
     */
    public function test_the_contact_details_are_marked_up_as_pairs(): void
    {
        $html = $this->get('/fa/contact')->assertOk()->getContent();

        $this->assertStringContainsString('<dl', $html);
        $this->assertStringContainsString('<dt', $html);

        $this->assertGreaterThanOrEqual(
            4,
            substr_count($html, '<dt'),
            'Postal code, phone, fax and the desks should each be a labelled pair.',
        );
    }

    /** Everything a visitor came for, on the page, in its own block. */
    public function test_every_address_and_desk_is_on_the_page(): void
    {
        $contact = config('site.contact');

        $this->get('/fa/contact')
            ->assertOk()
            ->assertSee(content('common.headquarters'))
            ->assertSee(content('common.plant'))
            ->assertSee(content('contact.reach_us'))
            // Persian digits on a Persian page — see LocaliseDigits.
            ->assertSee(Digits::text($contact['hq']['postal_code']))
            ->assertSee($contact['sales_email'])
            ->assertSee($contact['export_email'])
            ->assertSee('tel:'.str_replace(' ', '', $contact['phone']), false);
    }

    /** The form is still the point of the page, not something below the fold. */
    public function test_the_message_form_is_still_there(): void
    {
        $this->get('/fa/contact')
            ->assertOk()
            ->assertSee(content('form.contact_title'))
            ->assertSee('action="'.route('contact.store').'"', false);
    }
}
