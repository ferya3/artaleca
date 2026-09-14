<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\QuickContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The messenger button in the corner of every page.
 *
 * An enquiry form is the right way to ask for a price and the wrong way to ask
 * a question. Somebody standing on a site with a pallet of the wrong grade in
 * front of them wants an answer in the next minute, and in Iran that means
 * WhatsApp, Telegram or Rubika. What these tests hold is the part that is easy
 * to get wrong and invisible when it is: that whatever an editor pastes turns
 * into a working link, and that a channel with nothing behind it is not
 * offered at all.
 */
class QuickContactTest extends TestCase
{
    use RefreshDatabase;

    private function set(string $field, string $value): void
    {
        Setting::put('contact.'.$field, $value, 'support', false);
    }

    /**
     * Every shape an editor might paste, and the one link each becomes.
     *
     * This is the whole reason the settings store a number and a username
     * rather than a URL: nobody types a `wa.me` link, they paste what their
     * phone gave them.
     *
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public static function pastes(): array
    {
        return [
            'iranian mobile, plain' => ['whatsapp', '09123456789', 'https://wa.me/989123456789'],
            'iranian mobile, spaced' => ['whatsapp', '0912 345 6789', 'https://wa.me/989123456789'],
            'iranian mobile, dashed' => ['whatsapp', '0912-345-6789', 'https://wa.me/989123456789'],
            'iranian mobile, persian digits' => ['whatsapp', '۰۹۱۲۳۴۵۶۷۸۹', 'https://wa.me/989123456789'],
            'iranian mobile, plus 98' => ['whatsapp', '+98 912 345 6789', 'https://wa.me/989123456789'],
            'iranian mobile, 0098' => ['whatsapp', '00989123456789', 'https://wa.me/989123456789'],
            'a number from somewhere else' => ['whatsapp', '+1 415 555 0100', 'https://wa.me/14155550100'],
            'a landline, written internationally' => ['whatsapp', '+98 21 8888 0011', 'https://wa.me/982188880011'],

            'telegram handle' => ['telegram', 'artaleca', 'https://t.me/artaleca'],
            'telegram at-handle' => ['telegram', '@artaleca', 'https://t.me/artaleca'],
            'telegram full link' => ['telegram', 'https://t.me/artaleca', 'https://t.me/artaleca'],
            'telegram link, no scheme' => ['telegram', 't.me/artaleca', 'https://t.me/artaleca'],

            'rubika handle' => ['rubika', 'artaleca_sales', 'https://rubika.ir/artaleca_sales'],
            'rubika full link' => ['rubika', 'https://rubika.ir/artaleca_sales', 'https://rubika.ir/artaleca_sales'],
        ];
    }

    #[DataProvider('pastes')]
    public function test_whatever_is_pasted_becomes_the_right_link(string $field, string $pasted, string $expected): void
    {
        $this->set($field, $pasted);

        $this->assertSame($expected, QuickContact::channels()[$field]['href']);
    }

    /**
     * A channel with nothing in it is not offered.
     *
     * A dead WhatsApp link is worse than no WhatsApp link: it costs the
     * visitor the one thing they opened the button for, and it says the
     * company does not answer.
     */
    public function test_an_unset_channel_does_not_appear(): void
    {
        $channels = QuickContact::channels();

        $this->assertArrayNotHasKey('whatsapp', $channels);
        $this->assertArrayNotHasKey('telegram', $channels);
        $this->assertArrayNotHasKey('rubika', $channels);

        // The phone always is, because it has a shipped default — so the
        // button is never an empty panel.
        $this->assertArrayHasKey('phone', $channels);
    }

    /** Nor is one whose value could not be made into a link. */
    public function test_an_unusable_value_does_not_appear(): void
    {
        foreach (['whatsapp' => '02188880011', 'telegram' => '@', 'rubika' => 'a'] as $field => $junk) {
            $this->set($field, $junk);
        }

        $channels = QuickContact::channels();

        // A landline is not a WhatsApp number; `@` is not a handle; two
        // characters is below every messenger's minimum.
        $this->assertArrayNotHasKey('whatsapp', $channels);
        $this->assertArrayNotHasKey('telegram', $channels);
        $this->assertArrayNotHasKey('rubika', $channels);
    }

