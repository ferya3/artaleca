<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\Setting;
use App\Support\DeliveryTerms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The delivery-terms select.
 *
 * It shipped as five bare Incoterm codes written into the Blade template —
 * EXW, FOB, CFR, CIF, DAP — which is two faults in one field. They could not
 * be changed without a deploy, and to almost everybody filling the form in
 * they mean nothing: a contractor ordering thirty cubic metres for a roof
 * screed has no reason to know what FOB is, and an unreadable option is one
 * that gets guessed at or left alone.
 */
class DeliveryTermsTest extends TestCase
{
    use RefreshDatabase;

    /** Every option the buyer reads carries a sentence, not just a code. */
    public function test_the_shipped_options_explain_themselves(): void
    {
        $options = DeliveryTerms::options('fa');

        $this->assertSame(['EXW', 'FOB', 'CFR', 'CIF', 'DAP'], array_keys($options));

        foreach ($options as $code => $label) {
            $this->assertStringContainsString($code, $label, "{$code} lost its code.");

            $this->assertMatchesRegularExpression(
                '/\p{Arabic}/u',
                $label,
                "{$code} is offered in Persian with no Persian in it — which is the state this field was in.",
            );
        }
    }

    public function test_each_language_gets_its_own_explanations(): void
    {
        $this->assertStringContainsString('Ex works', DeliveryTerms::options('en')['EXW']);
        $this->assertDoesNotMatchRegularExpression('/\p{Arabic}/u', DeliveryTerms::options('en')['EXW']);

        // Arabic is a real translation, not the Persian one served twice.
        $this->assertNotSame(
            DeliveryTerms::options('fa')['CIF'],
            DeliveryTerms::options('ar')['CIF'],
        );
    }

    /** The whole point of the change: an editor can rewrite the list. */
    public function test_an_editor_can_replace_the_list(): void
    {
        Setting::put(DeliveryTerms::SETTING, [
            'fa' => "EXW | تحویل درب کارخانه\nDAP | تحویل در محل شما",
        ], 'delivery');

        $options = DeliveryTerms::options('fa');

        $this->assertSame(['EXW', 'DAP'], array_keys($options));
        $this->assertSame('تحویل درب کارخانه (EXW)', $options['EXW']);
    }

    /**
     * Validation reads the same list the select is built from. Without that,
     * a term the editor adds is offered by the form and rejected by the
     * request — which looks like a broken site, not a missing option.
     */
    public function test_a_term_an_editor_adds_is_accepted_and_a_removed_one_is_not(): void
    {
        $product = $this->product();

        Setting::put(DeliveryTerms::SETTING, ['fa' => 'FCA | تحویل به حمل‌کننده'], 'delivery');

        $this->post('/fa/quote', $this->payload($product, 'FCA'))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->post('/fa/quote', $this->payload($product, 'CIF'))
            ->assertSessionHasErrors('delivery_terms');
    }

    /** An emptied setting falls back to the shipped list, never to no options. */
    public function test_clearing_the_setting_restores_the_shipped_list(): void
    {
        Setting::put(DeliveryTerms::SETTING, ['fa' => '   '], 'delivery');

        $this->assertSame(['EXW', 'FOB', 'CFR', 'CIF', 'DAP'], array_keys(DeliveryTerms::options('fa')));
    }

    /**
     * The code is stored on the enquiry, emailed, and handed to a freight
     * forwarder, so it cannot carry whatever arrived by paste — Persian
     * digits, spaces, punctuation or a line of prose.
     */
    public function test_codes_are_sanitised(): void
    {
        Setting::put(DeliveryTerms::SETTING, [
            'fa' => "  exw  | تحویل درب کارخانه\n"
                ."f.o.b — بندرعباس | تحویل روی کشتی\n"
                ."| بدون کد\n"
                ."EXW | تکراری\n"
                .'DAP',
        ], 'delivery');

        $options = DeliveryTerms::options('fa');

        // Lower case is raised, punctuation dropped, a line with no code is
        // skipped, a repeat of a code already seen is ignored, and a bare code
        // stands as its own label.
        $this->assertSame(['EXW', 'FOB', 'DAP'], array_keys($options));
        $this->assertSame('تحویل درب کارخانه (EXW)', $options['EXW']);
        $this->assertSame('DAP', $options['DAP']);
    }

    /** A paste cannot turn the select into a thousand rows. */
    public function test_the_list_is_capped(): void
    {
        $lines = collect(range(1, 60))->map(fn (int $i) => "T{$i} | شرط {$i}")->implode("\n");

        Setting::put(DeliveryTerms::SETTING, ['fa' => $lines], 'delivery');

        $this->assertLessThanOrEqual(20, count(DeliveryTerms::options('fa')));
    }

    /** The form renders the explanations, not the bare codes it used to. */
    public function test_the_quote_form_shows_the_explanations(): void
    {
        $this->product();

        $html = $this->get('/fa/quote')->assertOk()->getContent();

        $this->assertStringContainsString('تحویل درب کارخانه (EXW)', $html);

        // The old rendering was `<option value="EXW">EXW</option>`.
        $this->assertDoesNotMatchRegularExpression(
            '/<option value="EXW"[^>]*>\s*EXW\s*</',
            $html,
            'The select is still offering a bare Incoterm code.',
        );
    }

    /** And the panel says what the code means, for whoever reads the enquiry. */
    public function test_the_enquiry_screen_spells_the_code_out(): void
    {
        $product = $this->product();

        $this->post('/fa/quote', $this->payload($product, 'CIF'))->assertRedirect();

        $enquiry = ContactMessage::query()->latest('id')->firstOrFail();

        $this->actingAs($this->makeAdmin())
            ->get('/admin/enquiries/'.$enquiry->id)
            ->assertOk()
            ->assertSee('CIF — حمل و بیمه تا بندر مقصد');
    }

    /** The panel field is on its own settings page and reachable. */
    public function test_the_list_is_editable_in_the_panel(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get('/admin/settings/delivery')
            ->assertOk()
            // The shipped list shows as the placeholder, so an empty box reads
            // as "still the shipped list" rather than as nothing configured.
            ->assertSee('EXW | تحویل درب کارخانه', false);

        $this->actingAs($admin)
            ->put('/admin/settings/delivery', [
                'delivery|terms' => ['fa' => 'EXW | درب کارخانه', 'en' => '', 'ar' => ''],
            ])
            ->assertRedirect();

        $this->assertSame(['EXW'], array_keys(DeliveryTerms::options('fa')));

        // A language left blank keeps its own shipped list rather than
        // inheriting a Persian sentence nobody can read.
        $this->assertSame(['EXW', 'FOB', 'CFR', 'CIF', 'DAP'], array_keys(DeliveryTerms::options('en')));
    }

    private function product(): Product
    {
        return $this->makeProduct(['slug' => 'terms-under-test']);
    }

    /** @return array<string, mixed> */
    private function payload(Product $product, string $terms): array
    {
        return [
            'name' => 'آزمون',
            'email' => 'buyer@example.com',
            'phone' => '09120000000',
            'product_id' => $product->id,
            'quantity' => '۵۰۰ متر مکعب',
            'delivery_terms' => $terms,
            'consent' => '1',
            'started_at' => $this->humanFormToken(),
        ];
    }
}
