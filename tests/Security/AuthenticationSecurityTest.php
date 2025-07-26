<?php

namespace Tests\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\FailedLoginAttempt;
use App\Models\SecurityEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_prevents_brute_force_login_attacks(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password')
        ]);

        // Attempt multiple failed logins
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('login'), [
                'email' => 'test@example.com',
                'password' => 'wrong-password'
            ]);
            
            $response->assertSessionHasErrors('email');
        }

        // Check that account is locked after max attempts
        $failedAttempt = FailedLoginAttempt::where('email', 'test@example.com')->first();
        $this->assertNotNull($failedAttempt);
        $this->assertTrue($failedAttempt->isCurrentlyBlocked());

        // Verify that even correct password is rejected when blocked
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'correct-password'
        ]);
        
        $response->assertSessionHasErrors();
        $this->assertGuest();

        // Verify security event was logged
        $this->assertDatabaseHas('security_events', [
            'event_type' => SecurityEvent::TYPE_FAILED_LOGIN,
            'severity' => SecurityEvent::SEVERITY_MEDIUM
        ]);
    }

    public function test_rate_limits_login_attempts_per_ip(): void
    {
        // Clear any existing rate limits
        RateLimiter::clear('login.127.0.0.1');

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password')
        ]);

        // Make rapid login attempts from same IP
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post(route('login'), [
                'email' => 'test@example.com',
                'password' => 'wrong-password'
            ]);
        }

        // Next attempt should be rate limited
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    public function test_prevents_session_fixation_attacks(): void
    {
        $user = User::factory()->create();

        // Get initial session ID
        $response = $this->get(route('login'));
        $initialSessionId = session()->getId();

        // Login user
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password'
        ]);

        // Session ID should change after login
        $newSessionId = session()->getId();
        $this->assertNotEquals($initialSessionId, $newSessionId);
    }

    public function test_prevents_csrf_attacks(): void
    {
        $user = User::factory()->create();

        // Attempt login without CSRF token
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
                         ->post(route('login'), [
                             'email' => $user->email,
                             'password' => 'password'
                         ]);

        // With CSRF middleware enabled, this should fail
        $this->withMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $response->assertStatus(419); // CSRF token mismatch
    }

    public function test_enforces_strong_password_policy(): void
    {
        // Test weak passwords are rejected
        $weakPasswords = [
            '123456',
            'password',
            'qwerty',
            'abc123',
            '12345678'
        ];

        foreach ($weakPasswords as $weakPassword) {
            $response = $this->post(route('register'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => $weakPassword,
                'password_confirmation' => $weakPassword
            ]);

            $response->assertSessionHasErrors('password');
        }

        // Test strong password is accepted
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'StrongP@ssw0rd123!',
            'password_confirmation' => 'StrongP@ssw0rd123!'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_prevents_timing_attacks_on_login(): void
    {
        // Create a user
        User::factory()->create([
            'email' => 'existing@example.com',
            'password' => Hash::make('password')
        ]);

        // Measure time for existing user with wrong password
        $start = microtime(true);
        $this->post(route('login'), [
            'email' => 'existing@example.com',
            'password' => 'wrong-password'
        ]);
        $existingUserTime = microtime(true) - $start;

        // Measure time for non-existing user
        $start = microtime(true);
        $this->post(route('login'), [
            'email' => 'nonexisting@example.com',
            'password' => 'wrong-password'
        ]);
        $nonExistingUserTime = microtime(true) - $start;

        // Time difference should be minimal (less than 100ms)
        $timeDifference = abs($existingUserTime - $nonExistingUserTime);
        $this->assertLessThan(0.1, $timeDifference, 'Timing attack vulnerability detected');
    }

    public function test_logs_suspicious_login_patterns(): void
    {
        // Multiple failed logins from different IPs for same user
        $user = User::factory()->create(['email' => 'target@example.com']);

        $suspiciousIPs = ['192.168.1.1', '10.0.0.1', '172.16.0.1'];

        foreach ($suspiciousIPs as $ip) {
            $this->app['request']->server->set('REMOTE_ADDR', $ip);
            
            for ($i = 0; $i < 3; $i++) {
                $this->post(route('login'), [
                    'email' => 'target@example.com',
                    'password' => 'wrong-password'
                ]);
            }
        }

        // Should log suspicious activity
        $this->assertDatabaseHas('security_events', [
            'event_type' => SecurityEvent::TYPE_SUSPICIOUS_ACTIVITY,
            'severity' => SecurityEvent::SEVERITY_HIGH
        ]);
    }

    public function test_prevents_password_reset_abuse(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Attempt multiple password reset requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post(route('password.email'), [
                'email' => 'test@example.com'
            ]);
        }

        // Should be rate limited
        $response = $this->post(route('password.email'), [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(429);
    }

    public function test_validates_password_reset_tokens(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Test with invalid token
        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'test@example.com',
            'password' => 'NewP@ssw0rd123!',
            'password_confirmation' => 'NewP@ssw0rd123!'
        ]);

        $response->assertSessionHasErrors('email');

        // Test with expired token (simulate)
        $expiredToken = 'expired-token-12345';
        
        $response = $this->post(route('password.update'), [
            'token' => $expiredToken,
            'email' => 'test@example.com',
            'password' => 'NewP@ssw0rd123!',
            'password_confirmation' => 'NewP@ssw0rd123!'
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_enforces_secure_session_configuration(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password'
        ]);

        // Check session cookie security settings
        $cookies = $response->headers->getCookies();
        $sessionCookie = collect($cookies)->first(function($cookie) {
            return $cookie->getName() === config('session.cookie');
        });

        if ($sessionCookie) {
            $this->assertTrue($sessionCookie->isHttpOnly(), 'Session cookie should be HTTP only');
            $this->assertTrue($sessionCookie->isSecure() || !config('app.env') === 'production', 'Session cookie should be secure in production');
        }
    }

    public function test_prevents_concurrent_session_abuse(): void
    {
        $user = User::factory()->create();

        // Login from first session
        $response1 = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $this->assertAuthenticated();

        // Simulate login from different device/location
        $this->app['request']->server->set('HTTP_USER_AGENT', 'Different Browser');
        $this->app['request']->server->set('REMOTE_ADDR', '192.168.1.100');

        $response2 = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password'
        ]);

        // Should log security event for concurrent sessions
        $this->assertDatabaseHas('security_events', [
            'user_id' => $user->id,
            'event_type' => SecurityEvent::TYPE_CONCURRENT_SESSION
        ]);
    }

    public function test_detects_credential_stuffing_attacks(): void
    {
        // Create multiple users
        $users = User::factory()->count(10)->create();

        // Simulate credential stuffing attack (same password for multiple accounts)
        $commonPassword = 'password123';
        
        foreach ($users as $user) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => $commonPassword
            ]);
        }

        // Should detect and log credential stuffing attempt
        $this->assertDatabaseHas('security_events', [
            'event_type' => SecurityEvent::TYPE_CREDENTIAL_STUFFING,
            'severity' => SecurityEvent::SEVERITY_CRITICAL
        ]);
    }

    public function test_enforces_account_lockout_policy(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Exceed failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('login'), [
                'email' => 'test@example.com',
                'password' => 'wrong-password'
            ]);
        }

        // Account should be locked
        $failedAttempt = FailedLoginAttempt::where('email', 'test@example.com')->first();
        $this->assertTrue($failedAttempt->isCurrentlyBlocked());

        // Wait for lockout period to expire (simulate)
        $failedAttempt->update(['blocked_until' => now()->subMinute()]);

        // Should be able to login again
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_validates_email_verification_security(): void
    {
        $user = User::factory()->unverified()->create();

        // Test with invalid verification link
        $response = $this->get(route('verification.verify', [
            'id' => $user->id,
            'hash' => 'invalid-hash'
        ]));

        $response->assertStatus(403);

        // Test with valid verification link
        $validHash = sha1($user->getEmailForVerification());
        
        $response = $this->get(route('verification.verify', [
            'id' => $user->id,
            'hash' => $validHash
        ]));

        $response->assertRedirect();
        
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }
}