<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RefreshTimelinesJob;
use App\Models\User;
use App\Models\Tenant;
use App\Services\TimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Mockery;

class RefreshTimelinesJobTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        Redis::flushall();
    }

    /** @test */
    public function it_refreshes_timelines_for_specific_users()
    {
        $users = User::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);
        $userIds = $users->pluck('id')->toArray();

        $timelineService = Mockery::mock(TimelineService::class);
        $timelineService->shouldReceive('generateTimelineForUser')
                       ->times(3)
                       ->andReturn(['posts' => [], 'next_cursor' => null, 'has_more' => false]);

        $this->app->instance(TimelineService::class, $timelineService);

        $job = RefreshTimelinesJob::forUsers($userIds);
        $job->handle($timelineService);

        $timelineService->shouldHaveReceived('generateTimelineForUser')->times(3);
    }

    /** @test */
    public function it_refreshes_timelines_for_active_users_when_no_specific_users_provided()
    {
        // Create active users (updated within last 7 days)
        $activeUsers = User::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'updated_at' => now()->subDays(3)
        ]);

        // Create inactive users (updated more than 7 days ago)
        $inactiveUsers = User::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'updated_at' => now()->subDays(10)
        ]);

        $timelineService = Mockery::mock(TimelineService::class);
        $timelineService->shouldReceive('generateTimelineForUser')
                       ->times(2) // Only active users
                       ->andReturn(['posts' => [], 'next_cursor' => null, 'has_more' => false]);

        $this->app->instance(TimelineService::class, $timelineService);

        $job = RefreshTimelinesJob::forActiveUsers();
        $job->handle($timelineService);

        $timelineService->shouldHaveReceived('generateTimelineForUser')->times(2);
    }

    /** @test */
    public function it_invalidates_cache_when_force_refresh_is_true()
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $timelineService = Mockery::mock(TimelineService::class);
        $timelineService->shouldReceive('invalidateTimelineCache')
                       ->once()
                       ->with($user);
        $timelineService->shouldReceive('generateTimelineForUser')
                       ->once()
                       ->with($user)
                       ->andReturn(['posts' => [], 'next_cursor' => null, 'has_more' => false]);

        $this->app->instance(TimelineService::class, $timelineService);

        $job = RefreshTimelinesJob::forUsers([$user->id], true);
        $job->handle($timelineService);

        $timelineService->shouldHaveReceived('invalidateTimelineCache')->once();
        $timelineService->shouldHaveReceived('generateTimelineForUser')->once();
    }

    /** @test */
    public function it_does_not_invalidate_cache_when_force_refresh_is_false()
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $timelineService = Mockery::mock(TimelineService::class);
        $timelineService->shouldNotReceive('invalidateTimelineCache');
        $timelineService->shouldReceive('generateTimelineForUser')
                       ->once()
                       ->with($user)
                       ->andReturn(['posts' => [], 'next_cursor' => null, 'has_more' => false]);

        $this->app->instance(TimelineService::class, $timelineService);

        $job = RefreshTimelinesJob::forUsers([$user->id], false);
        $job->handle($timelineService);

        $timelineService->shouldNotHaveReceived('invalidateTimelineCache');
        $timelineService->shouldHaveReceived('generateTimelineForUser')->once();
    }

    /** @test */
    public function it_logs_progress_and_completion()
    {
        Log::shouldReceive('info')
           ->with('Starting timeline refresh', Mockery::type('array'))
           ->once();

        Log::shouldReceive('info')
           ->with('Timeline refresh completed', Mockery::type('array'))
           ->once();

        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $timelineService = Mockery::mock(TimelineService::class);
        $timelineService->shouldReceive('generateTimelineForUser')
                       ->once()
                       ->andReturn(['posts' => [], 'next_cursor' => null, 'has_more' => false]);

        $this->app->instance(TimelineService::class, $timelineService);

        $job = RefreshTimelinesJob::forUsers([$user->id]);
        $job->handle($timelineService);
    }

    /** @test */
    public function it_handles_individual_user_failures_gracefully()
    {
        $users = User::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);
        $userIds = $users->pluck('id')->toArray();

        $timelineService = Mockery::mock(TimelineService::class);
        
        // First user succeeds
        $timelineService->shouldReceive('generateTimelineForUser')
                       ->once()
                       ->with($users[0])
                       ->andReturn(['posts' => [], 'next_cursor' => null, 'has_more' => false]);
        
        // Second user fails
        $timelineService->shouldReceive('generateTimelineForUser')
                       ->once()
                       ->with($users[1])
                       ->andThrow(new \Exception('Timeline generation failed'));

        Log::shouldReceive('info')->twice(); // Start and completion logs
        Log::shouldReceive('error')
           ->with('Failed to refresh timeline for user', Mockery::type('array'))
           ->once();

        $this->app->instance(TimelineService::class, $timelineService);

        $job = RefreshTimelinesJob::forUsers($userIds);
        $job->handle($timelineService);

        // Job should complete despite individual failures
        $this->assertTrue(true); // If we reach here, job didn't throw
    }

    /** @test */
    public function it_logs_errors_when_job_fails_completely()
    {
        Log::shouldReceive('info')->once(); // Start log
        Log::shouldReceive('error')
           ->with('Timeline refresh job failed', Mockery::type('array'))
           ->once();

        $timelineService = Mockery::mock(TimelineService::class);

        $this->app->instance(TimelineService::class, $timelineService);

        $job = new RefreshTimelinesJob([999]); // Non-existent user ID
        
        $this->expectException(\Exception::class);
        $job->handle($timelineService);
    }

    /** @test */
    public function it_creates_job_for_specific_users()
    {
        $userIds = [1, 2, 3];
        $job = RefreshTimelinesJob::forUsers($userIds, true);

        $this->assertInstanceOf(RefreshTimelinesJob::class, $job);
    }

    /** @test */
    public function it_creates_job_for_active_users()
    {
        $job = RefreshTimelinesJob::forActiveUsers(false);

        $this->assertInstanceOf(RefreshTimelinesJob::class, $job);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}