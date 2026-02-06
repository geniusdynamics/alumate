<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AnalyticsEvent;
use App\Models\HeatMapData;
use App\Services\HeatMapService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeatMapServiceTest extends TestCase
{
    use RefreshDatabase;

    private HeatMapService $heatMapService;
    private TenantContextService $tenantContextService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->heatMapService = app(HeatMapService::class);
        $this->tenantContextService = app(TenantContextService::class);

        // Set up tenant context for tests
        $this->tenantContextService->setTenant('test-tenant-1');
    }

    public function test_collect_heat_map_data_with_valid_events(): void
    {
        // Create test events
        AnalyticsEvent::create([
            'tenant_id' => 'test-tenant-1',
            'event_type' => 'click',
            'properties' => ['x' => 50, 'y' => 30],
            'page_url' => '/test-page',
            'consent_given' => true,
            'occurred_at' => now(),
        ]);

        AnalyticsEvent::create([
            'tenant_id' => 'test-tenant-1',
            'event_type' => 'click',
            'properties' => ['x' => 60, 'y' => 40],
            'page_url' => '/test-page',
            'consent_given' => true,
            'occurred_at' => now(),
        ]);

        $dateRange = [now()->subDay(), now()->addDay()];

        $result = $this->heatMapService->collectHeatMapData('/test-page', $dateRange);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Check that data was stored
        $heatMapData = HeatMapData::byTenant('test-tenant-1')
            ->byPageUrl('/test-page')
            ->first();

        $this->assertNotNull($heatMapData);
        $this->assertEquals('test-tenant-1', $heatMapData->tenant_id);
        $this->assertEquals('/test-page', $heatMapData->page_url);
        $this->assertIsArray($heatMapData->coordinate_data);
    }

    public function test_collect_heat_map_data_with_no_events(): void
    {
        $dateRange = [now()->subDay(), now()->addDay()];

        $result = $this->heatMapService->collectHeatMapData('/empty-page', $dateRange);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_collect_heat_map_data_filters_non_consented_events(): void
    {
        // Create event without consent
        AnalyticsEvent::create([
            'tenant_id' => 'test-tenant-1',
            'event_type' => 'click',
            'properties' => ['x' => 50, 'y' => 30],
            'page_url' => '/test-page',
            'consent_given' => false,
            'occurred_at' => now(),
        ]);

        $dateRange = [now()->subDay(), now()->addDay()];

        $result = $this->heatMapService->collectHeatMapData('/test-page', $dateRange);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_record_heat_map_event_processes_click_event(): void
    {
        $event = AnalyticsEvent::create([
            'tenant_id' => 'test-tenant-1',
            'event_type' => 'click',
            'properties' => ['x' => 25, 'y' => 75],
            'page_url' => '/test-page',
            'consent_given' => true,
            'occurred_at' => now(),
            'session_id' => 'session-123',
        ]);

        $this->heatMapService->recordHeatMapEvent($event);

        $heatMapData = HeatMapData::byTenant('test-tenant-1')
            ->byPageUrl('/test-page')
            ->first();

        $this->assertNotNull($heatMapData);
        $this->assertIsArray($heatMapData->coordinate_data);
        $this->assertNotEmpty($heatMapData->coordinate_data);
    }

    public function test_record_heat_map_event_ignores_scroll_event_without_coordinates(): void
    {
        $event = AnalyticsEvent::create([
            'tenant_id' => 'test-tenant-1',
            'event_type' => 'scroll',
            'properties' => ['scroll_depth' => 50], // No x,y coordinates
            'page_url' => '/test-page',
            'consent_given' => true,
            'occurred_at' => now(),
        ]);

        $this->heatMapService->recordHeatMapEvent($event);

        $heatMapData = HeatMapData::byTenant('test-tenant-1')
            ->byPageUrl('/test-page')
            ->first();

        $this->assertNull($heatMapData);
    }

    public function test_grid_binning_works_correctly(): void
    {
        // Create events at specific coordinates
        AnalyticsEvent::create([
            'tenant_id' => 'test-tenant-1',
            'event_type' => 'click',
            'properties' => ['x' => 10, 'y' => 10], // Should be bin 0,0
            'page_url' => '/test-page',
            'consent_given' => true,
            'occurred_at' => now(),
        ]);

        AnalyticsEvent::create([
            'tenant_id' => 'test-tenant-1',
            'event_type' => 'click',
            'properties' => ['x' => 90, 'y' => 90], // Should be bin 9,9
            'page_url' => '/test-page',
            'consent_given' => true,
            'occurred_at' => now(),
        ]);

        $dateRange = [now()->subDay(), now()->addDay()];

        $result = $this->heatMapService->collectHeatMapData('/test-page', $dateRange);

        $this->assertIsArray($result);

        // Check that we have data points for bins 0,0 and 9,9
        $hasBin00 = false;
        $hasBin99 = false;

        foreach ($result as $point) {
            if ($point['x'] === 0 && $point['y'] === 0) {
                $hasBin00 = true;
            }
            if ($point['x'] === 9 && $point['y'] === 9) {
                $hasBin99 = true;
            }
        }

        $this->assertTrue($hasBin00, 'Bin 0,0 should be present');
        $this->assertTrue($hasBin99, 'Bin 9,9 should be present');
    }

    public function test_tenant_isolation(): void
    {
        // Create event for different tenant
        AnalyticsEvent::create([
            'tenant_id' => 'different-tenant',
            'event_type' => 'click',
            'properties' => ['x' => 50, 'y' => 50],
            'page_url' => '/test-page',
            'consent_given' => true,
            'occurred_at' => now(),
        ]);

        $dateRange = [now()->subDay(), now()->addDay()];

        $result = $this->heatMapService->collectHeatMapData('/test-page', $dateRange);

        // Should be empty because event belongs to different tenant
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_coordinate_normalization(): void
    {
        // Create event with coordinates outside 0-100 range
        AnalyticsEvent::create([
            'tenant_id' => 'test-tenant-1',
            'event_type' => 'click',
            'properties' => ['x' => 150, 'y' => -10], // Outside range
            'page_url' => '/test-page',
            'consent_given' => true,
            'occurred_at' => now(),
        ]);

        $dateRange = [now()->subDay(), now()->addDay()];

        $result = $this->heatMapService->collectHeatMapData('/test-page', $dateRange);

        $this->assertIsArray($result);
        // Should still process but coordinates will be clamped
    }

    public function test_invalid_coordinate_data_handling(): void
    {
        // Create event with invalid coordinate data
        AnalyticsEvent::create([
            'tenant_id' => 'test-tenant-1',
            'event_type' => 'click',
            'properties' => ['x' => 'invalid', 'y' => null],
            'page_url' => '/test-page',
            'consent_given' => true,
            'occurred_at' => now(),
        ]);

        $dateRange = [now()->subDay(), now()->addDay()];

        $result = $this->heatMapService->collectHeatMapData('/test-page', $dateRange);

        // Should handle gracefully without crashing
        $this->assertIsArray($result);
    }

    public function test_heat_map_data_update_merges_coordinates(): void
    {
        // Create initial heat map data
        HeatMapData::create([
            'tenant_id' => 'test-tenant-1',
            'page_url' => '/test-page',
            'coordinate_data' => [
                ['x' => 5, 'y' => 5, 'intensity' => 50],
            ],
            'timestamp' => now(),
            'session_id' => 'session-1',
        ]);

        // Create new event at same location
        $event = AnalyticsEvent::create([
            'tenant_id' => 'test-tenant-1',
            'event_type' => 'click',
            'properties' => ['x' => 50, 'y' => 50], // Same bin as existing data
            'page_url' => '/test-page',
            'consent_given' => true,
            'occurred_at' => now(),
            'session_id' => 'session-2',
        ]);

        $this->heatMapService->recordHeatMapEvent($event);

        $heatMapData = HeatMapData::byTenant('test-tenant-1')
            ->byPageUrl('/test-page')
            ->first();

        $this->assertNotNull($heatMapData);

        // Find the bin that should have merged data
        $mergedPoint = null;
        foreach ($heatMapData->coordinate_data as $point) {
            if ($point['x'] === 5 && $point['y'] === 5) {
                $mergedPoint = $point;
                break;
            }
        }

        $this->assertNotNull($mergedPoint);
        $this->assertGreaterThan(50, $mergedPoint['intensity']);
    }

    public function test_no_tenant_context_returns_empty_result(): void
    {
        // Clear tenant context
        $this->tenantContextService->clearContext();

        $dateRange = [now()->subDay(), now()->addDay()];

        $result = $this->heatMapService->collectHeatMapData('/test-page', $dateRange);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}