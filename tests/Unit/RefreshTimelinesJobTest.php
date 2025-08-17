<?php

namespace Tests\Unit;

use App\Jobs\RefreshTimelinesJob;
use App\Models\Post;
use App\Models\User;
use App\Services\TimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RefreshTimelinesJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_job_for_new_post()
    {
        $post = Post::factory()->create();

        $job = RefreshTimelinesJob::forNewPost($post);

        $this->assertInstanceOf(RefreshTimelinesJob::class, $job);
    }

    public function test_can_create_job_for_users()
    {
        $userIds = [1, 2, 3];

        $job = RefreshTimelinesJob::forUsers($userIds);

        $this->assertInstanceOf(RefreshTimelinesJob::class, $job);
    }

    public function test_can_create_job_for_all_active_users()
    {
        $job = RefreshTimelinesJob::forAllActiveUsers();

        $this->assertInstanceOf(RefreshTimelinesJob::class, $job);
    }

    public function test_job_handles_new_post()
    {
        $post = Post::factory()->create();

        $timelineService = $this->createMock(TimelineService::class);
        $timelineService->expects($this->once())
            ->method('invalidateTimelineCacheForPost')
            ->with($post);

        $job = RefreshTimelinesJob::forNewPost($post);
        $job->handle($timelineService);
    }

    public function test_job_handles_specific_users()
    {
        $users = User::factory()->count(2)->create();
        $userIds = $users->pluck('id')->toArray();

        $timelineService = $this->createMock(TimelineService::class);
        $timelineService->expects($this->exactly(2))
            ->method('invalidateTimelineCache');

        $job = RefreshTimelinesJob::forUsers($userIds);
        $job->handle($timelineService);
    }

    public function test_job_can_be_queued()
    {
        Queue::fake();

        $post = Post::factory()->create();

        RefreshTimelinesJob::forNewPost($post)->dispatch();

        Queue::assertPushed(RefreshTimelinesJob::class);
    }

    public function test_job_has_correct_properties()
    {
        $job = RefreshTimelinesJob::forNewPost(Post::factory()->create());

        $this->assertEquals(300, $job->timeout);
        $this->assertEquals(3, $job->tries);
    }

    public function test_job_handles_exceptions_gracefully()
    {
        $post = Post::factory()->create();

        $timelineService = $this->createMock(TimelineService::class);
        $timelineService->method('invalidateTimelineCacheForPost')
            ->willThrowException(new \Exception('Test exception'));

        $job = RefreshTimelinesJob::forNewPost($post);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');

        $job->handle($timelineService);
    }
}
