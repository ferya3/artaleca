<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every picture upload in the panel says what shape it wants.
 *
 * The same photograph is shown at two different aspect ratios in several
 * places — a use is square on its card and wide on its page, a project is 3:2
 * then 16:9 — so an upload that looks right in the form comes back cropped
 * through the head or the feet somewhere else. That is not something an editor
 * can be expected to work out from the result; it has to be said at the point
 * of upload.
 *
 * Eight of the nine fields said nothing at all.
 */
class UploadGuidanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Reads the controllers rather than the rendered form, because the
     * guidance has to be attached to the field definition — which is the thing
     * a new resource copies from an existing one.
     *
     * @return list<array{0: string}>
     */
    public static function controllers(): array
    {
        return array_map(
            fn (string $name) => [$name],
            ['Product', 'ProductCategory', 'Project', 'Post', 'Page', 'Application'],
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('controllers')]
    public function test_the_image_field_tells_the_editor_what_shape_to_upload(string $controller): void
    {
        $source = (string) file_get_contents(app_path("Http/Controllers/Admin/{$controller}Controller.php"));

        preg_match_all("/\['name' => '[a-z_]*(?:image|cover|logo|path)[a-z_]*'.*?\],/s", $source, $fields);

        $this->assertNotEmpty($fields[0], "{$controller}Controller has no image field where this test expects one.");

        foreach ($fields[0] as $field) {
            if (! str_contains($field, "'type' => 'image'")) {
                continue;
            }

            $this->assertStringContainsString(
                "'hint'",
                $field,
                "{$controller}Controller has a picture upload with no hint. The editor cannot see "
                .'which aspect ratio it will be cropped to.',
            );
        }
    }

    /**
     * A hint that does not name a ratio is decoration. Every one of these
     * fields feeds a fixed frame, and the number is the only part an editor
     * can act on before choosing the photograph.
     */
    public function test_each_hint_names_the_ratio_it_will_be_cropped_to(): void
    {
        $hints = [
            'image_product_hint' => '۱:۱',
            'image_square_hint' => '۱:۱',
            'image_project_hint' => '۱:۱',
            'image_post_hint' => '۱۶:۹',
            'image_page_hint' => '۱۶:۹',
            'image_category_hint' => '۱٫۹۱:۱',
        ];

        foreach ($hints as $key => $ratio) {
            $this->assertStringContainsString(
                $ratio,
                __("admin.fields.{$key}", [], 'fa'),
                "The Persian {$key} no longer names the {$ratio} frame it is cropped to.",
            );
        }
    }
}
