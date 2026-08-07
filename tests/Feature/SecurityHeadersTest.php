<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_baseline_headers_are_present(): void
    {
        $this->get('/fa')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
    }

    /**
     * A nonce-based policy is the point: 'unsafe-inline' in script-src would
     * make the CSP decorative, because any injected script would then run.
     */
    public function test_the_content_security_policy_is_nonce_based(): void
    {
        $csp = $this->get('/fa')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9]{24}'/", $csp);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
    }

    public function test_the_nonce_changes_between_requests(): void
    {
        $first = $this->get('/fa')->headers->get('Content-Security-Policy');
        $second = $this->get('/fa')->headers->get('Content-Security-Policy');

        $this->assertNotSame($first, $second);
    }

    /** The nonce in the header must match the one stamped on the inline JSON-LD. */
    public function test_the_inline_structured_data_carries_the_matching_nonce(): void
    {
        $response = $this->get('/fa');

        preg_match("/'nonce-([A-Za-z0-9]{24})'/", $response->headers->get('Content-Security-Policy'), $matches);

        $response->assertSee('nonce="'.$matches[1].'"', false);
    }

    public function test_hsts_is_only_sent_over_https(): void
    {
        $this->get('/fa')->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/fa')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
    }
}
