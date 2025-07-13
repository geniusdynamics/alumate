<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // Run the seeder
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /** @test */
    public function roles_are_created()
    {
        $this->assertDatabaseHas('roles', ['name' => 'Super Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Institution Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Graduate']);
        $this->assertDatabaseHas('roles', ['name' => 'Employer']);
    }

    /** @test */
    public function permissions_are_created()
    {
        $this->assertDatabaseHas('permissions', ['name' => 'manage institutions']);
        $this->assertDatabaseHas('permissions', ['name' => 'manage courses']);
        // ... add more assertions for all permissions
    }

    /** @test */
    public function a_user_can_be_assigned_a_role()
    {
        $user = User::factory()->create();
        $role = Role::findByName('Graduate');

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('Graduate'));
    }

    /** @test */
    public function a_role_can_be_given_a_permission()
    {
        $role = Role::findByName('Institution Admin');
        $permission = Permission::findByName('manage courses');

        $this->assertTrue($role->hasPermissionTo($permission));
    }
}
