<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Course;
use App\Models\LearningProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningProgressTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock tenant context
        session(['tenant_id' => 'test-tenant']);

        $this->user = User::factory()->create();
        $this->course = Course::factory()->create(['tenant_id' => 'test-tenant']);
    }

    public function test_can_create_learning_progress(): void
    {
        $progress = LearningProgress::create([
            'tenant_id' => 'test-tenant',
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'modules_completed' => 3,
            'total_score' => 85.5,
            'engagement_score' => 75.0,
            'certified' => false,
        ]);

        $this->assertInstanceOf(LearningProgress::class, $progress);
        $this->assertEquals('test-tenant', $progress->tenant_id);
        $this->assertEquals($this->user->id, $progress->user_id);
        $this->assertEquals($this->course->id, $progress->course_id);
        $this->assertEquals(3, $progress->modules_completed);
        $this->assertEquals(85.5, $progress->total_score);
        $this->assertEquals(75.0, $progress->engagement_score);
        $this->assertFalse($progress->certified);
    }

    public function test_belongs_to_user_relationship(): void
    {
        $progress = LearningProgress::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->assertInstanceOf(User::class, $progress->user);
        $this->assertEquals($this->user->id, $progress->user->id);
    }

    public function test_belongs_to_course_relationship(): void
    {
        $progress = LearningProgress::factory()->create([
            'course_id' => $this->course->id
        ]);

        $this->assertInstanceOf(Course::class, $progress->course);
        $this->assertEquals($this->course->id, $progress->course->id);
    }

    public function test_scope_by_tenant(): void
    {
        LearningProgress::factory()->create(['tenant_id' => 'tenant1']);
        LearningProgress::factory()->create(['tenant_id' => 'tenant1']);
        LearningProgress::factory()->create(['tenant_id' => 'tenant2']);

        $tenant1Progress = LearningProgress::byTenant('tenant1')->get();
        $tenant2Progress = LearningProgress::byTenant('tenant2')->get();

        $this->assertCount(2, $tenant1Progress);
        $this->assertCount(1, $tenant2Progress);
        $this->assertEquals('tenant1', $tenant1Progress->first()->tenant_id);
        $this->assertEquals('tenant2', $tenant2Progress->first()->tenant_id);
    }

    public function test_scope_by_user(): void
    {
        $user2 = User::factory()->create();

        LearningProgress::factory()->create(['user_id' => $this->user->id]);
        LearningProgress::factory()->create(['user_id' => $this->user->id]);
        LearningProgress::factory()->create(['user_id' => $user2->id]);

        $user1Progress = LearningProgress::byUser($this->user->id)->get();
        $user2Progress = LearningProgress::byUser($user2->id)->get();

        $this->assertCount(2, $user1Progress);
        $this->assertCount(1, $user2Progress);
    }

    public function test_scope_by_course(): void
    {
        $course2 = Course::factory()->create(['tenant_id' => 'test-tenant']);

        LearningProgress::factory()->create(['course_id' => $this->course->id]);
        LearningProgress::factory()->create(['course_id' => $course2->id]);

        $course1Progress = LearningProgress::byCourse($this->course->id)->get();
        $course2Progress = LearningProgress::byCourse($course2->id)->get();

        $this->assertCount(1, $course1Progress);
        $this->assertCount(1, $course2Progress);
    }

    public function test_scope_eligible_for_certification(): void
    {
        // Eligible progress
        LearningProgress::factory()->create([
            'total_score' => 85.0,
            'modules_completed' => 6,
            'certified' => false
        ]);

        // Ineligible - low score
        LearningProgress::factory()->create([
            'total_score' => 70.0,
            'modules_completed' => 6,
            'certified' => false
        ]);

        // Ineligible - few modules
        LearningProgress::factory()->create([
            'total_score' => 85.0,
            'modules_completed' => 3,
            'certified' => false
        ]);

        // Already certified
        LearningProgress::factory()->create([
            'total_score' => 85.0,
            'modules_completed' => 6,
            'certified' => true
        ]);

        $eligible = LearningProgress::eligibleForCertification()->get();

        $this->assertCount(1, $eligible);
        $this->assertEquals(85.0, $eligible->first()->total_score);
        $this->assertEquals(6, $eligible->first()->modules_completed);
        $this->assertFalse($eligible->first()->certified);
    }

    public function test_scope_certified(): void
    {
        LearningProgress::factory()->create(['certified' => true]);
        LearningProgress::factory()->create(['certified' => true]);
        LearningProgress::factory()->create(['certified' => false]);

        $certified = LearningProgress::certified()->get();

        $this->assertCount(2, $certified);
        $certified->each(function ($progress) {
            $this->assertTrue($progress->certified);
        });
    }

    public function test_meets_certification_criteria(): void
    {
        $progress = LearningProgress::factory()->create([
            'total_score' => 88.0,
            'modules_completed' => 7,
            'certified' => false
        ]);

        // Test with default criteria
        $this->assertTrue($progress->meetsCertificationCriteria());

        // Test with custom criteria
        $this->assertTrue($progress->meetsCertificationCriteria([
            'min_score' => 85,
            'modules_completed' => 5
        ]));

        // Test failing criteria
        $this->assertFalse($progress->meetsCertificationCriteria([
            'min_score' => 90,
            'modules_completed' => 5
        ]));
    }

    public function test_mark_as_certified(): void
    {
        $progress = LearningProgress::factory()->create(['certified' => false]);

        $result = $progress->markAsCertified();

        $this->assertTrue($result);
        $this->assertTrue($progress->fresh()->certified);
    }

    public function test_get_completion_percentage_attribute(): void
    {
        // Mock course with modules_count
        $course = Course::factory()->create([
            'tenant_id' => 'test-tenant',
            'metadata' => ['modules_count' => 10]
        ]);

        $progress = LearningProgress::factory()->create([
            'course_id' => $course->id,
            'modules_completed' => 7
        ]);

        $this->assertEquals(70.0, $progress->completion_percentage);
    }

    public function test_get_completion_percentage_with_zero_modules(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => 'test-tenant',
            'metadata' => [] // No modules_count
        ]);

        $progress = LearningProgress::factory()->create([
            'course_id' => $course->id,
            'modules_completed' => 5
        ]);

        $this->assertEquals(0.0, $progress->completion_percentage);
    }

    public function test_casts_work_correctly(): void
    {
        $progress = LearningProgress::factory()->create([
            'modules_completed' => '5', // String that should be cast to int
            'total_score' => '85.5', // String that should be cast to decimal
            'engagement_score' => '75.0',
            'certified' => '1' // String that should be cast to boolean
        ]);

        $this->assertIsInt($progress->modules_completed);
        $this->assertEquals(5, $progress->modules_completed);
        $this->assertIsFloat($progress->total_score);
        $this->assertEquals(85.5, $progress->total_score);
        $this->assertIsFloat($progress->engagement_score);
        $this->assertEquals(75.0, $progress->engagement_score);
        $this->assertIsBool($progress->certified);
        $this->assertTrue($progress->certified);
    }

    public function test_fillable_attributes(): void
    {
        $fillable = [
            'tenant_id',
            'user_id',
            'course_id',
            'modules_completed',
            'total_score',
            'engagement_score',
            'certified'
        ];

        $progress = new LearningProgress();

        foreach ($fillable as $attribute) {
            $this->assertContains($attribute, $progress->getFillable());
        }
    }

    public function test_unique_constraint_on_tenant_user_course(): void
    {
        // Create first progress record
        LearningProgress::factory()->create([
            'tenant_id' => 'test-tenant',
            'user_id' => $this->user->id,
            'course_id' => $this->course->id
        ]);

        // Attempt to create duplicate - should work with updateOrCreate
        $duplicate = LearningProgress::updateOrCreate(
            [
                'tenant_id' => 'test-tenant',
                'user_id' => $this->user->id,
                'course_id' => $this->course->id
            ],
            ['modules_completed' => 5]
        );

        $this->assertEquals(5, $duplicate->modules_completed);

        // Verify only one record exists
        $count = LearningProgress::where('tenant_id', 'test-tenant')
            ->where('user_id', $this->user->id)
            ->where('course_id', $this->course->id)
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_handles_null_values_gracefully(): void
    {
        $progress = LearningProgress::factory()->create([
            'modules_completed' => null,
            'total_score' => null,
            'engagement_score' => null
        ]);

        $this->assertNull($progress->modules_completed);
        $this->assertNull($progress->total_score);
        $this->assertNull($progress->engagement_score);
        $this->assertEquals(0.0, $progress->completion_percentage);
    }
}