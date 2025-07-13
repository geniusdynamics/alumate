<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        Permission::create(['name' => 'manage institutions']);
        Permission::create(['name' => 'manage courses']);
        Permission::create(['name' => 'manage tutors']);
        Permission::create(['name' => 'manage graduates']);
        Permission::create(['name' => 'upload graduates']);
        Permission::create(['name' => 'approve graduates']);
        Permission::create(['name' => 'view jobs']);
        Permission::create(['name' => 'post jobs']);
        Permission::create(['name' => 'manage applications']);
        Permission::create(['name' => 'view announcements']);
        Permission::create(['name' => 'update profile']);
        Permission::create(['name' => 'verify graduates']);

        // create roles and assign created permissions

        $role = Role::create(['name' => 'Super Admin']);
        $role->givePermissionTo(Permission::all());

        $role = Role::create(['name' => 'Institution Admin']);
        $role->givePermissionTo([
            'manage courses',
            'manage tutors',
            'manage graduates',
            'upload graduates',
            'view announcements',
        ]);

        $role = Role::create(['name' => 'Graduate']);
        $role->givePermissionTo([
            'view jobs',
            'view announcements',
            'update profile',
        ]);

        $role = Role::create(['name' => 'Employer']);
        $role->givePermissionTo([
            'post jobs',
            'manage applications',
            'verify graduates',
        ]);
    }
}
