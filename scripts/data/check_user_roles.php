<?php

require_once '../../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once '../../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "🔍 Checking User Roles and Permissions\n";
echo "=====================================\n\n";

try {
    // Get all users with their roles
    $users = \App\Models\User::with('roles')->get();

    echo "📊 Users and their roles:\n";
    echo "-------------------------\n";

    foreach ($users as $user) {
        echo "👤 {$user->name} ({$user->email})\n";
        echo "   ID: {$user->id}\n";
        echo "   Type: {$user->user_type}\n";

        if ($user->roles->count() > 0) {
            echo '   Roles: '.$user->roles->pluck('name')->join(', ')."\n";

            // Get permissions through roles
            $permissions = $user->getAllPermissions();
            if ($permissions->count() > 0) {
                echo '   Permissions: '.$permissions->pluck('name')->take(5)->join(', ');
                if ($permissions->count() > 5) {
                    echo ' (+'.($permissions->count() - 5).' more)';
                }
                echo "\n";
            } else {
                echo "   Permissions: None\n";
            }
        } else {
            echo "   Roles: ❌ NO ROLES ASSIGNED\n";
        }
        echo "\n";
    }

    // Check available roles
    echo "🎭 Available roles in system:\n";
    echo "-----------------------------\n";
    $roles = \Spatie\Permission\Models\Role::with('permissions')->get();

    foreach ($roles as $role) {
        echo "🏷️  {$role->name}\n";
        if ($role->permissions->count() > 0) {
            echo '   Permissions: '.$role->permissions->pluck('name')->take(3)->join(', ');
            if ($role->permissions->count() > 3) {
                echo ' (+'.($role->permissions->count() - 3).' more)';
            }
            echo "\n";
        }
        echo "\n";
    }

    // Check specific super admin user
    echo "🔧 Super Admin User Check:\n";
    echo "---------------------------\n";
    $superAdmin = \App\Models\User::where('email', 'admin@system.com')->first();

    if ($superAdmin) {
        echo "✅ Super admin user found\n";
        echo '   Has super-admin role: '.($superAdmin->hasRole('super-admin') ? '✅ Yes' : '❌ No')."\n";
        echo '   Can access super-admin dashboard: '.($superAdmin->can('access super-admin dashboard') ? '✅ Yes' : '❌ No')."\n";
        echo '   All permissions count: '.$superAdmin->getAllPermissions()->count()."\n";
    } else {
        echo "❌ Super admin user not found!\n";
    }

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
    echo 'File: '.$e->getFile().':'.$e->getLine()."\n";
}

echo "\n💡 If users don't have roles, run:\n";
echo "php artisan db:seed --class=RolePermissionSeeder\n";
echo "php scripts/data/create_sample_data.php\n";
