<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Download;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Support\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_baseline_security_headers_are_present(): void
    {
        $response = $this->get('/fa');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $this->assertNotNull($response->headers->get('Permissions-Policy'));
    }

    /**
     * The whole point of the nonce approach: an injected inline script has no
     * valid nonce, so a policy carrying 'unsafe-inline' would silently undo it.
     */
    public function test_the_csp_is_nonce_based_and_never_allows_unsafe_inline(): void
    {
        $csp = (string) $this->get('/fa')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
    }

    public function test_a_fresh_nonce_is_issued_for_every_request(): void
    {
        $first = $this->get('/fa')->headers->get('Content-Security-Policy');
        $second = $this->get('/fa')->headers->get('Content-Security-Policy');

        $this->assertNotSame($first, $second, 'The CSP nonce is being reused across requests.');
    }

    /**
     * Stored XSS through the structured-data block. Inside a <script>, the HTML
     * parser finds `</script` before the JSON parser runs, so an editor-supplied
     * title could otherwise break out and execute.
     */
    public function test_an_editor_cannot_break_out_of_the_json_ld_block(): void
    {
        $category = ProductCategory::create([
            'slug' => 'structural',
            'name' => ['fa' => 'سازه‌ای', 'en' => 'Structural', 'ar' => 'إنشائي'],
            'is_active' => true,
        ]);

        $product = Product::create([
            'product_category_id' => $category->id,
            'slug' => 'payload-grade',
            'name' => [
                'fa' => 'لیکا </script><script>alert(1)</script>',
                'en' => 'Leca </script><script>alert(1)</script>',
                'ar' => 'ليكا',
            ],
            'is_active' => true,
        ]);

        $html = $this->get("/fa/products/{$product->slug}")->assertOk()->getContent();

        $this->assertStringNotContainsString('</script><script>alert(1)', $html);
        $this->assertStringContainsString('</script>', $html);
    }

    public function test_a_stack_trace_is_never_rendered_when_debug_is_off(): void
    {
        config(['app.debug' => false]);

        $this->get('/fa/this-page-does-not-exist')
            ->assertNotFound()
            ->assertDontSee('vendor/laravel/framework')
            ->assertDontSee('Stack trace');
    }

    /** The admin is behind auth, and an anonymous hit must not reveal its shape. */
    public function test_the_admin_is_closed_to_anonymous_visitors(): void
    {
        foreach (['/admin', '/admin/products', '/admin/settings', '/admin/users'] as $path) {
            $this->get($path)->assertRedirect(route('admin.login'));
        }
    }

    /** A viewer may read; only an admin may reach users and settings. */
    public function test_a_viewer_cannot_reach_user_management(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER, 'is_active' => true]);

        $this->actingAs($viewer)->get('/admin/users')->assertForbidden();
        $this->actingAs($viewer)->get('/admin/settings')->assertForbidden();
    }

    /**
     * One message for both a wrong password and an unknown address, so the form
     * cannot be used to work out which accounts exist.
     */
    public function test_login_does_not_disclose_whether_an_account_exists(): void
    {
        $user = User::factory()->create(['email' => 'real@artaleca.com', 'is_active' => true]);

        $wrongPassword = $this->post('/admin/login', [
            'email' => 'real@artaleca.com',
            'password' => 'not-the-password',
        ]);

        $unknownAccount = $this->post('/admin/login', [
            'email' => 'nobody@artaleca.com',
            'password' => 'not-the-password',
        ]);

        $wrongPassword->assertSessionHasErrors('email');
        $unknownAccount->assertSessionHasErrors('email');

        $message = fn ($response) => $response->getSession()->get('errors')->getBag('default')->first('email');

        $this->assertSame(
            $message($wrongPassword),
            $message($unknownAccount),
            'The login form answers differently for a known and an unknown address.',
        );
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => 'real@artaleca.com', 'password' => 'wrong']);
        }

        $this->post('/admin/login', ['email' => 'real@artaleca.com', 'password' => 'wrong'])
            ->assertStatus(429);
    }

    /**
     * An upload's stored name and extension come from the sniffed MIME type,
     * never from the client — so a double extension cannot land as executable.
     */
    public function test_an_upload_cannot_be_stored_with_an_executable_name(): void
    {
        Storage::fake('media');

        $url = Media::storeImage(
            UploadedFile::fake()->image('shell.php.jpg', 400, 300),
            'products',
        );

        $this->assertStringNotContainsString('.php', $url);
        $this->assertMatchesRegularExpression('#^/storage/media/products/[A-Za-z0-9]+-\d+x\d+\.jpg$#', $url);
    }

    /** A document lives on a private disk and is only reachable when published. */
    public function test_an_unpublished_document_cannot_be_downloaded(): void
    {
        Storage::fake('documents');

        $download = Download::create([
            'slug' => 'internal-spec',
            'title' => ['fa' => 'داخلی', 'en' => 'Internal', 'ar' => 'داخلي'],
            'file_path' => 'documents/secret.pdf',
            'is_active' => false,
        ]);

        $this->get("/fa/downloads/{$download->slug}")->assertNotFound();
    }

    /** Traversal through the media deleter must not reach outside its disk. */
    public function test_the_media_deleter_refuses_to_traverse(): void
    {
        Storage::fake('media');
        Storage::disk('media')->put('keep.txt', 'keep');

        Media::deleteImage('/storage/media/../../../.env');
        Media::deleteImage('/etc/passwd');

        Storage::disk('media')->assertExists('keep.txt');
    }
}
