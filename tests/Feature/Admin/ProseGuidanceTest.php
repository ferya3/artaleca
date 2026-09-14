<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * Every long-text field in the panel says how to write in it.
 *
 * `App\Support\Prose` gives an editor three conventions — a blank line for a
 * paragraph, `## ` for a subheading, `- ` for a bullet — and a convention
 * nobody is told about is a feature nobody uses. The bodies on this site were
 * all one undifferentiated run for exactly that reason: there was no way to
 * write a heading, so nobody did, so nothing had structure.
 *
 * This reads the controllers rather than the rendered form, because the hint
 * has to be on the field definition — which is the thing a new resource is
 * copied from.
 */
class ProseGuidanceTest extends TestCase
{
    /** Field name → the controller that defines it. */
    private const FIELDS = [
        'PostController' => 'body',
        'PageController' => 'body',
        'ProjectController' => 'body',
        'ProductController' => 'description',
        'ApplicationController' => 'description',
        'FaqController' => 'answer',
    ];

    public function test_every_long_text_field_tells_the_editor_how_to_write_in_it(): void
    {
        foreach (self::FIELDS as $controller => $name) {
            $source = (string) file_get_contents(app_path("Http/Controllers/Admin/{$controller}.php"));

            // One field definition: from its own `['name' => …` up to the next
            // one, so a multi-line definition is read whole.
            preg_match("/\['name' => '{$name}'.*?(?=\['name' =>|\z)/s", $source, $field);

            $this->assertNotEmpty($field, "{$controller} has no '{$name}' field where this test expects one.");

            $this->assertStringContainsString(
                "__('admin.fields.prose_hint')",
                $field[0],
                "{$controller}'s '{$name}' is a long-text field with no writing guidance. The editor has "
                .'no way to know a blank line makes a paragraph or that `## ` makes a heading.',
            );
        }
    }

    /**
     * The hint has to carry the actual markers. A hint that says "you can use
     * formatting" tells an editor nothing they can type.
     */
    public function test_the_hint_names_all_three_conventions(): void
    {
        foreach (['fa', 'en'] as $locale) {
            $hint = __('admin.fields.prose_hint', [], $locale);

            $this->assertStringContainsString('##', $hint, "The {$locale} hint does not show the heading marker.");
            $this->assertStringContainsString('###', $hint, "The {$locale} hint does not show the subheading marker.");
            $this->assertStringContainsString('-', $hint, "The {$locale} hint does not show the bullet marker.");
            $this->assertStringContainsString('HTML', $hint, "The {$locale} hint does not say HTML is inert.");
        }
    }
}
