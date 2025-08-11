<?php

namespace Tests\Feature;

use App\Models\FailedLoginAttempt;
use App\Models\SecurityEvent;
use App\Models\SessionSecurity;
use App\Models\User;
use App\Services\SecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecuritySystemTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $securityService;

    protected $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->securityService = app(SecurityService::class);

        // Create roles
        Role::create(['name' => 'super-admin']);
        Role::create(['name' => 'graduate']);

        // Create super admin user
        $this->superAdmin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
        ]);
        $this->superAdmin->assignRole('super-admin');
    }

    public function test_security_event_logging()
    {
        $event = $this->securityService->logSecurityEvent(
            SecurityEvent::TYPE_FAILED_LOGIN,
            SecurityEvent::SEVERITY_MEDIUM,
            'Test security event',
            ['test' => 'data'],
            $this->superAdmin->id
        );

        $this->assertInstanceOf(SecurityEvent::class, $event);
        $this->assertEquals(SecurityEvent::TYPE_FAILED_LOGIN, $event->event_type);
        $this->assertEquals(SecurityEvent::SEVERITY_MEDIUM, $event->severity);
        $this->assertEquals('Test security event', $event->description);
        $this->assertEquals(['test' => 'data'], $event->metadata);
        $this->assertEquals($this->superAdmin->id, $event->user_id);
    }

    public function test_failed_login_attempt_tracking()
    {
        $email = 'test@example.com';
        $ip = '192.168.1.1';

        // First attempt
        $attempt1 = $this->securityService->handleFailedLogin($email, $ip);
        $this->assertEquals(1, $attempt1->attempts);
        $this->assertFalse($attempt1->isCurrentlyBlocked());

        // Multiple attempts to trigger blocking
        for ($i = 2; $i <= 5; $i++) {
            $attempt = $this->securityService->handleFailedLogin($email, $ip);
            $this->assertEquals($i, $attempt->attempts);
        }

        // Should be blocked after 5 attempts
        $finalAttempt = FailedLoginAttempt::where('email', $email)->where('ip_address', $ip)->first();
        $this->assertTrue($finalAttempt->isCurrentlyBlocked());
    }

    public function test_successful_login_handling()
    {
        $user = User::factory()->create();
        $user->assignRole('graduate');

        // Create a failed login attempt first
        FailedLoginAttempt::create([
            'email' => $user->email,
            'ip_address' => '192.168.1.1',
            'attempts' => 3,
            'last_attempt_at' => now(),
        ]);

        $this->securityService->handleSuccessfulLogin($user);

        // Failed login attempts should be cleared
        $this->assertDatabaseMissing('failed_login_attempts', [
            'email' => $user->email,
            'ip_address' => '192.168.1.1',
        ]);

        // Session should be tracked
        $this->assertDatabaseHas('session_security', [
            'user_id' => $user->id,
        ]);
    }

    public function test_two_factor_auth_enable_disable()
    {
        $user = User::factory()->create();

        // Enable 2FA
        $twoFactor = $this->securityService->enableTwoFactorAuth($user);

        $this->assertTrue($twoFactor->enabled);
        $this->assertNotNull($twoFactor->secret);
        $this->assertNotEmpty($twoFactor->recovery_codes);

        // Disable 2FA
        $this->securityService->disableTwoFactorAuth($user);

        $twoFactor->refresh();
        $this->assertFalse($twoFactor->enabled);
        $this->assertNull($twoFactor->secret);
        $this->assertNull($twoFactor->recovery_codes);
    }

    public function test_rate_limit_detection()
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

    public function test_malicious_request_detection()
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

    public function test_security_dashboard_access()
    {
        $this->actingAs($this->superAdmin)
            ->get(route('security.dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Security/Dashboard'));
    }

    public function test_security_dashboard_unauthorized_access()
    {
        $regularUser = User::factory()->create();
        $regularUser->assignRole('graduate');

        $this->actingAs($regularUser)
            ->get(route('security.dashboard'))
            ->assertStatus(403);
    }

    public function test_security_events_listing()
    {
        // Create test security events
        SecurityEvent::factory()->count(5)->create();

        $this->actingAs($this->superAdmin)
            ->get(route('security.events'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Security/Events'));
    }

    public function test_security_event_resolution()
    {
        $event = SecurityEvent::create([
            'event_type' => SecurityEvent::TYPE_SUSPICIOUS_ACTIVITY,
            'severity' => SecurityEvent::SEVERITY_HIGH,
            'ip_address' => '192.168.1.1',
            'description' => 'Test event',
            'resolved' => false,
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('security.events.resolve', $event), [
                'notes' => 'Resolved by admin',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $event->refresh();
        $this->assertTrue($event->resolved);
        $this->assertEquals($this->superAdmin->id, $event->resolved_by);
        $this->assertEquals('Resolved by admin', $event->resolution_notes);
    }

    public function test_failed_logins_listing()
    {
        // Create test failed login attempts
        FailedLoginAttempt::create([
            'email' => 'test@example.com',
            'ip_address' => '192.168.1.1',
            'attempts' => 3,
            'last_attempt_at' => now(),
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('security.failed-logins'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Security/FailedLogins'));
    }

    public function test_ip_unblocking()
    {
        $attempt = FailedLoginAttempt::create([
            'email' => 'test@example.com',
            'ip_address' => '192.168.1.1',
            'attempts' => 5,
            'last_attempt_at' => now(),
            'blocked_until' => now()->addMinutes(30),
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('security.unblock-ip'), [
                'email' => 'test@example.com',
                'ip_address' => '192.168.1.1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $attempt->refresh();
        $this->assertNull($attempt->blocked_until);
        $this->assertEquals(0, $attempt->attempts);
    }

    public function test_active_sessions_listing()
    {
        // Create test session
        SessionSecurity::create([
            'session_id' => 'test_session_123',
            'user_id' => $this->superAdmin->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
            'last_activity' => now(),
            'expires_at' => now()->addHours(2),
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('security.sessions'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Security/ActiveSessions'));
    }

    public function test_session_termination()
    {
        $session = SessionSecurity::create([
            'session_id' => 'test_session_123',
            'user_id' => $this->superAdmin->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
            'last_activity' => now(),
            'expires_at' => now()->addHours(2),
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('security.sessions.terminate', $session))
            ->assertRedirect()
            ->assertSessionHas('success');

        $session->refresh();
        $this->assertTrue($session->expires_at->isPast());
    }

    public function test_two_factor_setup_page()
    {
        $this->actingAs($this->superAdmin)
            ->get(route('security.two-factor.setup'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Security/TwoFactorSetup'));
    }

    public function test_system_health_monitoring()
    {
        $this->actingAs($this->superAdmin)
            ->get(route('security.system-health'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Security/SystemHealth'));
    }
}
