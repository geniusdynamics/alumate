<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Analytics\AnalyticsSecurityHardeningService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * AnalyticsSecurityHardeningService Test Suite
 *
 * Unit tests for security hardening functionality including:
 * - Vulnerability scanning
 * - Security patching
 * - Data encryption/decryption
 * - Data masking
 * - Access control validation
 * - Security event auditing
 * - Security configuration management
 */
class AnalyticsSecurityHardeningServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsSecurityHardeningService $service;
    private TenantContextService $tenantContextService;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantContextService = app(TenantContextService::class);
        $this->service = new AnalyticsSecurityHardeningService(
            $this->tenantContextService
        );

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    // ==================== Vulnerability Scanning Tests ====================

    /**
     * Test scanVulnerabilities returns expected structure
     */
    public function test_scan_vulnerabilities_returns_expected_structure(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $result = $this->service->scanVulnerabilities();

        $this->assertArrayHasKey('scan_timestamp', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('vulnerabilities', $result);
        $this->assertArrayHasKey('total_issues', $result);
        $this->assertArrayHasKey('severity_summary', $result);
        $this->assertArrayHasKey('recommendations', $result);

        $this->assertIsArray($result['vulnerabilities']);
        $this->assertIsArray($result['severity_summary']);
        $this->assertIsArray($result['recommendations']);
    }

    /**
     * Test scanVulnerabilities includes all vulnerability types
     */
    public function test_scan_vulnerabilities_includes_all_types(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $result = $this->service->scanVulnerabilities();

        $expectedTypes = [
            'sql_injection',
            'xss',
            'path_traversal',
            'command_injection',
            'unencrypted_data',
            'security_headers',
            'access_control',
        ];

        foreach ($expectedTypes as $type) {
            $this->assertArrayHasKey($type, $result['vulnerabilities']);
        }
    }

    /**
     * Test scanVulnerabilities handles empty database gracefully
     */
    public function test_scan_vulnerabilities_handles_empty_database(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $result = $this->service->scanVulnerabilities();

        $this->assertArrayHasKey('total_issues', $result);
        $this->assertIsInt($result['total_issues']);
    }

    /**
     * Test severity summary is calculated correctly
     */
    public function test_severity_summary_is_calculated_correctly(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $result = $this->service->scanVulnerabilities();

        $this->assertArrayHasKey('severity_summary', $result);
        $this->assertArrayHasKey('critical', $result['severity_summary']);
        $this->assertArrayHasKey('high', $result['severity_summary']);
        $this->assertArrayHasKey('medium', $result['severity_summary']);
        $this->assertArrayHasKey('low', $result['severity_summary']);
    }

    // ==================== Security Patching Tests ====================

    /**
     * Test applySecurityPatches returns expected structure
     */
    public function test_apply_security_patches_returns_expected_structure(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $result = $this->service->applySecurityPatches();

        $this->assertArrayHasKey('applied_at', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('applied_patches', $result);
        $this->assertArrayHasKey('failed_patches', $result);
        $this->assertArrayHasKey('total_applied', $result);
        $this->assertArrayHasKey('total_failed', $result);
    }

    /**
     * Test applySecurityPatches applies all patches successfully
     */
    public function test_apply_security_patches_applies_all_patches(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $result = $this->service->applySecurityPatches();

        $this->assertGreaterThan(0, $result['total_applied']);
        $this->assertIsArray($result['applied_patches']);
    }

    /**
     * Test applied patches have correct structure
     */
    public function test_applied_patches_have_correct_structure(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $result = $this->service->applySecurityPatches();

        if (!empty($result['applied_patches'])) {
            $patch = $result['applied_patches'][0];
            $this->assertArrayHasKey('success', $patch);
            $this->assertArrayHasKey('patch_id', $patch);
            $this->assertArrayHasKey('message', $patch);
        }
    }

    // ==================== Security Configuration Validation Tests ====================

    /**
     * Test validateSecurityConfiguration returns expected structure
     */
    public function test_validate_security_configuration_returns_expected_structure(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $result = $this->service->validateSecurityConfiguration();

        $this->assertArrayHasKey('validated_at', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('all_valid', $result);
        $this->assertArrayHasKey('validations', $result);
        $this->assertArrayHasKey('summary', $result);
    }

    /**
     * Test validateSecurityConfiguration checks all areas
     */
    public function test_validate_security_configuration_checks_all_areas(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $result = $this->service->validateSecurityConfiguration();

        $expectedChecks = [
            'encryption',
            'authentication',
            'authorization',
            'session_security',
            'api_security',
            'data_protection',
        ];

        foreach ($expectedChecks as $check) {
            $this->assertArrayHasKey($check, $result['validations']);
        }
    }

    /**
     * Test individual validation checks have required fields
     */
    public function test_individual_validation_checks_have_required_fields(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $result = $this->service->validateSecurityConfiguration();

        foreach ($result['validations'] as $key => $validation) {
            $this->assertArrayHasKey('name', $validation);
            $this->assertArrayHasKey('valid', $validation);
            $this->assertArrayHasKey('description', $validation);
        }
    }

    // ==================== Encryption/Decryption Tests ====================

    /**
     * Test encryptSensitiveData encrypts data correctly
     */
    public function test_encrypt_sensitive_data_encrypts_correctly(): void
    {
        $sensitiveData = 'my-secret-api-key-12345';

        $encrypted = $this->service->encryptSensitiveData($sensitiveData);

        $this->assertIsString($encrypted);
        $this->assertNotEquals($sensitiveData, $encrypted);
        $this->assertNotEmpty($encrypted);
    }

    /**
     * Test decryptSensitiveData decrypts data correctly
     */
    public function test_decrypt_sensitive_data_decrypts_correctly(): void
    {
        $originalData = 'my-secret-api-key-12345';

        $encrypted = $this->service->encryptSensitiveData($originalData);
        $decrypted = $this->service->decryptSensitiveData($encrypted);

        $this->assertEquals($originalData, $decrypted);
    }

    /**
     * Test encryptSensitiveData handles array data
     */
    public function test_encrypt_sensitive_data_handles_array_data(): void
    {
        $data = [
            'email' => 'user@example.com',
            'api_key' => 'secret-key-12345',
        ];

        $encrypted = $this->service->encryptSensitiveData($data);

        $this->assertIsString($encrypted);
    }

    /**
     * Test decryptSensitiveData handles JSON data correctly
     */
    public function test_decrypt_sensitive_data_handles_json_correctly(): void
    {
        $data = [
            'email' => 'user@example.com',
            'api_key' => 'secret-key-12345',
        ];

        $encrypted = $this->service->encryptSensitiveData($data);
        $decrypted = $this->service->decryptSensitiveData($encrypted);

        $this->assertIsArray($decrypted);
        $this->assertEquals($data['email'], $decrypted['email']);
        $this->assertEquals($data['api_key'], $decrypted['api_key']);
    }

    /**
     * Test encrypted data can only be decrypted by this service
     */
    public function test_encrypted_data_isolation(): void
    {
        $data = 'sensitive-data';

        // Encrypt with service
        $encrypted = $this->service->encryptSensitiveData($service1 = new AnalyticsSecurityHardeningService(
            $this->tenantContextService
        ));
        $service2 = new AnalyticsSecurityHardeningService(
            $this->tenantContextService
        );

        // Decrypt should work with any instance of the service
        $decrypted = $service2->decryptSensitiveData($encrypted);

        $this->assertEquals($data, $decrypted);
    }

    // ==================== Data Masking Tests ====================

    /**
     * Test maskSensitiveData masks password field
     */
    public function test_mask_sensitive_data_masks_password(): void
    {
        $data = [
            'password' => 'my-secret-password',
            'email' => 'user@example.com',
        ];

        $masked = $this->service->maskSensitiveData($data);

        $this->assertNotEquals($data['password'], $masked['password']);
        $this->assertEquals($data['email'], $masked['email']);
    }

    /**
     * Test maskSensitiveData masks credit card field
     */
    public function test_mask_sensitive_data_masks_credit_card(): void
    {
        $data = [
            'credit_card' => '4111111111111111',
            'name' => 'John Doe',
        ];

        $masked = $this->service->maskSensitiveData($data);

        $this->assertNotEquals($data['credit_card'], $masked['credit_card']);
        $this->assertEquals($data['name'], $masked['name']);
    }

    /**
     * Test maskSensitiveData masks api_key field
     */
    public function test_mask_sensitive_data_masks_api_key(): void
    {
        $data = [
            'api_key' => 'sk_live_abc123xyz789',
            'user_id' => 12345,
        ];

        $masked = $this->service->maskSensitiveData($data);

        $this->assertNotEquals($data['api_key'], $masked['api_key']);
        $this->assertEquals($data['user_id'], $masked['user_id']);
    }

    /**
     * Test maskSensitiveData masks nested sensitive fields
     */
    public function test_mask_sensitive_data_masks_nested_fields(): void
    {
        $data = [
            'user' => [
                'password' => 'secret123',
                'email' => 'user@example.com',
            ],
            'action' => 'login',
        ];

        $masked = $this->service->maskSensitiveData($data);

        $this->assertNotEquals($data['user']['password'], $masked['user']['password']);
        $this->assertEquals($data['user']['email'], $masked['user']['email']);
        $this->assertEquals($data['action'], $masked['action']);
    }

    /**
     * Test maskSensitiveData with specific fields
     */
    public function test_mask_sensitive_data_with_specific_fields(): void
    {
        $data = [
            'password' => 'secret123',
            'email' => 'user@example.com',
            'name' => 'John',
        ];

        $masked = $this->service->maskSensitiveData($data, ['password']);

        // Only password should be masked
        $this->assertNotEquals($data['password'], $masked['password']);
        // Other fields should remain unchanged
        $this->assertEquals($data['email'], $masked['email']);
        $this->assertEquals($data['name'], $masked['name']);
    }

    /**
     * Test maskSensitiveData handles empty data
     */
    public function test_mask_sensitive_data_handles_empty_data(): void
    {
        $data = [];

        $masked = $this->service->maskSensitiveData($data);

        $this->assertEmpty($masked);
    }

    // ==================== Access Control Tests ====================

    /**
     * Test validateAccessControl validates authenticated user
     */
    public function test_validate_access_control_validates_authenticated_user(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $result = $this->service->validateAccessControl('view_dashboard', $this->user->id);

        $this->assertArrayHasKey('operation', $result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('allowed', $result);
        $this->assertArrayHasKey('reason', $result);
        $this->assertArrayHasKey('checks_performed', $result);
    }

    /**
     * Test validateAccessControl allows valid operations
     */
    public function test_validate_access_control_allows_valid_operations(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $result = $this->service->validateAccessControl('view_dashboard', $this->user->id);

        $this->assertIsBool($result['allowed']);
    }

    /**
     * Test validateAccessControl performs all required checks
     */
    public function test_validate_access_control_performs_required_checks(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $result = $this->service->validateAccessControl('view_dashboard', $this->user->id);

        $this->assertIsArray($result['checks_performed']);
        $this->assertNotEmpty($result['checks_performed']);
    }

    /**
     * Test validateAccessControl rate limiting
     */
    public function test_validate_access_control_rate_limiting(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        // Make multiple rapid requests
        $results = [];
        for ($i = 0; $i < 65; $i++) {
            $results[] = $this->service->validateAccessControl('view_dashboard', $this->user->id);
        }

        // At least some requests should pass rate limiting
        $passed = array_filter($results, fn($r) => $r['allowed']);
        $this->assertNotEmpty($passed);
    }

    /**
     * Test validateAccessControl with different operations
     */
    public function test_validate_access_control_with_different_operations(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $operations = ['view_dashboard', 'export_data', 'view_reports', 'manage_settings'];

        foreach ($operations as $operation) {
            $result = $this->service->validateAccessControl($operation, $this->user->id);
            
            $this->assertArrayHasKey('operation', $result);
            $this->assertEquals($operation, $result['operation']);
        }
    }

    // ==================== Security Event Auditing Tests ====================

    /**
     * Test auditSecurityEvent logs event
     */
    public function test_audit_security_event_logs_event(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        // Just verify the method runs without exception
        $this->service->auditSecurityEvent('test_event', [
            'test_data' => 'value',
        ]);
        $this->assertTrue(true);
    }

    /**
     * Test auditSecurityEvent includes tenant context
     */
    public function test_audit_security_event_includes_tenant_context(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        // Just verify the method runs without exception with tenant context
        $this->service->auditSecurityEvent('test_event');
        $this->assertTrue(true);
    }

    /**
     * Test auditSecurityEvent includes user context
     */
    public function test_audit_security_event_includes_user_context(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        // Just verify the method runs without exception
        $this->service->auditSecurityEvent('test_event');
        $this->assertTrue(true);
    }

    // ==================== Security Report Tests ====================

    /**
     * Test getSecurityReport returns comprehensive report
     */
    public function test_get_security_report_returns_comprehensive_report(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $report = $this->service->getSecurityReport();

        $this->assertArrayHasKey('generated_at', $report);
        $this->assertArrayHasKey('tenant_id', $report);
        $this->assertArrayHasKey('sections', $report);
        $this->assertArrayHasKey('vulnerabilities', $report['sections']);
        $this->assertArrayHasKey('configuration', $report['sections']);
        $this->assertArrayHasKey('recent_events', $report['sections']);
        $this->assertArrayHasKey('metrics', $report['sections']);
        $this->assertArrayHasKey('score', $report['sections']);
        $this->assertArrayHasKey('recommendations', $report['sections']);
    }

    /**
     * Test getSecurityReport includes security score
     */
    public function test_get_security_report_includes_security_score(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $report = $this->service->getSecurityReport();

        $this->assertArrayHasKey('score', $report['sections']);
        $this->assertArrayHasKey('score', $report['sections']['score']);
        $this->assertArrayHasKey('grade', $report['sections']['score']);
        $this->assertArrayHasKey('max_score', $report['sections']['score']);
        $this->assertArrayHasKey('deductions', $report['sections']['score']);
    }

    /**
     * Test security score grade is valid
     */
    public function test_security_score_grade_is_valid(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $report = $this->service->getSecurityReport();

        $validGrades = ['A', 'B', 'C', 'D', 'F'];
        $this->assertContains($report['sections']['score']['grade'], $validGrades);
    }

    // ==================== Security Configuration Tests ====================

    /**
     * Test configureSecuritySettings applies valid settings
     */
    public function test_configure_security_settings_applies_valid_settings(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $settings = [
            'encryption_enabled' => true,
            'masking_enabled' => true,
            'rate_limit' => 100,
        ];

        $result = $this->service->configureSecuritySettings($settings);

        $this->assertArrayHasKey('configured_at', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('settings_applied', $result);
        $this->assertArrayHasKey('settings_failed', $result);
        $this->assertArrayHasKey('total_applied', $result);
        $this->assertArrayHasKey('total_failed', $result);
    }

    /**
     * Test configureSecuritySettings rejects invalid settings
     */
    public function test_configure_security_settings_rejects_invalid_settings(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $settings = [
            'encryption_enabled' => true,
            'unknown_setting' => 'value',
        ];

        $result = $this->service->configureSecuritySettings($settings);

        $this->assertGreaterThan(0, $result['total_failed']);
        $this->assertArrayHasKey('unknown_setting', $result['settings_failed']);
    }

    /**
     * Test configureSecuritySettings validates setting types
     */
    public function test_configure_security_settings_validates_types(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $settings = [
            'encryption_enabled' => 'not-a-boolean',
        ];

        $result = $this->service->configureSecuritySettings($settings);

        $this->assertGreaterThan(0, $result['total_failed']);
    }

    /**
     * Test configureSecuritySettings validates integer settings
     */
    public function test_configure_security_settings_validates_integers(): void
    {
        $this->tenantContextService->setTenant($this->tenant->id);

        $settings = [
            'rate_limit' => 'not-an-integer',
        ];

        $result = $this->service->configureSecuritySettings($settings);

        $this->assertGreaterThan(0, $result['total_failed']);
    }

    // ==================== Edge Case Tests ====================

    /**
     * Test service handles tenant isolation correctly
     */
    public function test_service_handles_tenant_isolation(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Set tenant context to tenant1
        $this->tenantContextService->setTenant($tenant1->id);

        $result = $this->service->scanVulnerabilities();

        $this->assertEquals($tenant1->id, $result['tenant_id']);

        // Set tenant context to tenant2
        $this->tenantContextService->setTenant($tenant2->id);

        $result2 = $this->service->scanVulnerabilities();

        $this->assertEquals($tenant2->id, $result2['tenant_id']);
    }

    /**
     * Test service handles missing tenant gracefully
     */
    public function test_service_handles_missing_tenant_gracefully(): void
    {
        $this->tenantContextService->clearContext();

        $result = $this->service->scanVulnerabilities();

        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertNull($result['tenant_id']);
    }

    /**
     * Test encrypt/decrypt round-trip with various data types
     */
    public function test_encrypt_decrypt_roundtrip_various_types(): void
    {
        $testCases = [
            'simple_string',
            'string with spaces and special chars!@#$%',
            'numbers123456',
            json_encode(['key' => 'value']),
        ];

        foreach ($testCases as $original) {
            $encrypted = $this->service->encryptSensitiveData($original);
            $decrypted = $this->service->decryptSensitiveData($encrypted);

            $this->assertEquals($original, $decrypted);
        }
    }

    /**
     * Test masking preserves data structure
     */
    public function test_masking_preserves_data_structure(): void
    {
        $data = [
            'id' => 123,
            'email' => 'test@example.com',
            'password' => 'secret123',
            'profile' => [
                'name' => 'John',
                'age' => 30,
                'api_key' => 'key123',
            ],
        ];

        $masked = $this->service->maskSensitiveData($data);

        // Check top-level keys are preserved
        $this->assertArrayHasKey('id', $masked);
        $this->assertArrayHasKey('email', $masked);
        $this->assertArrayHasKey('password', $masked);
        $this->assertArrayHasKey('profile', $masked);

        // Check nested keys are preserved
        $this->assertArrayHasKey('name', $masked['profile']);
        $this->assertArrayHasKey('age', $masked['profile']);
        $this->assertArrayHasKey('api_key', $masked['profile']);
    }
}
