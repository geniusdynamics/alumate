<?php

namespace Tests\Feature\Api;

use App\Models\AnalyticsEvent;
use App\Models\HeatMapData;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HeatMapApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private TenantContextService $tenantService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantService = app(TenantContextService::class);

        // Create tenant and set context
        $this->tenant = Tenant::factory()->create();
        $this->tenantService->setTenant($this->tenant);

        // Create authenticated user
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_get_heat_map_data_returns_successful_response()
    {
        // Create test analytics events
        AnalyticsEvent::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'click',
            'page_url' => 'https://example.com/test-page',
            'properties' => [
                'x' => 100,
                'y' => 200,
                'element' => 'button',
            ],
            'consent_given' => true,
        ]);

        $response = $this->getJson('/api/analytics/heatmaps/https%3A%2F%2Fexample.com%2Ftest-page');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'heatMapData',
                    'pageUrl',
                    'dateRange',
                    'totalClicks',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'pageUrl' => 'https://example.com/test-page',
                ],
            ]);
    }

    public function test_get_heat_map_data_with_date_filters()
    {
        // Create events with different dates
        AnalyticsEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'click',
            'page_url' => 'https://example.com/test-page',
            'occurred_at' => now()->subDays(10),
            'properties' => ['x' => 100, 'y' => 200],
            'consent_given' => true,
        ]);

        AnalyticsEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'click',
            'page_url' => 'https://example.com/test-page',
            'occurred_at' => now()->subDays(5),
            'properties' => ['x' => 150, 'y' => 250],
            'consent_given' => true,
        ]);

        $response = $this->getJson('/api/analytics/heatmaps/https%3A%2F%2Fexample.com%2Ftest-page?date_from=' . now()->subDays(7)->toDateString() . '&date_to=' . now()->toDateString());

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'totalClicks' => 1, // Only one event should be included
                ],
            ]);
    }

    public function test_get_heat_map_data_uses_cache_when_available()
    {
        $pageUrl = 'https://example.com/test-page';
        $dateRange = [
            'start' => now()->subDays(30)->toDateString(),
            'end' => now()->toDateString(),
        ];
        $cacheKey = "heatmap:{$this->tenant->id}:{$pageUrl}:" . md5(serialize($dateRange));

        $cachedData = [
            'heatMapData' => [['x' => 100, 'y' => 200, 'intensity' => 5]],
            'pageUrl' => $pageUrl,
            'dateRange' => $dateRange,
            'totalClicks' => 5,
        ];

        Cache::put($cacheKey, $cachedData, 3600);

        $response = $this->getJson('/api/analytics/heatmaps/' . urlencode($pageUrl));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => $cachedData,
            ]);
    }

    public function test_get_heat_map_data_handles_no_data_gracefully()
    {
        $response = $this->getJson('/api/analytics/heatmaps/https%3A%2F%2Fexample.com%2Fnonexistent-page');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'heatMapData' => [],
                    'totalClicks' => 0,
                ],
            ]);
    }

    public function test_get_heat_map_data_filters_by_consent()
    {
        // Create events with and without consent
        AnalyticsEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'click',
            'page_url' => 'https://example.com/test-page',
            'properties' => ['x' => 100, 'y' => 200],
            'consent_given' => true,
        ]);

        AnalyticsEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'click',
            'page_url' => 'https://example.com/test-page',
            'properties' => ['x' => 150, 'y' => 250],
            'consent_given' => false,
        ]);

        $response = $this->getJson('/api/analytics/heatmaps/https%3A%2F%2Fexample.com%2Ftest-page');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'totalClicks' => 1, // Only consented event should be included
                ],
            ]);
    }

    public function test_generate_heat_map_data_forces_regeneration()
    {
        // Create initial cached data
        $pageUrl = 'https://example.com/test-page';
        $dateRange = [
            'start' => now()->subDays(30)->toDateString(),
            'end' => now()->toDateString(),
        ];
        $cacheKey = "heatmap:{$this->tenant->id}:{$pageUrl}:" . md5(serialize($dateRange));

        $oldData = [
            'heatMapData' => [['x' => 100, 'y' => 200, 'intensity' => 1]],
            'pageUrl' => $pageUrl,
            'dateRange' => $dateRange,
            'totalClicks' => 1,
        ];

        Cache::put($cacheKey, $oldData, 3600);

        // Add new analytics event
        AnalyticsEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'click',
            'page_url' => $pageUrl,
            'properties' => ['x' => 100, 'y' => 200],
            'consent_given' => true,
        ]);

        $response = $this->postJson('/api/analytics/heatmaps/generate', [
            'page_url' => $pageUrl,
            'date_from' => $dateRange['start'],
            'date_to' => $dateRange['end'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Heat map data generated successfully',
                'data' => [
                    'totalClicks' => 1, // Should reflect fresh data
                ],
            ]);
    }

    public function test_generate_heat_map_data_validation()
    {
        $response = $this->postJson('/api/analytics/heatmaps/generate', [
            // Missing required page_url
            'date_from' => now()->subDays(30)->toDateString(),
            'date_to' => now()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page_url']);
    }

    public function test_tenant_isolation_in_heat_map_data()
    {
        // Create another tenant
        $otherTenant = Tenant::factory()->create();
        $this->tenantService->setTenant($otherTenant);

        // Create event for other tenant
        AnalyticsEvent::factory()->create([
            'tenant_id' => $otherTenant->id,
            'event_type' => 'click',
            'page_url' => 'https://example.com/test-page',
            'properties' => ['x' => 100, 'y' => 200],
            'consent_given' => true,
        ]);

        // Switch back to original tenant
        $this->tenantService->setTenant($this->tenant);

        $response = $this->getJson('/api/analytics/heatmaps/https%3A%2F%2Fexample.com%2Ftest-page');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'totalClicks' => 0, // Should not see other tenant's data
                ],
            ]);
    }

    public function test_heat_map_data_response_format()
    {
        AnalyticsEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'click',
            'page_url' => 'https://example.com/test-page',
            'properties' => [
                'x' => 100,
                'y' => 200,
                'element' => 'button',
            ],
            'consent_given' => true,
        ]);

        $response = $this->getJson('/api/analytics/heatmaps/https%3A%2F%2Fexample.com%2Ftest-page');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'heatMapData' => [
                        '*' => [
                            'x',
                            'y',
                            'intensity',
                        ],
                    ],
                    'pageUrl',
                    'dateRange' => [
                        'start',
                        'end',
                    ],
                    'totalClicks',
                ],
            ]);
    }

    public function test_heat_map_data_handles_invalid_date_range()
    {
        $response = $this->getJson('/api/analytics/heatmaps/https%3A%2F%2Fexample.com%2Ftest-page?date_from=2024-12-31&date_to=2024-01-01');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_to']);
    }

    public function test_unauthenticated_user_cannot_access_heat_map_data()
    {
        // Remove authentication
        $this->withoutMiddleware();

        $response = $this->getJson('/api/analytics/heatmaps/https%3A%2F%2Fexample.com%2Ftest-page');

        $response->assertStatus(401);
    }

    public function test_heat_map_data_handles_scroll_events()
    {
        AnalyticsEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'scroll',
            'page_url' => 'https://example.com/test-page',
            'properties' => [
                'x' => 50,
                'y' => 300,
                'scroll_depth' => 75,
            ],
            'consent_given' => true,
        ]);

        $response = $this->getJson('/api/analytics/heatmaps/https%3A%2F%2Fexample.com%2Ftest-page');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'totalClicks' => 1, // Scroll events should be counted
                ],
            ]);
    }
}