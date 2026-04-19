<?php

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\AnalyticsComplianceReportingService;
use App\Services\Analytics\PrivacyAuditService;
use App\Services\Analytics\PrivacyComplianceService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class AnalyticsComplianceReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsComplianceReportingService $complianceReportingService;
    protected TenantContextService $tenantContextService;
    protected PrivacyAuditService $privacyAuditService;
    protected PrivacyComplianceService $privacyComplianceService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the tenant context service
        $this->tenantContextService = Mockery::mock(TenantContextService::class);
        $this->tenantContextService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');
        $this->tenantContextService->shouldReceive('runInTenantContext')
            ->andReturnUsing(function ($tenantId, $callback) {
                return $callback();
            });

        // Mock the privacy audit service
        $this->privacyAuditService = Mockery::mock(PrivacyAuditService::class);
        $this->privacyAuditService->shouldReceive('logPrivacyEvent')
            ->andReturn(null);

        // Mock the privacy compliance service
        $this->privacyComplianceService = Mockery::mock(PrivacyComplianceService::class);

        // Create the service with mocked dependencies
        $this->complianceReportingService = new AnalyticsComplianceReportingService(
            $this->tenantContextService,
            $this->privacyAuditService,
            $this->privacyComplianceService
        );

        // Mock the request facade
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('ip')->andReturn('192.168.1.1');
        $request->shouldReceive('userAgent')->andReturn('Mozilla/5.0 Test');
        $this->app->instance(Request::class, $request);

        // Mock Auth facade
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_generate_gdpr_compliance_report(): void
    {
        $dateRange = [
            'from' => now()->subMonth()->toIso8601String(),
            'to' => now()->toIso8601String(),
        ];

        $report = $this->complianceReportingService->generateComplianceReport(
            AnalyticsComplianceReportingService::REPORT_TYPE_GDPR,
            $dateRange
        );

        $this->assertArrayHasKey('id', $report);
        $this->assertArrayHasKey('tenant_id', $report);
        $this->assertArrayHasKey('report_type', $report);
        $this->assertArrayHasKey('generated_at', $report);
        $this->assertArrayHasKey('period', $report);
        $this->assertArrayHasKey('sections', $report);
        $this->assertArrayHasKey('overall_status', $report);
        $this->assertArrayHasKey('summary', $report);

        $this->assertEquals('gdpr', $report['report_type']);
        $this->assertArrayHasKey('gdpr', $report['sections']);
        $this->assertArrayHasKey('checks', $report['sections']['gdpr']);
    }

    public function test_can_generate_ccpa_compliance_report(): void
    {
        $dateRange = [
            'from' => now()->subMonth()->toIso8601String(),
            'to' => now()->toIso8601String(),
        ];

        $report = $this->complianceReportingService->generateComplianceReport(
            AnalyticsComplianceReportingService::REPORT_TYPE_CCPA,
            $dateRange
        );

        $this->assertEquals('ccpa', $report['report_type']);
        $this->assertArrayHasKey('ccpa', $report['sections']);
        $this->assertArrayHasKey('checks', $report['sections']['ccpa']);
    }

    public function test_can_generate_data_retention_compliance_report(): void
    {
        $dateRange = [
            'from' => now()->subMonth()->toIso8601String(),
            'to' => now()->toIso8601String(),
        ];

        $report = $this->complianceReportingService->generateComplianceReport(
            AnalyticsComplianceReportingService::REPORT_TYPE_DATA_RETENTION,
            $dateRange
        );

        $this->assertEquals('data_retention', $report['report_type']);
        $this->assertArrayHasKey('retention_policies', $report['sections']['data_retention']);
    }

    public function test_can_generate_consent_compliance_report(): void
    {
        $dateRange = [
            'from' => now()->subMonth()->toIso8601String(),
            'to' => now()->toIso8601String(),
        ];

        $report = $this->complianceReportingService->generateComplianceReport(
            AnalyticsComplianceReportingService::REPORT_TYPE_CONSENT,
            $dateRange
        );

        $this->assertEquals('consent', $report['report_type']);
        $this->assertArrayHasKey('checks', $report['sections']['consent']);
        $this->assertArrayHasKey('consent_metrics', $report['sections']['consent']);
    }

    public function test_can_generate_comprehensive_compliance_report(): void
    {
        $dateRange = [
            'from' => now()->subMonth()->toIso8601String(),
            'to' => now()->toIso8601String(),
        ];

        $report = $this->complianceReportingService->generateComplianceReport(
            AnalyticsComplianceReportingService::REPORT_TYPE_COMPREHENSIVE,
            $dateRange
        );

        $this->assertEquals('comprehensive', $report['report_type']);
        $this->assertArrayHasKey('gdpr', $report['sections']);
        $this->assertArrayHasKey('ccpa', $report['sections']);
        $this->assertArrayHasKey('data_retention', $report['sections']);
        $this->assertArrayHasKey('consent', $report['sections']);
    }

    public function test_generate_compliance_report_throws_exception_for_invalid_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid report type: invalid_type');

        $this->complianceReportingService->generateComplianceReport('invalid_type', []);
    }

    public function test_validate_gdpr_compliance(): void
    {
        $result = $this->complianceReportingService->validateGDPRCompliance();

        $this->assertArrayHasKey('regulation', $result);
        $this->assertEquals('GDPR', $result['regulation']);
        $this->assertArrayHasKey('validated_at', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('summary', $result);

        // Check that all GDPR rights are checked
        $this->assertArrayHasKey('data_subject_rights', $result['checks']);
        $this->assertArrayHasKey('lawful_basis', $result['checks']);
        $this->assertArrayHasKey('data_minimization', $result['checks']);
        $this->assertArrayHasKey('purpose_limitation', $result['checks']);
        $this->assertArrayHasKey('storage_limitation', $result['checks']);
        $this->assertArrayHasKey('security_measures', $result['checks']);
    }

    public function test_validate_ccpa_compliance(): void
    {
        $result = $this->complianceReportingService->validateCCPACompliance();

        $this->assertArrayHasKey('regulation', $result);
        $this->assertEquals('CCPA', $result['regulation']);
        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('consumer_rights', $result['checks']);
        $this->assertArrayHasKey('do_not_sell', $result['checks']);
        $this->assertArrayHasKey('privacy_notice', $result['checks']);
    }

    public function test_validate_data_retention_compliance(): void
    {
        $result = $this->complianceReportingService->validateDataRetentionCompliance();

        $this->assertArrayHasKey('regulation', $result);
        $this->assertEquals('Data Retention', $result['regulation']);
        $this->assertArrayHasKey('retention_policies', $result);
        $this->assertArrayHasKey('summary', $result);

        // Check that all retention policies are checked
        $this->assertArrayHasKey('analytics_events', $result['retention_policies']);
        $this->assertArrayHasKey('session_data', $result['retention_policies']);
        $this->assertArrayHasKey('user_activity', $result['retention_policies']);
        $this->assertArrayHasKey('consent_records', $result['retention_policies']);
        $this->assertArrayHasKey('audit_logs', $result['retention_policies']);
    }

    public function test_validate_consent_compliance(): void
    {
        $result = $this->complianceReportingService->validateConsentCompliance();

        $this->assertArrayHasKey('regulation', $result);
        $this->assertEquals('Consent', $result['regulation']);
        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('consent_metrics', $result);

        // Check that all consent aspects are validated
        $this->assertArrayHasKey('consent_collection', $result['checks']);
        $this->assertArrayHasKey('consent_storage', $result['checks']);
        $this->assertArrayHasKey('consent_withdrawal', $result['checks']);
        $this->assertArrayHasKey('consent_documentation', $result['checks']);
    }

    public function test_get_compliance_status(): void
    {
        $status = $this->complianceReportingService->getComplianceStatus();

        $this->assertArrayHasKey('tenant_id', $status);
        $this->assertArrayHasKey('overall_status', $status);
        $this->assertArrayHasKey('checked_at', $status);
        $this->assertArrayHasKey('breakdown', $status);
        $this->assertArrayHasKey('compliance_score', $status);

        // Check breakdown structure
        $this->assertArrayHasKey('gdpr', $status['breakdown']);
        $this->assertArrayHasKey('ccpa', $status['breakdown']);
        $this->assertArrayHasKey('data_retention', $status['breakdown']);
        $this->assertArrayHasKey('consent', $status['breakdown']);

        // Each breakdown should have status and checked_at
        foreach ($status['breakdown'] as $area => $data) {
            $this->assertArrayHasKey('status', $data, "Missing 'status' in {$area}");
            $this->assertArrayHasKey('checked_at', $data, "Missing 'checked_at' in {$area}");
        }
    }

    public function test_get_compliance_metrics(): void
    {
        $dateRange = [
            'from' => now()->subMonth()->toIso8601String(),
            'to' => now()->toIso8601String(),
        ];

        $metrics = $this->complianceReportingService->getComplianceMetrics($dateRange);

        $this->assertArrayHasKey('tenant_id', $metrics);
        $this->assertArrayHasKey('period', $metrics);
        $this->assertArrayHasKey('generated_at', $metrics);
        $this->assertArrayHasKey('consent_metrics', $metrics);
        $this->assertArrayHasKey('retention_metrics', $metrics);
        $this->assertArrayHasKey('data_subject_requests', $metrics);
        $this->assertArrayHasKey('violation_metrics', $metrics);
        $this->assertArrayHasKey('compliance_trend', $metrics);
    }

    public function test_export_compliance_report_as_json(): void
    {
        $reportId = 'test-report-id';

        $export = $this->complianceReportingService->exportComplianceReport(
            $reportId,
            AnalyticsComplianceReportingService::FORMAT_JSON
        );

        $this->assertJson($export);

        $data = json_decode($export, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('report_type', $data);
        $this->assertArrayHasKey('overall_status', $data);
    }

    public function test_export_compliance_report_as_csv(): void
    {
        $reportId = 'test-report-id';

        $export = $this->complianceReportingService->exportComplianceReport(
            $reportId,
            AnalyticsComplianceReportingService::FORMAT_CSV
        );

        $this->assertStringContainsString('Compliance Report', $export);
        $this->assertStringContainsString('Report ID', $export);
        $this->assertStringContainsString('Overall Status', $export);
    }

    public function test_schedule_compliance_report(): void
    {
        $schedule = [
            'report_type' => AnalyticsComplianceReportingService::REPORT_TYPE_COMPREHENSIVE,
            'frequency' => 'monthly',
            'day_of_month' => 1,
            'time' => '00:00',
            'recipients' => ['admin@example.com'],
            'format' => AnalyticsComplianceReportingService::FORMAT_JSON,
        ];

        $result = $this->complianceReportingService->scheduleComplianceReport($schedule);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('report_type', $result);
        $this->assertArrayHasKey('frequency', $result);
        $this->assertArrayHasKey('enabled', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('next_run_at', $result);

        $this->assertEquals(AnalyticsComplianceReportingService::REPORT_TYPE_COMPREHENSIVE, $result['report_type']);
        $this->assertEquals('monthly', $result['frequency']);
        $this->assertTrue($result['enabled']);
    }

    public function test_get_compliance_alerts(): void
    {
        $alerts = $this->complianceReportingService->getComplianceAlerts();

        $this->assertArrayHasKey('tenant_id', $alerts);
        $this->assertArrayHasKey('checked_at', $alerts);
        $this->assertArrayHasKey('total_alerts', $alerts);
        $this->assertArrayHasKey('alerts', $alerts);

        $this->assertIsInt($alerts['total_alerts']);
        $this->assertIsArray($alerts['alerts']);
    }

    public function test_compliance_status_constants(): void
    {
        $this->assertEquals('compliant', AnalyticsComplianceReportingService::STATUS_COMPLIANT);
        $this->assertEquals('non_compliant', AnalyticsComplianceReportingService::STATUS_NON_COMPLIANT);
        $this->assertEquals('attention_required', AnalyticsComplianceReportingService::STATUS_ATTENTION_REQUIRED);
        $this->assertEquals('pending_review', AnalyticsComplianceReportingService::STATUS_PENDING_REVIEW);
    }

    public function test_report_type_constants(): void
    {
        $this->assertEquals('gdpr', AnalyticsComplianceReportingService::REPORT_TYPE_GDPR);
        $this->assertEquals('ccpa', AnalyticsComplianceReportingService::REPORT_TYPE_CCPA);
        $this->assertEquals('data_retention', AnalyticsComplianceReportingService::REPORT_TYPE_DATA_RETENTION);
        $this->assertEquals('consent', AnalyticsComplianceReportingService::REPORT_TYPE_CONSENT);
        $this->assertEquals('comprehensive', AnalyticsComplianceReportingService::REPORT_TYPE_COMPREHENSIVE);
    }

    public function test_export_format_constants(): void
    {
        $this->assertEquals('json', AnalyticsComplianceReportingService::FORMAT_JSON);
        $this->assertEquals('csv', AnalyticsComplianceReportingService::FORMAT_CSV);
        $this->assertEquals('pdf', AnalyticsComplianceReportingService::FORMAT_PDF);
    }

    public function test_gdpr_report_includes_all_required_rights(): void
    {
        $report = $this->complianceReportingService->generateComplianceReport(
            AnalyticsComplianceReportingService::REPORT_TYPE_GDPR,
            []
        );

        $gdprSection = $report['sections']['gdpr'];
        $checks = $gdprSection['checks']['data_subject_rights']['details'] ?? [];

        // Check all GDPR rights are included
        $this->assertArrayHasKey('right_to_access', $checks);
        $this->assertArrayHasKey('right_to_rectification', $checks);
        $this->assertArrayHasKey('right_to_erasure', $checks);
        $this->assertArrayHasKey('right_to_restriction', $checks);
        $this->assertArrayHasKey('right_to_data_portability', $checks);
        $this->assertArrayHasKey('right_to_object', $checks);
        $this->assertArrayHasKey('rights_related_to_automated_decision_making', $checks);
    }

    public function test_ccpa_report_includes_all_required_rights(): void
    {
        $report = $this->complianceReportingService->generateComplianceReport(
            AnalyticsComplianceReportingService::REPORT_TYPE_CCPA,
            []
        );

        $ccpaSection = $report['sections']['ccpa'];
        $checks = $ccpaSection['checks']['consumer_rights']['details'] ?? [];

        // Check all CCPA rights are included
        $this->assertArrayHasKey('right_to_know', $checks);
        $this->assertArrayHasKey('right_to_delete', $checks);
        $this->assertArrayHasKey('right_to_opt_out', $checks);
        $this->assertArrayHasKey('right_to_non_discrimination', $checks);
        $this->assertArrayHasKey('right_to_correct', $checks);
    }

    public function test_report_summary_calculates_correctly(): void
    {
        $report = $this->complianceReportingService->generateComplianceReport(
            AnalyticsComplianceReportingService::REPORT_TYPE_COMPREHENSIVE,
            []
        );

        $summary = $report['summary'];
        $this->assertArrayHasKey('total_sections', $summary);
        $this->assertArrayHasKey('compliant_sections', $summary);
        $this->assertArrayHasKey('attention_required', $summary);
        $this->assertArrayHasKey('non_compliant', $summary);

        $this->assertEquals(4, $summary['total_sections']);
        $this->assertEquals(4, $summary['compliant_sections'] + $summary['attention_required'] + $summary['non_compliant']);
    }

    public function test_data_retention_report_shows_compliance_per_data_type(): void
    {
        $report = $this->complianceReportingService->generateComplianceReport(
            AnalyticsComplianceReportingService::REPORT_TYPE_DATA_RETENTION,
            []
        );

        $retentionSection = $report['sections']['data_retention'];

        foreach ($retentionSection['retention_policies'] as $dataType => $policy) {
            $this->assertArrayHasKey('data_type', $policy);
            $this->assertArrayHasKey('retention_days', $policy);
            $this->assertArrayHasKey('records_checked', $policy);
            $this->assertArrayHasKey('records_to_purge', $policy);
            $this->assertArrayHasKey('compliant', $policy);
        }
    }

    public function test_compliance_status_respects_tenant_isolation(): void
    {
        // Set up mock for another tenant
        $otherTenantService = Mockery::mock(TenantContextService::class);
        $otherTenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('other-tenant-uuid');
        $otherTenantService->shouldReceive('runInTenantContext')
            ->andReturnUsing(function ($tenantId, $callback) {
                return $callback();
            });

        $otherPrivacyAuditService = Mockery::mock(PrivacyAuditService::class);
        $otherPrivacyAuditService->shouldReceive('logPrivacyEvent')
            ->andReturn(null);

        $otherComplianceService = new AnalyticsComplianceReportingService(
            $otherTenantService,
            $otherPrivacyAuditService,
            $this->privacyComplianceService
        );

        $status1 = $this->complianceReportingService->getComplianceStatus();
        $status2 = $otherComplianceService->getComplianceStatus();

        // Both should have their respective tenant IDs
        $this->assertEquals('test-tenant-uuid', $status1['tenant_id']);
        $this->assertEquals('other-tenant-uuid', $status2['tenant_id']);
    }

    public function test_consent_metrics_structure(): void
    {
        // Test the structure of consent metrics through the compliance report
        $report = $this->complianceReportingService->generateComplianceReport(
            AnalyticsComplianceReportingService::REPORT_TYPE_CONSENT,
            []
        );

        $this->assertArrayHasKey('consent_metrics', $report['sections']['consent']);
        $metrics = $report['sections']['consent']['consent_metrics'];

        $this->assertArrayHasKey('total_consent_records', $metrics);
        $this->assertArrayHasKey('consent_by_type', $metrics);
        $this->assertArrayHasKey('consent_rate', $metrics);
    }

    public function test_retention_metrics_structure(): void
    {
        // Test the structure of retention metrics through the compliance report
        $report = $this->complianceReportingService->generateComplianceReport(
            AnalyticsComplianceReportingService::REPORT_TYPE_DATA_RETENTION,
            []
        );

        $retentionSection = $report['sections']['data_retention'];
        $this->assertArrayHasKey('retention_policies', $retentionSection);

        foreach ($retentionSection['retention_policies'] as $dataType => $policy) {
            $this->assertArrayHasKey('data_type', $policy);
            $this->assertArrayHasKey('retention_days', $policy);
            $this->assertArrayHasKey('records_checked', $policy);
            $this->assertArrayHasKey('records_to_purge', $policy);
            $this->assertArrayHasKey('compliant', $policy);
        }
    }
}
