<?php

use App\Models\Connection;
use App\Models\Post;
use App\Models\User;
use App\Services\SecurityAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->securityAuditService = app(SecurityAuditService::class);
});

describe('GDPR Compliance', function () {
    it('allows users to export their personal data', function () {
        // Create user data
        Post::factory()->count(3)->create(['user_id' => $this->user->id]);
        Connection::factory()->count(2)->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/user/data-export');

        $response->assertAccepted()
            ->assertJson(['message' => 'Data export request submitted']);

        // Verify export job was queued
        $this->assertDatabaseHas('jobs', [
            'queue' => 'data-export',
        ]);
    });

    it('allows users to delete their account and all associated data', function () {
        $userId = $this->user->id;

        // Create associated data
        Post::factory()->count(3)->create(['user_id' => $userId]);
        Connection::factory()->count(2)->create(['user_id' => $userId]);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/user/account', [
            'password' => 'password',
            'confirmation' => 'DELETE',
        ]);

        $response->assertSuccessful();

        // Verify user and associated data are deleted
        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->assertDatabaseMissing('posts', ['user_id' => $userId]);
        $this->assertDatabaseMissing('connections', ['user_id' => $userId]);
    });

    it('provides granular privacy controls', function () {
        Sanctum::actingAs($this->user);

        $privacySettings = [
            'profile_visibility' => 'connections_only',
            'email_visibility' => 'private',
            'phone_visibility' => 'private',
            'location_visibility' => 'public',
            'work_history_visibility' => 'connections_only',
            'education_visibility' => 'public',
            'posts_visibility' => 'circles',
            'allow_search_indexing' => false,
            'allow_contact_by_email' => false,
            'allow_contact_by_phone' => false,
        ];

        $response = $this->putJson('/api/user/privacy-settings', [
            'privacy_settings' => $privacySettings,
        ]);

        $response->assertSuccessful();

        $this->user->refresh();
        expect($this->user->privacy_settings)->toEqual($privacySettings);
    });

    it('respects data retention policies', function () {
        // Create old inactive user
        $oldUser = User::factory()->create([
            'last_login_at' => now()->subYears(3),
            'created_at' => now()->subYears(4),
        ]);

        // Run data retention cleanup
        $this->artisan('privacy:cleanup-inactive-accounts');

        // Verify old inactive account is anonymized or deleted
        $this->assertDatabaseMissing('users', [
            'id' => $oldUser->id,
            'email' => $oldUser->email,
        ]);
    });

    it('handles consent management', function () {
        Sanctum::actingAs($this->user);

        $consentData = [
            'marketing_emails' => true,
            'analytics_tracking' => false,
            'third_party_sharing' => false,
            'profile_recommendations' => true,
            'location_tracking' => false,
        ];

        $response = $this->putJson('/api/user/consent', $consentData);

        $response->assertSuccessful();

        $this->user->refresh();
        expect($this->user->consent_settings)->toEqual($consentData);
    });

    it('provides data portability in standard format', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user/data-export/download/test-token');

        $response->assertSuccessful()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure([
                'user_profile',
                'posts',
                'connections',
                'messages',
                'export_metadata' => [
                    'exported_at',
                    'format_version',
                    'data_types',
                ],
            ]);
    });

    it('logs all data access for audit purposes', function () {
        Sanctum::actingAs($this->user);

        // Access sensitive data
        $this->getJson('/api/user/profile');
        $this->getJson('/api/user/connections');
        $this->getJson('/api/user/messages');

        // Verify audit logs
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'data_access',
            'resource_type' => 'user_profile',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'data_access',
            'resource_type' => 'connections',
        ]);
    });
});

