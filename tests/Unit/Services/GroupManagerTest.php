<?php

namespace Tests\Unit\Services;

use App\Models\EducationHistory;
use App\Models\Group;
use App\Models\Tenant;
use App\Models\User;
use App\Services\GroupManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupManagerTest extends TestCase
{
    use RefreshDatabase;

    protected GroupManager $groupManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->groupManager = new GroupManager;
    }

    public function test_creates_group_with_creator_as_admin()
    {
        $creator = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $groupData = [
            'name' => 'Test Group',
            'description' => 'A test group',
            'type' => 'custom',
            'privacy' => 'public',
            'institution_id' => $tenant->id,
            'settings' => ['test' => 'value'],
        ];

        $group = $this->groupManager->createGroup($groupData, $creator);

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals('Test Group', $group->name);
        $this->assertEquals($creator->id, $group->creator_id);
        $this->assertEquals(1, $group->member_count);

        // Creator should be admin
        $this->assertTrue($group->isAdmin($creator));
    }

    public function test_handles_school_group_invitation_with_auto_join()
    {
        $tenant = Tenant::factory()->create(['name' => 'Test University']);
        $group = Group::factory()->create([
            'type' => 'school',
            'institution_id' => $tenant->id,
        ]);

        $inviter = User::factory()->create();
        $user = User::factory()->create();

        // Create education history for the user at this institution
        EducationHistory::factory()->create([
            'graduate_id' => $user->id,
            'institution_name' => 'Test University',
        ]);

        $result = $this->groupManager->handleInvitation($group, $user, $inviter);

        $this->assertTrue($result);
        $this->assertTrue($group->users()->where('user_id', $user->id)->exists());
    }

    public function test_auto_joins_user_to_school_groups()
    {
        $tenant = Tenant::factory()->create(['name' => 'Test University']);
        $schoolGroup = Group::factory()->create([
            'type' => 'school',
            'privacy' => 'public',
            'institution_id' => $tenant->id,
        ]);

        $user = User::factory()->create();

        // Create education history
        EducationHistory::factory()->create([
            'graduate_id' => $user->id,
            'institution_name' => 'Test University',
        ]);

        $joinedCount = $this->groupManager->autoJoinSchoolGroups($user);

        $this->assertEquals(1, $joinedCount);
        $this->assertTrue($schoolGroup->users()->where('user_id', $user->id)->exists());
    }

    public function test_processes_join_request_for_private_group()
    {
        $group = Group::factory()->create(['privacy' => 'private']);
        $user = User::factory()->create();

        $result = $this->groupManager->processJoinRequest($group, $user);

        $this->assertTrue($result);

        // User should be added as pending member
        $membership = $group->users()->where('user_id', $user->id)->first();
        $this->assertEquals('pending', $membership->pivot->status);
    }

    public function test_processes_join_request_for_public_group()
    {
        $group = Group::factory()->create(['privacy' => 'public']);
        $user = User::factory()->create();

        $result = $this->groupManager->processJoinRequest($group, $user);

        $this->assertTrue($result);

        // User should be added as active member
        $membership = $group->users()->where('user_id', $user->id)->first();
        $this->assertEquals('active', $membership->pivot->status);
    }

    public function test_approves_pending_member()
    {
        $group = Group::factory()->create();
        $admin = User::factory()->create();
        $user = User::factory()->create();

        $group->addMember($admin, 'admin');
        $group->users()->attach($user->id, [
            'role' => 'member',
            'status' => 'pending',
            'joined_at' => now(),
        ]);

        $result = $this->groupManager->approveMember($group, $user, $admin);

        $this->assertTrue($result);

        $membership = $group->users()->where('user_id', $user->id)->first();
        $this->assertEquals('active', $membership->pivot->status);
    }

    public function test_rejects_pending_member()
    {
        $group = Group::factory()->create();
        $admin = User::factory()->create();
        $user = User::factory()->create();

        $group->addMember($admin, 'admin');
        $group->users()->attach($user->id, [
            'role' => 'member',
            'status' => 'pending',
            'joined_at' => now(),
        ]);

        $result = $this->groupManager->rejectMember($group, $user, $admin);

        $this->assertTrue($result);
        $this->assertFalse($group->users()->where('user_id', $user->id)->exists());
    }

    public function test_updates_member_role()
    {
        $group = Group::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $group->addMember($admin, 'admin');
        $group->addMember($member, 'member');

        $result = $this->groupManager->updateMemberRole($group, $member, 'moderator', $admin);

        $this->assertTrue($result);

        $membership = $group->users()->where('user_id', $member->id)->first();
        $this->assertEquals('moderator', $membership->pivot->role);
    }

    public function test_cannot_update_creator_role()
    {
        $creator = User::factory()->create();
        $group = Group::factory()->create(['creator_id' => $creator->id]);
        $admin = User::factory()->create();

        $group->addMember($creator, 'admin');
        $group->addMember($admin, 'admin');

        $result = $this->groupManager->updateMemberRole($group, $creator, 'member', $admin);

        $this->assertFalse($result);
    }

    public function test_gets_recommended_groups()
    {
        $tenant = Tenant::factory()->create(['name' => 'Test University']);
        $user = User::factory()->create();

        // Create education history
        EducationHistory::factory()->create([
            'graduate_id' => $user->id,
            'institution_name' => 'Test University',
        ]);

        // Create groups
        $schoolGroup = Group::factory()->create([
            'type' => 'school',
            'privacy' => 'public',
            'institution_id' => $tenant->id,
        ]);

        $interestGroup = Group::factory()->create([
            'type' => 'interest',
            'privacy' => 'public',
        ]);

        $recommendations = $this->groupManager->getRecommendedGroups($user);

        $this->assertCount(2, $recommendations);
        $this->assertTrue($recommendations->contains($schoolGroup));
        $this->assertTrue($recommendations->contains($interestGroup));
    }

    public function test_gets_group_statistics()
    {
        Group::factory()->count(3)->create(['type' => 'school']);
        Group::factory()->count(2)->create(['type' => 'custom']);
        Group::factory()->count(4)->create(['privacy' => 'public']);
        Group::factory()->count(1)->create(['privacy' => 'private']);

        $stats = $this->groupManager->getGroupStatistics();

        $this->assertEquals(10, $stats['total_groups']);
        $this->assertEquals(3, $stats['school_groups']);
        $this->assertEquals(2, $stats['custom_groups']);
        $this->assertEquals(4, $stats['public_groups']);
        $this->assertEquals(1, $stats['private_groups']);
        $this->assertArrayHasKey('average_members_per_group', $stats);
        $this->assertArrayHasKey('largest_group_size', $stats);
    }
}
