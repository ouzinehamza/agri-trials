<?php
namespace Tests\Feature;

use Tests\TestCase;

/** SPEC §10 hardening: baseline security headers on every web response. */
class HardeningTest extends TestCase
{
    public function test_security_headers_are_present_on_web_responses(): void
    {
        $res = $this->get('/login');

        $res->assertHeader('X-Content-Type-Options', 'nosniff');
        $res->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $res->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $res->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
        $this->assertStringContainsString('camera=()', (string) $res->headers->get('Permissions-Policy'));
    }

    public function test_hsts_is_not_sent_over_plain_http(): void
    {
        // Local/CI requests are http; HSTS must not be advertised or it would poison http installs.
        $this->get('/login')->assertHeaderMissing('Strict-Transport-Security');
    }
}
