<?php

namespace Tests\Unit;

use App\Services\TimelineService;
use Tests\TestCase;

class TimelineServiceBasicTest extends TestCase
{
    /** @test */
    public function timeline_service_can_be_instantiated()
    {
        $service = new TimelineService;

        $this->assertInstanceOf(TimelineService::class, $service);
    }

    /** @test */
    public function timeline_service_has_required_methods()
    {
        $service = new TimelineService;

        $this->assertTrue(method_exists($service, 'generateTimelineForUser'));
        $this->assertTrue(method_exists($service, 'getCirclePosts'));
        $this->assertTrue(method_exists($service, 'getGroupPosts'));
        $this->assertTrue(method_exists($service, 'scorePost'));
        $this->assertTrue(method_exists($service, 'cacheTimeline'));
        $this->assertTrue(method_exists($service, 'invalidateTimelineCache'));
    }

    /** @test */
    public function timeline_controller_can_be_instantiated()
    {
        $service = new TimelineService;
        $controller = new \App\Http\Controllers\Api\TimelineController($service);

        $this->assertInstanceOf(\App\Http\Controllers\Api\TimelineController::class, $controller);
    }

    /** @test */
    public function refresh_timelines_job_can_be_instantiated()
    {
        $job = new \App\Jobs\RefreshTimelinesJob;

        $this->assertInstanceOf(\App\Jobs\RefreshTimelinesJob::class, $job);
    }

    /** @test */
    public function refresh_timelines_job_factory_methods_work()
    {
        $jobForUsers = \App\Jobs\RefreshTimelinesJob::forUsers([1, 2, 3]);
        $jobForActive = \App\Jobs\RefreshTimelinesJob::forAllActiveUsers();

        $this->assertInstanceOf(\App\Jobs\RefreshTimelinesJob::class, $jobForUsers);
        $this->assertInstanceOf(\App\Jobs\RefreshTimelinesJob::class, $jobForActive);
    }
}
