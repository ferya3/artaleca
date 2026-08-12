<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Geo;
use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Choosing a language from where the visitor is.
 *
 * The whole feature lives at one URL — the bare root — and the tests that
 * matter most are the ones asserting it stays there. A site that redirects by
 * address on every URL shows Googlebot, crawling from the United States, only
 * the English pages, and the Persian ones leave the index.
 */
class GeoLocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::delete(storage_path('app/'.Geo::PREFIX_FILE));
        Geo::forget();

        parent::tearDown();
    }

    private function withIranianRanges(): void
    {
        $path = storage_path('app/'.Geo::PREFIX_FILE);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, "# test fixture\n2.144.0.0/12\n5.22.0.0/17\n2001:df0:1000::/48\n");

        Geo::forget();
    }

    public function test_an_iranian_address_lands_on_the_persian_site(): void
    {
        $this->withIranianRanges();

        $this->get('/', ['REMOTE_ADDR' => '2.144.10.1'])->assertRedirect('/fa');
    }

    /**
     * The case the request was actually about: a VPN moves the address out of
     * Iran, so the site comes up in English even though the browser still asks
     * for Persian.
     */
    public function test_an_address_outside_iran_lands_on_english(): void
    {
        $this->withIranianRanges();

        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->withHeaders(['Accept-Language' => 'fa-IR,fa;q=0.9'])
            ->get('/')
            ->assertRedirect('/en');
    }

    public function test_an_arab_export_market_lands_on_arabic(): void
    {
        config(['site.geo.header' => 'CF-IPCountry']);

        $this->withHeaders(['CF-IPCountry' => 'AE'])->get('/')->assertRedirect('/ar');
    }

    /**
     * With no source configured the feature is simply off, and the site falls
     * back to the browser's own stated preference.
     */
    public function test_with_no_geo_source_the_browser_decides(): void
    {
        $this->withHeaders(['Accept-Language' => 'en-GB,en;q=0.9'])->get('/')->assertRedirect('/en');
        $this->withHeaders(['Accept-Language' => 'fa-IR,fa;q=0.9'])->get('/')->assertRedirect('/fa');
    }

    /**
     * A country header is only believed when config names one.
     *
     * Proxies are trusted at `*` so the app can see a real client address, which
     * means every request looks "trusted" and any header can be forged. Config
     * naming the header is the operator saying an edge they control sets it.
     */
    public function test_a_country_header_is_ignored_unless_config_names_it(): void
    {
        config(['site.geo.header' => null]);

        $this->withHeaders(['CF-IPCountry' => 'IR', 'Accept-Language' => 'en'])
            ->get('/')
            ->assertRedirect('/en');
    }

    /** The visitor's own last choice outranks everything geography can say. */
    public function test_a_remembered_choice_beats_the_address(): void
    {
        $this->withIranianRanges();

        $this->withUnencryptedCookie(Locales::COOKIE, 'en')
            ->get('/', ['REMOTE_ADDR' => '2.144.10.1'])
            ->assertRedirect('/en');
    }

    /** Reading a page in a language is what records the choice. */
    public function test_visiting_a_language_remembers_it(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertPlainCookie(Locales::COOKIE, 'en');
    }

    /**
     * Nothing below the root is ever redirected. This is the guard that keeps
     * the Persian pages in the index.
     */
    public function test_a_language_url_is_served_as_asked_for_whatever_the_address(): void
    {
        $this->withIranianRanges();

        // An Iranian address asking for English gets English.
        $this->get('/en/products', ['REMOTE_ADDR' => '2.144.10.1'])->assertOk();
        $this->get('/en', ['REMOTE_ADDR' => '2.144.10.1'])->assertOk();

        // And an address outside Iran asking for Persian gets Persian.
        $this->get('/fa/products', ['REMOTE_ADDR' => '8.8.8.8'])->assertOk();
    }

    /** IPv6 is matched on the same path, not skipped. */
    public function test_an_iranian_ipv6_address_is_recognised(): void
    {
        $this->withIranianRanges();

        $this->assertTrue(Geo::isIranian('2001:df0:1000::5'));
        $this->assertFalse(Geo::isIranian('2001:4860:4860::8888'));
    }

    /** Prefix matching has to respect the bit length, not just the leading bytes. */
    public function test_the_edges_of_a_range_are_respected(): void
    {
        $this->withIranianRanges();

        // 5.22.0.0/17 covers 5.22.0.0 – 5.22.127.255 and no further.
        $this->assertTrue(Geo::isIranian('5.22.127.255'));
        $this->assertFalse(Geo::isIranian('5.22.128.0'));
    }

    /** A malformed address must be a "no", never an error. */
    public function test_a_malformed_address_is_not_iranian(): void
    {
        $this->withIranianRanges();

        $this->assertFalse(Geo::isIranian('not-an-address'));
        $this->assertFalse(Geo::isIranian(''));
    }
}
