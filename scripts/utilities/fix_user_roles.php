<?php

require_once '../../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once '../../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "🔧 Fixing User Roles and Permissions\n";
echo "====================================\n\n";

try {
    // First, create the correct permissions
    $permissions = [
        'access super-admin dashboard',
        'manage institutions',
        'manage users',
        'view analytics',
        'manage system settings',
        'access institution-admin dashboard',
        'manage graduates',
        'manage courses',
        'view institution analytics',
        'access employer dashboard',
        'post jobs',
        'view candidates',
        'access graduate dashboard',
        'update profile',
        'apply for jobs',
    ];

    echo "📋 Creating permissions...\n";
    foreach ($permissions as $permission) {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        echo "  ✓ {$permission}\n";
    }

    // Create the correct roles with proper names
    echo "\n🎭 Creating roles...\n";

    $superAdminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin']);
    $institutionAdminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'institution-admin']);
    $employerRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'employer']);
    $graduateRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'graduate']);

    echo "  ✓ super-admin\n";
    echo "  ✓ institution-admin\n";
    echo "  ✓ employer\n";
    echo "  ✓ graduate\n";

    // Assign permissions to roles
    echo "\n🔐 Assigning permissions to roles...\n";

    $superAdminRole->syncPermissions([
        'access super-admin dashboard',
        'manage institutions',
        'manage users',
        'view analytics',
        'manage system settings',
    ]);
    echo "  ✓ Super admin permissions assigned\n";

    $institutionAdminRole->syncPermissions([
        'access institution-admin dashboard',
        'manage graduates',
        'manage courses',
        'view institution analytics',
    ]);
    echo "  ✓ Institution admin permissions assigned\n";

    $employerRole->syncPermissions([
        'access employer dashboard',
        'post jobs',
        'view candidates',
    ]);
    echo "  ✓ Employer permissions assigned\n";

    $graduateRole->syncPermissions([
        'access graduate dashboard',
        'update profile',
        'apply for jobs',
    ]);
    echo "  ✓ Graduate permissions assigned\n";

    // Fix existing users
    echo "\n👥 Fixing existing users...\n";

    // Fix super admin
    $superAdmin = \App\Models\User::where('email', 'admin@system.com')->first();
    if ($superAdmin) {
        $superAdmin->syncRoles(['super-admin']);
        echo "  ✓ admin@system.com → super-admin role\n";
    }

    // Fix institution admin
    $institutionAdmin = \App\Models\User::where('email', 'admin@tech-institute.edu')->first();
    if ($institutionAdmin) {
        $institutionAdmin->syncRoles(['institution-admin']);
        echo "  ✓ admin@tech-institute.edu → institution-admin role\n";
    }

    // Fix graduate
    $graduate = \App\Models\User::where('email', 'john.smith@student.edu')->first();
    if ($graduate) {
        $graduate->syncRoles(['graduate']);
        echo "  ✓ john.smith@student.edu → graduate role\n";
    }

    // Create/fix employer user
    $employer = \App\Models\User::firstOrCreate(
        ['email' => 'techcorp@company.com'],
        [
            'name' => 'TechCorp Recruiter',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]
    );
    $employer->syncRoles(['employer']);
    echo "  ✓ techcorp@company.com → employer role\n";

    // Remove old incorrect roles
    echo "\n🧹 Cleaning up old roles...\n";
    $oldRoles = \Spatie\Permission\Models\Role::whereIn('name', ['Super Admin', 'Institution Admin', 'Graduate'])->get();
    foreach ($oldRoles as $role) {
        echo "  🗑️  Removing old role: {$role->name}\n";
        $role->delete();
    }

    echo "\n✅ Role fix completed!\n\n";

    // Verify the fix
    echo "🔍 Verification:\n";
    echo "----------------\n";

    $superAdmin = \App\Models\User::where('email', 'admin@system.com')->first();
    if ($superAdmin) {
        echo "Super Admin (admin@system.com):\n";
        echo '  Has super-admin role: '.($superAdmin->hasRole('super-admin') ? '✅ Yes' : '❌ No')."\n";
        echo '  Can access dashboard: '.($superAdmin->can('access super-admin dashboard') ? '✅ Yes' : '❌ No')."\n";
        echo '  Permissions count: '.$superAdmin->getAllPermissions()->count()."\n\n";
    }

    echo "🎯 Test the fix:\n";
    echo "1. Login as admin@system.com / password\n";
    echo "2. Go to: http://127.0.0.1:8080/super-admin/dashboard\n";
    echo "3. Should work without 403 error!\n";

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
    echo 'File: '.$e->getFile().':'.$e->getLine()."\n";
}
