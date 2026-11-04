<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/test-security-headers', function () {
            return response()->json(['status' => 'ok']);
        });
    }

    public function test_it_adds_security_headers_to_responses(): void
    {
        $response = $this->get('/test-security-headers');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_it_adds_hsts_header_when_request_is_secure(): void
    {
        $response = $this->get('https://localhost/test-security-headers');

        $response->assertStatus(200);
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
    }
}
