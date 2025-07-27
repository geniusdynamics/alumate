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

        // create permissions - use firstOrCreate to avoid duplicates
        $permissions = [
            'manage institutions',
            'manage courses',
            'manage tutors',
            'manage graduates',
            'upload graduates',
            'approve graduates',
            'view jobs',
            'post jobs',
            'manage applications',
            'view announcements',
            'update profile',
            'verify graduates',
            'view institutions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // create roles and assign created permissions
        $role = Role::firstOrCreate(['name' => 'Super Admin']);
        $role->givePermissionTo(Permission::all());

        $role = Role::firstOrCreate(['name' => 'Institution Admin']);
        $role->givePermissionTo([
            'manage courses',
            'manage tutors',
            'manage graduates',
            'upload graduates',
            'view announcements',
        ]);

        $role = Role::firstOrCreate(['name' => 'Graduate']);
        $role->givePermissionTo([
            'view jobs',
            'manage applications',
            'view announcements',
            'update profile',
        ]);

        $role = Role::firstOrCreate(['name' => 'Employer']);
        $role->givePermissionTo([
            'post jobs',
            'manage applications',
            'view institutions',
        ]);

        $role = Role::firstOrCreate(['name' => 'Tutor']);
        $role->givePermissionTo([
            'manage graduates',
            'verify graduates',
            'view announcements',
        ]);
    }
}