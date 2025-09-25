<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "=== Checking Demo Users in Database ===\n";
    
    // First, check the structure of the users table
    $columns = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'users' ORDER BY ordinal_position");
    echo "Users table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column->column_name}: {$column->data_type}\n";
    }
    echo "\n";
    
    // Check users in public schema
    $users = DB::table('users')->select('id', 'name', 'email', 'created_at')->orderBy('id')->get();
    
    echo "Found " . count($users) . " users in the database:\n\n";
    
    foreach ($users as $user) {
        echo "ID: {$user->id}\n";
        echo "Name: {$user->name}\n";
        echo "Email: {$user->email}\n";
        echo "Created: {$user->created_at}\n";
        echo "---\n";
    }
    
    // Check if we have the expected demo users
    $expectedEmails = [
        'admin@system.com',
        'admin@tech-institute.edu',
        'techcorp@company.com',
        'john.smith@student.edu'
    ];
    
    echo "\nVerifying expected demo users:\n";
    foreach ($expectedEmails as $email) {
        $user = DB::table('users')->where('email', $email)->first();
        if ($user) {
            echo "✓ {$email} - Found\n";
        } else {
            echo "✗ {$email} - Missing\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>