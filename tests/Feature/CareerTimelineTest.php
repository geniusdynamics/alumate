<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CareerTimeline;
use App\Models\CareerMilestone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class CareerTimelineTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_user_can_create_career_entry()
    {
        $user = User::factory()->create();
        
        $careerData = [
            'company' => 'Tech Corp',
            'title' => 'Software Engineer',
            'start_date' => '2023-01-01',
            'end_date' => '2024-01-01',
            'description' => 'Developed web applications',
            'is_current' => false,
            'achievements' => ['Built 5 major features', 'Led team of 3 developers'],
            'location' => 'San Francisco, CA',
            'industry' => 'Technology',
            'employment_type' => 'full-time'
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/career', $careerData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Career entry added successfully'
            ]);

        $this->assertDatabaseHas('career_timelines', [
            'user_id' => $user->id,
            'company' => 'Tech Corp',
            'title' => 'Software Engineer'
        ]);
    }

    public function test_user_can_update_career_entry()
    {
        $user = User::factory()->create();
        $careerEntry = CareerTimeline::factory()->create([
            'user_id' => $user->id,
            'company' => 'Old Corp',
            'title' => 'Junior Developer'
        ]);

        $updateData = [
            'company' => 'New Corp',
            'title' => 'Senior Developer',
            'description' => 'Updated description'
        ];

        $response = $this->actingAs($user)
            ->putJson("/api/career/{$careerEntry->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Career entry updated successfully'
            ]);

        $this->assertDatabaseHas('career_timelines', [
            'id' => $careerEntry->id,
            'company' => 'New Corp',
            'title' => 'Senior Developer'
        ]);
    }

    public function test_user_can_delete_career_entry()
    {
        $user = User::factory()->create();
        $careerEntry = CareerTimeline::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/career/{$careerEntry->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Career entry deleted successfully'
            ]);

        $this->assertSoftDeleted('career_timelines', ['id' => $careerEntry->id]);
    }

    public function test_user_cannot_modify_other_users_career_entry()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $careerEntry = CareerTimeline::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)
            ->putJson("/api/career/{$careerEntry->id}", ['title' => 'Hacker']);

        $response->assertStatus(404);
    }

    public function test_user_can_create_milestone()
    {
        $user = User::factory()->create();
        
        $milestoneData = [
            'type' => 'promotion',
            'title' => 'Promoted to Senior Developer',
            'description' => 'Got promoted after excellent performance',
            'date' => '2024-01-01',
            'visibility' => 'public',
            'company' => 'Tech Corp',
            'is_featured' => true
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/milestones', $milestoneData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Milestone added successfully'
            ]);

        $this->assertDatabaseHas('career_milestones', [
            'user_id' => $user->id,
            'type' => 'promotion',
            'title' => 'Promoted to Senior Developer'
        ]);
    }

    public function test_user_can_view_career_timeline()
    {
        $user = User::factory()->create();
        
        // Create career entries
        CareerTimeline::factory()->count(2)->create(['user_id' => $user->id]);
        
        // Create milestones
        CareerMilestone::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/users/{$user->id}/career");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'timeline',
                    'career_entries',
                    'milestones',
                    'progression',
                    'stats',
                    'can_edit'
                ]
            ]);
    }

    public function test_career_progression_calculation()
    {
        $user = User::factory()->create();
        
        // Create career entries with different durations
        CareerTimeline::factory()->create([
            'user_id' => $user->id,
            'company' => 'Company A',
            'start_date' => Carbon::now()->subYears(3),
            'end_date' => Carbon::now()->subYears(2),
            'is_current' => false
        ]);
        
        CareerTimeline::factory()->create([
            'user_id' => $user->id,
            'company' => 'Company B',
            'start_date' => Carbon::now()->subYears(2),
            'end_date' => null,
            'is_current' => true
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/users/{$user->id}/career");

        $response->assertStatus(200);
        
        $progression = $response->json('data.progression');
        
        $this->assertArrayHasKey('total_experience_years', $progression);
        $this->assertArrayHasKey('companies_count', $progression);
        $this->assertArrayHasKey('promotions_count', $progression);
        $this->assertEquals(2, $progression['companies_count']);
    }

    public function test_milestone_visibility_controls()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Create milestones with different visibility levels
        CareerMilestone::factory()->create([
            'user_id' => $user1->id,
            'visibility' => 'public',
            'title' => 'Public Milestone'
        ]);
        
        CareerMilestone::factory()->create([
            'user_id' => $user1->id,
            'visibility' => 'private',
            'title' => 'Private Milestone'
        ]);

        // User2 viewing User1's timeline should only see public milestones
        $response = $this->actingAs($user2)
            ->getJson("/api/users/{$user1->id}/career");

        $response->assertStatus(200);
        
        $milestones = $response->json('data.milestones');
        $this->assertCount(1, $milestones);
        $this->assertEquals('Public Milestone', $milestones[0]['title']);
    }

    public function test_current_position_updates_previous_current()
    {
        $user = User::factory()->create();
        
        // Create a current position
        $currentPosition = CareerTimeline::factory()->create([
            'user_id' => $user->id,
            'is_current' => true
        ]);

        // Add a new current position
        $newPositionData = [
            'company' => 'New Company',
            'title' => 'New Title',
            'start_date' => '2024-01-01',
            'is_current' => true,
            'employment_type' => 'full-time'
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/career', $newPositionData);

        $response->assertStatus(201);

        // Check that the previous current position is no longer current
        $currentPosition->refresh();
        $this->assertFalse($currentPosition->is_current);

        // Check that the new position is current
        $this->assertDatabaseHas('career_timelines', [
            'user_id' => $user->id,
            'company' => 'New Company',
            'is_current' => true
        ]);
    }

    public function test_career_suggestions_are_generated()
    {
        $user = User::factory()->create();
        
        // Create some career history
        CareerTimeline::factory()->create([
            'user_id' => $user->id,
            'start_date' => Carbon::now()->subMonths(6),
            'is_current' => true
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/career/suggestions');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'title',
                        'description',
                        'priority'
                    ]
                ]
            ]);
    }

    public function test_career_options_endpoint()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/career/options');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'milestone_types',
                    'visibility_options',
                    'employment_types'
                ]
            ]);
    }
}