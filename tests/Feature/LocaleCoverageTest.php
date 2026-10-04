<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A language is either on the site or it is not.
 *
 * Adding one is a single line in `config/site.php`, and everything downstream —
 * routing, `hreflang`, the sitemap, the switcher, the per-locale inputs in the
 * panel — follows from it automatically. That is the right design and it is
 * also the trap: the config line alone makes the locale *routable* while its
 * translation files are still missing, and the pages come up in English with
 * nobody told.
 *
 * These tests are what stops a half-added language from shipping.
 */
class LocaleCoverageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The panel is staff-only and its language selector offers Persian and
     * English. Translating 286 strings of admin chrome into a language no
     * editor runs the panel in is work with no reader, so it is deliberately
     * absent for the other locales — Arabic included, since before this.
     */
    private const STAFF_ONLY = ['admin'];

    /**
     * The config file, read directly.
     *
     * A data provider runs before the application is booted, so `config()`
     * has no container to resolve against and throws. Requiring the file is
     * the same source of truth without needing the framework up.
     *
     * @return list<array{0: string}>
     */
    public static function locales(): array
    {
        $site = require dirname(__DIR__, 2).'/config/site.php';

        return array_map(fn (string $code) => [$code], array_keys($site['locales']));
    }

    /** Flattened `file.key.subkey => value` for one locale. */
    private function strings(string $locale): array
    {
        $flatten = function (array $lines, string $prefix = '') use (&$flatten): array {
            $out = [];

            foreach ($lines as $key => $value) {
                $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
                $out += is_array($value) ? $flatten($value, $path) : [$path => $value];
            }

            return $out;
        };

        $strings = [];

        foreach (glob(lang_path($locale).'/*.php') ?: [] as $file) {
            $name = basename($file, '.php');

            if (in_array($name, self::STAFF_ONLY, true)) {
                continue;
            }

            foreach ($flatten(require $file) as $key => $value) {
                $strings["{$name}.{$key}"] = $value;
            }
        }

        return $strings;
    }

    #[DataProvider('locales')]
    public function test_every_locale_carries_every_string(string $locale): void
    {
        $expected = $this->strings('en');
        $actual = $this->strings($locale);

        $this->assertNotEmpty($expected, 'The English files could not be read.');

        $missing = array_keys(array_diff_key($expected, $actual));
        $extra = array_keys(array_diff_key($actual, $expected));

        $this->assertSame([], $missing, "{$locale} is missing: ".implode(', ', array_slice($missing, 0, 10)));
        $this->assertSame([], $extra, "{$locale} has keys English does not: ".implode(', ', array_slice($extra, 0, 10)));
    }

    /**
     * Present but untranslated is the failure this is really for. A file
     * copied from English and left passes every other check on the site while
     * serving English to a Russian reader.
     *
     * Short strings and placeholders are exempt: `pH`, `:type · :size` and the
     * brand name are identical in every language because they should be.
     */
    #[DataProvider('locales')]
    public function test_no_locale_is_quietly_still_english(string $locale): void
    {
        if ($locale === 'en') {
            $this->markTestSkipped('English is the yardstick.');
        }

        $english = $this->strings('en');
        $untranslated = [];

        foreach ($this->strings($locale) as $key => $value) {
            if (! is_string($value) || mb_strlen($value) < 20) {
                continue;
            }

            // A standard's designation is the same string everywhere, and
            // has to be: "ASTM C136 / ISIRI 4977" is its name, not a phrase
            // about it. Anything that is only capitals, digits and the
            // punctuation that separates them is a code, not prose.
            if (preg_match('/^[A-Z0-9][A-Z0-9 \/\-.,+()]*$/', $value)) {
                continue;
            }

            if (($english[$key] ?? null) === $value) {
                $untranslated[] = $key;
            }
        }

        $this->assertSame(
            [],
            $untranslated,
            "{$locale} still serves English for: ".implode(', ', array_slice($untranslated, 0, 10)),
        );
    }

    /**
     * Every locale reaches a real page, in its own direction.
     *
     * The direction matters more than it looks: it drives every logical
     * property in the stylesheet, so a locale declared `ltr` by mistake does
     * not merely read oddly — the layout mirrors.
     */
    #[DataProvider('locales')]
    public function test_the_locale_serves_its_own_pages(string $locale): void
    {
        $html = $this->get("/{$locale}")->assertOk()->getContent();

        $this->assertStringContainsString(
            '<html lang="'.$locale.'" dir="'.Locales::direction($locale).'"',
            $html,
            "/{$locale} does not declare itself correctly.",
        );

        // And the switcher offers every other language from it.
        foreach (Locales::codes() as $other) {
            $this->assertStringContainsString("/{$other}", $html, "No way to reach {$other} from {$locale}.");
        }
    }

    /**
     * `hreflang` is what tells a search engine these are translations rather
     * than duplicates, so the set has to be complete — every locale plus
     * `x-default` — on every page.
     */
    public function test_every_page_declares_the_whole_translation_set(): void
    {
        $html = $this->get('/ru')->assertOk()->getContent();

        preg_match_all('/hreflang="([^"]+)"/', $html, $matches);
        $found = array_unique($matches[1]);

        foreach (Locales::codes() as $code) {
            $this->assertContains(
                Locales::hreflang($code),
                $found,
                "hreflang is missing {$code} (".Locales::hreflang($code).').',
            );
        }

        $this->assertContains('x-default', $found);
    }

    /**
     * Sorani is written in the Arabic script, so it rides on the font the site
     * already ships — checked rather than hoped, because the six letters the
     * Kurdish alphabet adds to Arabic are exactly the ones a Persian-first font
     * is liable to omit.
     *
     * Russian is the opposite case and the reason `[lang='ru']` exists in the
     * stylesheet: Vazirmatn carries no Cyrillic at all.
     */
    public function test_russian_does_not_ask_for_a_font_without_cyrillic(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            "/\[lang='ru'\]\s*\{[^}]*--font-sans:/",
            $css,
            'Russian has no font override, so it would be set in a face with no Cyrillic.',
        );

        $this->assertDoesNotMatchRegularExpression(
            "/\[lang='ru'\]\s*\{[^}]*Vazirmatn/",
            $css,
            'The Russian override still names the font that cannot render it.',
        );
    }
}
