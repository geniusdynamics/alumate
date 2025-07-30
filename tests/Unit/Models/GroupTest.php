<?php

namespace Tests\Unit\Models;

use App\Models\Group;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_can_be_created()
    {
        $creator = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $group = Group::create([
            'name' => 'Test Group',
            'description' => 'A test group',
            'type' => 'custom',
            'privacy' => 'public',
            'institution_id' => $tenant->id,
            'creator_id' => $creator->id,
            'settings' => ['test' => 'value'],
        ]);

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals('Test Group', $group->name);
        $this->assertEquals('custom', $group->type);
        $this->assertEquals('public', $group->privacy);
    }

    public function test_group_casts_settings_as_array()
    {
        $group = Group::factory()->create([
            'settings' => ['test' => 'value']
        ]);

        $this->assertIsArray($group->settings);
        $this->assertEquals('value', $group->settings['test']);
    }

    public function test_group_can_add_member()
    {
        $group = Group::factory()->create(['privacy' => 'public']);
        $user = User::factory()->create();

        $result = $group->addMember($user);

        $this->assertTrue($result);
        $this->assertTrue($group->users()->where('user_id', $user->id)->exists());
        $this->assertEquals(1, $group->fresh()->member_count);
    }

    public function test_group_adds_pending_member_for_private_groups()
    {
        $group = Group::factory()->create(['privacy' => 'private']);
        $user = User::factory()->create();

        $result = $group->addMember($user);

        $this->assertTrue($result);
        $membership = $group->users()->where('user_id', $user->id)->first();
        $this->assertEquals('pending', $membership->pivot->status);
        $this->assertEquals(0, $group->fresh()->member_count); // Pending members don't count
    }

    public function test_group_can_approve_member()
    {
        $group = Group::factory()->create(['privacy' => 'private']);
        $user = User::factory()->create();

        $group->addMember($user); // This creates a pending membership
        $result = $group->approveMember($user);

        $this->assertTrue($result);
        $membership = $group->users()->where('user_id', $user->id)->first();
        $this->assertEquals('active', $membership->pivot->status);
        $this->assertEquals(1, $group->fresh()->member_count);
    }

    public function test_group_can_check_user_permissions()
    {
        $group = Group::factory()->create();
        $admin = User::factory()->create();
        $moderator = User::factory()->create();
        $member = User::factory()->create();

        $group->addMember($admin, 'admin');
        $group->addMember($moderator, 'moderator');
        $group->addMember($member, 'member');

        $this->assertTrue($group->isAdmin($admin));
        $this->assertFalse($group->isAdmin($moderator));
        $this->assertFalse($group->isAdmin($member));

        $this->assertTrue($group->isModerator($admin));
        $this->assertTrue($group->isModerator($moderator));
        $this->assertFalse($group->isModerator($member));
    }

    public function test_group_can_check_posting_permissions()
    {
        $group = Group::factory()->create([
            'settings' => ['posting_restriction' => 'admins_only']
        ]);
        
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $group->addMember($admin, 'admin');
        $group->addMember($member, 'member');

        $this->assertTrue($group->canUserPost($admin));
        $this->assertFalse($group->canUserPost($member));
    }

    public function test_group_scopes_work_correctly()
    {
        Group::factory()->create(['type' => 'school']);
        Group::factory()->create(['type' => 'custom']);
        Group::factory()->create(['privacy' => 'public']);
        Group::factory()->create(['privacy' => 'private']);

        $this->assertEquals(1, Group::ofType('school')->count());
        $this->assertEquals(1, Group::ofType('custom')->count());
        $this->assertEquals(1, Group::public()->count());
        $this->assertEquals(1, Group::schoolGroups()->count());
    }
}