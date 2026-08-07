<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\EnquiryReceived;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'مهندس نمونه',
            'company' => 'شرکت آزمون',
            'email' => 'buyer@example.com',
            'phone' => '+98 912 000 0000',
            'subject' => 'استعلام فنی',
            'message' => 'لطفاً دیتاشیت گرید ۴ تا ۱۰ را ارسال کنید.',
            'consent' => '1',
            'website' => '',
            'started_at' => $this->humanFormToken(),
        ], $overrides);
    }

    public function test_a_valid_enquiry_is_stored_and_the_sales_desk_is_notified(): void
    {
        Mail::fake();

        $this->post('/fa/contact', $this->payload())
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseCount('contact_messages', 1);

        $message = ContactMessage::sole();
        $this->assertSame('contact', $message->type);
        $this->assertSame('fa', $message->locale);
        $this->assertSame('new', $message->status);

        Mail::assertSent(EnquiryReceived::class);
    }

    public function test_the_senders_ip_is_stored_only_as_a_hash(): void
    {
        Mail::fake();

        $this->post('/fa/contact', $this->payload());

        $message = ContactMessage::sole();

        $this->assertNotNull($message->ip_hash);
        $this->assertSame(64, strlen($message->ip_hash));
        $this->assertStringNotContainsString('127.0.0.1', $message->ip_hash);
    }

    public function test_a_filled_honeypot_is_rejected(): void
    {
        Mail::fake();

        $this->post('/fa/contact', $this->payload(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingSent();
    }

    public function test_a_submission_faster_than_a_human_could_type_is_rejected(): void
    {
        $this->post('/fa/contact', $this->payload(['started_at' => $this->humanFormToken(0)]))
            ->assertSessionHasErrors('started_at');

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_a_stale_form_is_rejected(): void
    {
        $this->post('/fa/contact', $this->payload(['started_at' => $this->humanFormToken(7201)]))
            ->assertSessionHasErrors('started_at');
    }

    /** The timing check is worthless if the timestamp can simply be edited. */
    public function test_an_unsigned_timestamp_is_rejected(): void
    {
        $this->post('/fa/contact', $this->payload(['started_at' => (string) (now()->getTimestamp() - 60)]))
            ->assertSessionHasErrors('started_at');

        $this->post('/fa/contact', $this->payload(['started_at' => (now()->getTimestamp() - 60).'.forged']))
            ->assertSessionHasErrors('started_at');
    }

    public function test_consent_is_required(): void
    {
        $this->post('/fa/contact', $this->payload(['consent' => null]))
            ->assertSessionHasErrors('consent');
    }

    public function test_required_fields_are_validated(): void
    {
        $this->post('/fa/contact', $this->payload([
            'name' => '',
            'email' => 'not-an-email',
            'message' => 'short',
        ]))->assertSessionHasErrors(['name', 'email', 'message']);
    }

    public function test_a_delivery_failure_does_not_lose_the_enquiry(): void
    {
        // The record is what the business needs; a broken SMTP server must not
        // turn a lead into a 500 page.
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('smtp down'));

        $this->post('/fa/contact', $this->payload())
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_the_form_is_rate_limited_per_ip(): void
    {
        Mail::fake();

        for ($i = 0; $i < 3; $i++) {
            $this->post('/fa/contact', $this->payload(['email' => "buyer{$i}@example.com"]));
        }

        $this->post('/fa/contact', $this->payload(['email' => 'flood@example.com']))
            ->assertStatus(429);
    }

    public function test_a_quote_request_captures_the_commercial_detail(): void
    {
        Mail::fake();

        $product = $this->makeProduct();

        $this->post('/fa/quote', $this->payload([
            'product_id' => $product->id,
            'quantity' => '500 m³',
            'delivery_terms' => 'CIF',
        ]))->assertRedirect()->assertSessionHas('status');

        $message = ContactMessage::sole();

        $this->assertSame('quote', $message->type);
        $this->assertSame($product->id, $message->product_id);
        $this->assertSame('500 m³', $message->quantity);
        $this->assertSame('CIF', $message->delivery_terms);
    }

    public function test_a_quote_cannot_reference_an_unpublished_product(): void
    {
        $product = $this->makeProduct(['is_active' => false]);

        $this->post('/fa/quote', $this->payload([
            'product_id' => $product->id,
            'quantity' => '500 m³',
        ]))->assertSessionHasErrors('product_id');
    }
}
