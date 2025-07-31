<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Skill;
use App\Models\UserSkill;
use App\Models\SkillEndorsement;
use App\Models\LearningResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $endorser;
    protected Skill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->endorser = User::factory()->create();
        $this->skill = Skill::factory()->create([
            'name' => 'JavaScript',
            'category' => 'Technical',
            'is_verified' => true,
        ]);
    }

    public function test_user_can_add_skill()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/users/skills', [
                'skill_name' => 'React',
                'category' => 'Technical',
                'proficiency_level' => 'Intermediate',
                'years_experience' => 3,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user_skill' => [
                    'id',
                    'proficiency_level',
                    'years_experience',
                    'skill' => ['name', 'category']
                ]
            ]);

        $this->assertDatabaseHas('skills', ['name' => 'React']);
        $this->assertDatabaseHas('user_skills', [
            'user_id' => $this->user->id,
            'proficiency_level' => 'Intermediate',
            'years_experience' => 3,
        ]);
    }

    public function test_user_can_get_their_skills()
    {
        $userSkill = UserSkill::factory()->create([
            'user_id' => $this->user->id,
            'skill_id' => $this->skill->id,
            'proficiency_level' => 'Advanced',
            'years_experience' => 5,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->user->id}/skills");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'skills' => [
                    '*' => [
                        'id',
                        'proficiency_level',
                        'years_experience',
                        'endorsed_count',
                        'skill' => ['name', 'category'],
                        'endorsements'
                    ]
                ],
                'total_skills',
                'total_endorsements'
            ]);
    }

    public function test_user_can_endorse_skill()
    {
        $userSkill = UserSkill::factory()->create([
            'user_id' => $this->user->id,
            'skill_id' => $this->skill->id,
        ]);

        $response = $this->actingAs($this->endorser)
            ->postJson('/api/skills/endorse', [
                'user_skill_id' => $userSkill->id,
                'message' => 'Great JavaScript skills!',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'endorsement' => [
                    'id',
                    'message',
                    'user_skill' => ['skill' => ['name']],
                    'endorser' => ['name']
                ]
            ]);

        $this->assertDatabaseHas('skill_endorsements', [
            'user_skill_id' => $userSkill->id,
            'endorser_id' => $this->endorser->id,
            'message' => 'Great JavaScript skills!',
        ]);

        // Check that endorsed_count was incremented
        $this->assertEquals(1, $userSkill->fresh()->endorsed_count);
    }

    public function test_user_cannot_endorse_own_skill()
    {
        $userSkill = UserSkill::factory()->create([
            'user_id' => $this->user->id,
            'skill_id' => $this->skill->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/skills/endorse', [
                'user_skill_id' => $userSkill->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Users cannot endorse their own skills'
            ]);
    }

    public function test_user_can_search_skills()
    {
        Skill::factory()->create(['name' => 'JavaScript', 'is_verified' => true]);
        Skill::factory()->create(['name' => 'Java', 'is_verified' => true]);
        Skill::factory()->create(['name' => 'Python', 'is_verified' => true]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/skills/search?query=Java');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'skills' => [
                    '*' => ['id', 'name', 'category', 'is_verified']
                ],
                'count'
            ]);

        $skills = $response->json('skills');
        $this->assertCount(2, $skills); // JavaScript and Java
    }

    public function test_user_can_get_skill_suggestions()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/skills/suggestions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'suggestions',
                'count'
            ]);
    }

    public function test_user_can_create_learning_resource()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/learning-resources', [
                'title' => 'Advanced JavaScript Course',
                'description' => 'Learn advanced JavaScript concepts',
                'type' => 'Course',
                'url' => 'https://example.com/course',
                'skill_ids' => [$this->skill->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'resource' => [
                    'id',
                    'title',
                    'description',
                    'type',
                    'url',
                    'creator' => ['name'],
                    'skills'
                ]
            ]);

        $this->assertDatabaseHas('learning_resources', [
            'title' => 'Advanced JavaScript Course',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_user_can_rate_learning_resource()
    {
        $resource = LearningResource::factory()->create([
            'created_by' => $this->endorser->id,
            'skill_ids' => [$this->skill->id],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/learning-resources/{$resource->id}/rate", [
                'rating' => 4.5,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'resource' => ['rating', 'rating_count']
            ]);

        $resource->refresh();
        $this->assertEquals(4.5, $resource->rating);
        $this->assertEquals(1, $resource->rating_count);
    }

    public function test_user_can_get_skill_progression()
    {
        $userSkill = UserSkill::factory()->create([
            'user_id' => $this->user->id,
            'skill_id' => $this->skill->id,
        ]);

        // Create some endorsements for progression data
        SkillEndorsement::factory()->count(3)->create([
            'user_skill_id' => $userSkill->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/skills/{$this->skill->id}/progression");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'skill' => ['name'],
                'current_level',
                'years_experience',
                'total_endorsements',
                'progression'
            ]);
    }

    public function test_user_can_get_learning_recommendations()
    {
        $userSkill = UserSkill::factory()->create([
            'user_id' => $this->user->id,
            'skill_id' => $this->skill->id,
            'proficiency_level' => 'Beginner',
        ]);

        // Create some learning resources
        LearningResource::factory()->count(3)->create([
            'skill_ids' => [$this->skill->id],
            'type' => 'Course',
            'rating' => 4.0,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/skills/{$this->skill->id}/recommendations");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'recommendations' => [
                    '*' => [
                        'id',
                        'title',
                        'type',
                        'rating'
                    ]
                ],
                'count'
            ]);
    }

    public function test_user_can_get_skills_gap_analysis()
    {
        // Add some skills to user
        UserSkill::factory()->create([
            'user_id' => $this->user->id,
            'skill_id' => $this->skill->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/skills/gap-analysis');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_skills',
                'recommended_skills',
                'skill_gaps',
                'gap_count'
            ]);
    }

    public function test_skill_endorsement_increments_count()
    {
        $userSkill = UserSkill::factory()->create([
            'user_id' => $this->user->id,
            'skill_id' => $this->skill->id,
            'endorsed_count' => 0,
        ]);

        SkillEndorsement::create([
            'user_skill_id' => $userSkill->id,
            'endorser_id' => $this->endorser->id,
        ]);

        $this->assertEquals(1, $userSkill->fresh()->endorsed_count);
    }

    public function test_skill_endorsement_deletion_decrements_count()
    {
        $userSkill = UserSkill::factory()->create([
            'user_id' => $this->user->id,
            'skill_id' => $this->skill->id,
            'endorsed_count' => 1,
        ]);

        $endorsement = SkillEndorsement::create([
            'user_skill_id' => $userSkill->id,
            'endorser_id' => $this->endorser->id,
        ]);

        $endorsement->delete();

        $this->assertEquals(0, $userSkill->fresh()->endorsed_count);
    }
}