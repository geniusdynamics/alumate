<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\SavedSearch;
use App\Models\User;
use App\Services\MatchingService;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->course = Course::factory()->create([
            'name' => 'Computer Science',
            'skills_gained' => ['JavaScript', 'PHP', 'Database Design'],
        ]);

        $this->graduate = Graduate::factory()->create([
            'course_id' => $this->course->id,
            'skills' => ['JavaScript', 'PHP', 'Laravel'],
            'job_search_active' => true,
            'allow_employer_contact' => true,
        ]);

        $this->job = Job::factory()->create([
            'course_id' => $this->course->id,
            'required_skills' => ['JavaScript', 'PHP'],
            'status' => 'active',
        ]);
    }

    public function test_search_service_can_search_jobs()
    {
        $searchService = app(SearchService::class);

        $results = $searchService->searchJobs([
            'keywords' => 'developer',
            'skills' => ['JavaScript'],
        ]);

        $this->assertNotNull($results);
        $this->assertGreaterThan(0, $results->total());
    }

    public function test_search_service_can_search_graduates()
    {
        $user = User::factory()->create();
        $user->assignRole('employer');

        $this->actingAs($user);

        $searchService = app(SearchService::class);

        $results = $searchService->searchGraduates([
            'skills' => ['JavaScript'],
            'employment_status' => 'unemployed',
        ]);

        $this->assertNotNull($results);
    }

    public function test_matching_service_can_calculate_job_graduate_match()
    {
        $matchingService = app(MatchingService::class);

        $matchData = $matchingService->calculateJobGraduateMatch($this->job, $this->graduate);

        $this->assertArrayHasKey('match_score', $matchData);
        $this->assertArrayHasKey('match_factors', $matchData);
        $this->assertArrayHasKey('compatibility_score', $matchData);
        $this->assertGreaterThan(0, $matchData['match_score']);
    }

    public function test_user_can_save_search()
    {
        $user = User::factory()->create();
        $user->assignRole('graduate');

        $this->actingAs($user);

        $response = $this->postJson('/api/search/save', [
            'name' => 'My Job Search',
            'search_type' => 'jobs',
            'search_criteria' => [
                'keywords' => 'developer',
                'location' => 'Remote',
            ],
            'alert_enabled' => true,
            'alert_frequency' => 'daily',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('saved_searches', [
            'user_id' => $user->id,
            'name' => 'My Job Search',
            'search_type' => 'jobs',
        ]);
    }

    public function test_user_can_get_job_recommendations()
    {
        $user = User::factory()->create();
        $user->assignRole('graduate');
        $user->graduate()->associate($this->graduate);
        $user->save();

        $this->actingAs($user);

        $response = $this->getJson('/api/search/recommendations?type=jobs&limit=5');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'recommendations',
            'total',
        ]);
    }

    public function test_search_suggestions_work()
    {
        $response = $this->getJson('/api/search/suggestions?q=java&type=skills');

        $response->assertStatus(200);
        $response->assertJsonStructure(['suggestions']);
    }

    public function test_saved_search_can_be_executed()
    {
        $user = User::factory()->create();
        $user->assignRole('graduate');

        $savedSearch = SavedSearch::create([
            'user_id' => $user->id,
            'name' => 'Test Search',
            'search_type' => 'jobs',
            'search_criteria' => ['keywords' => 'developer'],
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->postJson("/api/search/saved/{$savedSearch->id}/execute");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'results',
            'saved_search',
        ]);
    }

    public function test_matching_service_can_generate_job_matches()
    {
        $matchingService = app(MatchingService::class);

        $matches = $matchingService->generateJobMatches($this->job, 10);

        $this->assertIsArray($matches);
        $this->assertGreaterThan(0, count($matches));

        foreach ($matches as $match) {
            $this->assertArrayHasKey('graduate', $match);
            $this->assertArrayHasKey('match_data', $match);
        }
    }

    public function test_matching_service_can_store_matches()
    {
        $matchingService = app(MatchingService::class);

        $matchData = $matchingService->calculateJobGraduateMatch($this->job, $this->graduate);
        $storedMatch = $matchingService->storeJobGraduateMatch($this->job, $this->graduate, $matchData);

        $this->assertDatabaseHas('job_graduate_matches', [
            'job_id' => $this->job->id,
            'graduate_id' => $this->graduate->id,
        ]);

        $this->assertEquals($matchData['match_score'], $storedMatch->match_score);
    }

    public function test_search_analytics_are_tracked()
    {
        $user = User::factory()->create();
        $user->assignRole('graduate');

        $this->actingAs($user);

        $response = $this->getJson('/api/search/jobs', [
            'keywords' => 'developer',
            'location' => 'Remote',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('search_analytics', [
            'user_id' => $user->id,
            'search_type' => 'jobs',
        ]);
    }
}
