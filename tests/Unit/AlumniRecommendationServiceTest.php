<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Circle;
use App\Models\Connection;
use App\Services\AlumniRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class AlumniRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AlumniRecommendationService $service;
    private User $user;
    private User $candidate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AlumniRecommendationService();
        
        // Create test users
        $this->user = User::factory()->create([
            'name' => 'John Doe',
            'location' => 'New York, NY',
            'bio' => 'Software engineer interested in AI and machine learning'
        ]);
        
        $this->candidate = User::factory()->create([
            'name' => 'Jane Smith',
            'location' => 'New York, NY',
            'bio' => 'Data scientist working with machine learning algorithms'
        ]);
    }

    public function test_get_recommendations_for_user_returns_collection()
    {
        $recommendations = $this->service->getRecommendationsForUser($this->user, 5);
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $recommendations);
    }

    public function test_calculate_connection_score_with_shared_circles()
    {
        // Create a shared circle
        $circle = Circle::factory()->create([
            'name' => 'Class of 2020',
            'type' => 'school_year'
        ]);
        
        $this->user->circles()->attach($circle);
        $this->candidate->circles()->attach($circle);
        
        $score = $this->service->calculateConnectionScore($this->user, $this->candidate);
        
        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(1, $score);
    }

    public function test_calculate_connection_score_with_mutual_connections()
    {
        // Create mutual connection
        $mutualConnection = User::factory()->create();
        
        Connection::create([
            'user_id' => $this->user->id,
            'connected_user_id' => $mutualConnection->id,
            'status' => 'accepted'
        ]);
        
        Connection::create([
            'user_id' => $this->candidate->id,
            'connected_user_id' => $mutualConnection->id,
            'status' => 'accepted'
        ]);
        
        $score = $this->service->calculateConnectionScore($this->user, $this->candidate);
        
        $this->assertGreaterThan(0, $score);
    }

    public function test_get_shared_circles_returns_common_circles()
    {
        $circle1 = Circle::factory()->create(['name' => 'Circle 1']);
        $circle2 = Circle::factory()->create(['name' => 'Circle 2']);
        $circle3 = Circle::factory()->create(['name' => 'Circle 3']);
        
        // User belongs to circles 1 and 2
        $this->user->circles()->attach([$circle1->id, $circle2->id]);
        
        // Candidate belongs to circles 2 and 3
        $this->candidate->circles()->attach([$circle2->id, $circle3->id]);
        
        $sharedCircles = $this->service->getSharedCircles($this->user, $this->candidate);
        
        $this->assertCount(1, $sharedCircles);
        $this->assertEquals($circle2->id, $sharedCircles->first()->id);
    }

    public function test_get_mutual_connections_returns_common_connections()
    {
        $connection1 = User::factory()->create();
        $connection2 = User::factory()->create();
        $connection3 = User::factory()->create();
        
        // User connected to 1 and 2
        Connection::create([
            'user_id' => $this->user->id,
            'connected_user_id' => $connection1->id,
            'status' => 'accepted'
        ]);
        Connection::create([
            'user_id' => $this->user->id,
            'connected_user_id' => $connection2->id,
            'status' => 'accepted'
        ]);
        
        // Candidate connected to 2 and 3
        Connection::create([
            'user_id' => $this->candidate->id,
            'connected_user_id' => $connection2->id,
            'status' => 'accepted'
        ]);
        Connection::create([
            'user_id' => $this->candidate->id,
            'connected_user_id' => $connection3->id,
            'status' => 'accepted'
        ]);
        
        $mutualConnections = $this->service->getMutualConnections($this->user, $this->candidate);
        
        $this->assertCount(1, $mutualConnections);
        $this->assertEquals($connection2->id, $mutualConnections->first()->id);
    }

    public function test_get_interest_similarity_calculates_overlap()
    {
        $user = User::factory()->create([
            'bio' => 'Software engineer interested in machine learning and artificial intelligence'
        ]);
        
        $candidate = User::factory()->create([
            'bio' => 'Data scientist working with machine learning and deep learning'
        ]);
        
        $similarity = $this->service->getInterestSimilarity($user, $candidate);
        
        $this->assertGreaterThan(0, $similarity);
        $this->assertLessThanOrEqual(1, $similarity);
    }

    public function test_filter_recommendations_excludes_connected_users()
    {
        // Create connection between users
        Connection::create([
            'user_id' => $this->user->id,
            'connected_user_id' => $this->candidate->id,
            'status' => 'accepted'
        ]);
        
        $recommendations = collect([
            [
                'user' => $this->candidate,
                'score' => 0.8,
                'reasons' => [],
                'shared_circles' => collect(),
                'mutual_connections' => collect()
            ]
        ]);
        
        $filtered = $this->service->filterRecommendations($recommendations, $this->user);
        
        $this->assertCount(0, $filtered);
    }

    public function test_filter_recommendations_excludes_pending_requests()
    {
        // Create pending connection request
        Connection::create([
            'user_id' => $this->user->id,
            'connected_user_id' => $this->candidate->id,
            'status' => 'pending'
        ]);
        
        $recommendations = collect([
            [
                'user' => $this->candidate,
                'score' => 0.8,
                'reasons' => [],
                'shared_circles' => collect(),
                'mutual_connections' => collect()
            ]
        ]);
        
        $filtered = $this->service->filterRecommendations($recommendations, $this->user);
        
        $this->assertCount(0, $filtered);
    }

    public function test_dismiss_recommendation_caches_dismissal()
    {
        $this->service->dismissRecommendation($this->user, $this->candidate->id);
        
        $dismissedKey = "dismissed_recommendations:user:{$this->user->id}";
        $dismissed = Cache::get($dismissedKey, []);
        
        $this->assertContains($this->candidate->id, $dismissed);
    }

    public function test_clear_recommendation_cache_removes_cache()
    {
        $cacheKey = "recommendations:user:{$this->user->id}";
        Cache::put($cacheKey, ['test' => 'data'], 3600);
        
        $this->service->clearRecommendationCache($this->user);
        
        $this->assertNull(Cache::get($cacheKey));
    }

    public function test_get_second_degree_connections_returns_correct_users()
    {
        $firstDegree = User::factory()->create();
        $secondDegree = User::factory()->create();
        
        // User -> First degree connection
        Connection::create([
            'user_id' => $this->user->id,
            'connected_user_id' => $firstDegree->id,
            'status' => 'accepted'
        ]);
        
        // First degree -> Second degree connection
        Connection::create([
            'user_id' => $firstDegree->id,
            'connected_user_id' => $secondDegree->id,
            'status' => 'accepted'
        ]);
        
        $secondDegreeConnections = $this->service->getSecondDegreeConnections($this->user);
        
        $this->assertCount(1, $secondDegreeConnections);
        $this->assertEquals($secondDegree->id, $secondDegreeConnections->first()->id);
    }

    public function test_scoring_weights_are_applied_correctly()
    {
        // Create scenario with all scoring factors
        $circle = Circle::factory()->create(['type' => 'school_year']);
        $mutualConnection = User::factory()->create();
        
        $this->user->circles()->attach($circle);
        $this->candidate->circles()->attach($circle);
        
        Connection::create([
            'user_id' => $this->user->id,
            'connected_user_id' => $mutualConnection->id,
            'status' => 'accepted'
        ]);
        Connection::create([
            'user_id' => $this->candidate->id,
            'connected_user_id' => $mutualConnection->id,
            'status' => 'accepted'
        ]);
        
        // Set same location for geographic score
        $this->user->update(['location' => 'San Francisco, CA']);
        $this->candidate->update(['location' => 'San Francisco, CA']);
        
        $score = $this->service->calculateConnectionScore($this->user, $this->candidate);
        
        // Score should be combination of all factors
        $this->assertGreaterThan(0.5, $score); // Should be relatively high with multiple factors
        $this->assertLessThanOrEqual(1, $score);
    }

    public function test_recommendations_are_cached()
    {
        $cacheKey = "recommendations:user:{$this->user->id}";
        
        // First call should cache results
        $recommendations1 = $this->service->getRecommendationsForUser($this->user, 5);
        $this->assertNotNull(Cache::get($cacheKey));
        
        // Second call should return cached results
        $recommendations2 = $this->service->getRecommendationsForUser($this->user, 5);
        $this->assertEquals($recommendations1->count(), $recommendations2->count());
    }
}