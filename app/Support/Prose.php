<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Editor-written text, turned into real prose.
 *
 * Every long body on this site is stored as plain text and was rendered with
 * `nl2br(e($body))`, which produces one element containing a dozen `<br>`s. It
 * looks approximately right and is wrong in a way that compounds: there are no
 * paragraphs, so the stylesheet's paragraph spacing never applies, a screen
 * reader is read one enormous run with no structure, and nothing an editor
 * writes can ever be a heading or a list.
 *
 * So a blank line means a new paragraph — which is what everyone already types
 * — and two conventions are recognised on top of it:
 *
 *     ## A subheading            a block starting `## ` becomes an <h2>
 *     ### A smaller one          `### ` becomes an <h3>
 *     - a point                  a block of `- ` lines becomes a <ul>
 *
 * Deliberately not Markdown. A full parser brings links, images, raw HTML and
 * a dependency, and every one of those is a way for editor-supplied text to
 * become markup on the page. Here the text is escaped first and the only tags
 * that exist are the ones this class writes, so there is no path from the panel
 * to arbitrary HTML.
 */
final class Prose
{
    /** @return string escaped HTML — safe to render unescaped */
    public static function toHtml(?string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $text));

        if ($text === '') {
            return '';
        }

        $html = [];

        foreach (preg_split('/\n{2,}/', $text) ?: [] as $block) {
            $block = trim($block);

            if ($block === '') {
                continue;
            }

            $html[] = self::block($block);
        }

        return implode("\n", $html);
    }

    /** Whether there is anything to render, without building it. */
    public static function filled(?string $text): bool
    {
        return trim((string) $text) !== '';
    }

    private static function block(string $block): string
    {
        $lines = array_map('trim', explode("\n", $block));

        // A list: every line is a bullet. Not "some lines", because a paragraph
        // that happens to contain a dash should stay a paragraph.
        $bullets = array_filter($lines, fn (string $line) => (bool) preg_match('/^[-*]\s+/u', $line));

        if (count($bullets) === count($lines)) {
            $items = array_map(
                fn (string $line) => '<li>'.e(preg_replace('/^[-*]\s+/u', '', $line)).'</li>',
                $lines,
            );

            return '<ul>'.implode('', $items).'</ul>';
        }

        // A heading: the marker is on the first line, and anything after it in
        // the same block is the paragraph that follows.
        if (preg_match('/^(#{2,3})\s+(.*)$/u', $lines[0], $heading)) {
            $tag = strlen($heading[1]) === 2 ? 'h2' : 'h3';
            $rest = array_slice($lines, 1);

            return "<{$tag}>".e(trim($heading[2]))."</{$tag}>"
                .($rest === [] ? '' : '<p>'.self::lines($rest).'</p>');
        }

        return '<p>'.self::lines($lines).'</p>';
    }

    /**
     * Single newlines inside a paragraph stay newlines.
     *
     * They are how an editor writes an address or a short sequence that belongs
     * together, and turning them into spaces would silently reflow it.
     *
     * @param  list<string>  $lines
     */
    private static function lines(array $lines): string
    {
        return implode('<br>', array_map(fn (string $line) => e($line), $lines));
    }
}
