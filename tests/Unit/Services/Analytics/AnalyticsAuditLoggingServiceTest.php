<?php

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\AnalyticsAuditLoggingService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class AnalyticsAuditLoggingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsAuditLoggingService $auditLoggingService;
    protected TenantContextService $tenantContextService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the tenant context service
        $this->tenantContextService = Mockery::mock(TenantContextService::class);
        $this->tenantContextService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        // Create the service with mocked dependencies
        $this->auditLoggingService = new AnalyticsAuditLoggingService($this->tenantContextService);

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

    public function test_can_log_audit_event(): void
    {
        $event = [
            'type' => AnalyticsAuditLoggingService::EVENT_USER_ACTION,
            'action' => 'test_action',
            'resource_type' => 'test_resource',
            'description' => 'Test audit event',
            'metadata' => ['key' => 'value'],
        ];

        $logId = $this->auditLoggingService->logAuditEvent($event);

        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);

        $log = DB::table('audit_logs')->find($logId);
        $this->assertNotNull($log);
        $this->assertEquals(AnalyticsAuditLoggingService::EVENT_USER_ACTION, $log->event_type);
        $this->assertEquals('test_action', $log->action);
        $this->assertEquals('test_resource', $log->resource_type);
        $this->assertEquals('test-tenant-uuid', $log->tenant_id);
        $this->assertEquals(1, $log->user_id);
    }

    public function test_can_log_data_access(): void
    {
        $userId = 1;
        $resource = AnalyticsAuditLoggingService::RESOURCE_DASHBOARD;
        $action = 'view';
        $context = [
            'resource_id' => 123,
            'additional_info' => 'test',
        ];

        $logId = $this->auditLoggingService->logDataAccess($userId, $resource, $action, $context);

        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);

        $log = DB::table('audit_logs')->find($logId);
        $this->assertNotNull($log);
        $this->assertEquals(AnalyticsAuditLoggingService::EVENT_DATA_ACCESS, $log->event_type);
        $this->assertEquals('view', $log->action);
        $this->assertEquals(AnalyticsAuditLoggingService::RESOURCE_DASHBOARD, $log->resource_type);
        $this->assertEquals(123, $log->resource_id);
    }

    public function test_can_log_data_modification(): void
    {
        $userId = 1;
        $resource = AnalyticsAuditLoggingService::RESOURCE_REPORT;
        $changes = [
            'event_type' => AnalyticsAuditLoggingService::EVENT_DATA_CREATE,
            'action' => 'create',
            'resource_id' => 456,
            'before' => null,
            'after' => ['name' => 'New Report', 'status' => 'active'],
            'fields' => ['name', 'status'],
        ];

        $logId = $this->auditLoggingService->logDataModification($userId, $resource, $changes);

        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);

        $log = DB::table('audit_logs')->find($logId);
        $this->assertNotNull($log);
        $this->assertEquals(AnalyticsAuditLoggingService::EVENT_DATA_CREATE, $log->event_type);
        $this->assertEquals('create', $log->action);
        $this->assertEquals(AnalyticsAuditLoggingService::RESOURCE_REPORT, $log->resource_type);
        $this->assertEquals(456, $log->resource_id);

        $metadata = json_decode($log->metadata, true);
        $this->assertEquals(['name' => 'New Report', 'status' => 'active'], $metadata['after']);
        $this->assertEquals(['name', 'status'], $metadata['fields_modified']);
    }

    public function test_can_log_configuration_change(): void
    {
        $userId = 1;
        $config = 'analytics.settings.retention_period';
        $changes = [
            'old_value' => 30,
            'new_value' => 60,
        ];

        $logId = $this->auditLoggingService->logConfigurationChange($userId, $config, $changes);

        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);

        $log = DB::table('audit_logs')->find($logId);
        $this->assertNotNull($log);
        $this->assertEquals(AnalyticsAuditLoggingService::EVENT_CONFIG_CHANGE, $log->event_type);
        $this->assertEquals('configuration_update', $log->action);
        $this->assertEquals(AnalyticsAuditLoggingService::RESOURCE_CONFIG, $log->resource_type);

        $metadata = json_decode($log->metadata, true);
        $this->assertEquals($config, $metadata['config_key']);
        $this->assertEquals(30, $metadata['old_value']);
        $this->assertEquals(60, $metadata['new_value']);
    }

    public function test_can_get_audit_logs_with_filters(): void
    {
        // Create multiple audit logs
        $this->auditLoggingService->logDataAccess(1, AnalyticsAuditLoggingService::RESOURCE_DASHBOARD, 'view');
        $this->auditLoggingService->logDataAccess(1, AnalyticsAuditLoggingService::RESOURCE_DASHBOARD, 'export');
        $this->auditLoggingService->logDataAccess(2, AnalyticsAuditLoggingService::RESOURCE_REPORT, 'view');

        $filters = [
            'user_id' => 1,
            'resource_type' => AnalyticsAuditLoggingService::RESOURCE_DASHBOARD,
        ];

        $logs = $this->auditLoggingService->getAuditLogs($filters, 10);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $logs);
        $this->assertEquals(2, $logs->total());
    }

    public function test_can_get_audit_log_by_id(): void
    {
        $event = [
            'type' => AnalyticsAuditLoggingService::EVENT_USER_ACTION,
            'action' => 'test_action',
            'resource_type' => 'test_resource',
        ];

        $logId = $this->auditLoggingService->logAuditEvent($event);

        $log = $this->auditLoggingService->getAuditLogById($logId);

        $this->assertNotNull($log);
        $this->assertEquals($logId, $log->id);
        $this->assertEquals('test_action', $log->action);
    }

    public function test_get_audit_log_by_id_returns_null_for_nonexistent(): void
    {
        $log = $this->auditLoggingService->getAuditLogById(99999);

        $this->assertNull($log);
    }

    public function test_can_export_audit_logs_as_json(): void
    {
        // Create audit logs
        $this->auditLoggingService->logDataAccess(1, AnalyticsAuditLoggingService::RESOURCE_DASHBOARD, 'view');
        $this->auditLoggingService->logDataAccess(1, AnalyticsAuditLoggingService::RESOURCE_REPORT, 'export');

        $export = $this->auditLoggingService->exportAuditLogs([], 'json');

        $this->assertJson($export);

        $data = json_decode($export, true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
    }

    public function test_can_export_audit_logs_as_csv(): void
    {
        // Create audit logs
        $this->auditLoggingService->logDataAccess(1, AnalyticsAuditLoggingService::RESOURCE_DASHBOARD, 'view');
        $this->auditLoggingService->logDataAccess(1, AnalyticsAuditLoggingService::RESOURCE_REPORT, 'export');

        $export = $this->auditLoggingService->exportAuditLogs([], 'csv');

        $this->assertStringContainsString('action', $export);
        $this->assertStringContainsString('view', $export);
        $this->assertStringContainsString('export', $export);
    }

    public function test_can_get_audit_summary(): void
    {
        // Create audit logs
        $this->auditLoggingService->logDataAccess(1, AnalyticsAuditLoggingService::RESOURCE_DASHBOARD, 'view');
        $this->auditLoggingService->logDataAccess(2, AnalyticsAuditLoggingService::RESOURCE_DASHBOARD, 'export');
        $this->auditLoggingService->logDataAccess(1, AnalyticsAuditLoggingService::RESOURCE_REPORT, 'view');

        $dateRange = [
            'from' => now()->subDay(),
            'to' => now(),
        ];

        $summary = $this->auditLoggingService->getAuditSummary($dateRange);

        $this->assertArrayHasKey('total_events', $summary);
        $this->assertArrayHasKey('events_by_type', $summary);
        $this->assertArrayHasKey('events_by_action', $summary);
        $this->assertArrayHasKey('events_by_user', $summary);
        $this->assertArrayHasKey('events_by_resource', $summary);
        $this->assertArrayHasKey('daily_breakdown', $summary);

        $this->assertEquals(3, $summary['total_events']);
    }

    public function test_can_get_audit_trail_for_user(): void
    {
        // Create audit logs for specific user
        $this->auditLoggingService->logDataAccess(1, AnalyticsAuditLoggingService::RESOURCE_DASHBOARD, 'view');
        $this->auditLoggingService->logDataAccess(1, AnalyticsAuditLoggingService::RESOURCE_REPORT, 'export');
        $this->auditLoggingService->logDataAccess(2, AnalyticsAuditLoggingService::RESOURCE_DASHBOARD, 'view');

        $trail = $this->auditLoggingService->getAuditTrail(1, 10);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $trail);
        $this->assertEquals(2, $trail->count());

        // All entries should belong to user 1
        $trail->each(function ($log) {
            $this->assertEquals(1, $log->user_id);
        });
    }

    public function test_can_search_audit_logs(): void
    {
        // Create audit logs
        $this->auditLoggingService->logDataAccess(1, AnalyticsAuditLoggingService::RESOURCE_DASHBOARD, 'view_report');
        $this->auditLoggingService->logDataAccess(1, AnalyticsAuditLoggingService::RESOURCE_REPORT, 'export_data');
        $this->auditLoggingService->logDataAccess(2, AnalyticsAuditLoggingService::RESOURCE_DASHBOARD, 'view_dashboard');

        $results = $this->auditLoggingService->searchAuditLogs('view', 10);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $results);
        $this->assertEquals(2, $results->count());
    }

    public function test_audit_logs_respect_tenant_isolation(): void
    {
        // Set up mock for another tenant
        $otherTenantService = Mockery::mock(TenantContextService::class);
        $otherTenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('other-tenant-uuid');

        $otherAuditService = new AnalyticsAuditLoggingService($otherTenantService);

        // Create log in first tenant
        $logId1 = $this->auditLoggingService->logAuditEvent([
            'action' => 'tenant1_action',
            'resource_type' => 'test',
        ]);

        // Create log in second tenant
        $logId2 = $otherAuditService->logAuditEvent([
            'action' => 'tenant2_action',
            'resource_type' => 'test',
        ]);

        // Get logs from first tenant - should only see tenant1 log
        $filters = ['action' => 'tenant1_action'];
        $logs1 = $this->auditLoggingService->getAuditLogs($filters, 10);
        $this->assertEquals(1, $logs1->total());

        // Get logs from second tenant - should only see tenant2 log
        $filters = ['action' => 'tenant2_action'];
        $logs2 = $otherAuditService->getAuditLogs($filters, 10);
        $this->assertEquals(1, $logs2->total());
    }

    public function test_can_get_dashboard_stats(): void
    {
        // Create some audit logs
        $this->auditLoggingService->logDataAccess(1, AnalyticsAuditLoggingService::RESOURCE_DASHBOARD, 'view');
        $this->auditLoggingService->logDataAccess(1, AnalyticsAuditLoggingService::RESOURCE_DASHBOARD, 'view');
        $this->auditLoggingService->logDataAccess(2, AnalyticsAuditLoggingService::RESOURCE_REPORT, 'export');

        $stats = $this->auditLoggingService->getDashboardStats();

        $this->assertArrayHasKey('today_events', $stats);
        $this->assertArrayHasKey('week_events', $stats);
        $this->assertArrayHasKey('month_events', $stats);
        $this->assertArrayHasKey('total_users', $stats);
        $this->assertArrayHasKey('top_actions', $stats);

        $this->assertGreaterThanOrEqual(3, $stats['today_events']);
        $this->assertEquals(2, $stats['total_users']);
    }

    public function test_can_cleanup_old_logs(): void
    {
        // Insert old logs directly
        DB::table('audit_logs')->insert([
            'tenant_id' => 'test-tenant-uuid',
            'user_id' => 1,
            'event_type' => AnalyticsAuditLoggingService::EVENT_DATA_ACCESS,
            'action' => 'old_action',
            'resource_type' => 'test',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test',
            'metadata' => '{}',
            'created_at' => now()->subYear()->subDay(),
        ]);

        // Insert recent log
        $recentLogId = $this->auditLoggingService->logDataAccess(1, 'test', 'recent_action');

        $deleted = $this->auditLoggingService->cleanupOldLogs(30);

        // Old log should be deleted
        $this->assertEquals(1, $deleted);

        // Recent log should still exist
        $recentLog = DB::table('audit_logs')->find($recentLogId);
        $this->assertNotNull($recentLog);
    }

    public function test_get_audit_log_by_id_enforces_tenant_isolation(): void
    {
        // Create a log
        $logId = $this->auditLoggingService->logAuditEvent([
            'action' => 'test_action',
            'resource_type' => 'test',
        ]);

        // Try to access with different tenant context
        $otherTenantService = Mockery::mock(TenantContextService::class);
        $otherTenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('other-tenant-uuid');

        $otherAuditService = new AnalyticsAuditLoggingService($otherTenantService);

        // Should return null because the log belongs to a different tenant
        $log = $otherAuditService->getAuditLogById($logId);
        $this->assertNull($log);
    }
}