    /**
     * A handle is a path segment, so anything that could make it point
     * somewhere else is dropped rather than escaped.
     */
    public function test_a_handle_cannot_escape_its_path_segment(): void
    {
        foreach ([
            'artaleca/../../evil',
            'artaleca?next=evil.com',
            'artaleca#anchor',
            'evil.com/artaleca',
        ] as $attempt) {
            $this->set('telegram', $attempt);

            $href = QuickContact::channels()['telegram']['href'] ?? '';

            $this->assertMatchesRegularExpression(
                '#^https://t\.me/[A-Za-z0-9_]+$#',
                $href,
                "`{$attempt}` produced `{$href}`.",
            );
        }
    }

    /** The button renders, and every configured channel is in it. */
    public function test_the_button_is_on_every_page(): void
    {
        $this->set('whatsapp', '09123456789');
        $this->set('telegram', '@artaleca');
        $this->set('rubika', '@artaleca');

        foreach (['/fa', '/fa/products', '/fa/contact'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertStringContainsString('data-quick-contact', $html, "No support button on {$path}.");
            $this->assertStringContainsString('https://wa.me/989123456789', $html);
            $this->assertStringContainsString('https://t.me/artaleca', $html);
            $this->assertStringContainsString('https://rubika.ir/artaleca', $html);
        }
    }

    /**
     * It is a `<details>`, which is what makes it work with no JavaScript —
     * the same decision the mobile menu made. The one control on the site
     * whose job is to rescue a stuck visitor must not depend on a script
     * having loaded.
     */
    public function test_the_button_needs_no_javascript(): void
    {
        $this->set('telegram', '@artaleca');

        $html = $this->get('/fa')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<details[^>]*class="quick-contact[^"]*"[^>]*data-quick-contact/',
            $html,
        );
        $this->assertStringContainsString('quick-contact__toggle', $html);
    }

    /** Every messenger link leaves the site, and safely. */
    public function test_messenger_links_open_away_and_carry_no_referrer(): void
    {
        $this->set('whatsapp', '09123456789');

        $html = $this->get('/fa')->assertOk()->getContent();

        preg_match('/<a[^>]*href="https:\/\/wa\.me[^"]*"[^>]*>/', $html, $anchor);
        $this->assertNotEmpty($anchor);

        $this->assertStringContainsString('target="_blank"', $anchor[0]);
        $this->assertStringContainsString('rel="noopener noreferrer"', $anchor[0]);

        // But not the phone: `target=_blank` on a `tel:` leaves an empty tab
        // behind on a desktop browser.
        preg_match('/<a[^>]*href="tel:[^"]*"[^>]*class="quick-contact__row"[^>]*>/', $html, $phone);
        $this->assertEmpty($phone ? array_filter($phone, fn ($m) => str_contains($m, '_blank')) : []);
    }

    /** The labels are the app names, in each language. */
    public function test_the_channels_are_named_in_every_language(): void
    {
        $this->set('whatsapp', '09123456789');

        foreach (['fa' => 'واتساپ', 'en' => 'WhatsApp', 'ar' => 'واتساب'] as $locale => $name) {
            $this->get('/'.$locale)->assertOk()->assertSee($name, false);
        }
    }

    /**
     * The number is shown in Latin digits, because the site localises digits
     * per language on the way out. An editor types `۰۹۱۲…` and those Persian
     * digits would otherwise be served untouched to an English reader.
     */
    public function test_a_persian_number_reaches_the_english_page_in_latin(): void
    {
        $this->set('whatsapp', '۰۹۱۲ ۳۴۵ ۶۷۸۹');

        $this->get('/en')->assertOk()->assertSee('09123456789', false);
        $this->get('/fa')->assertOk()->assertSee('۰۹۱۲۳۴۵۶۷۸۹', false);
    }

    /** The contact page carries the same channels, not a slower subset. */
    public function test_the_contact_page_lists_the_messengers(): void
    {
        $this->set('telegram', '@artaleca');

        $this->get('/fa/contact')
            ->assertOk()
            ->assertSee(content('contact.messengers'), false)
            ->assertSee('https://t.me/artaleca', false);
    }

    /** And the panel is where all three are set. */
    public function test_the_accounts_are_editable_in_the_panel(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get('/admin/settings/support')
            ->assertOk()
            ->assertSee('contact|whatsapp', false)
            ->assertSee('contact|telegram', false)
            ->assertSee('contact|rubika', false);

        $this->actingAs($admin)
            ->put('/admin/settings/support', [
                'contact|whatsapp' => '0912 345 6789',
                'contact|telegram' => '@artaleca',
                'contact|rubika' => 'artaleca',
                'contact|sales_phone' => '+98 21 8888 0011',
            ])
            ->assertRedirect();

        $this->assertSame(
            ['whatsapp', 'telegram', 'rubika', 'phone'],
            array_keys(QuickContact::channels()),
        );
    }
}
