<?php

use App\Models\User;
use App\Services\SecurityAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Security Audit Service', function () {
    it('can perform basic security audit', function () {
        $service = app(SecurityAuditService::class);

        $audit = $service->performSecurityAudit();

        expect($audit)->toBeArray()
            ->toHaveKeys([
                'authentication_security',
                'authorization_controls',
                'data_privacy',
                'social_graph_security',
                'api_security',
                'infrastructure_security',
                'compliance_status',
                'vulnerability_scan',
                'audit_timestamp',
            ]);
    });

    it('can calculate data checksum', function () {
        $user = new User([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'bio' => 'Test bio',
        ]);
        $user->updated_at = now();

        $service = app(SecurityAuditService::class);

        $checksum = $service->calculateDataChecksum($user);

        expect($checksum)->toBeString()
            ->toHaveLength(64); // SHA256 hash length
    });

    it('can verify data integrity', function () {
        $user = new User([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'bio' => 'Test bio',
        ]);
        $user->updated_at = now();

        $service = app(SecurityAuditService::class);

        $integrity = $service->verifyDataIntegrity($user);

        expect($integrity)->toBeArray()
            ->toHaveKeys([
                'is_valid',
                'current_checksum',
                'stored_checksum',
                'verified_at',
            ]);
    });

    it('can generate compliance report', function () {
        $service = app(SecurityAuditService::class);

        $report = $service->generateComplianceReport();

        expect($report)->toBeArray()
            ->toHaveKeys([
                'gdpr_compliance',
                'data_retention',
                'consent_management',
                'security_measures',
                'audit_trail',
                'generated_at',
            ]);
    });

    it('can scan for privacy violations', function () {
        $service = app(SecurityAuditService::class);

        $violations = $service->scanForPrivacyViolations();

        expect($violations)->toBeArray();
    });

    it('can validate data transfer compliance', function () {
        $user = new User(['country' => 'DE']);
        $service = app(SecurityAuditService::class);

        $validation = $service->validateDataTransfer($user);

        expect($validation)->toBeArray()
            ->toHaveKeys([
                'is_compliant',
                'transfer_mechanism',
                'adequacy_decision',
                'safeguards',
                'requires_consent',
            ]);
    });

    it('can monitor suspicious activity', function () {
        $service = app(SecurityAuditService::class);

        $monitoring = $service->monitorSuspiciousActivity();

        expect($monitoring)->toBeArray()
            ->toHaveKeys([
                'unusual_login_patterns',
                'mass_data_access',
                'privilege_abuse',
                'automated_behavior',
                'data_exfiltration',
            ]);
    });
});
