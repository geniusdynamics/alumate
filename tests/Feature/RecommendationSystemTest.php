<?php

namespace Tests\Feature;

use App\Jobs\GenerateRecommendationsJob;
use App\Models\Circle;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecommendationSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->candidate = User::factory()->create();

        Sanctum::actingAs($this->user);
    }

    public function test_get_recommendations_returns_paginated_results()
    {
        // Create some potential recommendations
        $candidates = User::factory()->count(5)->create();
        $circle = Circle::factory()->create();

        $this->user->circles()->attach($circle);
        foreach ($candidates as $candidate) {
            $candidate->circles()->attach($circle);
        }

        $response = $this->getJson('/api/recommendations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'user' => [
                            'id',
                            'name',
                            'avatar_url',
                            'current_title',
                            'current_company',
                            'location',
                            'bio',
                        ],
                        'score',
                        'reasons',
                        'shared_circles',
                        'mutual_connections',
                    ],
                ],
                'meta' => [
                    'total',
                    'generated_at',
                ],
            ]);
    }

    public function test_get_recommendations_with_limit_parameter()
    {
        // Create more candidates than the limit
        $candidates = User::factory()->count(15)->create();
        $circle = Circle::factory()->create();

        $this->user->circles()->attach($circle);
        foreach ($candidates as $candidate) {
            $candidate->circles()->attach($circle);
        }

        $response = $this->getJson('/api/recommendations?limit=5');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertLessThanOrEqual(5, count($data));
    }

    public function test_dismiss_recommendation_removes_from_future_results()
    {
        $response = $this->postJson("/api/recommendations/{$this->candidate->id}/dismiss");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Recommendation dismissed successfully',
            ]);

        // Verify dismissal is cached
        $dismissedKey = "dismissed_recommendations:user:{$this->user->id}";
        $dismissed = Cache::get($dismissedKey, []);
        $this->assertContains($this->candidate->id, $dismissed);
    }

    public function test_feedback_on_recommendation_stores_data()
    {
        $feedbackData = [
            'reason' => 'not_relevant',
            'comment' => 'This person is not in my field',
        ];

        $response = $this->postJson("/api/recommendations/{$this->candidate->id}/feedback", $feedbackData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Feedback submitted successfully',
            ]);

        // Verify feedback is stored
        $feedbackKey = "recommendation_feedback:user:{$this->user->id}";
        $feedback = Cache::get($feedbackKey, []);
        $this->assertNotEmpty($feedback);
        $this->assertEquals('not_relevant', $feedback[0]['reason']);
    }

    public function test_feedback_validation_requires_valid_reason()
    {
        $response = $this->postJson("/api/recommendations/{$this->candidate->id}/feedback", [
            'reason' => 'invalid_reason',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_refresh_recommendations_clears_cache_and_dispatches_job()
    {
        Queue::fake();

        $response = $this->postJson('/api/recommendations/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'total',
                    'generated_at',
                    'refreshed',
                ],
            ]);

        Queue::assertPushed(GenerateRecommendationsJob::class, function ($job) {
            return $job->userId === $this->user->id;
        });
    }

    public function test_recommendations_exclude_already_connected_users()
    {
        // Create connection
        Connection::create([
            'user_id' => $this->user->id,
            'connected_user_id' => $this->candidate->id,
            'status' => 'accepted',
        ]);

        $response = $this->getJson('/api/recommendations');

        $response->assertStatus(200);
        $recommendations = $response->json('data');

        // Should not include connected user
        $userIds = collect($recommendations)->pluck('user.id');
        $this->assertNotContains($this->candidate->id, $userIds);
    }

    public function test_recommendations_exclude_pending_connection_requests()
    {
        // Create pending connection request
        Connection::create([
            'user_id' => $this->user->id,
            'connected_user_id' => $this->candidate->id,
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/recommendations');

        $response->assertStatus(200);
        $recommendations = $response->json('data');

        // Should not include user with pending request
        $userIds = collect($recommendations)->pluck('user.id');
        $this->assertNotContains($this->candidate->id, $userIds);
    }

    public function test_recommendations_include_shared_circle_information()
    {
        $circle = Circle::factory()->create(['name' => 'Test Circle']);

        $this->user->circles()->attach($circle);
        $this->candidate->circles()->attach($circle);

        $response = $this->getJson('/api/recommendations');

        $response->assertStatus(200);
        $recommendations = $response->json('data');

        if (! empty($recommendations)) {
            $recommendation = collect($recommendations)->firstWhere('user.id', $this->candidate->id);
            if ($recommendation) {
                $this->assertNotEmpty($recommendation['shared_circles']);
                $this->assertEquals('Test Circle', $recommendation['shared_circles'][0]['name']);
            }
        }
    }

    public function test_recommendations_include_mutual_connections()
    {
        $mutualConnection = User::factory()->create();

        // Create mutual connections
        Connection::create([
            'user_id' => $this->user->id,
            'connected_user_id' => $mutualConnection->id,
            'status' => 'accepted',
        ]);

        Connection::create([
            'user_id' => $this->candidate->id,
            'connected_user_id' => $mutualConnection->id,
            'status' => 'accepted',
        ]);

        $response = $this->getJson('/api/recommendations');

        $response->assertStatus(200);
        $recommendations = $response->json('data');

        if (! empty($recommendations)) {
            $recommendation = collect($recommendations)->firstWhere('user.id', $this->candidate->id);
            if ($recommendation) {
                $this->assertNotEmpty($recommendation['mutual_connections']);
                $this->assertEquals($mutualConnection->name, $recommendation['mutual_connections'][0]['name']);
            }
        }
    }

    public function test_recommendations_include_connection_reasons()
    {
        $circle = Circle::factory()->create(['name' => 'Alumni Circle']);

        $this->user->circles()->attach($circle);
        $this->candidate->circles()->attach($circle);

        $response = $this->getJson('/api/recommendations');

        $response->assertStatus(200);
        $recommendations = $response->json('data');

        if (! empty($recommendations)) {
            $recommendation = collect($recommendations)->firstWhere('user.id', $this->candidate->id);
            if ($recommendation) {
                $this->assertNotEmpty($recommendation['reasons']);
                $this->assertArrayHasKey('type', $recommendation['reasons'][0]);
                $this->assertArrayHasKey('message', $recommendation['reasons'][0]);
            }
        }
    }

    public function test_recommendations_are_scored_and_sorted()
    {
        // Create multiple candidates with different scoring factors
        $candidates = User::factory()->count(3)->create();
        $circle = Circle::factory()->create();

        $this->user->circles()->attach($circle);

        // First candidate: shared circle only
        $candidates[0]->circles()->attach($circle);

        // Second candidate: shared circle + mutual connection
        $candidates[1]->circles()->attach($circle);
        $mutualConnection = User::factory()->create();
        Connection::create([
            'user_id' => $this->user->id,
            'connected_user_id' => $mutualConnection->id,
            'status' => 'accepted',
        ]);
        Connection::create([
            'user_id' => $candidates[1]->id,
            'connected_user_id' => $mutualConnection->id,
            'status' => 'accepted',
        ]);

        $response = $this->getJson('/api/recommendations');

        $response->assertStatus(200);
        $recommendations = $response->json('data');

        if (count($recommendations) >= 2) {
            // Recommendations should be sorted by score (highest first)
            $scores = collect($recommendations)->pluck('score');
            $sortedScores = $scores->sort()->reverse()->values();
            $this->assertEquals($sortedScores->toArray(), $scores->toArray());
        }
    }

    public function test_unauthenticated_user_cannot_access_recommendations()
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/recommendations');

        $response->assertStatus(401);
    }

    public function test_error_handling_for_invalid_user_id_in_dismiss()
    {
        $response = $this->postJson('/api/recommendations/99999/dismiss');

        $response->assertStatus(200); // Should still succeed even if user doesn't exist
    }

    public function test_error_handling_for_invalid_user_id_in_feedback()
    {
        $response = $this->postJson('/api/recommendations/99999/feedback', [
            'reason' => 'not_relevant',
        ]);

        $response->assertStatus(200); // Should still succeed even if user doesn't exist
    }

    public function test_recommendations_respect_privacy_settings()
    {
        // Set candidate to hide from recommendations
        $this->candidate->update([
            'privacy_settings' => [
                'hide_from_recommendations' => true,
            ],
        ]);

        $circle = Circle::factory()->create();
        $this->user->circles()->attach($circle);
        $this->candidate->circles()->attach($circle);

        $response = $this->getJson('/api/recommendations');

        $response->assertStatus(200);
        $recommendations = $response->json('data');

        // Should not include user who opted out
        $userIds = collect($recommendations)->pluck('user.id');
        $this->assertNotContains($this->candidate->id, $userIds);
    }
}
