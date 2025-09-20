<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

// Create admin@system.com user
$user = User::firstOrCreate(
    ['email' => 'admin@system.com'],
    [
        'name' => 'System Admin',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]
);

// Assign super-admin role if it exists
if (Role::where('name', 'super-admin')->exists()) {
    $user->assignRole('super-admin');
}

echo "Created/Updated admin@system.com user with super-admin role\n";