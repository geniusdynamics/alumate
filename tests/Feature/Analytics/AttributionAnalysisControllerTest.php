<?php

namespace Tests\Feature\Analytics;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Analytics\AttributionTrackingService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Feature tests for AttributionAnalysisController
 * 
 * This test suite covers:
 * - Touchpoint CRUD operations
 * - Attribution calculation endpoints
 * - Model comparison endpoints
 * - Channel performance analysis
 * - Budget recommendations
 * - Conversion path analysis
 * - Request validation
 * - Tenant isolation
 * - Error handling
 */
class AttributionAnalysisControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant1;
    private Tenant $tenant2;
    private User $user1;
    private User $user2;
    private AttributionTrackingService $attributionService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two separate tenants
        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Test Tenant 1',
            'slug' => 'test-tenant-1',
            'status' => 'active',
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Test Tenant 2',
            'slug' => 'test-tenant-2',
            'status' => 'active',
        ]);

        // Create users for each tenant
        $this->user1 = User::factory()->create([
            'email' => 'user1@testtenant1.com',
            'tenant_id' => $this->tenant1->id,
        ]);

        $this->user2 = User::factory()->create([
            'email' => 'user2@testtenant2.com',
            'tenant_id' => $this->tenant2->id,
        ]);

        $this->attributionService = app(AttributionTrackingService::class);
    }

    // ==================== Authentication Tests ====================

    /**
     * Test that endpoints require authentication
     */
    public function test_endpoints_require_authentication(): void
    {
        $endpoints = [
            ['GET', '/api/analytics/attribution-analysis/touchpoints'],
            ['POST', '/api/analytics/attribution-analysis/touchpoints'],
            ['GET', '/api/analytics/attribution-analysis/touchpoints/1'],
            ['GET', '/api/analytics/attribution-analysis/calculate/1'],
            ['GET', '/api/analytics/attribution-analysis/compare/1'],
            ['GET', '/api/analytics/attribution-analysis/channels/performance'],
            ['GET', '/api/analytics/attribution-analysis/budget/recommendations'],
            ['GET', '/api/analytics/attribution-analysis/conversion-path/1'],
        ];

        foreach ($endpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint);
            $response->assertUnauthorized();
        }
    }

    // ==================== Touchpoint CRUD Tests ====================

    /**
     * Test listing touchpoints for a tenant
     */
    public function test_can_list_touchpoints(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        // Create touchpoints for tenant1
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'campaign' => 'welcome_series',
                'touchpoint_type' => 'email_opened',
                'timestamp' => now()->subDays(5),
                'session_id' => 'session-1',
                'metadata' => json_encode(['email_id' => 'email-1']),
                'created_at' => now(),
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'social',
                'campaign' => 'facebook_ads',
                'touchpoint_type' => 'ad_click',
                'timestamp' => now()->subDays(3),
                'session_id' => 'session-2',
                'metadata' => json_encode(['ad_id' => 'fb-123']),
                'created_at' => now(),
            ],
        ]);

        // Create touchpoints for tenant2 (should not appear for tenant1)
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant2->id,
                'user_id' => $this->user2->id,
                'channel' => 'email',
                'campaign' => 'newsletter',
                'touchpoint_type' => 'email_sent',
                'timestamp' => now()->subDays(2),
                'session_id' => 'session-3',
                'metadata' => json_encode(['email_id' => 'email-2']),
                'created_at' => now(),
            ],
        ]);

        $response = $this->getJson('/api/analytics/attribution-analysis/touchpoints');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'touchpoints' => [
                        '*' => [
                            'id',
                            'user_id',
                            'channel',
                            'campaign',
                            'touchpoint_type',
                            'timestamp',
                            'session_id',
                        ],
                    ],
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                    ],
                ],
            ]);

        // Verify only tenant1's touchpoints are returned
        $data = $response->json('data.touchpoints');
        $this->assertCount(2, $data);
        $this->assertTrue(
            collect($data)->every(fn($t) => $t['channel'] === 'email' || $t['channel'] === 'social')
        );
    }

    /**
     * Test storing a new touchpoint
     */
    public function test_can_store_touchpoint(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        $touchpointData = [
            'user_id' => $this->user1->id,
            'channel' => 'email',
            'campaign' => 'spring_promo',
            'touchpoint_type' => 'email_clicked',
            'timestamp' => now()->toISOString(),
            'session_id' => 'new-session-123',
            'metadata' => [
                'email_id' => 'spring-2024-01',
                'link_id' => 'cta-button',
            ],
        ];

        $response = $this->postJson('/api/analytics/attribution-analysis/touchpoints', $touchpointData);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'channel',
                    'campaign',
                    'touchpoint_type',
                    'timestamp',
                ],
            ]);

        $this->assertDatabaseHas('attribution_touchpoints', [
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'channel' => 'email',
            'campaign' => 'spring_promo',
            'touchpoint_type' => 'email_clicked',
        ]);
    }

    /**
     * Test storing touchpoint with validation errors
     */
    public function test_store_touchpoint_validates_required_fields(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        // Missing required fields
        $response = $this->postJson('/api/analytics/attribution-analysis/touchpoints', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'channel', 'touchpoint_type']);
    }

    /**
     * Test storing touchpoint with invalid channel
     */
    public function test_store_touchpoint_validates_channel_enum(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        $touchpointData = [
            'user_id' => $this->user1->id,
            'channel' => 'invalid_channel',
            'touchpoint_type' => 'page_view',
        ];

        $response = $this->postJson('/api/analytics/attribution-analysis/touchpoints', $touchpointData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['channel']);
    }

    /**
     * Test getting touchpoint details
     */
    public function test_can_show_touchpoint(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        $touchpointId = DB::table('attribution_touchpoints')->insertGetId([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'channel' => 'direct',
            'campaign' => null,
            'touchpoint_type' => 'website_visit',
            'timestamp' => now(),
            'session_id' => 'session-show-test',
            'metadata' => json_encode(['source' => 'browser_bookmark']),
            'created_at' => now(),
        ]);

        $response = $this->getJson("/api/analytics/attribution-analysis/touchpoints/{$touchpointId}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'user_id',
                    'channel',
                    'touchpoint_type',
                    'timestamp',
                    'session_id',
                    'metadata',
                ],
            ])
            ->assertJsonPath('data.id', $touchpointId);
    }

    /**
     * Test getting non-existent touchpoint returns 404
     */
    public function test_show_touchpoint_returns_404_for_non_existent(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        $response = $this->getJson('/api/analytics/attribution-analysis/touchpoints/99999');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    // ==================== Attribution Calculation Tests ====================

    /**
     * Test calculating attribution for a user
     */
    public function test_can_calculate_attribution(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        // Create touchpoints for user1
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'campaign' => 'welcome',
                'touchpoint_type' => 'email_opened',
                'timestamp' => now()->subDays(10),
                'session_id' => 'session-calc-1',
                'created_at' => now(),
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'social',
                'campaign' => 'instagram',
                'touchpoint_type' => 'ad_view',
                'timestamp' => now()->subDays(8),
                'session_id' => 'session-calc-2',
                'created_at' => now(),
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'direct',
                'campaign' => null,
                'touchpoint_type' => 'signup',
                'timestamp' => now()->subDays(5),
                'session_id' => 'session-calc-3',
                'created_at' => now(),
            ],
        ]);

        $response = $this->getJson("/api/analytics/attribution-analysis/calculate/{$this->user1->id}?model=last_click");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'model',
                    'attributions' => [
                        '*' => [
                            'channel',
                            'touchpoints',
                            'credit',
                            'percentage',
                        ],
                    ],
                    'total_touchpoints',
                    'conversion_value',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals($this->user1->id, $data['user_id']);
        $this->assertEquals('last_click', $data['model']);
        $this->assertGreaterThan(0, count($data['attributions']));
    }

    /**
     * Test attribution calculation with different models
     */
    public function test_attribution_calculation_supports_multiple_models(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        // Create touchpoints
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'touchpoint_type' => 'email_opened',
                'timestamp' => now()->subDays(10),
                'session_id' => 'session-model-1',
                'created_at' => now(),
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'direct',
                'touchpoint_type' => 'signup',
                'timestamp' => now()->subDays(5),
                'session_id' => 'session-model-2',
                'created_at' => now(),
            ],
        ]);

        $models = ['first_click', 'last_click', 'linear', 'time_decay', 'position_based'];

        foreach ($models as $model) {
            $response = $this->getJson("/api/analytics/attribution-analysis/calculate/{$this->user1->id}?model={$model}");

            $response->assertOk()
                ->assertJsonPath('data.model', $model);
        }
    }

    /**
     * Test attribution calculation for user with no touchpoints
     */
    public function test_attribution_calculation_returns_empty_for_no_touchpoints(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        $response = $this->getJson("/api/analytics/attribution-analysis/calculate/{$this->user1->id}");

        $response->assertOk()
            ->assertJsonPath('data.total_touchpoints', 0)
            ->assertJsonPath('data.attributions', []);
    }

    // ==================== Model Comparison Tests ====================

    /**
     * Test comparing attribution models
     */
    public function test_can_compare_models(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        // Create touchpoints
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'touchpoint_type' => 'email_opened',
                'timestamp' => now()->subDays(10),
                'session_id' => 'session-compare-1',
                'created_at' => now(),
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'social',
                'touchpoint_type' => 'ad_click',
                'timestamp' => now()->subDays(5),
                'session_id' => 'session-compare-2',
                'created_at' => now(),
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'direct',
                'touchpoint_type' => 'signup',
                'timestamp' => now()->subDays(1),
                'session_id' => 'session-compare-3',
                'created_at' => now(),
            ],
        ]);

        $response = $this->getJson("/api/analytics/attribution-analysis/compare/{$this->user1->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'total_touchpoints',
                    'models' => [
                        'first_click' => [
                            'channel',
                            'credit',
                            'percentage',
                        ],
                        'last_click' => [],
                        'linear' => [],
                        'time_decay' => [],
                        'position_based' => [],
                    ],
                    'recommendations' => [],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(5, $data['models']);
    }

    // ==================== Channel Performance Tests ====================

    /**
     * Test getting channel performance
     */
    public function test_can_get_channel_performance(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        // Create touchpoints for tenant1
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'campaign' => 'campaign_a',
                'touchpoint_type' => 'email_sent',
                'timestamp' => now()->subDays(30),
                'session_id' => 'session-perf-1',
                'created_at' => now(),
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'campaign' => 'campaign_b',
                'touchpoint_type' => 'email_opened',
                'timestamp' => now()->subDays(20),
                'session_id' => 'session-perf-2',
                'created_at' => now(),
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'social',
                'campaign' => 'facebook_ads',
                'touchpoint_type' => 'ad_click',
                'timestamp' => now()->subDays(15),
                'session_id' => 'session-perf-3',
                'created_at' => now(),
            ],
        ]);

        $response = $this->getJson('/api/analytics/attribution-analysis/channels/performance');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period',
                    'channels' => [
                        '*' => [
                            'channel',
                            'total_touchpoints',
                            'unique_users',
                            'conversions',
                            'roi_percentage',
                            'cost_per_acquisition',
                        ],
                    ],
                    'totals' => [
                        'total_touchpoints',
                        'total_channels',
                        'total_conversions',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $channels = collect($data['channels']);
        $this->assertTrue($channels->contains('channel', 'email'));
        $this->assertTrue($channels->contains('channel', 'social'));
    }

    /**
     * Test channel performance with date filters
     */
    public function test_channel_performance_respects_date_filters(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        // Create touchpoints at different times
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'touchpoint_type' => 'email_sent',
                'timestamp' => now()->subDays(60),
                'session_id' => 'session-date-1',
                'created_at' => now()->subDays(60),
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'touchpoint_type' => 'email_opened',
                'timestamp' => now()->subDays(10),
                'session_id' => 'session-date-2',
                'created_at' => now()->subDays(10),
            ],
        ]);

        $response = $this->getJson('/api/analytics/attribution-analysis/channels/performance?start_date=' . now()->subDays(30)->toISOString() . '&end_date=' . now()->toISOString());

        $response->assertOk()
            ->assertJsonPath('data.channels.0.total_touchpoints', 1);
    }

    // ==================== Budget Recommendations Tests ====================

    /**
     * Test getting budget recommendations
     */
    public function test_can_get_budget_recommendations(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        // Create touchpoints
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'campaign' => 'newsletter',
                'touchpoint_type' => 'email_sent',
                'timestamp' => now()->subDays(30),
                'session_id' => 'session-budget-1',
                'created_at' => now(),
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'social',
                'campaign' => 'linkedin',
                'touchpoint_type' => 'ad_click',
                'timestamp' => now()->subDays(20),
                'session_id' => 'session-budget-2',
                'created_at' => now(),
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'direct',
                'touchpoint_type' => 'signup',
                'timestamp' => now()->subDays(10),
                'session_id' => 'session-budget-3',
                'created_at' => now(),
            ],
        ]);

        $response = $this->getJson('/api/analytics/attribution-analysis/budget/recommendations?total_budget=10000');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_budget',
                    'recommendations' => [
                        '*' => [
                            'channel',
                            'current_allocation',
                            'recommended_allocation',
                            'expected_roi',
                            'efficiency_score',
                            'rationale',
                        ],
                    ],
                    'summary' => [
                        'total_recommended',
                        'expected_total_conversions',
                        'confidence_level',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(10000, $data['total_budget']);
        $this->assertGreaterThan(0, count($data['recommendations']));
    }

    /**
     * Test budget recommendations with custom date range
     */
    public function test_budget_recommendations_with_custom_date_range(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        $response = $this->getJson('/api/analytics/attribution-analysis/budget/recommendations?total_budget=5000&start_date=' . now()->subDays(60)->toISOString() . '&end_date=' . now()->toISOString());

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    // ==================== Conversion Path Tests ====================

    /**
     * Test getting conversion path for a user
     */
    public function test_can_get_conversion_path(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        // Create a complete conversion path
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'paid_search',
                'campaign' => 'brand_terms',
                'touchpoint_type' => 'ad_click',
                'timestamp' => now()->subDays(14),
                'session_id' => 'session-path-1',
                'created_at' => now()->subDays(14),
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'campaign' => 'welcome_series',
                'touchpoint_type' => 'email_opened',
                'timestamp' => now()->subDays(12),
                'session_id' => 'session-path-1',
                'created_at' => now()->subDays(12),
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'retargeting',
                'campaign' => 'abandoned_cart',
                'touchpoint_type' => 'ad_click',
                'timestamp' => now()->subDays(7),
                'session_id' => 'session-path-2',
                'created_at' => now()->subDays(7),
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'direct',
                'campaign' => null,
                'touchpoint_type' => 'purchase',
                'timestamp' => now()->subDays(3),
                'session_id' => 'session-path-3',
                'created_at' => now()->subDays(3),
            ],
        ]);

        $response = $this->getJson("/api/analytics/attribution-analysis/conversion-path/{$this->user1->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'total_touchpoints',
                    'conversion_status',
                    'path' => [
                        '*' => [
                            'id',
                            'channel',
                            'campaign',
                            'touchpoint_type',
                            'timestamp',
                            'time_to_conversion',
                        ],
                    ],
                    'metrics' => [
                        'avg_touchpoints_per_conversion',
                        'avg_time_to_conversion',
                        'top_channels',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals($this->user1->id, $data['user_id']);
        $this->assertEquals(4, $data['total_touchpoints']);
        $this->assertEquals('converted', $data['conversion_status']);
        $this->assertCount(4, $data['path']);
    }

    /**
     * Test conversion path for user who hasn't converted
     */
    public function test_conversion_path_for_non_converting_user(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        // Create touchpoints without conversion
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'social',
                'touchpoint_type' => 'ad_view',
                'timestamp' => now()->subDays(5),
                'session_id' => 'session-noconv-1',
                'created_at' => now(),
            ],
        ]);

        $response = $this->getJson("/api/analytics/attribution-analysis/conversion-path/{$this->user1->id}");

        $response->assertOk()
            ->assertJsonPath('data.conversion_status', 'pending')
            ->assertJsonPath('data.total_touchpoints', 1);
    }

    // ==================== Tenant Isolation Tests ====================

    /**
     * Test that touchpoints are isolated between tenants
     */
    public function test_touchpoints_are_isolated_between_tenants(): void
    {
        $tenantContextService = app(TenantContextService::class);

        // Create touchpoints for tenant1
        $tenantContextService->setTenant($this->tenant1->id);
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'touchpoint_type' => 'email_sent',
                'timestamp' => now(),
                'session_id' => 'tenant-iso-1',
                'created_at' => now(),
            ],
        ]);

        // Create touchpoints for tenant2
        $tenantContextService->setTenant($this->tenant2->id);
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant2->id,
                'user_id' => $this->user2->id,
                'channel' => 'social',
                'touchpoint_type' => 'ad_click',
                'timestamp' => now(),
                'session_id' => 'tenant-iso-2',
                'created_at' => now(),
            ],
        ]);

        // Tenant1 should only see its own touchpoints
        $this->actingAs($this->user1);
        $tenantContextService->setTenant($this->tenant1->id);

        $response = $this->getJson('/api/analytics/attribution-analysis/touchpoints');
        $data = $response->json('data.touchpoints');
        $this->assertCount(1, $data);
        $this->assertEquals('email', $data[0]['channel']);

        // Tenant2 should only see its own touchpoints
        $this->actingAs($this->user2);
        $tenantContextService->setTenant($this->tenant2->id);

        $response = $this->getJson('/api/analytics/attribution-analysis/touchpoints');
        $data = $response->json('data.touchpoints');
        $this->assertCount(1, $data);
        $this->assertEquals('social', $data[0]['channel']);
    }

    /**
     * Test that channel performance is isolated between tenants
     */
    public function test_channel_performance_is_isolated_between_tenants(): void
    {
        $tenantContextService = app(TenantContextService::class);

        // Create touchpoints for tenant1
        $tenantContextService->setTenant($this->tenant1->id);
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'touchpoint_type' => 'email_sent',
                'timestamp' => now()->subDays(10),
                'session_id' => 'channel-iso-1',
                'created_at' => now(),
            ],
        ]);

        // Create touchpoints for tenant2
        $tenantContextService->setTenant($this->tenant2->id);
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant2->id,
                'user_id' => $this->user2->id,
                'channel' => 'social',
                'touchpoint_type' => 'ad_click',
                'timestamp' => now()->subDays(10),
                'session_id' => 'channel-iso-2',
                'created_at' => now(),
            ],
        ]);

        // Tenant1 should only see its own channel data
        $this->actingAs($this->user1);
        $tenantContextService->setTenant($this->tenant1->id);

        $response = $this->getJson('/api/analytics/attribution-analysis/channels/performance');
        $data = $response->json('data.channels');
        $this->assertCount(1, $data);
        $this->assertEquals('email', $data[0]['channel']);

        // Tenant2 should only see its own channel data
        $this->actingAs($this->user2);
        $tenantContextService->setTenant($this->tenant2->id);

        $response = $this->getJson('/api/analytics/attribution-analysis/channels/performance');
        $data = $response->json('data.channels');
        $this->assertCount(1, $data);
        $this->assertEquals('social', $data[0]['channel']);
    }

    /**
     * Test that budget recommendations are isolated between tenants
     */
    public function test_budget_recommendations_are_isolated_between_tenants(): void
    {
        $tenantContextService = app(TenantContextService::class);

        // Create touchpoints for tenant1
        $tenantContextService->setTenant($this->tenant1->id);
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'touchpoint_type' => 'email_sent',
                'timestamp' => now()->subDays(30),
                'session_id' => 'budget-iso-1',
                'created_at' => now(),
            ],
        ]);

        // Create touchpoints for tenant2
        $tenantContextService->setTenant($this->tenant2->id);
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant2->id,
                'user_id' => $this->user2->id,
                'channel' => 'paid_search',
                'touchpoint_type' => 'ad_click',
                'timestamp' => now()->subDays(30),
                'session_id' => 'budget-iso-2',
                'created_at' => now(),
            ],
        ]);

        // Tenant1 should only see recommendations based on its data
        $this->actingAs($this->user1);
        $tenantContextService->setTenant($this->tenant1->id);

        $response = $this->getJson('/api/analytics/attribution-analysis/budget/recommendations?total_budget=1000');
        $data = $response->json('data');
        $recommendationChannels = collect($data['recommendations'])->pluck('channel')->toArray();
        $this->assertContains('email', $recommendationChannels);
        $this->assertNotContains('paid_search', $recommendationChannels);

        // Tenant2 should only see recommendations based on its data
        $this->actingAs($this->user2);
        $tenantContextService->setTenant($this->tenant2->id);

        $response = $this->getJson('/api/analytics/attribution-analysis/budget/recommendations?total_budget=1000');
        $data = $response->json('data');
        $recommendationChannels = collect($data['recommendations'])->pluck('channel')->toArray();
        $this->assertContains('paid_search', $recommendationChannels);
        $this->assertNotContains('email', $recommendationChannels);
    }

    // ==================== Error Handling Tests ====================

    /**
     * Test handling of invalid model parameter
     */
    public function test_invalid_model_parameter_returns_error(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        $response = $this->getJson("/api/analytics/attribution-analysis/calculate/{$this->user1->id}?model=invalid_model");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['model']);
    }

    /**
     * Test handling of invalid budget parameter
     */
    public function test_invalid_budget_parameter_returns_error(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        $response = $this->getJson('/api/analytics/attribution-analysis/budget/recommendations?total_budget=-100');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['total_budget']);
    }

    /**
     * Test handling of invalid date format
     */
    public function test_invalid_date_format_returns_error(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        $response = $this->getJson('/api/analytics/attribution-analysis/channels/performance?start_date=invalid-date');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    /**
     * Test handling of user from different tenant
     */
    public function test_cannot_access_other_tenant_user_data(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        // Create touchpoint for user1
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'touchpoint_type' => 'email_sent',
                'timestamp' => now(),
                'session_id' => 'cross-tenant-1',
                'created_at' => now(),
            ],
        ]);

        // Set context to tenant2
        $tenantContextService->setTenant($this->tenant2->id);

        // Attempt to calculate attribution for user1 (from tenant1) while in tenant2 context
        // This should return empty or error, not tenant1's data
        $response = $this->getJson("/api/analytics/attribution-analysis/calculate/{$this->user1->id}");

        // Should return no data since user1 doesn't belong to tenant2
        $data = $response->json('data');
        $this->assertEquals(0, $data['total_touchpoints'] ?? 0);
    }

    // ==================== Pagination and Filtering Tests ====================

    /**
     * Test touchpoint list pagination
     */
    public function test_touchpoint_list_is_paginated(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        // Create 15 touchpoints
        for ($i = 0; $i < 15; $i++) {
            DB::table('attribution_touchpoints')->insert([
                [
                    'tenant_id' => $this->tenant1->id,
                    'user_id' => $this->user1->id,
                    'channel' => ['email', 'social', 'direct'][$i % 3],
                    'touchpoint_type' => 'page_view',
                    'timestamp' => now()->subHours($i),
                    'session_id' => "session-paginate-{$i}",
                    'created_at' => now(),
                ],
            ]);
        }

        // Test default pagination (should be 10 per page)
        $response = $this->getJson('/api/analytics/attribution-analysis/touchpoints');
        $data = $response->json('data');
        $this->assertCount(10, $data['touchpoints']);
        $this->assertEquals(15, $data['pagination']['total']);

        // Test custom per_page
        $response = $this->getJson('/api/analytics/attribution-analysis/touchpoints?per_page=5');
        $data = $response->json('data');
        $this->assertCount(5, $data['touchpoints']);
        $this->assertEquals(15, $data['pagination']['total']);
    }

    /**
     * Test filtering touchpoints by channel
     */
    public function test_touchpoint_list_can_be_filtered_by_channel(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        // Create touchpoints with different channels
        for ($i = 0; $i < 5; $i++) {
            DB::table('attribution_touchpoints')->insert([
                [
                    'tenant_id' => $this->tenant1->id,
                    'user_id' => $this->user1->id,
                    'channel' => $i < 3 ? 'email' : 'social',
                    'touchpoint_type' => 'page_view',
                    'timestamp' => now()->subHours($i),
                    'session_id' => "session-filter-{$i}",
                    'created_at' => now(),
                ],
            ]);
        }

        // Filter by email
        $response = $this->getJson('/api/analytics/attribution-analysis/touchpoints?channel=email');
        $data = $response->json('data.touchpoints');
        $this->assertCount(3, $data);
        $this->assertTrue(collect($data)->every(fn($t) => $t['channel'] === 'email'));

        // Filter by social
        $response = $this->getJson('/api/analytics/attribution-analysis/touchpoints?channel=social');
        $data = $response->json('data.touchpoints');
        $this->assertCount(2, $data);
        $this->assertTrue(collect($data)->every(fn($t) => $t['channel'] === 'social'));
    }

    /**
     * Test filtering touchpoints by date range
     */
    public function test_touchpoint_list_can_be_filtered_by_date_range(): void
    {
        $this->actingAs($this->user1);
        
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        // Create touchpoints at different times
        DB::table('attribution_touchpoints')->insert([
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'touchpoint_type' => 'page_view',
                'timestamp' => now()->subDays(30),
                'session_id' => 'session-date-filter-1',
                'created_at' => now()->subDays(30),
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'touchpoint_type' => 'page_view',
                'timestamp' => now()->subDays(5),
                'session_id' => 'session-date-filter-2',
                'created_at' => now()->subDays(5),
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->user1->id,
                'channel' => 'email',
                'touchpoint_type' => 'page_view',
                'timestamp' => now(),
                'session_id' => 'session-date-filter-3',
                'created_at' => now(),
            ],
        ]);

        // Filter by last 7 days
        $response = $this->getJson('/api/analytics/attribution-analysis/touchpoints?start_date=' . now()->subDays(7)->toISOString() . '&end_date=' . now()->toISOString());
        $data = $response->json('data.touchpoints');
        $this->assertCount(2, $data);
    }
}
