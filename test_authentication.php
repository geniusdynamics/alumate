<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "=== Testing Authentication System ===\n";
    
    // Test password verification for demo users
    $testUsers = [
        ['email' => 'admin@system.com', 'password' => 'password'],
        ['email' => 'admin@tech-institute.edu', 'password' => 'password'],
        ['email' => 'techcorp@company.com', 'password' => 'password'],
        ['email' => 'john.smith@student.edu', 'password' => 'password']
    ];
    
    foreach ($testUsers as $testUser) {
        echo "\nTesting login for: {$testUser['email']}\n";
        
        // Find user
        $user = User::where('email', $testUser['email'])->first();
        if (!$user) {
            echo "✗ User not found\n";
            continue;
        }
        
        echo "✓ User found: {$user->name}\n";
        
        // Test password
        if (Hash::check($testUser['password'], $user->password)) {
            echo "✓ Password verification successful\n";
        } else {
            echo "✗ Password verification failed\n";
        }
        
        // Test authentication attempt
        $credentials = [
            'email' => $testUser['email'],
            'password' => $testUser['password']
        ];
        
        if (Auth::attempt($credentials)) {
            echo "✓ Authentication successful\n";
            Auth::logout(); // Clean up
        } else {
            echo "✗ Authentication failed\n";
        }
    }
    
    echo "\n=== Authentication Test Summary ===\n";
    echo "All demo users have been tested for login functionality.\n";
    echo "If all tests passed, the authentication system is working properly.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>