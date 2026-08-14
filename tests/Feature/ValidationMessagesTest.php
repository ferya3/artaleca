<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Validation messages have to be sentences, in all three languages.
 *
 * With no `validation.php` to resolve against, Laravel prints the key — a
 * visitor was shown `validation.uploaded` and `validation.max.string`. Nothing
 * errors, nothing logs; the form simply talks nonsense at whoever filled it in.
 */
class ValidationMessagesTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<array{0:string}> */
    public static function locales(): array
    {
        return ['fa' => ['fa'], 'en' => ['en'], 'ar' => ['ar']];
    }

    #[DataProvider('locales')]
    public function test_every_language_resolves_the_rules_the_forms_use(string $locale): void
    {
        $this->app->setLocale($locale);

        foreach (['required', 'email', 'uploaded', 'image', 'mimes', 'max.string', 'max.file', 'min.string'] as $key) {
            $message = __('validation.'.$key);

            $this->assertNotSame(
                'validation.'.$key,
                $message,
                "[{$locale}] validation.{$key} is unresolved and would print as its own key.",
            );
            $this->assertNotSame('', trim((string) $message));
        }
    }

    /** A rejected public form must show sentences, not keys. */
    public function test_a_rejected_contact_form_shows_readable_messages(): void
    {
        // Following the redirect asserts what the visitor is actually shown,
        // rather than what happens to be sitting in the session bag.
        $html = $this->from('/fa/contact')
            ->followingRedirects()
            ->post('/fa/contact', ['name' => '', 'email' => 'not-an-email', 'message' => ''])
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('validation.', $html, 'A raw validation key reached the page.');
        $this->assertStringContainsString(__('validation.email', ['attribute' => __('form.email')]), $html);
    }

    /** And so must the admin, which is where the oversized upload was rejected. */
    public function test_an_oversized_upload_is_rejected_with_a_readable_message(): void
    {
        Storage::fake('media');

        $admin = User::factory()->create([
            'email' => 'validation@artaleca.com',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $limit = (int) config('site.uploads.max_image_kb');

        $html = $this->actingAs($admin)
            ->from('/admin/site-images')
            ->followingRedirects()
            ->put('/admin/site-images', [
                'media|hero' => ['fa' => ['light' => UploadedFile::fake()->create('huge.jpg', $limit + 512, 'image/jpeg')]],
            ])
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('validation.', $html, 'A raw validation key reached the page.');
    }

    /**
     * The advertised limit must be one the server can actually accept. PHP
     * rejects anything larger before a rule runs, and the only message left is
     * `uploaded` — which names no size at all.
     */
    public function test_the_advertised_upload_limit_never_exceeds_what_php_accepts(): void
    {
        $php = php_upload_limit_kb();

        $this->assertLessThanOrEqual($php, (int) config('site.uploads.max_image_kb'));
        $this->assertLessThanOrEqual($php, (int) config('site.uploads.max_document_kb'));
    }
}