describe('Data Protection', function () {
    it('encrypts sensitive personal information', function () {
        $sensitiveData = [
            'phone' => '+1234567890',
            'address' => '123 Main St, City, State',
            'emergency_contact' => 'John Doe - +0987654321',
        ];

        $this->user->update($sensitiveData);

        // Check that data is encrypted in database
        $rawData = \DB::table('users')->where('id', $this->user->id)->first();

        expect($rawData->phone)->not->toBe($sensitiveData['phone']);
        expect($rawData->address)->not->toBe($sensitiveData['address']);

        // But accessible through model
        $this->user->refresh();
        expect($this->user->phone)->toBe($sensitiveData['phone']);
    });

    it('implements secure file storage with access controls', function () {
        Storage::fake('private');

        Sanctum::actingAs($this->user);

        $file = \Illuminate\Http\UploadedFile::fake()->image('profile.jpg');

        $response = $this->postJson('/api/user/documents', [
            'document' => $file,
            'type' => 'transcript',
        ]);

        $response->assertSuccessful();

        // File should be stored securely
        $filename = $response->json('filename');
        Storage::disk('private')->assertExists("documents/{$this->user->id}/{$filename}");

        // Other users shouldn't be able to access it
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/user/documents/{$filename}");
        $response->assertForbidden();
    });

    it('validates data integrity with checksums', function () {
        $originalData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'bio' => 'Software engineer with 5 years experience',
        ];

        $this->user->update($originalData);
        $originalChecksum = $this->securityAuditService->calculateDataChecksum($this->user);

        // Simulate data tampering
        \DB::table('users')
            ->where('id', $this->user->id)
            ->update(['bio' => 'Tampered data']);

        $this->user->refresh();
        $newChecksum = $this->securityAuditService->calculateDataChecksum($this->user);

        expect($newChecksum)->not->toBe($originalChecksum);

        // Security audit should detect tampering
        $integrityCheck = $this->securityAuditService->verifyDataIntegrity($this->user);
        expect($integrityCheck['is_valid'])->toBeFalse();
    });

    it('implements secure session management', function () {
        Sanctum::actingAs($this->user);

        // Get current session info
        $response = $this->getJson('/api/user/sessions');
        $response->assertSuccessful();

        $sessions = $response->json('sessions');
        expect(count($sessions))->toBeGreaterThan(0);

        // Each session should have security metadata
        foreach ($sessions as $session) {
            expect($session)->toHaveKeys([
                'id',
                'ip_address',
                'user_agent',
                'last_activity',
                'is_current',
            ]);
        }
    });

    it('prevents data exposure in logs', function () {
        // Simulate error that might log sensitive data
        try {
            $this->user->update([
                'password' => 'invalid-hash-format',
                'email' => 'invalid-email-format',
            ]);
        } catch (\Exception $e) {
            // Check that sensitive data isn't in exception message
            expect($e->getMessage())->not->toContain('password');
            expect($e->getMessage())->not->toContain($this->user->email);
        }
    });

    it('implements secure password reset flow', function () {
        $response = $this->postJson('/api/password/email', [
            'email' => $this->user->email,
        ]);

        $response->assertSuccessful();

        // Verify reset token is securely generated and stored
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $this->user->email,
        ]);

        $resetRecord = \DB::table('password_reset_tokens')
            ->where('email', $this->user->email)
            ->first();

        // Token should be hashed
        expect(strlen($resetRecord->token))->toBeGreaterThan(60); // Hashed token length

        // Token should expire
        expect($resetRecord->created_at)->not->toBeNull();
    });

    it('implements secure two-factor authentication', function () {
        Sanctum::actingAs($this->user);

        // Enable 2FA
        $response = $this->postJson('/api/user/two-factor-authentication');
        $response->assertSuccessful();

        $qrCode = $response->json('qr_code');
        $recoveryCodes = $response->json('recovery_codes');

        expect($qrCode)->not->toBeEmpty();
        expect($recoveryCodes)->toHaveCount(8);

        // Verify 2FA is required for sensitive operations
        $response = $this->putJson('/api/user/password', [
            'current_password' => 'password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(423) // Locked - 2FA required
            ->assertJson(['message' => 'Two-factor authentication required']);
    });
});

describe('Compliance Monitoring', function () {
    it('tracks data processing activities', function () {
        Sanctum::actingAs($this->user);

        // Perform various data operations
        $this->getJson('/api/user/profile');
        $this->putJson('/api/user/profile', ['bio' => 'Updated bio']);
        $this->postJson('/api/posts', ['content' => 'New post']);

        // Verify processing activities are logged
        $activities = $this->securityAuditService->getDataProcessingActivities($this->user->id);

        expect($activities)->toContain([
            'activity' => 'profile_read',
            'legal_basis' => 'legitimate_interest',
            'data_categories' => ['profile_data'],
        ]);

        expect($activities)->toContain([
            'activity' => 'profile_update',
            'legal_basis' => 'consent',
            'data_categories' => ['profile_data'],
        ]);
    });

    it('generates compliance reports', function () {
        $report = $this->securityAuditService->generateComplianceReport();

        expect($report)->toHaveKeys([
            'gdpr_compliance',
            'data_retention',
            'consent_management',
            'security_measures',
            'audit_trail',
            'generated_at',
        ]);

        expect($report['gdpr_compliance']['data_export_available'])->toBeTrue();
        expect($report['gdpr_compliance']['data_deletion_available'])->toBeTrue();
        expect($report['gdpr_compliance']['consent_management'])->toBeTrue();
    });

    it('monitors for privacy violations', function () {
        // Simulate potential privacy violation
        $violations = $this->securityAuditService->scanForPrivacyViolations();

        expect($violations)->toBeArray();

        // Should detect if any user data is exposed inappropriately
        foreach ($violations as $violation) {
            expect($violation)->toHaveKeys([
                'type',
                'severity',
                'description',
                'affected_users',
                'detected_at',
            ]);
        }
    });

    it('validates cross-border data transfer compliance', function () {
        $this->user->update(['country' => 'DE']); // EU user

        Sanctum::actingAs($this->user);

        // Check data transfer compliance
        $transferCheck = $this->securityAuditService->validateDataTransfer($this->user);

        expect($transferCheck)->toHaveKeys([
            'is_compliant',
            'transfer_mechanism',
            'adequacy_decision',
            'safeguards',
        ]);

        if ($transferCheck['requires_consent']) {
            expect($this->user->consent_settings['international_transfer'])->toBeTrue();
        }
    });
});
