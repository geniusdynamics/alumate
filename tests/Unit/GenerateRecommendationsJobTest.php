<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Jobs\GenerateRecommendationsJob;
use App\Services\AlumniRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;

class GenerateRecommendationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_generates_recommendations_for_specific_user()
    {
        $user = User::factory()->create();
        
        $mockService = Mockery::mock(AlumniRecommendationService::class);
        $mockService->shouldReceive('clearRecommendationCache')
                   ->once()
                   ->with($user);
        $mockService->shouldReceive('getRecommendationsForUser')
                   ->once()
                   ->with($user, 20)
                   ->andReturn(collect());
        
        $this->app->instance(AlumniRecommendationService::class, $mockService);
        
        $job = new GenerateRecommendationsJob($user->id);
        $job->handle($mockService);
        
        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    public function test_job_generates_recommendations_for_all_users()
    {
        // Create some users with recent login
        $users = User::factory()->count(3)->create([
            'last_login_at' => now()->subDays(5)
        ]);
        
        // Create user with old login (should be skipped)
        User::factory()->create([
            'last_login_at' => now()->subDays(40)
        ]);
        
        $mockService = Mockery::mock(AlumniRecommendationService::class);
        $mockService->shouldReceive('clearRecommendationCache')
                   ->times(3); // Only for recent users
        $mockService->shouldReceive('getRecommendationsForUser')
                   ->times(3)
                   ->andReturn(collect());
        
        $this->app->instance(AlumniRecommendationService::class, $mockService);
        
        $job = new GenerateRecommendationsJob(null, true);
        $job->handle($mockService);
        
        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    public function test_job_handles_user_not_found_gracefully()
    {
        $mockService = Mockery::mock(AlumniRecommendationService::class);
        $mockService->shouldNotReceive('clearRecommendationCache');
        $mockService->shouldNotReceive('getRecommendationsForUser');
        
        $this->app->instance(AlumniRecommendationService::class, $mockService);
        
        Log::shouldReceive('warning')
           ->once()
           ->with('User not found for recommendation generation', ['user_id' => 99999]);
        
        $job = new GenerateRecommendationsJob(99999);
        $job->handle($mockService);
        
        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    public function test_job_logs_progress_for_bulk_generation()
    {
        // Create many users to trigger progress logging
        User::factory()->count(150)->create([
            'last_login_at' => now()->subDays(5)
        ]);
        
        $mockService = Mockery::mock(AlumniRecommendationService::class);
        $mockService->shouldReceive('clearRecommendationCache')
                   ->andReturn(null);
        $mockService->shouldReceive('getRecommendationsForUser')
                   ->andReturn(collect());
        
        $this->app->instance(AlumniRecommendationService::class, $mockService);
        
        Log::shouldReceive('info')
           ->with('Starting bulk recommendation generation for all users');
        Log::shouldReceive('info')
           ->with('Bulk recommendation generation progress', Mockery::type('array'));
        Log::shouldReceive('info')
           ->with('Completed bulk recommendation generation', Mockery::type('array'));
        
        $job = new GenerateRecommendationsJob(null, true);
        $job->handle($mockService);
        
        $this->assertTrue(true);
    }

    public function test_job_handles_service_exceptions()
    {
        $user = User::factory()->create();
        
        $mockService = Mockery::mock(AlumniRecommendationService::class);
        $mockService->shouldReceive('clearRecommendationCache')
                   ->andThrow(new \Exception('Service error'));
        
        $this->app->instance(AlumniRecommendationService::class, $mockService);
        
        Log::shouldReceive('error')
           ->once()
           ->with('Failed to generate recommendations', Mockery::type('array'));
        
        $job = new GenerateRecommendationsJob($user->id);
        
        $this->expectException(\Exception::class);
        $job->handle($mockService);
    }

    public function test_job_has_correct_tags()
    {
        $user = User::factory()->create();
        
        $job = new GenerateRecommendationsJob($user->id);
        $tags = $job->tags();
        
        $this->assertContains('recommendations', $tags);
        $this->assertContains("user:{$user->id}", $tags);
    }

    public function test_bulk_job_has_correct_tags()
    {
        $job = new GenerateRecommendationsJob(null, true);
        $tags = $job->tags();
        
        $this->assertContains('recommendations', $tags);
        $this->assertContains('bulk-generation', $tags);
    }

    public function test_failed_job_logs_error()
    {
        $user = User::factory()->create();
        $exception = new \Exception('Test failure');
        
        Log::shouldReceive('error')
           ->once()
           ->with('GenerateRecommendationsJob failed', Mockery::type('array'));
        
        $job = new GenerateRecommendationsJob($user->id);
        $job->failed($exception);
        
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}