<?php

namespace Tests\Feature\Security;

use App\Services\SecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SecurityMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected SecurityService $securityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = app(SecurityService::class);
    }

    /** @test */
    public function it_detects_sql_injection_patterns()
    {
        $request = Request::create('/test', 'POST', [
            'input' => 'SELECT * FROM users WHERE id = 1 UNION SELECT password FROM admin'
        ]);

        $result = $this->securityService->checkSuspiciousPatterns($request);

        $this->assertTrue($result, 'Should detect SQL injection pattern');
    }

    /** @test */
    public function it_detects_xss_patterns()
    {
        $request = Request::create('/test', 'POST', [
            'input' => '<script>alert("XSS")</script>'
        ]);

        $result = $this->securityService->checkSuspiciousPatterns($request);

        $this->assertTrue($result, 'Should detect XSS pattern');
    }

    /** @test */
    public function it_detects_command_injection_patterns()
    {
        $request = Request::create('/test', 'POST', [
            'input' => '; rm -rf /'
        ]);

        $result = $this->securityService->checkSuspiciousPatterns($request);

        $this->assertTrue($result, 'Should detect command injection pattern');
    }

    /** @test */
    public function it_allows_normal_input()
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a normal message'
        ]);

        $result = $this->securityService->checkSuspiciousPatterns($request);

        $this->assertFalse($result, 'Should allow normal input');
    }

    /** @test */
    public function it_handles_rate_limiting()
    {
        $identifier = 'test-user-123';

        // First few attempts should pass
        for ($i = 0; $i < 5; $i++) {
            $result = $this->securityService->detectRateLimitViolation($identifier, 5, 1);
            $this->assertFalse($result, "Attempt $i should not be rate limited");
        }

        // Next attempt should be rate limited
        $result = $this->securityService->detectRateLimitViolation($identifier, 5, 1);
        $this->assertTrue($result, 'Should be rate limited after 5 attempts');
    }

    /** @test */
    public function it_handles_geoip_without_database()
    {
        $result = $this->securityService->isIpBlocked('127.0.0.1');

        // Should return false when database doesn't exist (graceful degradation)
        $this->assertFalse($result, 'Should gracefully handle missing GeoIP database');
    }
}