<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessAnalyticsEvents;
use App\Models\AnalyticsEvent;
use App\Models\ComponentAnalytic;
use App\Services\AnalyticsService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ProcessAnalyticsEventsTest extends TestCase
{
    use RefreshDatabase;

    private ProcessAnalyticsEvents $job;
    private array $eventIds;
    private string $tenantId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantId = 'tenant-123';

        // Create test events
        $event1 = AnalyticsEvent::factory()->create([
            'tenant_id' => $this->tenantId,
            'event_type' => 'page_view',
            'properties' => ['page_url' => '/test'],
            'consent_flags' => ['analytics'],
        ]);

        $event2 = AnalyticsEvent::factory()->create([
            'tenant_id' => $this->tenantId,
            'event_type' => 'component_interaction',
            'properties' => ['component_instance_id' => 1],
            'consent_flags' => ['analytics'],
        ]);

        $this->eventIds = [$event1->id, $event2->id];
        $this->job = new ProcessAnalyticsEvents($this->eventIds, $this->tenantId);
    }

    public function test_job_processes_events_successfully(): void
    {
        // Mock services
        $analyticsService = $this->mock(AnalyticsService::class);
        $tenantContextService = $this->mock(TenantContextService::class);

        $tenantContextService->shouldReceive('setTenant')
            ->once()
            ->with($this->tenantId);

        // Execute job
        $this->job->handle($analyticsService, $tenantContextService);

        // Verify events were processed (soft deleted if old, or marked compliant)
        $event1 = AnalyticsEvent::find($this->eventIds[0]);
        $event2 = AnalyticsEvent::find($this->eventIds[1]);

        $this->assertNotNull($event1);
        $this->assertNotNull($event2);
        $this->assertTrue($event1->is_compliant);
        $this->assertTrue($event2->is_compliant);
    }

    public function test_job_handles_missing_events_gracefully(): void
    {
        $invalidEventIds = [99999, 99998]; // Non-existent IDs

        $job = new ProcessAnalyticsEvents($invalidEventIds, $this->tenantId);

        $analyticsService = $this->mock(AnalyticsService::class);
        $tenantContextService = $this->mock(TenantContextService::class);

        $tenantContextService->shouldReceive('setTenant')
            ->once()
            ->with($this->tenantId);

        // Should not throw exception
        $job->handle($analyticsService, $tenantContextService);

        // Verify no events were processed
        $this->assertEquals(0, ComponentAnalytic::count());
    }

    public function test_job_enforces_compliance_flags(): void
    {
        // Create event without required consent
        $event = AnalyticsEvent::factory()->create([
            'tenant_id' => $this->tenantId,
            'event_type' => 'page_view',
            'consent_flags' => [], // No consent
        ]);

        $job = new ProcessAnalyticsEvents([$event->id], $this->tenantId);

        $analyticsService = $this->mock(AnalyticsService::class);
        $tenantContextService = $this->mock(TenantContextService::class);

        $tenantContextService->shouldReceive('setTenant')
            ->once()
            ->with($this->tenantId);

        $job->handle($analyticsService, $tenantContextService);

        // Refresh event from database
        $event->refresh();

        // Should be marked as non-compliant
        $this->assertFalse($event->is_compliant);
    }

    public function test_job_soft_deletes_old_events(): void
    {
        // Create old event (over retention period)
        $oldEvent = AnalyticsEvent::factory()->create([
            'tenant_id' => $this->tenantId,
            'event_type' => 'page_view',
            'consent_flags' => ['analytics'],
            'created_at' => now()->subDays(400), // Over 365 days
        ]);

        $job = new ProcessAnalyticsEvents([$oldEvent->id], $this->tenantId);

        $analyticsService = $this->mock(AnalyticsService::class);
        $tenantContextService = $this->mock(TenantContextService::class);

        $tenantContextService->shouldReceive('setTenant')
            ->once()
            ->with($this->tenantId);

        $job->handle($analyticsService, $tenantContextService);

        // Event should be soft deleted
        $this->assertSoftDeleted($oldEvent);
    }

    public function test_job_creates_component_analytics_for_interactions(): void
    {
        $componentInstanceId = 123;

        $event = AnalyticsEvent::factory()->create([
            'tenant_id' => $this->tenantId,
            'event_type' => 'component_interaction',
            'properties' => ['component_instance_id' => $componentInstanceId],
            'consent_flags' => ['analytics'],
        ]);

        $job = new ProcessAnalyticsEvents([$event->id], $this->tenantId);

        $analyticsService = $this->mock(AnalyticsService::class);
        $tenantContextService = $this->mock(TenantContextService::class);

        $tenantContextService->shouldReceive('setTenant')
            ->once()
            ->with($this->tenantId);

        $job->handle($analyticsService, $tenantContextService);

        // Verify component analytic was created
        $this->assertDatabaseHas('component_analytics', [
            'component_instance_id' => $componentInstanceId,
            'event_type' => 'component_interaction',
        ]);
    }

    public function test_job_handles_processing_errors_gracefully(): void
    {
        // Create event that will cause an error (invalid data)
        $event = AnalyticsEvent::factory()->create([
            'tenant_id' => $this->tenantId,
            'event_type' => 'invalid_type',
            'properties' => null, // This might cause issues
            'consent_flags' => ['analytics'],
        ]);

        $job = new ProcessAnalyticsEvents([$event->id], $this->tenantId);

        $analyticsService = $this->mock(AnalyticsService::class);
        $tenantContextService = $this->mock(TenantContextService::class);

        $tenantContextService->shouldReceive('setTenant')
            ->once()
            ->with($this->tenantId);

        // Should not throw exception despite error
        $job->handle($analyticsService, $tenantContextService);

        // Event should still exist (not deleted due to error)
        $this->assertDatabaseHas('analytics_events', [
            'id' => $event->id,
        ]);
    }

    public function test_job_logs_processing_details(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Starting ProcessAnalyticsEvents job', [
                'event_count' => 2,
                'tenant_id' => $this->tenantId,
            ]);

        Log::shouldReceive('info')
            ->once()
            ->with('ProcessAnalyticsEvents job completed', \Mockery::on(function ($data) {
                return $data['processed'] === 2 && $data['errors'] === 0 && $data['tenant_id'] === $this->tenantId;
            }));

        $analyticsService = $this->mock(AnalyticsService::class);
        $tenantContextService = $this->mock(TenantContextService::class);

        $tenantContextService->shouldReceive('setTenant')
            ->once()
            ->with($this->tenantId);

        $this->job->handle($analyticsService, $tenantContextService);
    }

    public function test_job_tags_are_correct(): void
    {
        $tags = $this->job->tags();

        $this->assertContains('analytics', $tags);
        $this->assertContains('event-processing', $tags);
        $this->assertContains('tenant:' . $this->tenantId, $tags);
    }

    public function test_job_handles_empty_event_list(): void
    {
        $job = new ProcessAnalyticsEvents([], $this->tenantId);

        $analyticsService = $this->mock(AnalyticsService::class);
        $tenantContextService = $this->mock(TenantContextService::class);

        $tenantContextService->shouldReceive('setTenant')
            ->once()
            ->with($this->tenantId);

        // Should handle empty list gracefully
        $job->handle($analyticsService, $tenantContextService);

        // No assertions needed, just ensure no exceptions
    }

    public function test_job_processes_different_event_types(): void
    {
        $events = [
            AnalyticsEvent::factory()->create([
                'tenant_id' => $this->tenantId,
                'event_type' => 'page_view',
                'consent_flags' => ['analytics'],
            ]),
            AnalyticsEvent::factory()->create([
                'tenant_id' => $this->tenantId,
                'event_type' => 'user_engagement',
                'consent_flags' => ['analytics'],
            ]),
            AnalyticsEvent::factory()->create([
                'tenant_id' => $this->tenantId,
                'event_type' => 'conversion',
                'consent_flags' => ['analytics'],
            ]),
        ];

        $eventIds = collect($events)->pluck('id')->toArray();
        $job = new ProcessAnalyticsEvents($eventIds, $this->tenantId);

        $analyticsService = $this->mock(AnalyticsService::class);
        $tenantContextService = $this->mock(TenantContextService::class);

        $tenantContextService->shouldReceive('setTenant')
            ->once()
            ->with($this->tenantId);

        $job->handle($analyticsService, $tenantContextService);

        // All events should be processed without errors
        foreach ($events as $event) {
            $event->refresh();
            $this->assertTrue($event->is_compliant);
        }
    }
}