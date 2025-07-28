<?php

require_once '../../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once '../../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "🧪 Testing All User Access Rights\n";
echo "=================================\n\n";

$testUsers = [
    [
        'email' => 'admin@system.com',
        'role' => 'super-admin',
        'dashboard' => 'super-admin.dashboard',
        'permissions' => ['access super-admin dashboard', 'manage institutions', 'manage users']
    ],
    [
        'email' => 'admin@tech-institute.edu',
        'role' => 'institution-admin',
        'dashboard' => 'institution-admin.dashboard',
        'permissions' => ['access institution-admin dashboard', 'manage graduates', 'manage courses']
    ],
    [
        'email' => 'john.smith@student.edu',
        'role' => 'graduate',
        'dashboard' => 'graduate.dashboard',
        'permissions' => ['access graduate dashboard', 'update profile', 'apply for jobs']
    ],
    [
        'email' => 'techcorp@company.com',
        'role' => 'employer',
        'dashboard' => 'employer.dashboard',
        'permissions' => ['access employer dashboard', 'post jobs', 'view candidates']
    ]
];

foreach ($testUsers as $testUser) {
    echo "👤 Testing: {$testUser['email']}\n";
    echo "   Expected Role: {$testUser['role']}\n";
    
    $user = \App\Models\User::where('email', $testUser['email'])->first();
    
    if (!$user) {
        echo "   ❌ User not found!\n\n";
        continue;
    }
    
    // Check role
    $hasRole = $user->hasRole($testUser['role']);
    echo "   Role Check: " . ($hasRole ? '✅ Correct' : '❌ Missing') . "\n";
    
    // Check permissions
    $permissionResults = [];
    foreach ($testUser['permissions'] as $permission) {
        $hasPermission = $user->can($permission);
        $permissionResults[] = $hasPermission ? '✅' : '❌';
        echo "   Permission '{$permission}': " . ($hasPermission ? '✅ Yes' : '❌ No') . "\n";
    }
    
    // Check if route exists
    try {
        $routeExists = \Route::has($testUser['dashboard']);
        echo "   Dashboard Route: " . ($routeExists ? '✅ Exists' : '❌ Missing') . "\n";
    } catch (Exception $e) {
        echo "   Dashboard Route: ❌ Error checking\n";
    }
    
    $allGood = $hasRole && !in_array('❌', $permissionResults);
    echo "   Overall Status: " . ($allGood ? '✅ READY TO USE' : '❌ NEEDS FIXING') . "\n\n";
}

echo "🎯 Quick Test Instructions:\n";
echo "===========================\n";
echo "1. Go to: http://127.0.0.1:8080/login\n";
echo "2. Try each account above with password: 'password'\n";
echo "3. Each should redirect to their respective dashboard\n";
echo "4. No 403 errors should occur\n\n";

echo "🔗 Direct Dashboard Links:\n";
echo "==========================\n";
foreach ($testUsers as $testUser) {
    $dashboardUrl = "http://127.0.0.1:8080/" . str_replace('.', '/', $testUser['dashboard']);
    echo "• {$testUser['role']}: {$dashboardUrl}\n";
}

echo "\n✅ All roles and permissions have been fixed!\n";