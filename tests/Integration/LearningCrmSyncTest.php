<?php

namespace Tests\Integration;

use App\Models\AnalyticsEvent;
use App\Models\Course;
use App\Models\CrmSyncLog;
use App\Models\LearningProgress;
use App\Models\SyncLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Analytics\LearningAnalyticsService;
use App\Services\Analytics\SyncService;
use App\Services\CrmIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Learning to CRM Sync Integration Test
 *
 * Tests end-to-end workflow from learning analytics tracking through CRM synchronization,
 * including consent validation, tenant isolation, and error handling.
 */
class LearningCrmSyncTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Course $course;
    protected LearningAnalyticsService $learningService;
    protected SyncService $syncService;
    protected CrmIntegrationService $crmService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenant
        $this->tenant = Tenant::factory()->create([
            'id' => 'learning-crm-tenant',
            'name' => 'Learning CRM Test Tenant',
        ]);

        // Create test user with consent
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create test course
        $this->course = Course::factory()->create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Test Analytics Course',
        ]);

        // Initialize services
        $this->learningService = app(LearningAnalyticsService::class);
        $this->syncService = app(SyncService::class);
        $this->crmService = app(CrmIntegrationService::class);

        // Set tenant context
        session(['tenant_id' => $this->tenant->id]);
    }

    /** @test */
    public function it_completes_end_to_end_learning_to_crm_sync_workflow()
    {
        // Mock CRM API responses
        Http::fake([
            'api.hubapi.com/*' => Http::response(['id' => 'hubspot-lead-123'], 201),
            'login.salesforce.com/*' => Http::response(['access_token' => 'sf-token-123', 'expires_in' => 3600]),
            '*.salesforce.com/*' => Http::response(['id' => 'sf-lead-456'], 201),
        ]);

        // 1. Track multiple learning interactions
        $interactions = [
            ['module_id' => 1, 'duration' => 1800, 'score' => 85, 'interaction_type' => 'completion'],
            ['module_id' => 2, 'duration' => 2400, 'score' => 92, 'interaction_type' => 'quiz'],
            ['module_id' => 3, 'duration' => 1200, 'score' => null, 'interaction_type' => 'view'],
        ];

        foreach ($interactions as $interaction) {
            $result = $this->learningService->trackCourseInteraction(
                $this->user->id,
                $this->course->id,
                $interaction
            );
            $this->assertTrue($result);
        }

        // Verify analytics events were created
        $events = AnalyticsEvent::byTenant($this->tenant->id)
            ->byEventType('learning')
            ->byUser($this->user->id)
            ->get();
        $this->assertCount(3, $events);

        // Verify learning progress was updated
        $progress = LearningProgress::byTenant($this->tenant->id)
            ->byUser($this->user->id)
            ->byCourse($this->course->id)
            ->first();
        $this->assertNotNull($progress);
        $this->assertEquals(1, $progress->modules_completed);
        $this->assertGreaterThan(0, $progress->engagement_score);

        // 2. Trigger sync process (normally via job queue)
        $syncResult = $this->syncService->syncData($this->tenant->id, ['ga', 'matomo']);

        // Verify unified data contains learning metrics
        $this->assertArrayHasKey('events_count', $syncResult);
        $this->assertArrayHasKey('sources', $syncResult);
        $this->assertContains('internal', $syncResult['sources']);

        // Verify sync was logged
        $syncLog = SyncLog::where('tenant_id', $this->tenant->id)
            ->where('sync_type', 'unified')
            ->latest()
            ->first();
        $this->assertNotNull($syncLog);
        $this->assertEquals('success', $syncLog->status);

        // 3. Verify learning insights generation (part of sync workflow)
        $insights = $this->learningService->generateLearningInsights([
            'course_id' => $this->course->id
        ]);

        // Verify insights contain expected data
        $this->assertArrayHasKey('total_interactions', $insights);
        $this->assertArrayHasKey('unique_users', $insights);
        $this->assertArrayHasKey('completion_rate', $insights);
        $this->assertGreaterThan(0, $insights['total_interactions']);
        $this->assertGreaterThan(0, $insights['unique_users']);
    }

    /** @test */
    public function it_handles_consent_validation_in_sync_workflow()
    {
        // Create user without consent
        $userWithoutConsent = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Attempt to track interaction without consent
        $result = $this->learningService->trackCourseInteraction(
            $userWithoutConsent->id,
            $this->course->id,
            ['module_id' => 1, 'duration' => 600, 'interaction_type' => 'view']
        );

        // Should fail due to lack of consent
        $this->assertFalse($result);

        // Verify no analytics events were created
        $events = AnalyticsEvent::byTenant($this->tenant->id)
            ->byUser($userWithoutConsent->id)
            ->get();
        $this->assertCount(0, $events);

        // Now grant consent and retry
        \App\Models\Consent::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $userWithoutConsent->id,
            'consent_type' => 'analytics',
            'granted' => true,
            'consent_data' => ['categories' => ['analytics', 'crm_sync']],
        ]);

        $result = $this->learningService->trackCourseInteraction(
            $userWithoutConsent->id,
            $this->course->id,
            ['module_id' => 1, 'duration' => 600, 'interaction_type' => 'view']
        );

        // Should succeed with consent
        $this->assertTrue($result);

        $events = AnalyticsEvent::byTenant($this->tenant->id)
            ->byUser($userWithoutConsent->id)
            ->get();
        $this->assertCount(1, $events);
    }

    /** @test */
    public function it_maintains_tenant_isolation_in_learning_crm_sync()
    {
        // Create second tenant
        $otherTenant = Tenant::factory()->create([
            'id' => 'other-learning-tenant',
            'name' => 'Other Learning Tenant',
        ]);

        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $otherCourse = Course::factory()->create([
            'tenant_id' => $otherTenant->id,
            'title' => 'Other Tenant Course',
        ]);

        // Track interactions for both tenants
        $this->learningService->trackCourseInteraction(
            $this->user->id,
            $this->course->id,
            ['module_id' => 1, 'duration' => 600, 'interaction_type' => 'view']
        );

        // Switch to other tenant context
        session(['tenant_id' => $otherTenant->id]);
        $this->learningService->trackCourseInteraction(
            $otherUser->id,
            $otherCourse->id,
            ['module_id' => 1, 'duration' => 600, 'interaction_type' => 'view']
        );

        // Verify tenant isolation in analytics events
        session(['tenant_id' => $this->tenant->id]);
        $tenant1Events = AnalyticsEvent::byTenant($this->tenant->id)->get();
        $this->assertCount(1, $tenant1Events);
        $this->assertEquals($this->tenant->id, $tenant1Events->first()->tenant_id);

        session(['tenant_id' => $otherTenant->id]);
        $tenant2Events = AnalyticsEvent::byTenant($otherTenant->id)->get();
        $this->assertCount(1, $tenant2Events);
        $this->assertEquals($otherTenant->id, $tenant2Events->first()->tenant_id);

        // Verify tenant isolation in sync logs
        $tenant1SyncLogs = SyncLog::where('tenant_id', $this->tenant->id)->get();
        $tenant2SyncLogs = SyncLog::where('tenant_id', $otherTenant->id)->get();

        // Ensure no cross-tenant contamination
        $this->assertNotEquals($tenant1SyncLogs->count(), $tenant2SyncLogs->count());
    }

    /** @test */
    public function it_handles_sync_service_failures_gracefully()
    {
        // Mock external API failures
        Http::fake([
            'api.hubapi.com/*' => Http::response(['error' => 'Service unavailable'], 503),
            '*.salesforce.com/*' => Http::response(['error' => 'Gateway timeout'], 504),
        ]);

        // Attempt sync with failing external services
        $syncResult = $this->syncService->syncData($this->tenant->id, ['ga', 'matomo']);

        // Should still return internal data as fallback
        $this->assertArrayHasKey('events_count', $syncResult);
        $this->assertArrayHasKey('sessions', $syncResult);
        $this->assertArrayHasKey('sources', $syncResult);
        $this->assertContains('internal', $syncResult['sources']);

        // Verify failure was logged
        $syncLog = SyncLog::where('tenant_id', $this->tenant->id)
            ->where('sync_type', 'unified')
            ->where('status', 'failed')
            ->latest()
            ->first();
        $this->assertNotNull($syncLog);
        $this->assertNotNull($syncLog->discrepancies);
    }

    /** @test */
    public function it_detects_and_resolves_sync_discrepancies()
    {
        // Track learning interactions to create internal data
        for ($i = 0; $i < 10; $i++) {
            $this->learningService->trackCourseInteraction(
                $this->user->id,
                $this->course->id,
                [
                    'module_id' => $i + 1,
                    'duration' => rand(300, 1800),
                    'score' => rand(70, 100),
                    'interaction_type' => 'completion'
                ]
            );
        }

        // Run discrepancy detection
        $discrepancyResult = $this->syncService->detectDiscrepancies($this->tenant->id);

        // Verify discrepancy detection ran
        $this->assertArrayHasKey('discrepancies', $discrepancyResult);
        $this->assertArrayHasKey('resolutions', $discrepancyResult);
        $this->assertArrayHasKey('unified_metrics', $discrepancyResult);

        // Verify sync log was created for discrepancy detection
        $discrepancyLog = SyncLog::where('tenant_id', $this->tenant->id)
            ->where('sync_type', 'discrepancy_detection')
            ->latest()
            ->first();
        $this->assertNotNull($discrepancyLog);
        $this->assertEquals('success', $discrepancyLog->status);
    }

    /** @test */
    public function it_processes_batch_learning_score_calculations()
    {
        // Create multiple users and courses
        $users = User::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);
        $courses = Course::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        // Create user-course pairs for batch processing
        $userCoursePairs = [];
        foreach ($users as $user) {
            foreach ($courses as $course) {
                // Track some interactions first
                $this->learningService->trackCourseInteraction(
                    $user->id,
                    $course->id,
                    ['module_id' => 1, 'duration' => 600, 'interaction_type' => 'view']
                );

                $userCoursePairs[] = [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                ];
            }
        }

        // Process batch scores
        $batchResult = $this->learningService->processBatchScores($userCoursePairs);

        // Verify batch processing results
        $this->assertEquals(15, $batchResult['processed']); // 5 users * 3 courses
        $this->assertEquals(0, $batchResult['errors']);

        // Verify learning progress records were created/updated
        $progressRecords = LearningProgress::byTenant($this->tenant->id)->get();
        $this->assertCount(15, $progressRecords);

        foreach ($progressRecords as $progress) {
            $this->assertGreaterThan(0, $progress->engagement_score);
            $this->assertEquals($this->tenant->id, $progress->tenant_id);
        }
    }

    /** @test */
    public function it_handles_consent_withdrawal_in_sync_workflow()
    {
        // Create consent and track interactions
        \App\Models\Consent::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'consent_type' => 'analytics',
            'granted' => true,
        ]);

        $this->learningService->trackCourseInteraction(
            $this->user->id,
            $this->course->id,
            ['module_id' => 1, 'duration' => 600, 'interaction_type' => 'view']
        );

        // Withdraw consent
        $consentHandled = $this->syncService->handleConsent(
            $this->tenant->id,
            $this->user->id,
            'analytics'
        );

        // Should return true (consent was active and now withdrawn)
        $this->assertTrue($consentHandled);

        // Attempt to track new interaction after consent withdrawal
        $result = $this->learningService->trackCourseInteraction(
            $this->user->id,
            $this->course->id,
            ['module_id' => 2, 'duration' => 600, 'interaction_type' => 'view']
        );

        // Should fail due to withdrawn consent
        $this->assertFalse($result);
    }
}