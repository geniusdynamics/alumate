<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SearchService;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\Course;
use App\Models\Employer;
use App\Models\SavedSearch;
use App\Models\SearchAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SearchService $searchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchService = app(SearchService::class);
    }

    public function test_can_search_graduates(): void
    {
        $course = Course::factory()->create();
        Graduate::factory()->count(5)->create([
            'course_id' => $course->id,
            'skills' => ['PHP', 'Laravel', 'JavaScript'],
            'employment_status' => ['status' => 'unemployed'],
            'job_search_active' => true,
            'allow_employer_contact' => true
        ]);

        $results = $this->searchService->searchGraduates([
            'skills' => ['PHP'],
            'course_id' => $course->id,
            'employment_status' => 'unemployed'
        ]);

        $this->assertCount(5, $results);
        foreach ($results as $graduate) {
            $this->assertContains('PHP', $graduate->skills);
            $this->assertEquals($course->id, $graduate->course_id);
            $this->assertTrue($graduate->job_search_active);
        }
    }

    public function test_can_search_jobs(): void
    {
        $course = Course::factory()->create();
        $employer = Employer::factory()->create();
        
        Job::factory()->count(3)->create([
            'course_id' => $course->id,
            'employer_id' => $employer->id,
            'required_skills' => ['PHP', 'Laravel'],
            'status' => 'active',
            'location' => 'Remote'
        ]);

        $results = $this->searchService->searchJobs([
            'skills' => ['PHP'],
            'course_id' => $course->id,
            'location' => 'Remote'
        ]);

        $this->assertCount(3, $results);
        foreach ($results as $job) {
            $this->assertContains('PHP', $job->required_skills);
            $this->assertEquals($course->id, $job->course_id);
            $this->assertEquals('Remote', $job->location);
        }
    }

    public function test_can_perform_advanced_graduate_search(): void
    {
        $course = Course::factory()->create();
        Graduate::factory()->count(3)->create([
            'course_id' => $course->id,
            'graduation_year' => 2024,
            'gpa' => 3.5,
            'skills' => ['PHP', 'JavaScript'],
            'employment_status' => ['status' => 'unemployed']
        ]);

        $results = $this->searchService->advancedGraduateSearch([
            'course_id' => $course->id,
            'graduation_year_from' => 2024,
            'graduation_year_to' => 2024,
            'min_gpa' => 3.0,
            'skills' => ['PHP'],
            'employment_status' => 'unemployed'
        ]);

        $this->assertCount(3, $results);
        foreach ($results as $graduate) {
            $this->assertEquals(2024, $graduate->graduation_year);
            $this->assertGreaterThanOrEqual(3.0, $graduate->gpa);
            $this->assertContains('PHP', $graduate->skills);
        }
    }

    public function test_can_perform_advanced_job_search(): void
    {
        $course = Course::factory()->create();
        $employer = Employer::factory()->create();
        
        Job::factory()->count(2)->create([
            'course_id' => $course->id,
            'employer_id' => $employer->id,
            'salary_min' => 50000,
            'salary_max' => 70000,
            'required_skills' => ['PHP', 'Laravel'],
            'experience_level' => 'entry',
            'job_type' => 'full-time'
        ]);

        $results = $this->searchService->advancedJobSearch([
            'course_id' => $course->id,
            'salary_min' => 45000,
            'salary_max' => 75000,
            'skills' => ['PHP'],
            'experience_level' => 'entry',
            'job_type' => 'full-time'
        ]);

        $this->assertCount(2, $results);
        foreach ($results as $job) {
            $this->assertGreaterThanOrEqual(45000, $job->salary_min);
            $this->assertLessThanOrEqual(75000, $job->salary_max);
            $this->assertContains('PHP', $job->required_skills);
            $this->assertEquals('entry', $job->experience_level);
        }
    }

    public function test_can_save_search(): void
    {
        $user = $this->createUserWithRole('employer');
        
        $savedSearch = $this->searchService->saveSearch($user, [
            'name' => 'PHP Developers',
            'type' => 'graduates',
            'criteria' => [
                'skills' => ['PHP', 'Laravel'],
                'employment_status' => 'unemployed'
            ],
            'alert_frequency' => 'daily'
        ]);

        $this->assertInstanceOf(SavedSearch::class, $savedSearch);
        $this->assertEquals('PHP Developers', $savedSearch->name);
        $this->assertEquals('graduates', $savedSearch->type);
        $this->assertEquals($user->id, $savedSearch->user_id);
        $this->assertArrayHasKey('skills', $savedSearch->criteria);
    }

    public function test_can_create_search_alert(): void
    {
        $user = $this->createUserWithRole('graduate');
        
        $alert = $this->searchService->createSearchAlert($user, [
            'name' => 'Laravel Jobs',
            'type' => 'jobs',
            'criteria' => [
                'skills' => ['Laravel'],
                'location' => 'Remote'
            ],
            'frequency' => 'weekly',
            'is_active' => true
        ]);

        $this->assertInstanceOf(SearchAlert::class, $alert);
        $this->assertEquals('Laravel Jobs', $alert->name);
        $this->assertEquals('jobs', $alert->type);
        $this->assertEquals('weekly', $alert->frequency);
        $this->assertTrue($alert->is_active);
    }

    public function test_can_execute_saved_search(): void
    {
        $user = $this->createUserWithRole('employer');
        $course = Course::factory()->create();
        
        // Create test graduates
        Graduate::factory()->count(3)->create([
            'course_id' => $course->id,
            'skills' => ['PHP', 'Laravel'],
            'employment_status' => ['status' => 'unemployed'],
            'job_search_active' => true
        ]);

        $savedSearch = SavedSearch::create([
            'user_id' => $user->id,
            'name' => 'PHP Developers',
            'type' => 'graduates',
            'criteria' => [
                'skills' => ['PHP'],
                'employment_status' => 'unemployed'
            ]
        ]);

        $results = $this->searchService->executeSavedSearch($savedSearch);

        $this->assertCount(3, $results);
        foreach ($results as $graduate) {
            $this->assertContains('PHP', $graduate->skills);
        }
    }

    public function test_can_get_search_suggestions(): void
    {
        // Create test data
        Graduate::factory()->count(5)->create(['skills' => ['PHP', 'Laravel', 'JavaScript']]);
        Job::factory()->count(3)->create(['required_skills' => ['Python', 'Django', 'React']]);

        $suggestions = $this->searchService->getSearchSuggestions('graduates', 'skills');

        $this->assertIsArray($suggestions);
        $this->assertContains('PHP', $suggestions);
        $this->assertContains('Laravel', $suggestions);
        $this->assertContains('JavaScript', $suggestions);
    }

    public function test_can_get_popular_searches(): void
    {
        $user1 = $this->createUserWithRole('employer');
        $user2 = $this->createUserWithRole('employer');

        // Create saved searches
        SavedSearch::factory()->count(3)->create([
            'user_id' => $user1->id,
            'type' => 'graduates',
            'criteria' => ['skills' => ['PHP']]
        ]);
        SavedSearch::factory()->count(2)->create([
            'user_id' => $user2->id,
            'type' => 'graduates',
            'criteria' => ['skills' => ['JavaScript']]
        ]);

        $popular = $this->searchService->getPopularSearches('graduates');

        $this->assertIsArray($popular);
        $this->assertNotEmpty($popular);
    }

    public function test_can_track_search_analytics(): void
    {
        $user = $this->createUserWithRole('employer');
        
        $this->searchService->trackSearchAnalytics($user, [
            'type' => 'graduates',
            'criteria' => ['skills' => ['PHP']],
            'results_count' => 5,
            'execution_time' => 0.25
        ]);

        $this->assertDatabaseHas('search_analytics', [
            'user_id' => $user->id,
            'search_type' => 'graduates',
            'results_count' => 5
        ]);
    }

    public function test_can_get_search_history(): void
    {
        $user = $this->createUserWithRole('employer');
        
        // Track some searches
        $this->searchService->trackSearchAnalytics($user, [
            'type' => 'graduates',
            'criteria' => ['skills' => ['PHP']],
            'results_count' => 5
        ]);
        $this->searchService->trackSearchAnalytics($user, [
            'type' => 'graduates',
            'criteria' => ['skills' => ['JavaScript']],
            'results_count' => 3
        ]);

        $history = $this->searchService->getSearchHistory($user);

        $this->assertCount(2, $history);
        $this->assertEquals('graduates', $history[0]->search_type);
    }

    public function test_can_optimize_search_query(): void
    {
        $criteria = [
            'skills' => ['PHP', 'Laravel', 'JavaScript'],
            'employment_status' => 'unemployed',
            'graduation_year_from' => 2020,
            'graduation_year_to' => 2024
        ];

        $optimized = $this->searchService->optimizeSearchQuery('graduates', $criteria);

        $this->assertIsArray($optimized);
        $this->assertArrayHasKey('skills', $optimized);
        $this->assertArrayHasKey('employment_status', $optimized);
    }
}