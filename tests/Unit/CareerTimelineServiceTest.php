<?php

namespace Tests\Unit;

use App\Models\CareerMilestone;
use App\Models\CareerTimeline;
use App\Models\User;
use App\Services\CareerTimelineService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareerTimelineServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CareerTimelineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->service = new CareerTimelineService;
    }

    public function test_get_timeline_for_user_returns_correct_structure()
    {
        $user = User::factory()->create();

        // Create career entries
        CareerTimeline::factory()->count(2)->create(['user_id' => $user->id]);

        // Create milestones
        CareerMilestone::factory()->count(3)->create(['user_id' => $user->id]);

        $result = $this->service->getTimelineForUser($user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('timeline', $result);
        $this->assertArrayHasKey('career_entries', $result);
        $this->assertArrayHasKey('milestones', $result);
        $this->assertArrayHasKey('progression', $result);
        $this->assertArrayHasKey('stats', $result);
        $this->assertArrayHasKey('can_edit', $result);

        $this->assertCount(2, $result['career_entries']);
        $this->assertCount(3, $result['milestones']);
        $this->assertTrue($result['can_edit']); // Same user
    }

    public function test_get_timeline_for_user_with_viewer_permissions()
    {
        $user = User::factory()->create();
        $viewer = User::factory()->create();

        // Create private milestone
        CareerMilestone::factory()->private()->create(['user_id' => $user->id]);

        // Create public milestone
        CareerMilestone::factory()->public()->create(['user_id' => $user->id]);

        $result = $this->service->getTimelineForUser($user, $viewer);

        $this->assertFalse($result['can_edit']); // Different user
        $this->assertCount(1, $result['milestones']); // Only public milestone visible
    }

    public function test_add_career_entry_creates_entry_successfully()
    {
        $user = User::factory()->create();

        $data = [
            'company' => 'Tech Corp',
            'title' => 'Software Engineer',
            'start_date' => '2023-01-01',
            'end_date' => '2023-12-31',
            'description' => 'Developed web applications',
            'is_current' => false,
            'achievements' => ['Built API', 'Led team'],
            'location' => 'San Francisco, CA',
            'industry' => 'Technology',
            'employment_type' => 'full-time',
        ];

        $entry = $this->service->addCareerEntry($data, $user);

        $this->assertInstanceOf(CareerTimeline::class, $entry);
        $this->assertEquals('Tech Corp', $entry->company);
        $this->assertEquals('Software Engineer', $entry->title);
        $this->assertEquals($user->id, $entry->user_id);
        $this->assertFalse($entry->is_current);
        $this->assertEquals(['Built API', 'Led team'], $entry->achievements);
    }

    public function test_add_career_entry_marks_as_current_and_updates_others()
    {
        $user = User::factory()->create();

        // Create existing current position
        $existingEntry = CareerTimeline::factory()->current()->create(['user_id' => $user->id]);

        $data = [
            'company' => 'New Corp',
            'title' => 'Senior Engineer',
            'start_date' => '2024-01-01',
            'is_current' => true,
        ];

        $newEntry = $this->service->addCareerEntry($data, $user);

        $this->assertTrue($newEntry->is_current);

        // Check that previous current position is no longer current
        $existingEntry->refresh();
        $this->assertFalse($existingEntry->is_current);
    }

    public function test_add_career_entry_validates_dates()
    {
        $user = User::factory()->create();

        $data = [
            'company' => 'Tech Corp',
            'title' => 'Engineer',
            'start_date' => '2023-12-31',
            'end_date' => '2023-01-01', // End before start
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('End date cannot be before start date');

        $this->service->addCareerEntry($data, $user);
    }

    public function test_add_career_entry_validates_future_start_date()
    {
        $user = User::factory()->create();

        $data = [
            'company' => 'Tech Corp',
            'title' => 'Engineer',
            'start_date' => now()->addYear()->format('Y-m-d'),
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Start date cannot be in the future');

        $this->service->addCareerEntry($data, $user);
    }

    public function test_update_career_entry_updates_successfully()
    {
        $user = User::factory()->create();
        $entry = CareerTimeline::factory()->create(['user_id' => $user->id]);

        $updateData = [
            'title' => 'Senior Software Engineer',
            'description' => 'Updated description',
        ];

        $updatedEntry = $this->service->updateCareerEntry($entry->id, $updateData, $user);

        $this->assertEquals('Senior Software Engineer', $updatedEntry->title);
        $this->assertEquals('Updated description', $updatedEntry->description);
    }

    public function test_update_career_entry_handles_current_position()
    {
        $user = User::factory()->create();
        $entry1 = CareerTimeline::factory()->current()->create(['user_id' => $user->id]);
        $entry2 = CareerTimeline::factory()->create(['user_id' => $user->id, 'is_current' => false]);

        $updateData = ['is_current' => true];

        $this->service->updateCareerEntry($entry2->id, $updateData, $user);

        // Check that entry1 is no longer current
        $entry1->refresh();
        $this->assertFalse($entry1->is_current);

        // Check that entry2 is now current
        $entry2->refresh();
        $this->assertTrue($entry2->is_current);
    }

    public function test_add_milestone_creates_milestone_successfully()
    {
        $user = User::factory()->create();

        $data = [
            'type' => CareerMilestone::TYPE_AWARD,
            'title' => 'Employee of the Year',
            'description' => 'Recognized for outstanding performance',
            'date' => '2023-06-01',
            'visibility' => CareerMilestone::VISIBILITY_PUBLIC,
            'company' => 'Tech Corp',
            'is_featured' => true,
        ];

        $milestone = $this->service->addMilestone($data, $user);

        $this->assertInstanceOf(CareerMilestone::class, $milestone);
        $this->assertEquals(CareerMilestone::TYPE_AWARD, $milestone->type);
        $this->assertEquals('Employee of the Year', $milestone->title);
        $this->assertEquals($user->id, $milestone->user_id);
        $this->assertTrue($milestone->is_featured);
    }

    public function test_calculate_career_progression_returns_correct_metrics()
    {
        $user = User::factory()->create();

        // Create career entries with different companies and dates
        CareerTimeline::factory()->create([
            'user_id' => $user->id,
            'company' => 'Company A',
            'start_date' => Carbon::parse('2020-01-01'),
            'end_date' => Carbon::parse('2021-12-31'),
            'is_current' => false,
        ]);

        CareerTimeline::factory()->create([
            'user_id' => $user->id,
            'company' => 'Company B',
            'start_date' => Carbon::parse('2022-01-01'),
            'end_date' => null,
            'is_current' => true,
        ]);

        $progression = $this->service->calculateCareerProgression($user);

        $this->assertIsArray($progression);
        $this->assertArrayHasKey('total_experience_months', $progression);
        $this->assertArrayHasKey('total_experience_years', $progression);
        $this->assertArrayHasKey('companies_count', $progression);
        $this->assertArrayHasKey('promotions_count', $progression);
        $this->assertArrayHasKey('career_growth_rate', $progression);
        $this->assertArrayHasKey('average_tenure_months', $progression);
        $this->assertArrayHasKey('industries', $progression);

        $this->assertEquals(2, $progression['companies_count']);
        $this->assertGreaterThan(0, $progression['total_experience_months']);
    }

    public function test_calculate_career_progression_handles_empty_timeline()
    {
        $user = User::factory()->create();

        $progression = $this->service->calculateCareerProgression($user);

        $this->assertEquals(0, $progression['total_experience_months']);
        $this->assertEquals(0, $progression['companies_count']);
        $this->assertEquals(0, $progression['promotions_count']);
        $this->assertEquals(0, $progression['career_growth_rate']);
        $this->assertEquals(0, $progression['average_tenure_months']);
        $this->assertEquals([], $progression['industries']);
    }

    public function test_suggest_career_goals_for_junior_developer()
    {
        $user = User::factory()->create();

        // Create entry for junior developer (< 2 years experience)
        CareerTimeline::factory()->create([
            'user_id' => $user->id,
            'start_date' => Carbon::parse('2023-06-01'),
            'is_current' => true,
        ]);

        $suggestions = $this->service->suggestCareerGoals($user);

        $this->assertIsArray($suggestions);
        $this->assertNotEmpty($suggestions);

        // Should suggest skill development for junior developers
        $skillSuggestion = collect($suggestions)->firstWhere('type', 'skill_development');
        $this->assertNotNull($skillSuggestion);
        $this->assertEquals('Build Core Skills', $skillSuggestion['title']);
    }

    public function test_suggest_career_goals_for_experienced_developer()
    {
        $user = User::factory()->create();

        // Create entry for experienced developer (> 5 years)
        CareerTimeline::factory()->create([
            'user_id' => $user->id,
            'start_date' => Carbon::parse('2018-01-01'),
            'is_current' => true,
        ]);

        $suggestions = $this->service->suggestCareerGoals($user);

        $this->assertIsArray($suggestions);
        $this->assertNotEmpty($suggestions);

        // Should suggest leadership for experienced developers
        $leadershipSuggestion = collect($suggestions)->firstWhere('type', 'leadership');
        $this->assertNotNull($leadershipSuggestion);
        $this->assertEquals('Leadership Development', $leadershipSuggestion['title']);
    }

    public function test_suggest_career_goals_for_long_tenure()
    {
        $user = User::factory()->create();

        // Create current position with long tenure (> 2 years)
        CareerTimeline::factory()->create([
            'user_id' => $user->id,
            'start_date' => Carbon::parse('2021-01-01'),
            'is_current' => true,
        ]);

        $suggestions = $this->service->suggestCareerGoals($user);

        // Should suggest career move for long tenure
        $careerMoveSuggestion = collect($suggestions)->firstWhere('type', 'career_move');
        $this->assertNotNull($careerMoveSuggestion);
        $this->assertEquals('Consider New Opportunities', $careerMoveSuggestion['title']);
    }

    public function test_timeline_combines_career_entries_and_milestones()
    {
        $user = User::factory()->create();

        // Create career entry
        CareerTimeline::factory()->create([
            'user_id' => $user->id,
            'start_date' => Carbon::parse('2023-01-01'),
        ]);

        // Create milestone
        CareerMilestone::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::parse('2023-06-01'),
        ]);

        $result = $this->service->getTimelineForUser($user);

        $this->assertNotEmpty($result['timeline']);

        // Timeline should contain both career entries and milestones
        $timelineTypes = collect($result['timeline'])->pluck('type')->unique();
        $this->assertContains('career_entry', $timelineTypes);
        $this->assertContains('milestone', $timelineTypes);
    }

    public function test_career_stats_calculation()
    {
        $user = User::factory()->create();

        // Create different types of milestones
        CareerMilestone::factory()->award()->create(['user_id' => $user->id]);
        CareerMilestone::factory()->certification()->create(['user_id' => $user->id]);
        CareerMilestone::factory()->achievement()->create(['user_id' => $user->id]);

        $result = $this->service->getTimelineForUser($user);
        $stats = $result['stats'];

        $this->assertEquals(3, $stats['total_milestones']);
        $this->assertEquals(1, $stats['awards_count']);
        $this->assertEquals(1, $stats['certifications_count']);
    }

    public function test_auto_milestone_creation_for_job_change()
    {
        $user = User::factory()->create();

        // Create previous job
        CareerTimeline::factory()->create([
            'user_id' => $user->id,
            'company' => 'Old Company',
            'start_date' => Carbon::parse('2022-01-01'),
            'end_date' => Carbon::parse('2023-12-31'),
            'is_current' => false,
        ]);

        // Add new job - should auto-create milestone
        $data = [
            'company' => 'New Company',
            'title' => 'Senior Engineer',
            'start_date' => '2024-01-01',
            'is_current' => true,
        ];

        $this->service->addCareerEntry($data, $user);

        // Check that milestone was created
        $milestone = CareerMilestone::where('user_id', $user->id)
            ->where('type', CareerMilestone::TYPE_JOB_CHANGE)
            ->first();

        $this->assertNotNull($milestone);
        $this->assertStringContains('New Company', $milestone->title);
    }

    public function test_promotion_detection()
    {
        $user = User::factory()->create();

        // Create previous job at same company
        CareerTimeline::factory()->create([
            'user_id' => $user->id,
            'company' => 'Tech Corp',
            'title' => 'Junior Engineer',
            'start_date' => Carbon::parse('2022-01-01'),
            'end_date' => Carbon::parse('2023-12-31'),
            'is_current' => false,
        ]);

        // Add promotion at same company
        $data = [
            'company' => 'Tech Corp',
            'title' => 'Senior Engineer',
            'start_date' => '2024-01-01',
            'is_current' => true,
        ];

        $this->service->addCareerEntry($data, $user);

        // Check that promotion milestone was created
        $milestone = CareerMilestone::where('user_id', $user->id)
            ->where('type', CareerMilestone::TYPE_PROMOTION)
            ->first();

        $this->assertNotNull($milestone);
        $this->assertStringContains('Senior Engineer', $milestone->title);
    }
}
