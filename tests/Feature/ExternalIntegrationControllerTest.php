<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Analytics\ExternalIntegrationController;
use App\Services\Analytics\GoogleAnalyticsService;
use App\Services\Analytics\MatomoService;
use App\Services\Analytics\AnalyticsDataSyncService;
use App\Services\Analytics\ConsentService;
use App\Models\User;
use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * External Integration Controller Test
 *
 * Tests for external platform integrations (Google Analytics & Matomo)
 */
class ExternalIntegrationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected ExternalIntegrationController $controller;
    protected GoogleAnalyticsService $googleAnalyticsService;
    protected MatomoService $matomoService;
    protected AnalyticsDataSyncService $dataSyncService;
    protected ConsentService $consentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->googleAnalyticsService = app(GoogleAnalyticsService::class);
        $this->matomoService = app(MatomoService::class);
        $this->dataSyncService = app(AnalyticsDataSyncService::class);
        $this->consentService = app(ConsentService::class);
        $this->controller = new ExternalIntegrationController(
            $this->googleAnalyticsService,
            $this->matomoService,
            $this->dataSyncService
        );
    }

    /**
     * Test getting unified analytics data
     */
    public function test_get_unified_data(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $response = $this->controller->getUnifiedData(request());

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('internal', $data['data']);
        $this->assertArrayHasKey('google_analytics', $data['data']);
        $this->assertArrayHasKey('matomo', $data['data']);
        $this->assertArrayHasKey('discrepancies', $data['data']);
        $this->assertArrayHasKey('summary', $data['data']);
    }

    /**
     * Test syncing events to external platforms
     */
    public function test_sync_events(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $events = [
            [
                'name' => 'test_event',
                'category' => 'test_category',
                'action' => 'test_action',
                'value' => 1,
            ],
        ];

        $request = request()->merge([
            'events' => $events,
            'sync_to_google' => true,
            'sync_to_matomo' => true,
        ]);

        $response = $this->controller->syncEvents($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('google_analytics', $data['data']);
        $this->assertArrayHasKey('matomo', $data['data']);
    }

    /**
     * Test getting sync status
     */
    public function test_get_sync_status(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $response = $this->controller->getSyncStatus();

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    /**
     * Test getting discrepancies
     */
    public function test_get_discrepancies(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $request = request()->merge([
            'start_date' => '30daysAgo',
            'end_date' => 'today',
            'metrics' => ['sessions', 'users', 'pageviews'],
        ]);

        $response = $this->controller->getDiscrepancies($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('discrepancies', $data['data']);
        $this->assertArrayHasKey('summary', $data['data']);
    }

    /**
     * Test resolving discrepancies
     */
    public function test_resolve_discrepancies(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $discrepancies = [
            [
                'metric' => 'sessions',
                'source' => 'google_analytics',
                'value' => 100,
                'average' => 110,
            ],
        ];

        $request = request()->merge([
            'discrepancies' => $discrepancies,
            'strategy' => 'average',
        ]);

        $response = $this->controller->resolveDiscrepancies($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('resolved', $data['data']);
        $this->assertArrayHasKey('failed', $data['data']);
    }

    /**
     * Test validating configuration
     */
    public function test_validate_configuration(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $response = $this->controller->validateConfiguration();

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('valid', $data['data']);
        $this->assertArrayHasKey('errors', $data['data']);
        $this->assertArrayHasKey('warnings', $data['data']);
    }

    /**
     * Test creating Google Analytics goal
     */
    public function test_create_google_analytics_goal(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $request = request()->merge([
            'name' => 'Test Goal',
            'event_name' => 'test_goal_complete',
            'value' => 100,
        ]);

        $response = $this->controller->createGoogleAnalyticsGoal($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    /**
     * Test creating Google Analytics audience
     */
    public function test_create_google_analytics_audience(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $request = request()->merge([
            'name' => 'Test Audience',
            'description' => 'Test audience description',
            'criteria' => [
                'user_segment' => 'active_users',
                'min_engagement' => 5,
            ],
        ]);

        $response = $this->controller->createGoogleAnalyticsAudience($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    /**
     * Test creating Matomo goal
     */
    public function test_create_matomo_goal(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $request = request()->merge([
            'name' => 'Test Goal',
            'event_category' => 'test_category',
            'event_action' => 'test_action',
            'value' => 100,
        ]);

        $response = $this->controller->createMatomoGoal($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    /**
     * Test creating Matomo segment
     */
    public function test_create_matomo_segment(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $request = request()->merge([
            'name' => 'Test Segment',
            'criteria' => [
                'user_segment' => 'active_users',
                'min_events' => 10,
            ],
            'enabled_all_users' => false,
        ]);

        $response = $this->controller->createMatomoSegment($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    /**
     * Test getting Google Analytics report
     */
    public function test_get_google_analytics_report(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $request = request()->merge([
            'date_ranges' => [
                ['startDate' => '30daysAgo', 'endDate' => 'today'],
            ],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'activeUsers'],
            ],
            'dimensions' => [
                ['name' => 'date'],
            ],
        ]);

        $response = $this->controller->getGoogleAnalyticsReport($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    /**
     * Test getting Google Analytics real-time data
     */
    public function test_get_google_analytics_realtime_data(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $response = $this->controller->getGoogleAnalyticsRealtimeData();

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    /**
     * Test getting Matomo report
     */
    public function test_get_matomo_report(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $request = request()->merge([
            'method' => 'API.get',
            'params' => [
                'period' => 'day',
                'date' => 'today',
            ],
        ]);

        $response = $this->controller->getMatomoReport($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    /**
     * Test getting Matomo real-time data
     */
    public function test_get_matomo_realtime_data(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $response = $this->controller->getMatomoRealtimeData();

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    /**
     * Test syncing Google Analytics goals
     */
    public function test_sync_google_analytics_goals(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $request = request()->merge([
            'funnels' => [
                [
                    'name' => 'Test Funnel',
                    'event_name' => 'funnel_complete',
                ],
            ],
        ]);

        $response = $this->controller->syncGoogleAnalyticsGoals($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    /**
     * Test exporting Google Analytics segments
     */
    public function test_export_google_analytics_segments(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $request = request()->merge([
            'segments' => [
                [
                    'name' => 'Test Segment',
                    'criteria' => [
                        'user_segment' => 'active_users',
                    ],
                ],
            ],
        ]);

        $response = $this->controller->exportGoogleAnalyticsSegments($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    /**
     * Test syncing Matomo data
     */
    public function test_sync_matomo_data(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $request = request()->merge([
            'sync_data' => [
                'type' => 'full_sync',
            ],
        ]);

        $response = $this->controller->syncMatomoData($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    /**
     * Test unauthenticated user cannot access endpoints
     */
    public function test_unauthenticated_user_cannot_access_endpoints(): void
    {
        Auth::logout();

        $response = $this->controller->getUnifiedData(request());

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test non-admin user cannot create goals
     */
    public function test_non_admin_user_cannot_create_goals(): void
    {
        $tenant = Tenant::factory()->create();
        $student = User::factory()->create(['is_super_admin' => false]);
        TenantUser::factory()->create([
            'user_id' => $student->id,
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_STUDENT,
            'is_active' => true,
        ]);

        Auth::login($student);

        $request = request()->merge([
            'name' => 'Test Goal',
            'event_name' => 'test_goal_complete',
        ]);

        $response = $this->controller->createGoogleAnalyticsGoal($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test validation fails with invalid event data
     */
    public function test_validation_fails_with_invalid_event_data(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $request = request()->merge([
            'events' => 'invalid', // Should be an array
        ]);

        $response = $this->controller->syncEvents($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test validation fails with invalid goal data
     */
    public function test_validation_fails_with_invalid_goal_data(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $request = request()->merge([
            'name' => '', // Should not be empty
        ]);

        $response = $this->controller->createGoogleAnalyticsGoal($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test validation fails with invalid discrepancy resolution strategy
     */
    public function test_validation_fails_with_invalid_resolution_strategy(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Auth::login($user);

        $request = request()->merge([
            'discrepancies' => [],
            'strategy' => 'invalid_strategy', // Should be one of: average, max, min, internal
        ]);

        $response = $this->controller->resolveDiscrepancies($request);

        $this->assertEquals(422, $response->getStatusCode());
    }
}
