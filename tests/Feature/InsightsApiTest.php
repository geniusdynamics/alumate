<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\Analytics\InsightsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InsightsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_get_insights_returns_paginated_data(): void
    {
        // Mock the insights service to return sample data
        $mockService = Mockery::mock(InsightsService::class);
        $mockService->shouldReceive('generateInsights')
            ->withAnyArgs()
            ->andReturn([
                [
                    'id' => '1',
                    'type' => 'trend',
                    'metric' => 'engagement',
                    'description' => 'Test trend',
                    'trend_score' => 15.5,
                    'timestamp' => now()->toISOString(),
                    'anomaly' => false,
                    'recommendation' => null
                ]
            ]);

        $this->app->instance(InsightsService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/analytics/insights?page=1&limit=20');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'type',
                        'metric',
                        'description',
                        'trend_score',
                        'timestamp',
                        'anomaly',
                        'recommendation'
                    ]
                ],
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page'
                ]
            ]);
    }

    public function test_generate_insights_returns_success(): void
    {
        $mockService = Mockery::mock(InsightsService::class);
        $mockService->shouldReceive('generateInsights')
            ->with([
                'period' => 'last_30_days',
                'metrics_filter' => [],
                'queue' => false
            ])
            ->andReturn([
                [
                    'id' => '1',
                    'type' => 'trend',
                    'metric' => 'engagement',
                    'description' => 'Generated insight',
                    'trend_score' => 10.0,
                    'timestamp' => now()->toISOString(),
                    'anomaly' => false,
                    'recommendation' => [
                        'type' => 'engagement_campaign',
                        'target' => 'high_churn_cohort',
                        'description' => 'Launch engagement campaign',
                        'expected_impact' => '+15% retention',
                        'priority' => 'high',
                        'action' => 'Launch re-engagement campaign'
                    ]
                ]
            ]);

        $this->app->instance(InsightsService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/analytics/insights/generate', [
                'period' => 'last_30_days',
                'metrics_filter' => []
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'result' => [
                    'id',
                    'type',
                    'metric',
                    'description',
                    'trend_score',
                    'timestamp',
                    'anomaly',
                    'recommendation' => [
                        'type',
                        'target',
                        'description',
                        'expected_impact',
                        'priority',
                        'action'
                    ]
                ]
            ]);
    }

    public function test_generate_insights_queued_returns_queued_response(): void
    {
        $mockService = Mockery::mock(InsightsService::class);
        $mockService->shouldReceive('generateInsights')
            ->with([
                'period' => 'last_30_days',
                'metrics_filter' => [],
                'queue' => true
            ])
            ->andReturn(['queued' => true, 'message' => 'Insight generation queued']);

        $this->app->instance(InsightsService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/analytics/insights/generate', [
                'period' => 'last_30_days',
                'metrics_filter' => [],
                'queue' => true
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Insights generated successfully',
                'result' => ['queued' => true, 'message' => 'Insight generation queued']
            ]);
    }

    public function test_track_feedback_returns_success(): void
    {
        $insightId = 'test-insight-1';
        $score = 8;
        $metadata = ['implemented' => true];

        $mockService = Mockery::mock(InsightsService::class);
        $mockService->shouldReceive('trackEffectiveness')
            ->with($insightId, $score, $metadata)
            ->once()
            ->andReturn(true);

        $this->app->instance(InsightsService::class, $mockService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/analytics/insights/{$insightId}/feedback", [
                'effectiveness_score' => $score,
                'metadata' => $metadata
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Effectiveness feedback recorded successfully'
            ]);

        // Verify cache
        $cachedData = Cache::get("insight_effectiveness_{$insightId}");
        $this->assertIsArray($cachedData);
        $this->assertCount(1, $cachedData);
        $this->assertEquals($score, $cachedData[0]['score']);
    }

    public function test_track_feedback_invalid_score_returns_validation_error(): void
    {
        $insightId = 'test-insight-1';
        $score = 11; // Invalid score

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/analytics/insights/{$insightId}/feedback", [
                'effectiveness_score' => $score
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['effectiveness_score' => 'The selected effectiveness score is invalid.']);
    }

    public function test_insights_api_unauthenticated_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/analytics/insights');

        $response->assertStatus(401);
    }

    public function test_generate_insights_unauthenticated_returns_unauthorized(): void
    {
        $response = $this->postJson('/api/analytics/insights/generate');

        $response->assertStatus(401);
    }

    public function test_track_feedback_unauthenticated_returns_unauthorized(): void
    {
        $response = $this->postJson('/api/analytics/insights/test/feedback');

        $response->assertStatus(401);
    }

    public function test_insights_api_rate_limited(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Simulate rate limiting by making multiple requests
        $responses = [];
        for ($i = 0; $i < 31; $i++) { // Assuming throttle:insights is 30/min
            $response = $this->getJson('/api/analytics/insights');
            $responses[] = $response;
        }

        $lastResponse = end($responses);
        $lastResponse->assertStatus(429); // Too Many Requests
    }

    public function test_consent_middleware_blocks_insights_without_consent(): void
    {
        // Mock consent service to return false
        $mockConsentService = Mockery::mock(\App\Services\Analytics\ConsentService::class);
        $mockConsentService->shouldReceive('hasConsent')->andReturn(false);
        $this->app->instance(\App\Services\Analytics\ConsentService::class, $mockConsentService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/analytics/insights');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Consent required for data access']);
    }
}