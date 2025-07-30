<?php

namespace Tests\Unit\Services;

use App\Models\Circle;
use App\Models\User;
use App\Models\EducationHistory;
use App\Services\CircleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CircleManagerTest extends TestCase
{
    use RefreshDatabase;

    protected CircleManager $circleManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->circleManager = new CircleManager();
    }

    public function test_generates_school_year_circle_for_user()
    {
        $user = User::factory()->create();
        
        // Create education history
        EducationHistory::factory()->create([
            'graduate_id' => $user->id,
            'institution_name' => 'Test University',
            'end_year' => 2020,
        ]);

        $circles = $this->circleManager->generateCirclesForUser($user);

        $this->assertCount(1, $circles);
        $circle = $circles->first();
        $this->assertEquals('school_year', $circle->type);
        $this->assertEquals('Test University Class of 2020', $circle->name);
        $this->assertTrue($circle->auto_generated);
    }

    public function test_generates_multi_school_circles_for_user_with_multiple_educations()
    {
        $user = User::factory()->create();
        
        // Create multiple education histories
        EducationHistory::factory()->create([
            'graduate_id' => $user->id,
            'institution_name' => 'University A',
            'end_year' => 2018,
        ]);
        
        EducationHistory::factory()->create([
            'graduate_id' => $user->id,
            'institution_name' => 'University B',
            'end_year' => 2020,
        ]);

        $circles = $this->circleManager->generateCirclesForUser($user);

        // Should have 2 school-year circles + 1 multi-school circle
        $this->assertCount(3, $circles);
        
        $multiSchoolCircle = $circles->where('type', 'multi_school')->first();
        $this->assertNotNull($multiSchoolCircle);
        $this->assertStringContains('Multi-School Alumni', $multiSchoolCircle->name);
    }

    public function test_finds_existing_circle_instead_of_creating_duplicate()
    {
        // Create an existing circle
        $existingCircle = Circle::create([
            'name' => 'Test University Class of 2020',
            'type' => 'school_year',
            'criteria' => [
                'institution_name' => 'Test University',
                'graduation_year' => 2020
            ],
            'auto_generated' => true,
        ]);

        $foundCircle = $this->circleManager->findOrCreateCircle([
            'type' => 'school_year',
            'institution_name' => 'Test University',
            'graduation_year' => 2020,
        ]);

        $this->assertEquals($existingCircle->id, $foundCircle->id);
        $this->assertEquals(1, Circle::count()); // No new circle created
    }

    public function test_creates_new_circle_when_none_exists()
    {
        $circle = $this->circleManager->findOrCreateCircle([
            'type' => 'school_year',
            'institution_name' => 'New University',
            'graduation_year' => 2021,
        ]);

        $this->assertInstanceOf(Circle::class, $circle);
        $this->assertEquals('New University Class of 2021', $circle->name);
        $this->assertEquals(1, Circle::count());
    }

    public function test_generates_school_combinations_correctly()
    {
        $educations = collect([
            (object) ['institution_name' => 'University A'],
            (object) ['institution_name' => 'University B'],
            (object) ['institution_name' => 'University C'],
        ]);

        $combinations = $this->circleManager->getSchoolCombinations($educations);

        // Should have combinations of 2 and 3 schools
        // 2-school combinations: AB, AC, BC (3 combinations)
        // 3-school combinations: ABC (1 combination)
        // Total: 4 combinations
        $this->assertCount(4, $combinations);
    }

    public function test_assigns_user_to_circles()
    {
        $user = User::factory()->create();
        $circles = Circle::factory()->count(3)->create();

        $this->circleManager->assignUserToCircles($user, $circles);

        foreach ($circles as $circle) {
            $this->assertTrue($circle->users()->where('user_id', $user->id)->exists());
        }
    }

    public function test_updates_circles_for_user()
    {
        $user = User::factory()->create();
        
        // Create initial education
        EducationHistory::factory()->create([
            'graduate_id' => $user->id,
            'institution_name' => 'Old University',
            'end_year' => 2018,
        ]);

        // Generate initial circles
        $this->circleManager->generateCirclesForUser($user);
        $initialCircleCount = $user->circles()->count();

        // Add new education
        EducationHistory::factory()->create([
            'graduate_id' => $user->id,
            'institution_name' => 'New University',
            'end_year' => 2020,
        ]);

        // Update circles
        $this->circleManager->updateCirclesForUser($user);

        // Should have more circles now (2 school-year + 1 multi-school)
        $this->assertGreaterThan($initialCircleCount, $user->fresh()->circles()->count());
    }

    public function test_gets_circle_statistics()
    {
        Circle::factory()->count(5)->create(['auto_generated' => true]);
        Circle::factory()->count(3)->create(['auto_generated' => false]);
        Circle::factory()->create(['type' => 'school_year']);
        Circle::factory()->create(['type' => 'multi_school']);

        $stats = $this->circleManager->getCircleStatistics();

        $this->assertEquals(10, $stats['total_circles']);
        $this->assertEquals(5, $stats['auto_generated_circles']);
        $this->assertEquals(3, $stats['custom_circles']);
        $this->assertArrayHasKey('average_members_per_circle', $stats);
        $this->assertArrayHasKey('largest_circle_size', $stats);
    }
}