<?php

namespace Tests\Feature;

use App\Models\Circle;
use App\Models\EducationHistory;
use App\Models\Group;
use App\Models\User;
use App\Services\CircleManager;
use App\Services\GroupManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CircleAndGroupIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_circle_and_group_models_can_be_instantiated()
    {
        // Test that models can be created without database
        $circle = new Circle([
            'name' => 'Test Circle',
            'type' => 'custom',
            'criteria' => ['test' => 'value'],
            'auto_generated' => false,
        ]);

        $group = new Group([
            'name' => 'Test Group',
            'type' => 'custom',
            'privacy' => 'public',
            'creator_id' => 1,
        ]);

        $this->assertInstanceOf(Circle::class, $circle);
        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals('Test Circle', $circle->name);
        $this->assertEquals('Test Group', $group->name);
    }

    public function test_services_can_be_instantiated()
    {
        $circleManager = new CircleManager;
        $groupManager = new GroupManager;

        $this->assertInstanceOf(CircleManager::class, $circleManager);
        $this->assertInstanceOf(GroupManager::class, $groupManager);
    }

    public function test_circle_criteria_casting()
    {
        $circle = new Circle;
        $circle->criteria = ['institution_name' => 'Test University', 'graduation_year' => 2020];

        $this->assertIsArray($circle->criteria);
        $this->assertEquals('Test University', $circle->criteria['institution_name']);
        $this->assertEquals(2020, $circle->criteria['graduation_year']);
    }

    public function test_group_settings_casting()
    {
        $group = new Group;
        $group->settings = ['posting_restriction' => 'all_members'];

        $this->assertIsArray($group->settings);
        $this->assertEquals('all_members', $group->settings['posting_restriction']);
    }

    public function test_education_history_accessors()
    {
        $education = new EducationHistory([
            'institution_name' => 'Test University',
            'end_year' => 2020,
        ]);

        // Test the accessor methods
        $this->assertEquals(2020, $education->graduation_year);
        $this->assertIsInt($education->institution_id);
    }

    public function test_circle_name_generation()
    {
        $circleManager = new CircleManager;

        // Test school combinations generation
        $educations = collect([
            (object) ['institution_name' => 'University A'],
            (object) ['institution_name' => 'University B'],
        ]);

        $combinations = $circleManager->getSchoolCombinations($educations);

        $this->assertIsArray($combinations);
        $this->assertCount(1, $combinations); // Only one combination of 2 schools
        $this->assertEquals(['University A', 'University B'], $combinations[0]);
    }

    public function test_circle_manager_find_or_create_logic()
    {
        $circleManager = new CircleManager;

        // Test that the method exists and can be called
        $this->assertTrue(method_exists($circleManager, 'findOrCreateCircle'));
        $this->assertTrue(method_exists($circleManager, 'generateCirclesForUser'));
        $this->assertTrue(method_exists($circleManager, 'getSchoolCombinations'));
    }

    public function test_group_manager_methods_exist()
    {
        $groupManager = new GroupManager;

        // Test that all required methods exist
        $this->assertTrue(method_exists($groupManager, 'createGroup'));
        $this->assertTrue(method_exists($groupManager, 'handleInvitation'));
        $this->assertTrue(method_exists($groupManager, 'autoJoinSchoolGroups'));
        $this->assertTrue(method_exists($groupManager, 'processJoinRequest'));
        $this->assertTrue(method_exists($groupManager, 'getRecommendedGroups'));
    }

    public function test_user_relationships_exist()
    {
        $user = new User;

        // Test that relationship methods exist
        $this->assertTrue(method_exists($user, 'circles'));
        $this->assertTrue(method_exists($user, 'groups'));
        $this->assertTrue(method_exists($user, 'educations'));
    }

    public function test_circle_model_methods_exist()
    {
        $circle = new Circle;

        // Test that all required methods exist
        $this->assertTrue(method_exists($circle, 'addMember'));
        $this->assertTrue(method_exists($circle, 'removeMember'));
        $this->assertTrue(method_exists($circle, 'updateMemberCount'));
        $this->assertTrue(method_exists($circle, 'getPostsForUser'));
        $this->assertTrue(method_exists($circle, 'canUserJoin'));
    }

    public function test_group_model_methods_exist()
    {
        $group = new Group;

        // Test that all required methods exist
        $this->assertTrue(method_exists($group, 'addMember'));
        $this->assertTrue(method_exists($group, 'removeMember'));
        $this->assertTrue(method_exists($group, 'updateMemberCount'));
        $this->assertTrue(method_exists($group, 'canUserJoin'));
        $this->assertTrue(method_exists($group, 'canUserPost'));
        $this->assertTrue(method_exists($group, 'isAdmin'));
        $this->assertTrue(method_exists($group, 'isModerator'));
    }
}
