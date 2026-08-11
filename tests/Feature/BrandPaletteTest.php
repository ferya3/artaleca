<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The palette, checked against the thing a colour change quietly breaks.
 *
 * Retuning the site to the logo's greens dropped the accent to 4.15:1 on the
 * muted surface — invisible in a screenshot, and only caught because it was
 * measured. The eyebrow labels it colours are 11px uppercase, so they need the
 * full 4.5:1; the next person to nudge a hex value should be told the same
 * thing by a failing test rather than by a visitor who cannot read the page.
 */
class BrandPaletteTest extends TestCase
{
    /**
     * The declared palette, read from the `@theme` block in source.
     *
     * Not from the built stylesheet: Tailwind drops theme variables no utility
     * references, so a colour that exists only as a brand fact — the forest
     * green of the wordmark — is simply absent there. The declaration is what
     * this test is about anyway.
     *
     * @return array<string, string>
     */
    private function tokens(): array
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        // Stop at the end of `@theme`; the dark theme redeclares the same names
        // further down and would otherwise overwrite the light values.
        $theme = strstr(strstr($css, '@theme {') ?: '', '| Base', true) ?: $css;

        preg_match_all('/--color-([a-z0-9-]+):\s*(#[0-9a-f]{6})/i', $theme, $matches, PREG_SET_ORDER);

        $tokens = [];

        foreach ($matches as [, $name, $value]) {
            $tokens[$name] ??= strtolower($value);
        }

        return $tokens;
    }

    private function contrast(string $a, string $b): float
    {
        $luminance = function (string $hex): float {
            $channel = function (int $v): float {
                $v /= 255;

                return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
            };

            [$r, $g, $b] = [
                $channel((int) hexdec(substr($hex, 1, 2))),
                $channel((int) hexdec(substr($hex, 3, 2))),
                $channel((int) hexdec(substr($hex, 5, 2))),
            ];

            return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        };

        $one = $luminance($a);
        $two = $luminance($b);

        return (max($one, $two) + 0.05) / (min($one, $two) + 0.05);
    }

    /**
     * Small text has to clear 4.5:1 on both grounds it is ever set on.
     *
     * White alone is not enough: half the sections sit on the muted surface,
     * which is where the accent first fell short.
     *
     * @return list<array{0:string,1:string}>
     */
    public static function smallTextOnLightGrounds(): array
    {
        return [
            'accent on white' => ['brand-600', '#ffffff'],
            'accent on the muted surface' => ['brand-600', 'surface-muted'],
            'muted text on white' => ['ink-500', '#ffffff'],
            'muted text on the muted surface' => ['ink-500', 'surface-muted'],
            'body text on white' => ['ink-700', '#ffffff'],
        ];
    }

    #[DataProvider('smallTextOnLightGrounds')]
    public function test_small_text_clears_the_contrast_floor(string $foreground, string $background): void
    {
        $tokens = $this->tokens();

        $fg = $tokens[$foreground] ?? null;
        $bg = str_starts_with($background, '#') ? $background : ($tokens[$background] ?? null);

        $this->assertNotNull($fg, "No --color-{$foreground} in the theme block.");
        $this->assertNotNull($bg, "No --color-{$background} in the theme block.");

        $ratio = $this->contrast($fg, $bg);

        $this->assertGreaterThanOrEqual(
            4.5,
            round($ratio, 2),
            "{$foreground} on {$background} is ".round($ratio, 2).':1 — below AA for 11px labels.',
        );
    }

    /** The permanently-dark blocks carry their own contrast obligation. */
    public function test_text_on_the_night_ground_stays_legible(): void
    {
        $tokens = $this->tokens();

        foreach (['night-300', 'night-400'] as $step) {
            $ratio = $this->contrast($tokens[$step], $tokens['night-950']);

            $this->assertGreaterThanOrEqual(4.5, round($ratio, 2), "{$step} on night-950 is ".round($ratio, 2).':1.');
        }
    }

    /**
     * The two colours the mark is actually made of.
     *
     * Not a style opinion: if either drifts, the site stops matching the logo
     * printed on the bags, and nothing else in the codebase would say so.
     */
    public function test_the_ramp_still_holds_the_logo_greens(): void
    {
        $tokens = $this->tokens();

        $this->assertSame('#4ca62e', $tokens['brand-500'], 'brand-500 is the leaf green of the mark.');
        $this->assertSame('#14361f', $tokens['brand-900'], 'brand-900 is the forest green of the wordmark.');
    }
}
