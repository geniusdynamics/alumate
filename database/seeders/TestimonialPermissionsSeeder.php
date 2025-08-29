<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TestimonialPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create testimonial permissions
        $permissions = [
            'testimonials.view' => 'View testimonials',
            'testimonials.create' => 'Create testimonials',
            'testimonials.update' => 'Update testimonials',
            'testimonials.delete' => 'Delete testimonials',
            'testimonials.moderate' => 'Moderate testimonials (approve/reject/archive)',
            'testimonials.manage' => 'Full testimonial management',
            'testimonials.analytics' => 'View testimonial analytics',
            'testimonials.export' => 'Export testimonials',
            'testimonials.import' => 'Import testimonials',
            'testimonials.restore' => 'Restore archived testimonials',
            'testimonials.force-delete' => 'Permanently delete testimonials',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name]);
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    /**
     * Assign testimonial permissions to existing roles
     */
    protected function assignPermissionsToRoles(): void
    {
        // Super Admin gets all permissions
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo([
                'testimonials.view',
                'testimonials.create',
                'testimonials.update',
                'testimonials.delete',
                'testimonials.moderate',
                'testimonials.manage',
                'testimonials.analytics',
                'testimonials.export',
                'testimonials.import',
                'testimonials.restore',
                'testimonials.force-delete',
            ]);
        }

        // Institution Admin gets management permissions
        $institutionAdmin = Role::where('name', 'institution-admin')->first();
        if ($institutionAdmin) {
            $institutionAdmin->givePermissionTo([
                'testimonials.view',
                'testimonials.create',
                'testimonials.update',
                'testimonials.delete',
                'testimonials.moderate',
                'testimonials.analytics',
                'testimonials.export',
                'testimonials.import',
            ]);
        }

        // Marketing Manager gets content management permissions
        $marketingManager = Role::where('name', 'marketing-manager')->first();
        if ($marketingManager) {
            $marketingManager->givePermissionTo([
                'testimonials.view',
                'testimonials.create',
                'testimonials.update',
                'testimonials.moderate',
                'testimonials.analytics',
                'testimonials.export',
            ]);
        }

        // Content Manager gets basic content permissions
        $contentManager = Role::where('name', 'content-manager')->first();
        if ($contentManager) {
            $contentManager->givePermissionTo([
                'testimonials.view',
                'testimonials.create',
                'testimonials.update',
            ]);
        }

        // Alumni can view and potentially create testimonials
        $alumni = Role::where('name', 'alumni')->first();
        if ($alumni) {
            $alumni->givePermissionTo([
                'testimonials.view',
                'testimonials.create',
            ]);
        }

        // Employer can view testimonials
        $employer = Role::where('name', 'employer')->first();
        if ($employer) {
            $employer->givePermissionTo([
                'testimonials.view',
            ]);
        }
    }
}