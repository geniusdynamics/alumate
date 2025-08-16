<?php

namespace Tests\Unit\Services;

use App\Models\FailedLoginAttempt;
use App\Models\SecurityEvent;
use App\Models\SessionSecurity;
use App\Models\TwoFactorAuth;
use App\Models\User;
use App\Services\SecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SecurityService $securityService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = app(SecurityService::class);
        $this->user = User::factory()->create();
    }

    public function test_can_log_security_event(): void
    {
        $event = $this->securityService->logSecurityEvent(
            SecurityEvent::TYPE_FAILED_LOGIN,
            SecurityEvent::SEVERITY_MEDIUM,
            'Test security event',
            ['test' => 'data'],
            $this->user->id
        );

        $this->assertInstanceOf(SecurityEvent::class, $event);
        $this->assertEquals(SecurityEvent::TYPE_FAILED_LOGIN, $event->event_type);
        $this->assertEquals(SecurityEvent::SEVERITY_MEDIUM, $event->severity);
        $this->assertEquals('Test security event', $event->description);
        $this->assertEquals(['test' => 'data'], $event->metadata);
        $this->assertEquals($this->user->id, $event->user_id);
    }

    public function test_can_handle_failed_login_attempt(): void
    {
        $email = 'test@example.com';
        $ip = '192.168.1.1';

        $attempt = $this->securityService->handleFailedLogin($email, $ip);

        $this->assertInstanceOf(FailedLoginAttempt::class, $attempt);
        $this->assertEquals($email, $attempt->email);
        $this->assertEquals($ip, $attempt->ip_address);
        $this->assertEquals(1, $attempt->attempts);
        $this->assertFalse($attempt->isCurrentlyBlocked());
    }

    public function test_blocks_after_max_attempts(): void
    {
        $email = 'test@example.com';
        $ip = '192.168.1.1';

        // Make 5 failed attempts
        for ($i = 1; $i <= 5; $i++) {
            $attempt = $this->securityService->handleFailedLogin($email, $ip);
        }

        $finalAttempt = FailedLoginAttempt::where('email', $email)->where('ip_address', $ip)->first();
        $this->assertTrue($finalAttempt->isCurrentlyBlocked());
    }

    public function test_can_handle_successful_login(): void
    {
        // Create a failed login attempt first
        FailedLoginAttempt::create([
            'email' => $this->user->email,
            'ip_address' => '192.168.1.1',
            'attempts' => 3,
            'last_attempt_at' => now(),
        ]);

        $this->securityService->handleSuccessfulLogin($this->user);

        // Failed login attempts should be cleared
        $this->assertDatabaseMissing('failed_login_attempts', [
            'email' => $this->user->email,
            'ip_address' => '192.168.1.1',
        ]);

        // Session should be tracked
        $this->assertDatabaseHas('session_security', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_enable_two_factor_auth(): void
    {
        $twoFactor = $this->securityService->enableTwoFactorAuth($this->user);

        $this->assertInstanceOf(TwoFactorAuth::class, $twoFactor);
        $this->assertTrue($twoFactor->enabled);
        $this->assertNotNull($twoFactor->secret);
        $this->assertNotEmpty($twoFactor->recovery_codes);
    }

    public function test_can_disable_two_factor_auth(): void
    {
        // First enable 2FA
        $twoFactor = $this->securityService->enableTwoFactorAuth($this->user);

        // Then disable it
        $this->securityService->disableTwoFactorAuth($this->user);

        $twoFactor->refresh();
        $this->assertFalse($twoFactor->enabled);
        $this->assertNull($twoFactor->secret);
        $this->assertNull($twoFactor->recovery_codes);
    }

    public function test_can_detect_rate_limit_violation(): void
    {
        $identifier = 'test_user_123';

        // Should not be rate limited initially
        $this->assertFalse($this->securityService->detectRateLimitViolation($identifier, 5, 1));

        // Simulate multiple requests
        for ($i = 0; $i < 4; $i++) {
            $this->assertFalse($this->securityService->detectRateLimitViolation($identifier, 5, 1));
        }

        // Should be rate limited on 6th attempt
        $this->assertTrue($this->securityService->detectRateLimitViolation($identifier, 5, 1));
    }

    public function test_can_detect_malicious_request(): void
    {
        // Mock a request with SQL injection attempt
        $this->app['request']->merge(['test' => "'; DROP TABLE users; --"]);

        $isMalicious = $this->securityService->detectMaliciousRequest();

        $this->assertTrue($isMalicious);

        // Should log security event
        $this->assertDatabaseHas('security_events', [
            'event_type' => SecurityEvent::TYPE_MALICIOUS_REQUEST,
            'severity' => SecurityEvent::SEVERITY_CRITICAL,
        ]);
    }

    public function test_can_validate_session_security(): void
    {
        $sessionId = 'test_session_123';
        $ipAddress = '192.168.1.1';
        $userAgent = 'Test Browser';

        // Create session
        SessionSecurity::create([
            'session_id' => $sessionId,
            'user_id' => $this->user->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'last_activity' => now(),
            'expires_at' => now()->addHours(2),
        ]);

        $isValid = $this->securityService->validateSessionSecurity($sessionId, $ipAddress, $userAgent);
        $this->assertTrue($isValid);

        // Test with different IP
        $isValid = $this->securityService->validateSessionSecurity($sessionId, '192.168.1.2', $userAgent);
        $this->assertFalse($isValid);
    }

    public function test_can_generate_security_report(): void
    {
        // Create test security events
        SecurityEvent::factory()->count(5)->create();
        FailedLoginAttempt::factory()->count(3)->create();

        $report = $this->securityService->generateSecurityReport();

        $this->assertIsArray($report);
        $this->assertArrayHasKey('events_summary', $report);
        $this->assertArrayHasKey('failed_logins', $report);
        $this->assertArrayHasKey('active_sessions', $report);
        $this->assertArrayHasKey('security_score', $report);
    }

    public function test_can_calculate_security_score(): void
    {
        $score = $this->securityService->calculateSecurityScore();

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_can_detect_suspicious_activity(): void
    {
        // Create multiple failed login attempts from same IP
        for ($i = 0; $i < 10; $i++) {
            FailedLoginAttempt::create([
                'email' => "user{$i}@example.com",
                'ip_address' => '192.168.1.1',
                'attempts' => 1,
                'last_attempt_at' => now(),
            ]);
        }

        $suspicious = $this->securityService->detectSuspiciousActivity();

        $this->assertTrue($suspicious);
    }

    public function test_can_cleanup_expired_sessions(): void
    {
        // Create expired session
        SessionSecurity::create([
            'session_id' => 'expired_session',
            'user_id' => $this->user->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
            'last_activity' => now()->subHours(3),
            'expires_at' => now()->subHour(),
        ]);

        // Create active session
        SessionSecurity::create([
            'session_id' => 'active_session',
            'user_id' => $this->user->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
            'last_activity' => now(),
            'expires_at' => now()->addHours(2),
        ]);

        $cleaned = $this->securityService->cleanupExpiredSessions();

        $this->assertEquals(1, $cleaned);
        $this->assertDatabaseMissing('session_security', ['session_id' => 'expired_session']);
        $this->assertDatabaseHas('session_security', ['session_id' => 'active_session']);
    }
}
