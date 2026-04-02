<?php

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\AnalyticsDataValidationService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class AnalyticsDataValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsDataValidationService $validationService;
    protected TenantContextService $tenantContextService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the tenant context service
        $this->tenantContextService = Mockery::mock(TenantContextService::class);
        $this->tenantContextService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        // Create the service with mocked dependencies
        $this->validationService = new AnalyticsDataValidationService(
            $this->tenantContextService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== Event Data Validation Tests ====================

    public function test_validate_event_data_with_valid_data(): void
    {
        $event = [
            'event_name' => 'page_view',
            'user_id' => 1,
            'occurred_at' => now()->toIso8601String(),
            'session_id' => 'abc123',
            'properties' => ['page' => '/home'],
        ];

        $result = $this->validationService->validateEventData($event);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertArrayHasKey('quality_score', $result);
        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('validated_at', $result);
    }

    public function test_validate_event_data_with_missing_required_fields(): void
    {
        $event = [
            'session_id' => 'abc123',
        ];

        $result = $this->validationService->validateEventData($event);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        
        // Check for required field errors
        $errorTypes = array_column($result['errors'], 'type');
        $this->assertContains('required', $errorTypes);
        
        // Check that quality score is reduced
        $this->assertLessThan(100, $result['quality_score']);
    }

    public function test_validate_event_data_with_invalid_event_name_format(): void
    {
        $event = [
            'event_name' => 'Invalid-Event-Name!',
            'user_id' => 1,
            'occurred_at' => now()->toIso8601String(),
        ];

        $result = $this->validationService->validateEventData($event);

        $this->assertFalse($result['valid']);
        
        $formatErrors = array_filter($result['errors'], function ($error) {
            return $error['field'] === 'event_name' && $error['type'] === 'format';
        });
        $this->assertNotEmpty($formatErrors);
    }

    public function test_validate_event_data_with_event_name_too_long(): void
    {
        $event = [
            'event_name' => str_repeat('a', 300),
            'user_id' => 1,
            'occurred_at' => now()->toIso8601String(),
        ];

        $result = $this->validationService->validateEventData($event);

        $this->assertFalse($result['valid']);
        
        $lengthErrors = array_filter($result['errors'], function ($error) {
            return $error['field'] === 'event_name' && $error['type'] === 'length';
        });
        $this->assertNotEmpty($lengthErrors);
    }

    public function test_validate_event_data_with_invalid_timestamp(): void
    {
        $event = [
            'event_name' => 'page_view',
            'user_id' => 1,
            'occurred_at' => 'invalid-date',
        ];

        $result = $this->validationService->validateEventData($event);

        $this->assertFalse($result['valid']);
        
        $timestampErrors = array_filter($result['errors'], function ($error) {
            return $error['field'] === 'occurred_at';
        });
        $this->assertNotEmpty($timestampErrors);
    }

    public function test_validate_event_data_with_old_timestamp(): void
    {
        $event = [
            'event_name' => 'page_view',
            'user_id' => 1,
            'occurred_at' => now()->subYears(2)->toIso8601String(),
        ];

        $result = $this->validationService->validateEventData($event);

        $this->assertFalse($result['valid']);
    }

    public function test_validate_event_data_with_future_timestamp(): void
    {
        $event = [
            'event_name' => 'page_view',
            'user_id' => 1,
            'occurred_at' => now()->addDays(5)->toIso8601String(),
        ];

        $result = $this->validationService->validateEventData($event);

        $this->assertFalse($result['valid']);
    }

    // ==================== Session Data Validation Tests ====================

    public function test_validate_session_data_with_valid_data(): void
    {
        $session = [
            'session_id' => 'abc123def456',
            'user_id' => 1,
            'start_time' => now()->toIso8601String(),
            'end_time' => now()->addMinutes(5)->toIso8601String(),
            'duration_seconds' => 300,
            'page_count' => 5,
        ];

        $result = $this->validationService->validateSessionData($session);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_session_data_with_missing_required_fields(): void
    {
        $session = [
            'page_count' => 5,
        ];

        $result = $this->validationService->validateSessionData($session);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        
        $errorTypes = array_column($result['errors'], 'type');
        $this->assertContains('required', $errorTypes);
    }

    public function test_validate_session_data_with_end_time_before_start_time(): void
    {
        $session = [
            'session_id' => 'abc123',
            'user_id' => 1,
            'start_time' => now()->toIso8601String(),
            'end_time' => now()->subMinutes(10)->toIso8601String(),
        ];

        $result = $this->validationService->validateSessionData($session);

        $this->assertFalse($result['valid']);
        
        $rangeErrors = array_filter($result['errors'], function ($error) {
            return $error['field'] === 'end_time' && $error['type'] === 'range';
        });
        $this->assertNotEmpty($rangeErrors);
    }

    public function test_validate_session_data_with_negative_duration(): void
    {
        $session = [
            'session_id' => 'abc123',
            'user_id' => 1,
            'start_time' => now()->toIso8601String(),
            'duration_seconds' => -100,
        ];

        $result = $this->validationService->validateSessionData($session);

        $this->assertFalse($result['valid']);
    }

    public function test_validate_session_data_with_excessive_page_count(): void
    {
        $session = [
            'session_id' => 'abc123',
            'user_id' => 1,
            'start_time' => now()->toIso8601String(),
            'page_count' => 5000,
        ];

        $result = $this->validationService->validateSessionData($session);

        $this->assertFalse($result['valid']);
    }

    // ==================== User Data Validation Tests ====================

    public function test_validate_user_data_with_valid_data(): void
    {
        $user = [
            'id' => 1,
            'email' => 'test@example.com',
            'graduation_year' => 2025,
        ];

        $result = $this->validationService->validateUserData($user);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_user_data_with_invalid_email(): void
    {
        $user = [
            'id' => 1,
            'email' => 'invalid-email',
        ];

        $result = $this->validationService->validateUserData($user);

        $this->assertFalse($result['valid']);
        
        $formatErrors = array_filter($result['errors'], function ($error) {
            return $error['field'] === 'email' && $error['type'] === 'format';
        });
        $this->assertNotEmpty($formatErrors);
    }

    public function test_validate_user_data_with_invalid_graduation_year(): void
    {
        $user = [
            'id' => 1,
            'email' => 'test@example.com',
            'graduation_year' => 1800,
        ];

        $result = $this->validationService->validateUserData($user);

        $this->assertFalse($result['valid']);
        
        $rangeErrors = array_filter($result['errors'], function ($error) {
            return $error['field'] === 'graduation_year';
        });
        $this->assertNotEmpty($rangeErrors);
    }

    public function test_validate_user_data_with_future_graduation_year(): void
    {
        $user = [
            'id' => 1,
            'email' => 'test@example.com',
            'graduation_year' => 2100,
        ];

        $result = $this->validationService->validateUserData($user);

        $this->assertFalse($result['valid']);
    }

    // ==================== Metrics Data Validation Tests ====================

    public function test_validate_metrics_data_with_valid_data(): void
    {
        $metrics = [
            'period' => [
                'start' => now()->subWeek()->toIso8601String(),
                'end' => now()->toIso8601String(),
            ],
            'page_views' => 1000,
            'unique_visitors' => 500,
            'sessions' => 750,
            'bounce_rate' => 45.5,
            'avg_session_duration' => 180,
        ];

        $result = $this->validationService->validateMetricsData($metrics);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_metrics_data_with_invalid_bounce_rate(): void
    {
        $metrics = [
            'bounce_rate' => 150,
        ];

        $result = $this->validationService->validateMetricsData($metrics);

        $this->assertFalse($result['valid']);
        
        $rangeErrors = array_filter($result['errors'], function ($error) {
            return $error['field'] === 'bounce_rate';
        });
        $this->assertNotEmpty($rangeErrors);
    }

    public function test_validate_metrics_data_with_negative_page_views(): void
    {
        $metrics = [
            'page_views' => -100,
        ];

        $result = $this->validationService->validateMetricsData($metrics);

        $this->assertFalse($result['valid']);
    }

    public function test_validate_metrics_data_with_invalid_period(): void
    {
        $metrics = [
            'period' => 'invalid-period',
        ];

        $result = $this->validationService->validateMetricsData($metrics);

        $this->assertFalse($result['valid']);
        
        $formatErrors = array_filter($result['errors'], function ($error) {
            return $error['field'] === 'period';
        });
        $this->assertNotEmpty($formatErrors);
    }

    // ==================== Cohort Data Validation Tests ====================

    public function test_validate_cohort_data_with_valid_data(): void
    {
        $cohort = [
            'name' => 'Class of 2025',
            'criteria' => [
                'grad_year' => 2025,
                'degree' => 'Bachelor',
            ],
            'members_count' => 100,
            'acquisition_date' => now()->toIso8601String(),
        ];

        $result = $this->validationService->validateCohortData($cohort);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_cohort_data_with_missing_name(): void
    {
        $cohort = [
            'members_count' => 100,
        ];

        $result = $this->validationService->validateCohortData($cohort);

        $this->assertFalse($result['valid']);
    }

    public function test_validate_cohort_data_with_cohort_name_too_long(): void
    {
        $cohort = [
            'name' => str_repeat('a', 300),
            'members_count' => 100,
        ];

        $result = $this->validationService->validateCohortData($cohort);

        $this->assertFalse($result['valid']);
    }

    public function test_validate_cohort_data_with_negative_members_count(): void
    {
        $cohort = [
            'name' => 'Test Cohort',
            'members_count' => -10,
        ];

        $result = $this->validationService->validateCohortData($cohort);

        $this->assertFalse($result['valid']);
    }

    // ==================== Attribution Data Validation Tests ====================

    public function test_validate_attribution_data_with_valid_data(): void
    {
        $attribution = [
            'user_id' => 1,
            'source' => 'google',
            'timestamp' => now()->toIso8601String(),
            'event_type' => 'click',
            'value' => 25.50,
        ];

        $result = $this->validationService->validateAttributionData($attribution);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_attribution_data_with_invalid_event_type(): void
    {
        $attribution = [
            'user_id' => 1,
            'source' => 'google',
            'timestamp' => now()->toIso8601String(),
            'event_type' => 'invalid_event',
        ];

        $result = $this->validationService->validateAttributionData($attribution);

        $this->assertFalse($result['valid']);
        
        $formatErrors = array_filter($result['errors'], function ($error) {
            return $error['field'] === 'event_type';
        });
        $this->assertNotEmpty($formatErrors);
    }

    public function test_validate_attribution_data_with_negative_value(): void
    {
        $attribution = [
            'user_id' => 1,
            'source' => 'google',
            'timestamp' => now()->toIso8601String(),
            'value' => -10,
        ];

        $result = $this->validationService->validateAttributionData($attribution);

        $this->assertFalse($result['valid']);
    }

    public function test_validate_attribution_data_with_source_too_long(): void
    {
        $attribution = [
            'user_id' => 1,
            'source' => str_repeat('a', 300),
            'timestamp' => now()->toIso8601String(),
        ];

        $result = $this->validationService->validateAttributionData($attribution);

        $this->assertFalse($result['valid']);
    }

    // ==================== Custom Event Data Validation Tests ====================

    public function test_validate_custom_event_data_with_valid_data(): void
    {
        $customEvent = [
            'name' => 'button_click',
            'user_id' => 1,
            'definition_id' => 1,
            'data_json' => ['button_id' => 'submit'],
        ];

        $result = $this->validationService->validateCustomEventData($customEvent);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_custom_event_data_with_invalid_definition_id(): void
    {
        $customEvent = [
            'name' => 'button_click',
            'user_id' => 1,
            'definition_id' => -1,
        ];

        $result = $this->validationService->validateCustomEventData($customEvent);

        $this->assertFalse($result['valid']);
    }

    public function test_validate_custom_event_data_with_name_too_long(): void
    {
        $customEvent = [
            'name' => str_repeat('a', 300),
            'user_id' => 1,
        ];

        $result = $this->validationService->validateCustomEventData($customEvent);

        $this->assertFalse($result['valid']);
    }

    public function test_validate_custom_event_data_with_non_array_data_json(): void
    {
        $customEvent = [
            'name' => 'button_click',
            'user_id' => 1,
            'data_json' => 'not an array',
        ];

        $result = $this->validationService->validateCustomEventData($customEvent);

        $this->assertFalse($result['valid']);
    }

    // ==================== Batch Data Validation Tests ====================

    public function test_validate_batch_data_with_valid_events(): void
    {
        $batch = [
            [
                'event_name' => 'page_view',
                'user_id' => 1,
                'occurred_at' => now()->toIso8601String(),
            ],
            [
                'event_name' => 'click',
                'user_id' => 2,
                'occurred_at' => now()->toIso8601String(),
            ],
        ];

        $result = $this->validationService->validateBatchData($batch);

        $this->assertTrue($result['valid']);
        $this->assertEquals(2, $result['batch_size']);
        $this->assertEquals(2, $result['valid_items']);
        $this->assertEquals(0, $result['invalid_items']);
    }

    public function test_validate_batch_data_with_empty_array(): void
    {
        $batch = [];

        $result = $this->validationService->validateBatchData($batch);

        $this->assertFalse($result['valid']);
    }

    public function test_validate_batch_data_with_non_array(): void
    {
        $result = $this->validationService->validateBatchData('not an array');

        $this->assertFalse($result['valid']);
    }

    public function test_validate_batch_data_with_mixed_valid_invalid_items(): void
    {
        $batch = [
            [
                'event_name' => 'page_view',
                'user_id' => 1,
                'occurred_at' => now()->toIso8601String(),
            ],
            [
                'user_id' => 2,
                // Missing required fields
            ],
            [
                'event_name' => 'click',
                'user_id' => 3,
                'occurred_at' => now()->toIso8601String(),
            ],
        ];

        $result = $this->validationService->validateBatchData($batch);

        $this->assertFalse($result['valid']);
        $this->assertEquals(3, $result['batch_size']);
        $this->assertEquals(2, $result['valid_items']);
        $this->assertEquals(1, $result['invalid_items']);
    }

    public function test_validate_batch_data_with_non_array_items(): void
    {
        $batch = [
            'not an array',
            [
                'event_name' => 'page_view',
                'user_id' => 1,
                'occurred_at' => now()->toIso8601String(),
            ],
        ];

        $result = $this->validationService->validateBatchData($batch);

        $this->assertFalse($result['valid']);
    }

    // ==================== Fix Validation Errors Tests ====================

    public function test_fix_validation_errors_with_truncation(): void
    {
        $data = [
            'name' => str_repeat('a', 300),
            'email' => 'test@example.com',
        ];

        $errors = [
            [
                'field' => 'name',
                'type' => AnalyticsDataValidationService::ERROR_TYPE_LENGTH,
                'message' => 'Name exceeds maximum length',
                'severity' => 'low',
            ],
        ];

        $result = $this->validationService->fixValidationErrors($errors, $data);

        $this->assertArrayHasKey('fixed_data', $result);
        $this->assertArrayHasKey('fixes_applied', $result);
        $this->assertEquals(1, $result['fixes_applied']);
        $this->assertLessThanOrEqual(255, strlen($result['fixed_data']['name']));
    }

    public function test_fix_validation_errors_with_negative_correction(): void
    {
        $data = [
            'page_views' => -100,
        ];

        $errors = [
            [
                'field' => 'page_views',
                'type' => AnalyticsDataValidationService::ERROR_TYPE_RANGE,
                'message' => 'Value cannot be negative',
                'severity' => 'medium',
            ],
        ];

        $result = $this->validationService->fixValidationErrors($errors, $data);

        $this->assertEquals(0, $result['fixed_data']['page_views']);
        $this->assertEquals(1, $result['fixes_applied']);
    }

    public function test_fix_validation_errors_with_unknown_type(): void
    {
        $data = [
            'unknown_field' => 'value',
        ];

        $errors = [
            [
                'field' => 'unknown_field',
                'type' => 'unknown_type',
                'message' => 'Unknown error',
                'severity' => 'high',
            ],
        ];

        $result = $this->validationService->fixValidationErrors($errors, $data);

        $this->assertEquals(0, $result['fixes_applied']);
        $this->assertNotEmpty($result['failed_fixes']);
    }

    // ==================== Validation Report Tests ====================

    public function test_get_validation_report_structure(): void
    {
        $report = $this->validationService->getValidationReport('event');

        $this->assertArrayHasKey('data_type', $report);
        $this->assertArrayHasKey('tenant_id', $report);
        $this->assertArrayHasKey('generated_at', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('error_breakdown', $report);
        $this->assertArrayHasKey('recommendations', $report);
        
        $this->assertArrayHasKey('total_records', $report['summary']);
        $this->assertArrayHasKey('valid_records', $report['summary']);
        $this->assertArrayHasKey('invalid_records', $report['summary']);
        $this->assertArrayHasKey('quality_score', $report['summary']);
    }

    public function test_get_validation_report_with_filters(): void
    {
        $filters = [
            'period' => [
                'start' => now()->subWeek()->toIso8601String(),
                'end' => now()->toIso8601String(),
            ],
        ];

        $report = $this->validationService->getValidationReport('event', $filters);

        $this->assertArrayHasKey('period', $report);
        $this->assertEquals($filters['period'], $report['period']);
    }

    // ==================== Constants Tests ====================

    public function test_error_type_constants(): void
    {
        $this->assertEquals('required', AnalyticsDataValidationService::ERROR_TYPE_REQUIRED);
        $this->assertEquals('format', AnalyticsDataValidationService::ERROR_TYPE_FORMAT);
        $this->assertEquals('range', AnalyticsDataValidationService::ERROR_TYPE_RANGE);
        $this->assertEquals('length', AnalyticsDataValidationService::ERROR_TYPE_LENGTH);
        $this->assertEquals('type', AnalyticsDataValidationService::ERROR_TYPE_TYPE);
        $this->assertEquals('unique', AnalyticsDataValidationService::ERROR_TYPE_UNIQUE);
        $this->assertEquals('tenant', AnalyticsDataValidationService::ERROR_TYPE_TENANT);
        $this->assertEquals('unknown', AnalyticsDataValidationService::ERROR_TYPE_UNKNOWN);
    }

    public function test_severity_constants(): void
    {
        $this->assertEquals('critical', AnalyticsDataValidationService::SEVERITY_CRITICAL);
        $this->assertEquals('high', AnalyticsDataValidationService::SEVERITY_HIGH);
        $this->assertEquals('medium', AnalyticsDataValidationService::SEVERITY_MEDIUM);
        $this->assertEquals('low', AnalyticsDataValidationService::SEVERITY_LOW);
        $this->assertEquals('info', AnalyticsDataValidationService::SEVERITY_INFO);
    }

    public function test_quality_score_constants(): void
    {
        $this->assertEquals(100, AnalyticsDataValidationService::QUALITY_EXCELLENT);
        $this->assertEquals(80, AnalyticsDataValidationService::QUALITY_GOOD);
        $this->assertEquals(60, AnalyticsDataValidationService::QUALITY_FAIR);
        $this->assertEquals(40, AnalyticsDataValidationService::QUALITY_POOR);
        $this->assertEquals(0, AnalyticsDataValidationService::QUALITY_CRITICAL);
    }

    // ==================== Tenant Isolation Tests ====================

    public function test_validate_data_respects_tenant_isolation(): void
    {
        // Set up mock for another tenant
        $otherTenantService = Mockery::mock(TenantContextService::class);
        $otherTenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('other-tenant-uuid');

        $otherValidationService = new AnalyticsDataValidationService($otherTenantService);

        // Same data with different tenant_id
        $eventForOtherTenant = [
            'event_name' => 'page_view',
            'user_id' => 1,
            'occurred_at' => now()->toIso8601String(),
            'tenant_id' => 'test-tenant-uuid',
        ];

        $result = $this->validationService->validateEventData($eventForOtherTenant);
        $otherResult = $otherValidationService->validateEventData($eventForOtherTenant);

        // First service should have errors for tenant mismatch
        $tenantErrors = array_filter($result['errors'], function ($error) {
            return $error['type'] === 'tenant';
        });
        
        // Second service should also have tenant errors since data has different tenant_id
        $otherTenantErrors = array_filter($otherResult['errors'], function ($error) {
            return $error['type'] === 'tenant';
        });

        // Both should detect tenant mismatch
        $this->assertNotEmpty($tenantErrors);
        $this->assertNotEmpty($otherTenantErrors);
    }
}
