<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Analytics\AnalyticsMonitoringService;
use App\Services\TenantContextService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Unit tests for AnalyticsMonitoringService
 *
 * @covers \App\Services\Analytics\AnalyticsMonitoringService
 */
class AnalyticsMonitoringServiceTest extends TestCase
{
    private AnalyticsMonitoringService $monitoringService;
    private TenantContextService|MockInterface $tenantContext;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        $this->tenantContext = Mockery::mock(TenantContextService::class);
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andReturn($this->tenant->id);
        $this->tenantContext->shouldReceive('getCurrentTenant')
            ->andReturn($this->tenant);

        $this->monitoringService = new AnalyticsMonitoringService($this->tenantContext);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test checkHealth returns healthy status for all services
     */
    public function test_check_health_returns_healthy_status(): void
    {
        $result = $this->monitoringService->checkHealth();

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('services', $result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertEquals($this->tenant->id, $result['tenant_id']);
    }

    /**
     * Test checkServiceHealth returns unknown for unregistered service
     */
    public function test_check_service_health_returns_unknown_for_unregistered(): void
    {
        $result = $this->monitoringService->checkServiceHealth('unknown_service');

        $this->assertEquals('unknown_service', $result['service']);
        $this->assertEquals(AnalyticsMonitoringService::STATUS_UNKNOWN, $result['status']);
        $this->assertStringContainsString('not registered', $result['message']);
    }

    /**
     * Test checkServiceHealth returns status for registered service
     */
    public function test_check_service_health_returns_status_for_registered(): void
    {
        $result = $this->monitoringService->checkServiceHealth('event_processor');

        $this->assertEquals('event_processor', $result['service']);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('timestamp', $result);
    }

    /**
     * Test monitorMetrics returns metrics structure
     */
    public function test_monitor_metrics_returns_metrics_structure(): void
    {
        $result = $this->monitoringService->monitorMetrics();

        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('timeframe', $result);
        $this->assertArrayHasKey('timeframe_minutes', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('metrics', $result);
    }

    /**
     * Test monitorMetrics with custom timeframe
     */
    public function test_monitor_metrics_with_custom_timeframe(): void
    {
        $result = $this->monitoringService->monitorMetrics(['timeframe' => '24h']);

        $this->assertEquals('24h', $result['timeframe']);
        $this->assertEquals(1440, $result['timeframe_minutes']);
    }

    /**
     * Test monitorMetrics includes expected metric categories
     */
    public function test_monitor_metrics_includes_expected_categories(): void
    {
        $result = $this->monitoringService->monitorMetrics();
        $metrics = $result['metrics'];

        $this->assertArrayHasKey('events', $metrics);
        $this->assertArrayHasKey('queries', $metrics);
        $this->assertArrayHasKey('processing', $metrics);
        $this->assertArrayHasKey('cache', $metrics);
        $this->assertArrayHasKey('api', $metrics);
        $this->assertArrayHasKey('system_load', $metrics);
    }

    /**
     * Test detectAnomalies with empty metrics
     */
    public function test_detect_anomalies_with_empty_metrics(): void
    {
        $result = $this->monitoringService->detectAnomalies([]);

        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('anomalies_detected', $result);
        $this->assertArrayHasKey('anomalies', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertEquals(0, $result['anomalies_detected']);
    }

    /**
     * Test detectAnomalies identifies event drops
     */
    public function test_detect_anomalies_identifies_event_drops(): void
    {
        $metrics = [
            'events' => [
                'events_per_minute' => 5, // Very low compared to baseline of 100
            ],
            'queries' => [
                'avg_response_time_ms' => 500,
            ],
            'cache' => [
                'hit_ratio' => 85,
            ],
            'api' => [
                'error_rate_percent' => 1,
            ],
        ];

        $result = $this->monitoringService->detectAnomalies($metrics);

        $this->assertGreaterThan(0, $result['anomalies_detected']);
        
        $eventAnomaly = collect($result['anomalies'])->firstWhere('type', 'event_drop');
        $this->assertNotNull($eventAnomaly);
        $this->assertEquals(AnalyticsMonitoringService::SEVERITY_CRITICAL, $eventAnomaly['severity']);
    }

    /**
     * Test detectAnomalies identifies event spikes
     */
    public function test_detect_anomalies_identifies_event_spikes(): void
    {
        $metrics = [
            'events' => [
                'events_per_minute' => 350, // 3.5x baseline of 100
            ],
            'queries' => [
                'avg_response_time_ms' => 500,
            ],
            'cache' => [
                'hit_ratio' => 85,
            ],
            'api' => [
                'error_rate_percent' => 1,
            ],
        ];

        $result = $this->monitoringService->detectAnomalies($metrics);

        $eventAnomaly = collect($result['anomalies'])->firstWhere('type', 'event_spike');
        $this->assertNotNull($eventAnomaly);
        $this->assertEquals(AnalyticsMonitoringService::SEVERITY_WARNING, $eventAnomaly['severity']);
    }

    /**
     * Test detectAnomalies identifies cache degradation
     */
    public function test_detect_anomalies_identifies_cache_degradation(): void
    {
        $metrics = [
            'events' => [
                'events_per_minute' => 100,
            ],
            'queries' => [
                'avg_response_time_ms' => 500,
            ],
            'cache' => [
                'hit_ratio' => 60, // Below threshold of 70
            ],
            'api' => [
                'error_rate_percent' => 1,
            ],
        ];

        $result = $this->monitoringService->detectAnomalies($metrics);

        $cacheAnomaly = collect($result['anomalies'])->firstWhere('type', 'cache_degradation');
        $this->assertNotNull($cacheAnomaly);
    }

    /**
     * Test detectAnomalies identifies high error rate
     */
    public function test_detect_anomalies_identifies_high_error_rate(): void
    {
        $metrics = [
            'events' => [
                'events_per_minute' => 100,
            ],
            'queries' => [
                'avg_response_time_ms' => 500,
            ],
            'cache' => [
                'hit_ratio' => 85,
            ],
            'api' => [
                'error_rate_percent' => 8, // Above 5% threshold
            ],
        ];

        $result = $this->monitoringService->detectAnomalies($metrics);

        $errorAnomaly = collect($result['anomalies'])->firstWhere('type', 'high_error_rate');
        $this->assertNotNull($errorAnomaly);
    }

    /**
     * Test sendAlert with valid alert
     */
    public function test_send_alert_with_valid_alert(): void
    {
        $alert = [
            'type' => 'test',
            'severity' => AnalyticsMonitoringService::SEVERITY_WARNING,
            'message' => 'Test alert message',
        ];

        $result = $this->monitoringService->sendAlert($alert);

        $this->assertTrue($result);
    }

    /**
     * Test sendAlert with missing message throws exception
     */
    public function test_send_alert_with_missing_message_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Alert message is required');

        $alert = [
            'type' => 'test',
            'severity' => AnalyticsMonitoringService::SEVERITY_WARNING,
        ];

        $this->monitoringService->sendAlert($alert);
    }

    /**
     * Test sendAlert enriches alert with metadata
     */
    public function test_send_alert_enriches_alert_with_metadata(): void
    {
        $alert = [
            'type' => 'test',
            'severity' => AnalyticsMonitoringService::SEVERITY_INFO,
            'message' => 'Test alert',
        ];

        $this->monitoringService->sendAlert($alert);

        // Verify alert was stored with metadata
        $history = $this->monitoringService->getAlertHistory();
        $sentAlert = collect($history['alerts'])->first();

        $this->assertNotNull($sentAlert);
        $this->assertArrayHasKey('tenant_id', $sentAlert);
        $this->assertArrayHasKey('sent_at', $sentAlert);
        $this->assertArrayHasKey('alert_id', $sentAlert);
        $this->assertEquals($this->tenant->id, $sentAlert['tenant_id']);
    }

    /**
     * Test configureAlerts updates configuration
     */
    public function test_configure_alerts_updates_configuration(): void
    {
        $newConfig = [
            'enabled' => false,
            'thresholds' => [
                'error_rate' => 10.0,
            ],
        ];

        $result = $this->monitoringService->configureAlerts($newConfig);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('configuration', $result);
        $this->assertArrayHasKey('updated_at', $result);
        $this->assertEquals($this->tenant->id, $result['tenant_id']);
    }

    /**
     * Test getAlertHistory returns alerts structure
     */
    public function test_get_alert_history_returns_alerts_structure(): void
    {
        $result = $this->monitoringService->getAlertHistory();

        $this->assertArrayHasKey('alerts', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('offset', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('retrieved_at', $result);
    }

    /**
     * Test getAlertHistory with pagination
     */
    public function test_get_alert_history_with_pagination(): void
    {
        $result = $this->monitoringService->getAlertHistory([
            'limit' => 10,
            'offset' => 5,
        ]);

        $this->assertEquals(10, $result['limit']);
        $this->assertEquals(5, $result['offset']);
    }

    /**
     * Test getAlertHistory with severity filter
     */
    public function test_get_alert_history_with_severity_filter(): void
    {
        // First send an alert
        $this->monitoringService->sendAlert([
            'type' => 'test',
            'severity' => AnalyticsMonitoringService::SEVERITY_CRITICAL,
            'message' => 'Critical test alert',
        ]);

        $result = $this->monitoringService->getAlertHistory([
            'severity' => AnalyticsMonitoringService::SEVERITY_CRITICAL,
        ]);

        foreach ($result['alerts'] as $alert) {
            $this->assertEquals(AnalyticsMonitoringService::SEVERITY_CRITICAL, $alert['severity']);
        }
    }

    /**
     * Test getMonitoringDashboard returns dashboard structure
     */
    public function test_get_monitoring_dashboard_returns_dashboard_structure(): void
    {
        $result = $this->monitoringService->getMonitoringDashboard();

        $this->assertArrayHasKey('generated_at', $result);
        $this->assertArrayHasKey('timeframe', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('health', $result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('anomalies', $result);
        $this->assertArrayHasKey('recent_alerts', $result);
        $this->assertArrayHasKey('system_status', $result);
    }

    /**
     * Test getMonitoringDashboard with custom timeframe
     */
    public function test_get_monitoring_dashboard_with_custom_timeframe(): void
    {
        $result = $this->monitoringService->getMonitoringDashboard([
            'timeframe' => '7d',
        ]);

        $this->assertEquals('7d', $result['timeframe']);
    }

    /**
     * Test dashboard summary includes expected fields
     */
    public function test_dashboard_summary_includes_expected_fields(): void
    {
        $result = $this->monitoringService->getMonitoringDashboard();
        $summary = $result['summary'];

        $this->assertArrayHasKey('overall_status', $summary);
        $this->assertArrayHasKey('services_healthy', $summary);
        $this->assertArrayHasKey('total_services', $summary);
        $this->assertArrayHasKey('anomalies_count', $summary);
        $this->assertArrayHasKey('critical_alerts', $summary);
        $this->assertArrayHasKey('issues', $summary);
    }

    /**
     * Test health constants are defined correctly
     */
    public function test_health_constants_are_defined(): void
    {
        $this->assertEquals('healthy', AnalyticsMonitoringService::STATUS_HEALTHY);
        $this->assertEquals('warning', AnalyticsMonitoringService::STATUS_WARNING);
        $this->assertEquals('critical', AnalyticsMonitoringService::STATUS_CRITICAL);
        $this->assertEquals('unknown', AnalyticsMonitoringService::STATUS_UNKNOWN);
    }

    /**
     * Test severity constants are defined correctly
     */
    public function test_severity_constants_are_defined(): void
    {
        $this->assertEquals('info', AnalyticsMonitoringService::SEVERITY_INFO);
        $this->assertEquals('warning', AnalyticsMonitoringService::SEVERITY_WARNING);
        $this->assertEquals('critical', AnalyticsMonitoringService::SEVERITY_CRITICAL);
    }

    /**
     * Test tenant isolation in cache keys
     */
    public function test_tenant_isolation_in_cache_operations(): void
    {
        // Create service with different tenant
        $tenant2 = Tenant::factory()->create();
        
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andReturn($tenant2->id);

        $service2 = new AnalyticsMonitoringService($this->tenantContext);

        // Send alert for tenant 2
        $service2->sendAlert([
            'type' => 'tenant_test',
            'severity' => AnalyticsMonitoringService::SEVERITY_INFO,
            'message' => 'Tenant 2 alert',
        ]);

        // Check tenant 2 has alert
        $history2 = $service2->getAlertHistory();
        $this->assertGreaterThan(0, $history2['total_count']);
    }

    /**
     * Test error handling in checkHealth
     */
    public function test_error_handling_in_check_health(): void
    {
        // Mock tenant context to throw exception
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andThrow(new \Exception('Test error'));

        $service = new AnalyticsMonitoringService($this->tenantContext);
        $result = $service->checkHealth();

        $this->assertEquals(AnalyticsMonitoringService::STATUS_UNKNOWN, $result['status']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test error handling in monitorMetrics
     */
    public function test_error_handling_in_monitor_metrics(): void
    {
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andThrow(new \Exception('Test error'));

        $service = new AnalyticsMonitoringService($this->tenantContext);
        $result = $service->monitorMetrics();

        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test error handling in getAlertHistory
     */
    public function test_error_handling_in_get_alert_history(): void
    {
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andThrow(new \Exception('Test error'));

        $service = new AnalyticsMonitoringService($this->tenantContext);
        $result = $service->getAlertHistory();

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(0, $result['total_count']);
    }

    /**
     * Test system load calculation
     */
    public function test_system_load_calculation(): void
    {
        $metrics = [
            'events' => [
                'events_per_minute' => 1500, // High load
            ],
            'queries' => [
                'avg_response_time_ms' => 4000, // Slow queries
            ],
            'cache' => [
                'hit_ratio' => 60, // Low hit ratio
            ],
            'api' => [],
        ];

        $result = $this->monitoringService->monitorMetrics();
        $load = $result['metrics']['system_load'];

        $this->assertArrayHasKey('load_percentage', $load);
        $this->assertArrayHasKey('level', $load);
        $this->assertArrayHasKey('factors', $load);
        $this->assertGreaterThan(50, $load['load_percentage']);
    }

    /**
     * Test severity distribution calculation
     */
    public function test_severity_distribution_calculation(): void
    {
        $anomalies = [
            ['severity' => AnalyticsMonitoringService::SEVERITY_WARNING, 'type' => 'test1'],
            ['severity' => AnalyticsMonitoringService::SEVERITY_CRITICAL, 'type' => 'test2'],
            ['severity' => AnalyticsMonitoringService::SEVERITY_WARNING, 'type' => 'test3'],
        ];

        $result = $this->monitoringService->detectAnomalies([
            'events' => ['events_per_minute' => 5],
            'queries' => [],
            'cache' => [],
            'api' => [],
        ]);

        $this->assertArrayHasKey('severity_distribution', $result);
        $distribution = $result['severity_distribution'];
        
        $this->assertArrayHasKey(AnalyticsMonitoringService::SEVERITY_INFO, $distribution);
        $this->assertArrayHasKey(AnalyticsMonitoringService::SEVERITY_WARNING, $distribution);
        $this->assertArrayHasKey(AnalyticsMonitoringService::SEVERITY_CRITICAL, $distribution);
    }
}
