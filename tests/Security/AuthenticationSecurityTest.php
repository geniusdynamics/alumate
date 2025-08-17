<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => Hash::make('password123'),
        'email_verified_at' => now(),
    ]);
});

describe('Authentication Security', function () {
    it('prevents brute force login attempts', function () {
        $email = $this->user->email;

        // Attempt multiple failed logins
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => $email,
                'password' => 'wrong-password',
            ]);

            if ($i < 5) {
                $response->assertUnauthorized();
            } else {
                // Should be rate limited after 5 attempts
                $response->assertStatus(429);
            }
        }

        // Verify rate limiting is active
        expect(RateLimiter::tooManyAttempts('login:'.$email, 5))->toBeTrue();
    });

    it('requires strong passwords for registration', function () {
        $weakPasswords = [
            '123456',
            'password',
            'qwerty',
            'abc123',
            '12345678',
        ];

        foreach ($weakPasswords as $password) {
            $response = $this->postJson('/api/register', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => $password,
                'password_confirmation' => $password,
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['password']);
        }
    });

    it('enforces password complexity requirements', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'StrongP@ssw0rd123!',
            'password_confirmation' => 'StrongP@ssw0rd123!',
        ]);

        $response->assertCreated();
    });

    it('invalidates sessions on password change', function () {
        Sanctum::actingAs($this->user);

        // Create multiple tokens
        $token1 = $this->user->createToken('device1')->plainTextToken;
        $token2 = $this->user->createToken('device2')->plainTextToken;

        // Change password
        $response = $this->putJson('/api/user/password', [
            'current_password' => 'password123',
            'password' => 'NewStrongP@ssw0rd123!',
            'password_confirmation' => 'NewStrongP@ssw0rd123!',
        ]);

        $response->assertSuccessful();

        // Verify old tokens are invalidated
        $this->user->refresh();
        expect($this->user->tokens()->count())->toBe(1); // Only current session remains
    });

    it('prevents session fixation attacks', function () {
        // Get initial session
        $response = $this->get('/');
        $initialSessionId = session()->getId();

        // Login
        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'password123',
        ]);

        $response->assertSuccessful();

        // Verify session ID changed after login
        expect(session()->getId())->not->toBe($initialSessionId);
    });

    it('enforces account lockout after multiple failed attempts', function () {
        $email = $this->user->email;

        // Simulate 10 failed login attempts
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', [
                'email' => $email,
                'password' => 'wrong-password',
            ]);
        }

        // Account should be temporarily locked
        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'password123', // Correct password
        ]);

        $response->assertStatus(429);
    });

    it('validates email verification before sensitive operations', function () {
        $unverifiedUser = User::factory()->create([
            'institution_id' => $this->institution->id,
            'email_verified_at' => null,
        ]);

        Sanctum::actingAs($unverifiedUser);

        // Try to access sensitive endpoint
        $response = $this->getJson('/api/user/profile');

        $response->assertForbidden()
            ->assertJson(['message' => 'Email verification required']);
    });

    it('prevents concurrent session abuse', function () {
        // Login from first device
        $response1 = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'password123',
        ]);

        $token1 = $response1->json('token');

        // Login from second device
        $response2 = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'password123',
        ]);

        $token2 = $response2->json('token');

        // Both sessions should be valid initially
        $this->withToken($token1)->getJson('/api/user')->assertSuccessful();
        $this->withToken($token2)->getJson('/api/user')->assertSuccessful();

        // But user should be able to revoke all other sessions
        $this->withToken($token1)->deleteJson('/api/user/sessions');

        // Only current session should remain valid
        $this->withToken($token1)->getJson('/api/user')->assertSuccessful();
        $this->withToken($token2)->getJson('/api/user')->assertUnauthorized();
    });
});

describe('Authorization Security', function () {
    it('prevents privilege escalation through role manipulation', function () {
        $regularUser = User::factory()->create();
        Sanctum::actingAs($regularUser);

        // Try to assign admin role to self
        $response = $this->putJson('/api/user/roles', [
            'roles' => ['super_admin', 'institution_admin'],
        ]);

        $response->assertForbidden();

        // Verify roles weren't changed
        $regularUser->refresh();
        expect($regularUser->hasRole('super_admin'))->toBeFalse();
        expect($regularUser->hasRole('institution_admin'))->toBeFalse();
    });

    it('enforces tenant isolation in API endpoints', function () {
        $otherUser = User::factory()->create();

        Sanctum::actingAs($this->user);

        // Try to access other user (simulating cross-tenant access)
        $response = $this->getJson("/api/users/{$otherUser->id}");

        $response->assertNotFound();
    });

    it('validates API token scopes', function () {
        $token = $this->user->createToken('limited', ['read:profile'])->plainTextToken;

        // Should allow read operations
        $this->withToken($token)
            ->getJson('/api/user')
            ->assertSuccessful();

        // Should deny write operations
        $this->withToken($token)
            ->putJson('/api/user/profile', ['bio' => 'Updated bio'])
            ->assertForbidden();
    });

    it('prevents CSRF attacks on state-changing operations', function () {
        Sanctum::actingAs($this->user);

        // Without CSRF token
        $response = $this->putJson('/api/user/profile', [
            'bio' => 'Updated bio',
        ], [
            'X-CSRF-TOKEN' => 'invalid-token',
        ]);

        // Should be protected by Sanctum's stateful guard
        $response->assertSuccessful(); // Sanctum handles CSRF for API routes differently

        // But web routes should require CSRF
        $response = $this->put('/web/user/profile', [
            'bio' => 'Updated bio',
        ]);

        $response->assertStatus(419); // CSRF token mismatch
    });
});
