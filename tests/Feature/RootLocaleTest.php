<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What the bare root does.
 *
 * It opens in Persian, and nothing about the visitor changes that except the
 * visitor themselves. Choosing a language from the browser header or from the
 * request's country was both built and removed: a site that picks a language
 * from where the request came from shows Googlebot — which crawls from the
 * United States — only the English pages, and the Persian ones leave the index.
 */
class RootLocaleTest extends TestCase
{
    use RefreshDatabase;

    /** Whatever the browser asks for, the site opens in Persian. */
    public function test_the_root_opens_in_persian(): void
    {
        $this->get('/')->assertRedirect('/fa');

        foreach (['en-GB,en;q=0.9', 'ar,en;q=0.8', 'de-DE,de;q=0.9', ''] as $header) {
            $this->withHeaders(['Accept-Language' => $header])->get('/')->assertRedirect('/fa');
        }
    }

    /**
     * The one thing that moves it: what the visitor last read. Not a guess
     * about them — something they told us by using the language switcher.
     */
    public function test_a_remembered_choice_is_honoured(): void
    {
        $this->withUnencryptedCookie(Locales::COOKIE, 'en')->get('/')->assertRedirect('/en');
        $this->withUnencryptedCookie(Locales::COOKIE, 'ar')->get('/')->assertRedirect('/ar');
    }

    /** A cookie holding anything else is ignored rather than trusted. */
    public function test_an_unsupported_cookie_falls_back_to_persian(): void
    {
        foreach (['de', '../fa', '', 'FA'] as $value) {
            $this->withUnencryptedCookie(Locales::COOKIE, $value)->get('/')->assertRedirect('/fa');
        }
    }

    /** Reading a page in a language is what records the choice. */
    public function test_visiting_a_language_remembers_it(): void
    {
        $this->get('/en')->assertOk()->assertPlainCookie(Locales::COOKIE, 'en');
        $this->get('/ar')->assertOk()->assertPlainCookie(Locales::COOKIE, 'ar');
    }

    /**
     * Every language URL is served exactly as asked for. This is the property
     * that keeps all three language versions in the index, and it is the reason
     * the country lookup came back out.
     */
    public function test_a_language_url_is_never_redirected(): void
    {
        $this->withHeaders(['Accept-Language' => 'fa-IR,fa;q=0.9'])->get('/en')->assertOk();
        $this->withHeaders(['Accept-Language' => 'en-US,en;q=0.9'])->get('/fa')->assertOk();

        $this->withUnencryptedCookie(Locales::COOKIE, 'fa')->get('/en/products')->assertOk();
    }
}
