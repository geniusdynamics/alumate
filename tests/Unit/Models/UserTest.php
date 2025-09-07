<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Graduate;
use App\Models\Employer;
use App\Models\StudentProfile;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user has correctly configured fillable attributes
     */
    public function test_fillable_attributes()
    {
        $user = new User();

        $fillable = $user->getFillable();

        // Test that key attributes are fillable
        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
        $this->assertContains('phone', $fillable);
        $this->assertContains('institution_id', $fillable);
        $this->assertContains('is_suspended', $fillable);
        $this->assertContains('last_login_at', $fillable);
    }

    /**
     * Test user has correctly configured casted attributes
     */
    public function test_cast_attributes()
    {
        $user = new User();

        $casts = $user->casts();

        // Test that key attributes are cast appropriately
        $this->assertArrayHasKey('email_verified_at', $casts);
        $this->assertEquals('datetime', $casts['email_verified_at']);

        $this->assertArrayHasKey('profile_data', $casts);
        $this->assertEquals('array', $casts['profile_data']);

        $this->assertArrayHasKey('preferences', $casts);
        $this->assertEquals('array', $casts['preferences']);

        $this->assertArrayHasKey('is_suspended', $casts);
        $this->assertEquals('boolean', $casts['is_suspended']);

        $this->assertArrayHasKey('is_active', $casts);
        $this->assertEquals('boolean', $casts['is_active']);
    }

    /**
     * Test user has correctly configured hidden attributes
     */
    public function test_hidden_attributes()
    {
        $user = new User();

        $hidden = $user->getHidden();

        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
        $this->assertContains('two_factor_secret', $hidden);
        $this->assertContains('two_factor_recovery_codes', $hidden);
    }

    /**
     * Test user belongs to institution relationship
     */
    public function test_belongs_to_institution_relationship()
    {
        $institution = Tenant::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);

        // Test relationship exists
        $this->assertInstanceOf(Tenant::class, $user->institution);

        // Test correct relation
        $this->assertEquals($institution->id, $user->institution->id);
    }

    /**
     * Test user has one graduate relationship
     */
    public function test_has_one_graduate_relationship()
    {
        $user = User::factory()->create();
        $graduate = Graduate::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(Graduate::class, $user->graduate);
        $this->assertEquals($graduate->id, $user->graduate->id);
    }

    /**
     * Test user has one employer relationship
     */
    public function test_has_one_employer_relationship()
    {
        $user = User::factory()->create();
        $employer = Employer::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(Employer::class, $user->employer);
        $this->assertEquals($employer->id, $user->employer->id);
    }

    /**
     * Test user has one student profile relationship
     */
    public function test_has_one_student_profile_relationship()
    {
        $user = User::factory()->create();
        $studentProfile = StudentProfile::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(StudentProfile::class, $user->studentProfile);
        $this->assertEquals($studentProfile->id, $user->studentProfile->id);
    }

    /**
     * Test user active scope filters correctly
     */
    public function test_active_scope()
    {
        // Create active, suspended, and inactive users
        User::factory()->create(['status' => 'active', 'is_suspended' => false]);
        User::factory()->create(['status' => 'active', 'is_suspended' => true]);
        User::factory()->create(['status' => 'inactive', 'is_suspended' => false]);

        $activeUsers = User::active()->get();

        $this->assertCount(1, $activeUsers);
        $this->assertEquals('active', $activeUsers->first()->status);
        $this->assertFalse($activeUsers->first()->is_suspended);
    }

    /**
     * Test user suspended scope
     */
    public function test_suspended_scope()
    {
        User::factory()->create(['is_suspended' => true]);
        User::factory()->create(['is_suspended' => false]);

        $suspendedUsers = User::suspended()->get();

        $this->assertCount(1, $suspendedUsers);
        $this->assertTrue($suspendedUsers->first()->is_suspended);
    }

    /**
     * Test user verified scope
     */
    public function test_verified_scope()
    {
        User::factory()->create(['email_verified_at' => now()]);
        User::factory()->create(['email_verified_at' => null]);

        $verifiedUsers = User::verified()->get();

        $this->assertCount(1, $verifiedUsers);
        $this->assertNotNull($verifiedUsers->first()->email_verified_at);
    }

    /**
     * Test accessors and mutators
     */
    public function test_full_name_accessor()
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        $this->assertEquals('John Doe', $user->full_name);
    }

    /**
     * Test avatar URL accessor default generator
     */
    public function test_avatar_url_accessor_default()
    {
        $user = User::factory()->create(['name' => 'John Doe', 'avatar' => null]);

        $avatarUrl = $user->avatar_url;

        $this->assertStringContains('ui-avatars.com', $avatarUrl);
        $this->assertStringContains('John+Doe', $avatarUrl);
    }

    /**
     * Test status badge accessor
     */
    public function test_status_badge_accessor()
    {
        // Test active status
        $activeUser = User::factory()->create(['status' => 'active', 'is_suspended' => false]);
        $badge = $activeUser->status_badge;
        $this->assertEquals('Active', $badge['text']);
        $this->assertEquals('green', $badge['color']);

        // Test suspended status
        $suspendedUser = User::factory()->create(['is_suspended' => true]);
        $badge = $suspendedUser->status_badge;
        $this->assertEquals('Suspended', $badge['text']);
        $this->assertEquals('red', $badge['color']);

        // Test inactive status
        $inactiveUser = User::factory()->create(['status' => 'inactive', 'is_suspended' => false]);
        $badge = $inactiveUser->status_badge;
        $this->assertEquals('Inactive', $badge['text']);
        $this->assertEquals('gray', $badge['color']);
    }

    /**
     * Test suspension methods
     */
    public function test_suspend_method()
    {
        $user = User::factory()->create(['is_suspended' => false]);
        $reason = 'Violation of terms';

        $user->suspend($reason);

        $this->assertTrue($user->is_suspended);
        $this->assertNotNull($user->suspended_at);
        $this->assertEquals($reason, $user->suspension_reason);
    }

    /**
     * Test unsuspend method
     */
    public function test_unsuspend_method()
    {
        $user = User::factory()->create([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspension_reason' => 'Test reason'
        ]);

        $user->unsuspend();

        $this->assertFalse($user->is_suspended);
        $this->assertNull($user->suspended_at);
        $this->assertNull($user->suspension_reason);
    }

    /**
     * Test update last activity method
     */
    public function test_update_last_activity_method()
    {
        $user = User::factory()->create(['last_activity_at' => null]);

        $beforeUpdate = $user->last_activity_at;
        $user->updateLastActivity();

        $this->assertNull($beforeUpdate);
        $this->assertNotNull($user->fresh()->last_activity_at);
    }

    /**
     * Test update last login method
     */
    public function test_update_last_login_method()
    {
        $user = User::factory()->create(['last_login_at' => null]);

        $beforeUpdate = $user->last_login_at;
        $user->updateLastLogin();

        $this->assertNull($beforeUpdate);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    /**
     * Test user type detection methods
     */
    public function test_is_alumni_method()
    {
        // Create a graduate user with role
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Graduate', 'guard_name' => 'web']);
        $user->assignRole($role);
        Graduate::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->isAlumni());
    }

    /**
     * Test is employer method
     */
    public function test_is_employer_method()
    {
        // Create an employer user with role
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Employer', 'guard_name' => 'web']);
        $user->assignRole($role);
        Employer::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->isEmployer());
    }

    /**
     * Test is student method
     */
    public function test_is_student_method()
    {
        // Create a student user with role
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Student', 'guard_name' => 'web']);
        $user->assignRole($role);
        StudentProfile::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->isStudent());
    }

    /**
     * Test get user type method
     */
    public function test_get_user_type_method()
    {
        // Test graduate user type
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Graduate', 'guard_name' => 'web']);
        $user->assignRole($role);
        Graduate::factory()->create(['user_id' => $user->id]);

        $this->assertEquals('alumni', $user->getUserType());
    }

    /**
     * Test profile completion percentage method
     */
    public function test_profile_completion_percentage()
    {
        // Create user with minimal data
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => null,
            'profile_data' => null
        ]);

        $percentage = $user->getProfileCompletionPercentage();

        // Should have 2 out of 3 required fields (name, email)
        $this->assertEquals(67, $percentage);

        // Add phone
        $user->update(['phone' => '123-456-7890']);
        $this->assertEquals(100, $user->fresh()->getProfileCompletionPercentage());
    }

    /**
     * Test recently active scope
     */
    public function test_recently_active_scope()
    {
        // Create users with different activity times
        User::factory()->create(['last_activity_at' => now()->subDays(10)]);
        User::factory()->create(['last_activity_at' => now()->subDays(35)]);

        $recentUsers = User::recentlyActive(20)->get();

        $this->assertCount(1, $recentUsers);
    }

    /**
     * Test has specific role method
     */
    public function test_has_specific_role_method()
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'TestRole', 'guard_name' => 'web']);
        $user->assignRole($role);

        $this->assertTrue($user->hasSpecificRole('TestRole'));
        $this->assertFalse($user->hasSpecificRole('NonExistentRole'));
    }

    /**
     * Test institution access control
     */
    public function test_can_access_institution_method()
    {
        $institution1 = Tenant::factory()->create();
        $institution2 = Tenant::factory()->create();

        $user = User::factory()->create(['institution_id' => $institution1->id]);

        // User can access own institution
        $this->assertTrue($user->canAccessInstitution($institution1->id));

        // User cannot access different institution
        $this->assertFalse($user->canAccessInstitution($institution2->id));

        // Super admin can access any institution
        $superAdmin = User::factory()->create();
        $superRole = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->assignRole($superRole);

        $this->assertTrue($superAdmin->canAccessInstitution($institution2->id));
    }

    /**
     * Test generate API token method
     */
    public function test_generate_api_token_method()
    {
        $user = User::factory()->create();

        $token = $user->generateApiToken();

        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // Verify token was created in database
        $this->assertCount(1, $user->tokens()->get());
    }

    /**
     * Test revoke all tokens method
     */
    public function test_revoke_all_tokens_method()
    {
        $user = User::factory()->create();

        // Create multiple tokens
        $user->createToken('token1');
        $user->createToken('token2');

        $this->assertCount(2, $user->tokens()->get());

        $user->revokeAllTokens();

        $this->assertCount(0, $user->tokens()->get());
    }

    /**
     * Test activity summary method
     */
    public function test_get_activity_summary_method()
    {
        $user = User::factory()->create();

        $summary = $user->getActivitySummary();

        // Test that method returns expected array structure
        $this->assertArrayHasKey('total_logins', $summary);
        $this->assertArrayHasKey('last_login', $summary);
        $this->assertArrayHasKey('total_activities', $summary);
        $this->assertArrayHasKey('most_active_day', $summary);

        // Test values are correct types
        $this->assertIsInt($summary['total_logins']);
        $this->assertIsInt($summary['total_activities']);
    }

    /**
     * Test is mentor method
     */
    public function test_is_mentor_method()
    {
        $user = User::factory()->create();

        // User without mentor profile
        $this->assertFalse($user->isMentor());

        // Create inactive mentor profile
        $mentorProfile = \App\Models\MentorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => false
        ]);

        $this->assertFalse($user->fresh()->isMentor());

        // Make mentor profile active
        $mentorProfile->update(['is_active' => true]);

        $this->assertTrue($user->fresh()->isMentor());
    }

    /**
     * Test can be mentor method
     */
    public function test_can_be_mentor_method()
    {
        $user = User::factory()->create();

        // User with education and career timeline can be mentor
        \App\Models\EducationHistory::factory()->create(['graduate_id' => $user->id]);
        \App\Models\CareerTimeline::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->canBeMentor());

        // User without education cannot be mentor
        $user2 = User::factory()->create();

        $this->assertFalse($user2->canBeMentor());
    }

    /**
     * Test searchable columns configuration
     */
    public function test_searchable_columns_configuration()
    {
        $user = new User();

        // Access the property using reflection since it's protected
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('searchableColumns');
        $property->setAccessible(true);
        $searchableColumns = $property->getValue($user);

        $this->assertContains('name', $searchableColumns);
        $this->assertContains('email', $searchableColumns);
        $this->assertContains('phone', $searchableColumns);
    }

    /**
     * Test sortable columns configuration
     */
    public function test_sortable_columns_configuration()
    {
        $user = new User();

        // Access the property using reflection since it's protected
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('sortableColumns');
        $property->setAccessible(true);
        $sortableColumns = $property->getValue($user);

        $this->assertContains('name', $sortableColumns);
        $this->assertContains('email', $sortableColumns);
        $this->assertContains('created_at', $sortableColumns);
        $this->assertContains('last_login_at', $sortableColumns);
        $this->assertContains('status', $sortableColumns);
    }
}