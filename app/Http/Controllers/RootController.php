<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Support\Locales;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The bare domain, `https://artaleca.com/`.
 *
 * It used to be nothing but a 302 to `/fa`. That is a perfectly good answer
 * for a browser, and the wrong one for every verifier that proves ownership
 * by reading the home page: eNamad fetches the root, does not follow
 * redirects, and reported a meta tag that was sitting on `/fa` as missing —
 * three times, once for each method it offers. Search Console's HTML-tag
 * method, Bing and Yandex all read the root the same way.
 *
 * So the root now *is* the Persian home page — served in place, 200, with
 * every verification tag in its head — and it says so: its canonical is
 * `/fa`, so a search engine files the two URLs as one page rather than as
 * duplicates.
 *
 * The visitor's own choice still wins. Somebody who last read the site in
 * English is sent to `/en` exactly as before; only "no choice made" and "chose
 * Persian" are answered here, and those two were always going to end on the
 * Persian home page anyway — this skips the round trip.
 */
class RootController extends Controller
{
    public function __invoke(Request $request, SetLocale $locale, HomeController $home): Response
    {
        $chosen = $request->cookie(Locales::COOKIE);
        $default = Locales::default();

        if (Locales::supports($chosen) && $chosen !== $default) {
            return redirect()->route('home', ['locale' => $chosen], 302);
        }

        /*
         * Through the same middleware every `/{locale}` page goes through, so
         * the page is set up identically — app locale, URL defaults, the
         * `Content-Language` header, the remembered-language cookie. There is
         * no `{locale}` segment here, which SetLocale already treats as "the
         * default".
         */
        return $locale->handle($request, function () use ($home, $default): Response {
            seo()->canonical(route('home', ['locale' => $default]));

            return response($home->index());
        });
    }
}
