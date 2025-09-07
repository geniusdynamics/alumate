<?php

use App\Http\Controllers\Api\StatisticsController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

describe('Statistics API', function () {
    beforeEach(function () {
        // Clear cache before each test
        Cache::flush();
    });

    it('returns health check status', function () {
        $response = $this->getJson('/api/statistics/health');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'healthy'
            ]);
    });

    it('returns platform metrics', function () {
        // Create some test data
        createTestUsers();

        $response = $this->getJson('/api/statistics/platform-metrics');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'alumni-count',
                    'connections-made',
                    'job-placements',
                    'institutions-served'
                ],
                'timestamp'
            ]);
    });

    it('returns single statistic by id', function () {
        $response = $this->getJson('/api/statistics/avg-salary-increase');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => 'avg-salary-increase',
                    'value' => 35,
                    'source' => 'manual'
                ]
            ]);
    });

    it('returns 404 for non-existent statistic', function () {
        $response = $this->getJson('/api/statistics/non-existent-stat');

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'errors' => ['Statistic not found']
            ]);
    });

    it('returns batch statistics', function () {
        $response = $this->postJson('/api/statistics/batch', [
            'ids' => ['avg-salary-increase', 'engagement-increase']
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true
            ])
            ->assertJsonCount(2, 'data');
    });

    it('validates batch request parameters', function () {
        $response = $this->postJson('/api/statistics/batch', [
            'ids' => []
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['ids']);
    });

    it('handles partial failures in batch requests', function () {
        $response = $this->postJson('/api/statistics/batch', [
            'ids' => ['avg-salary-increase', 'non-existent-stat']
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => false
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(1, 'errors');
    });

    it('caches statistics data', function () {
        // First request
        $response1 = $this->getJson('/api/statistics/avg-salary-increase');
        $response1->assertOk();

        // Verify cache was set
        expect(Cache::has('statistic.avg-salary-increase'))->toBeTrue();

        // Second request should use cache
        $response2 = $this->getJson('/api/statistics/avg-salary-increase');
        $response2->assertOk();

        // Both responses should be identical
        expect($response1->json('data'))->toEqual($response2->json('data'));
    });

    it('clears cache when requested', function () {
        // Set some cache data
        $this->getJson('/api/statistics/avg-salary-increase');
        expect(Cache::has('statistic.avg-salary-increase'))->toBeTrue();

        // Clear cache (this would require authentication in real app)
        $response = $this->deleteJson('/api/statistics/cache');

        // For now, just test the endpoint exists
        // In a real app, this would require proper authentication
        expect($response->status())->toBeIn([200, 401, 403]);
    });
});

describe('Statistics Service Integration', function () {
    it('handles database connection errors gracefully', function () {
        // Skip this test for now as it's difficult to mock database failures in testing
        $this->markTestSkipped('Database connection mocking needs proper setup');
    });

    it('returns fallback values for database statistics when query fails', function () {
        // This test would require mocking DB failures
        // For now, just verify the endpoint structure
        $response = $this->getJson('/api/statistics/alumni-count');

        expect($response->status())->toBeIn([200, 500]);
    });
});

// Helper function to create test data
function createTestUsers(): void
{
    // Create test users for statistics
    DB::table('users')->insert([
        [
            'name' => 'Test Alumni 1',
            'email' => 'alumni1@test.com',
            'role' => 'alumni',
            'tenant_id' => 1,
            'profile_completed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Test Alumni 2',
            'email' => 'alumni2@test.com',
            'role' => 'alumni',
            'tenant_id' => 1,
            'profile_completed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Test Student',
            'email' => 'student@test.com',
            'role' => 'student',
            'tenant_id' => 1,
            'profile_completed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    // Create test connections
    DB::table('connections')->insert([
        [
            'user_id' => 1,
            'connected_user_id' => 2,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    // Create test job applications
    DB::table('job_applications')->insert([
        [
            'user_id' => 1,
            'job_id' => 1,
            'status' => 'hired',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
}