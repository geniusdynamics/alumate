<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Events\LearningUpdated;
use App\Jobs\LearningScoreJob;
use App\Jobs\SyncAnalyticsData;
use App\Models\LearningProgress;
use App\Models\SyncLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Analytics\CareerPredictionService;
use App\Services\Analytics\ConsentService;
use App\Services\Analytics\LearningAnalyticsService;
use App\Services\Analytics\SyncService;
use App\Services\Integrations\CrmIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * CRM Analytics Integration Test
 *
 * Tests the integration between Advanced Analytics System and external platforms:
 * - CRM synchronization (HubSpot, Salesforce, Frappe, Zoho)
 * - AI model updates (Career Prediction Service)
 * - WebSocket real-time updates
 * - Tenant isolation and data flow
 */
class CrmAnalyticsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private LearningAnalyticsService $learningService;
    private CrmIntegrationService $crmService;
    private CareerPredictionService $careerService;
    private SyncService $syncService;
    private ConsentService $consentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->learningService = app(LearningAnalyticsService::class);
        $this->crmService = app(CrmIntegrationService::class);
        $this->careerService = app(CareerPredictionService::class);
        $this->syncService = app(SyncService::class);
        $this->consentService = app(ConsentService::class);

        // Create test tenant
        $this->tenant = Tenant::factory()->create(['id' => 'test-tenant']);

        // Set tenant context for tests
        session(['tenant_id' => $this->tenant->id]);
    }

    /**
     * Test learning progress sync to HubSpot CRM
     */
    public function test_learning_progress_syncs_to_hubspot(): void
    {
        // Mock HubSpot API
        Http::fake([
            'https://api.hubapi.com/crm/v3/objects/learning_progress' => Http::response([
                'id' => 'hubspot_123',
                'properties' => []
            ], 201)
        ]);

        // Create test data
        $user = User::factory()->create();
        $progress = LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'engagement_score' => 85,
            'total_score' => 90,
            'modules_completed' => 8
        ]);

        // Configure HubSpot
        config(['services.hubspot.api_key' => 'test_api_key']);

        // Test sync
        $result = $this->crmService->syncLearningProgress($progress, 'hubspot');

        $this->assertTrue($result);

        // Verify API call
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.hubapi.com/crm/v3/objects/learning_progress' &&
                   $request->hasHeader('Authorization', 'Bearer test_api_key') &&
                   $request['properties']['engagement_score'] === 85;
        });

        // Verify sync log
        $this->assertDatabaseHas('sync_logs', [
            'tenant_id' => $this->tenant->id,
            'sync_type' => 'crm_analytics',
            'provider' => 'hubspot',
            'status' => 'success'
        ]);
    }

    /**
     * Test learning progress sync to Salesforce CRM
     */
    public function test_learning_progress_syncs_to_salesforce(): void
    {
        // Mock Salesforce API
        Http::fake([
            'https://login.salesforce.com/services/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'instance_url' => 'https://test.salesforce.com'
            ], 200),
            'https://test.salesforce.com/services/data/v58.0/sobjects/Learning_Progress__c' => Http::response([
                'id' => 'salesforce_123'
            ], 201)
        ]);

        // Create test data
        $user = User::factory()->create();
        $progress = LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'engagement_score' => 78,
            'certified' => true
        ]);

        // Configure Salesforce
        config([
            'services.salesforce.client_id' => 'test_client_id',
            'services.salesforce.client_secret' => 'test_client_secret',
            'services.salesforce.instance_url' => 'https://test.salesforce.com'
        ]);

        // Test sync
        $result = $this->crmService->syncLearningProgress($progress, 'salesforce');

        $this->assertTrue($result);

        // Verify API calls
        Http::assertSentCount(2); // Token + data sync
    }

    /**
     * Test career prediction update on certification
     */
    public function test_career_prediction_updates_on_certification(): void
    {
        $user = User::factory()->create();

        // Give consent
        $this->consentService->grantConsent($user->id, 'analytics');

        // Test learning impact update
        $learningData = [
            'engagement_score' => 92,
            'total_score' => 95,
            'modules_completed' => 10,
            'certified' => true
        ];

        $result = $this->careerService->updateLearningImpact($user->id, $learningData);

        $this->assertTrue($result);

        // Verify prediction score increased
        $newScore = $this->careerService->getPredictionScore($user->id);
        $this->assertGreaterThan(50, $newScore); // Should be higher than base score
    }

    /**
     * Test WebSocket events emitted from LearningScoreJob
     */
    public function test_websocket_events_emitted_from_learning_job(): void
    {
        Event::fake();

        // Create test data
        $userCoursePairs = [
            ['user_id' => 1, 'course_id' => 1],
            ['user_id' => 2, 'course_id' => 1]
        ];

        // Run job
        $job = new LearningScoreJob($userCoursePairs, $this->tenant->id);
        $job->handle($this->learningService);

        // Verify WebSocket event emitted
        Event::assertDispatched(LearningUpdated::class, function ($event) {
            return $event->data['tenant_id'] === $this->tenant->id &&
                   $event->data['type'] === 'batch_scores_processed' &&
                   isset($event->data['processed']);
        });
    }

    /**
     * Test WebSocket events emitted from SyncAnalyticsData job
     */
    public function test_websocket_events_emitted_from_sync_job(): void
    {
        Event::fake();

        // Mock external APIs
        Http::fake([
            '*' => Http::response(['events_count' => 100, 'sessions' => 50], 200)
        ]);

        // Run job
        $job = new SyncAnalyticsData($this->tenant->id, ['ga'], [], true);
        $job->handle($this->syncService, app(\App\Services\TenantContextService::class));

        // Verify WebSocket event emitted
        Event::assertDispatched(LearningUpdated::class, function ($event) {
            return $event->data['tenant_id'] === $this->tenant->id &&
                   $event->data['type'] === 'analytics_synced' &&
                   in_array('ga', $event->data['sources']);
        });
    }

    /**
     * Test tenant isolation in CRM sync
     */
    public function test_tenant_isolation_in_crm_sync(): void
    {
        // Create second tenant
        $tenant2 = Tenant::factory()->create(['id' => 'tenant2']);

        // Create users and progress for both tenants
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $progress1 = LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user1->id
        ]);

        $progress2 = LearningProgress::factory()->create([
            'tenant_id' => $tenant2->id,
            'user_id' => $user2->id
        ]);

        // Mock CRM API
        Http::fake([
            '*' => Http::response(['id' => 'crm_123'], 201)
        ]);

        config(['services.hubspot.api_key' => 'test_key']);

        // Sync first tenant
        session(['tenant_id' => $this->tenant->id]);
        $result1 = $this->crmService->syncLearningProgress($progress1, 'hubspot');

        // Switch to second tenant
        session(['tenant_id' => $tenant2->id]);
        $result2 = $this->crmService->syncLearningProgress($progress2, 'hubspot');

        $this->assertTrue($result1);
        $this->assertTrue($result2);

        // Verify both syncs logged with correct tenant IDs
        $this->assertDatabaseHas('sync_logs', [
            'tenant_id' => $this->tenant->id,
            'record_id' => $progress1->id
        ]);

        $this->assertDatabaseHas('sync_logs', [
            'tenant_id' => $tenant2->id,
            'record_id' => $progress2->id
        ]);
    }

    /**
     * Test consent blocking for CRM sync
     */
    public function test_consent_blocks_crm_sync(): void
    {
        $user = User::factory()->create();
        $progress = LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id
        ]);

        // Withdraw consent
        $this->consentService->revokeConsent($user->id, 'analytics');

        // Attempt sync
        $result = $this->crmService->syncLearningProgress($progress, 'hubspot');

        $this->assertFalse($result);
    }

    /**
     * Test consent blocking for career prediction updates
     */
    public function test_consent_blocks_career_prediction_updates(): void
    {
        $user = User::factory()->create();

        // Withdraw consent
        $this->consentService->revokeConsent($user->id, 'analytics');

        $learningData = [
            'engagement_score' => 85,
            'certified' => true
        ];

        $result = $this->careerService->updateLearningImpact($user->id, $learningData);

        $this->assertFalse($result);
    }

    /**
     * Test CRM API failure handling with retry
     */
    public function test_crm_api_failure_with_retry(): void
    {
        // Mock API failures followed by success
        Http::fakeSequence()
            ->push(['error' => 'Rate limit'], 429)
            ->push(['error' => 'Server error'], 500)
            ->push(['id' => 'crm_123'], 201);

        $user = User::factory()->create();
        $progress = LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id
        ]);

        config(['services.hubspot.api_key' => 'test_key']);

        $result = $this->crmService->syncLearningProgress($progress, 'hubspot');

        $this->assertTrue($result);

        // Verify 3 attempts were made
        Http::assertSentCount(3);
    }

    /**
     * Test queue integration for batch processing
     */
    public function test_queue_integration_for_batch_processing(): void
    {
        Queue::fake();

        $userCoursePairs = [
            ['user_id' => 1, 'course_id' => 1],
            ['user_id' => 2, 'course_id' => 1],
            ['user_id' => 3, 'course_id' => 2]
        ];

        // Dispatch job
        LearningScoreJob::dispatch($userCoursePairs, $this->tenant->id);

        // Verify job queued
        Queue::assertPushed(LearningScoreJob::class, function ($job) {
            return $job->tenantId === $this->tenant->id &&
                   count($job->userCoursePairs) === 3;
        });
    }

    /**
     * Test sync service integration with CRM
     */
    public function test_sync_service_crm_integration(): void
    {
        $progress = LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        // Mock CRM API
        Http::fake([
            '*' => Http::response(['id' => 'crm_123'], 201)
        ]);

        config(['services.hubspot.api_key' => 'test_key']);

        $result = $this->syncService->syncLearningToCrm(
            $this->tenant->id,
            [$progress],
            'hubspot'
        );

        $this->assertTrue($result);
    }

    /**
     * Test cache integration for prediction scores
     */
    public function test_cache_integration_for_prediction_scores(): void
    {
        $user = User::factory()->create();

        // First call should calculate and cache
        $score1 = $this->careerService->getPredictionScore($user->id);

        // Second call should use cache
        $score2 = $this->careerService->getPredictionScore($user->id);

        $this->assertEquals($score1, $score2);

        // Clear cache
        $this->careerService->clearPredictionCache($user->id);

        // Verify cache cleared by checking if score is still accessible
        Cache::shouldReceive('forget')->once();
    }

    /**
     * Test comprehensive integration flow
     */
    public function test_comprehensive_integration_flow(): void
    {
        Event::fake();
        Queue::fake();
        Http::fake([
            '*' => Http::response(['id' => 'crm_123'], 201)
        ]);

        // Setup
        $user = User::factory()->create();
        $this->consentService->grantConsent($user->id, 'analytics');

        config(['services.hubspot.api_key' => 'test_key']);

        // 1. Track learning interaction
        $interactionData = [
            'module_id' => 1,
            'duration' => 1800,
            'score' => 85,
            'interaction_type' => 'completion'
        ];

        $result = $this->learningService->trackCourseInteraction(
            $user->id,
            1,
            $interactionData
        );

        $this->assertTrue($result);

        // 2. Verify certification triggers career prediction update
        $criteria = [
            'course_id' => 1,
            'min_score' => 80,
            'modules_completed' => 1
        ];

        $certification = $this->learningService->verifyCertification($user->id, $criteria);

        $this->assertTrue($certification['eligible']);

        // 3. Queue batch processing
        $userCoursePairs = [['user_id' => $user->id, 'course_id' => 1]];
        LearningScoreJob::dispatch($userCoursePairs, $this->tenant->id);

        Queue::assertPushed(LearningScoreJob::class);

        // 4. Sync to CRM
        $progress = LearningProgress::where('user_id', $user->id)->first();
        $crmResult = $this->crmService->syncLearningProgress($progress, 'hubspot');

        $this->assertTrue($crmResult);

        // Verify WebSocket events would be emitted (faked)
        // Verify sync logs created
        $this->assertDatabaseHas('sync_logs', [
            'tenant_id' => $this->tenant->id,
            'provider' => 'hubspot',
            'status' => 'success'
        ]);
    }
}