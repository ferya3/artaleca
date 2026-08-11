<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Applying to represent the plant.
 *
 * Stored as an enquiry with a type of its own rather than as a new model: it
 * arrives in the same inbox, is triaged with the same statuses and answered by
 * the same desk. It carries five questions no other form asks, which live in
 * `details` rather than as five columns that are null on every other row.
 */
class RepresentationApplicationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function application(array $overrides = []): array
    {
        $stamp = now()->subMinute()->getTimestamp();

        return array_merge([
            'name' => 'رضا محمدی',
            'company' => 'بازرگانی پارس',
            'email' => 'reza@example.com',
            'phone' => '+98 912 345 6789',
            'territory' => 'استان اصفهان',
            'activity' => 'فروش مصالح ساختمانی',
            'experience_years' => 12,
            'warehouse_m2' => 800,
            'monthly_volume' => '300 m³',
            'message' => 'ناوگان حمل اختصاصی داریم و در کاشان انبار سرپوشیده.',
            'consent' => '1',
            'website' => '',
            'started_at' => $stamp.'.'.hash_hmac('sha256', (string) $stamp, (string) config('app.key')),
        ], $overrides);
    }

    public function test_the_form_is_offered_on_the_representatives_page(): void
    {
        $this->get('/fa/representatives')
            ->assertOk()
            ->assertSee(__('representatives.apply_title'))
            ->assertSee('id="apply"', false)
            ->assertSee(route('representatives.store'), false);
    }

    public function test_an_application_is_stored_with_its_structured_answers(): void
    {
        Mail::fake();

        $this->post('/fa/representatives', $this->application())
            ->assertRedirect()
            ->assertSessionHas('status');

        $enquiry = ContactMessage::sole();

        $this->assertSame('representation', $enquiry->type);
        $this->assertSame('بازرگانی پارس', $enquiry->company);

        // The five form-specific answers, kept apart from the free text so the
        // sales desk can compare one applicant with the next.
        $this->assertSame([
            'territory' => 'استان اصفهان',
            'activity' => 'فروش مصالح ساختمانی',
            'experience_years' => 12,
            'warehouse_m2' => 800,
            'monthly_volume' => '300 m³',
        ], $enquiry->details);
    }

    /**
     * A distributor with no company, phone or territory is not an application
     * the desk can assess — asking here is cheaper than a round of emails.
     */
    public function test_the_fields_that_make_an_application_assessable_are_required(): void
    {
        $this->from('/fa/representatives')
            ->post('/fa/representatives', $this->application([
                'company' => '',
                'phone' => '',
                'territory' => '',
                'activity' => '',
            ]))
            ->assertSessionHasErrors(['company', 'phone', 'territory', 'activity']);

        $this->assertSame(0, ContactMessage::count());
    }

    /** Everything that is genuinely optional must stay optional. */
    public function test_an_application_without_the_optional_answers_is_accepted(): void
    {
        Mail::fake();

        $this->post('/fa/representatives', $this->application([
            'experience_years' => '',
            'warehouse_m2' => '',
            'monthly_volume' => '',
            'message' => '',
        ]))->assertSessionHasNoErrors();

        $enquiry = ContactMessage::sole();

        $this->assertSame(['territory' => 'استان اصفهان', 'activity' => 'فروش مصالح ساختمانی'], $enquiry->details);
    }

    /** The same spam defences as every other form on the site. */
    public function test_the_honeypot_and_the_timing_check_apply(): void
    {
        $this->post('/fa/representatives', $this->application(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $now = now()->getTimestamp();

        $this->post('/fa/representatives', $this->application([
            'started_at' => $now.'.'.hash_hmac('sha256', (string) $now, (string) config('app.key')),
        ]))->assertSessionHasErrors('started_at');

        $this->assertSame(0, ContactMessage::count());
    }

    /** Consent is not a formality: without it nothing is stored. */
    public function test_consent_is_required(): void
    {
        $this->post('/fa/representatives', $this->application(['consent' => null]))
            ->assertSessionHasErrors('consent');

        $this->assertSame(0, ContactMessage::count());
    }

    /** It lands in the one inbox, filterable, and shows what was answered. */
    public function test_an_application_reaches_the_admin_inbox(): void
    {
        Mail::fake();

        $this->post('/fa/representatives', $this->application());

        $admin = User::factory()->create([
            'email' => 'desk@artaleca.com',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $enquiry = ContactMessage::sole();

        $this->actingAs($admin)
            ->get('/admin/enquiries?type=representation')
            ->assertOk()
            ->assertSee('بازرگانی پارس');

        $this->actingAs($admin)
            ->get('/admin/enquiries/'.$enquiry->id)
            ->assertOk()
            ->assertSee(__('form.territory'))
            ->assertSee('استان اصفهان')
            ->assertSee(__('form.monthly_volume'));
    }

    /** Submitting the form must not be a way to send unlimited mail. */
    public function test_the_form_is_rate_limited(): void
    {
        Mail::fake();

        $limit = 0;

        for ($attempt = 0; $attempt < 12; $attempt++) {
            $response = $this->post('/fa/representatives', $this->application([
                'email' => "applicant{$attempt}@example.com",
            ]));

            if ($response->getStatusCode() === 429) {
                $limit = $attempt;
                break;
            }
        }

        $this->assertGreaterThan(0, $limit, 'The application form accepted twelve submissions without throttling.');
    }
}
