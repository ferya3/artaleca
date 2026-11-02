<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\TrustSeal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The eNamad badge in the footer.
 *
 * eNamad issues an `<a><img></a>` snippet. The site takes the seal's id and
 * Code out of it and builds the markup itself, so a settings box never becomes
 * a place to put script on every page.
 */
class TrustSealTest extends TestCase
{
    use RefreshDatabase;

    private const SNIPPET = "<a referrerpolicy='origin' target='_blank' href='https://trustseal.enamad.ir/?id=8468344&Code=7EzIUkuzLEPb9AqmxfkrcWcmzFhnzKhz'><img referrerpolicy='origin' src='https://trustseal.enamad.ir/logo.aspx?id=8468344&Code=7EzIUkuzLEPb9AqmxfkrcWcmzFhnzKhz' alt='' style='cursor:pointer' code='7EzIUkuzLEPb9AqmxfkrcWcmzFhnzKhz'></a>";

    public function test_the_issued_seal_is_in_the_footer_of_every_language(): void
    {
        foreach (['/', '/fa', '/en', '/ar', '/ru', '/ku'] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('href="https://trustseal.enamad.ir/?id=8468344&amp;Code=7EzIUkuzLEPb9AqmxfkrcWcmzFhnzKhz"', false)
                ->assertSee('src="https://trustseal.enamad.ir/logo.aspx?id=8468344&amp;Code=7EzIUkuzLEPb9AqmxfkrcWcmzFhnzKhz"', false)
                ->assertSee('referrerpolicy="origin"', false);
        }
    }

    /** Blocked by the policy, the badge is a broken image on every page. */
    public function test_the_content_security_policy_lets_the_badge_load(): void
    {
        $csp = (string) $this->get('/fa')->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression('#img-src [^;]*https://trustseal\.enamad\.ir#', $csp);
    }

    /** Whatever eNamad hands over — snippet, link, or entity-encoded source. */
    public function test_the_seal_is_read_from_whatever_was_pasted(): void
    {
        $expected = ['id' => '8468344', 'code' => '7EzIUkuzLEPb9AqmxfkrcWcmzFhnzKhz'];

        $this->assertSame($expected, TrustSeal::parse(self::SNIPPET));
        $this->assertSame($expected, TrustSeal::parse('https://trustseal.enamad.ir/?id=8468344&Code=7EzIUkuzLEPb9AqmxfkrcWcmzFhnzKhz'));
        $this->assertSame($expected, TrustSeal::parse(htmlspecialchars(self::SNIPPET)));
        $this->assertNull(TrustSeal::parse('just some text'));
        $this->assertNull(TrustSeal::parse('id=8468344'));
    }

    /** A new seal is pasted into the panel, not deployed. */
    public function test_a_seal_set_in_the_panel_replaces_the_shipped_one(): void
    {
        Setting::put(TrustSeal::SETTING, "<a href='https://trustseal.enamad.ir/?id=111&Code=AbCdEfGh12345678'></a>", 'seo', false);

        $this->get('/fa')
            ->assertSee('?id=111&amp;Code=AbCdEfGh12345678', false)
            ->assertDontSee('8468344', false);
    }

    /** What was pasted is never what is printed. */
    public function test_pasted_markup_is_not_echoed(): void
    {
        Setting::put(TrustSeal::SETTING, self::SNIPPET.'<script>alert(1)</script>', 'seo', false);

        $this->get('/fa')->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee("style='cursor:pointer'", false)
            ->assertSee('data-enamad-seal', false);
    }

    public function test_no_seal_renders_nothing(): void
    {
        config()->set('site.seo.enamad_seal', '');

        $this->get('/fa')->assertOk()->assertDontSee('data-enamad-seal', false);
    }
}
