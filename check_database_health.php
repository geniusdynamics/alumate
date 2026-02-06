<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "=== Database Health Check ===\n";
    
    // Test database connection
    echo "1. Testing database connection...\n";
    DB::connection()->getPdo();
    echo "✓ Database connection successful\n\n";
    
    // Check migration status
    echo "2. Checking migration status...\n";
    $migrations = DB::table('migrations')->orderBy('batch')->get();
    echo "Found " . count($migrations) . " migrations in public schema:\n";
    foreach ($migrations->take(5) as $migration) {
        echo "- {$migration->migration} (batch: {$migration->batch})\n";
    }
    if (count($migrations) > 5) {
        echo "... and " . (count($migrations) - 5) . " more\n";
    }
    echo "\n";
    
    // Check tenant migrations
    echo "3. Checking tenant migration status...\n";
    $tenantMigrations = DB::connection('tenant')->table('migrations')->orderBy('batch')->get();
    echo "Found " . count($tenantMigrations) . " migrations in tenant schema:\n";
    foreach ($tenantMigrations as $migration) {
        echo "- {$migration->migration} (batch: {$migration->batch})\n";
    }
    echo "\n";
    
    // Check key tables exist
    echo "4. Checking key tables exist...\n";
    $publicTables = ['users', 'tenants', 'domains', 'migrations'];
    foreach ($publicTables as $table) {
        if (Schema::hasTable($table)) {
            $count = DB::table($table)->count();
            echo "✓ {$table} table exists ({$count} records)\n";
        } else {
            echo "✗ {$table} table missing\n";
        }
    }
    echo "\n";
    
    // Check tenant tables
    echo "5. Checking tenant tables...\n";
    $tenantTables = ['courses', 'students', 'graduates', 'enrollments', 'grades'];
    foreach ($tenantTables as $table) {
        if (Schema::connection('tenant')->hasTable($table)) {
            $count = DB::connection('tenant')->table($table)->count();
            echo "✓ {$table} table exists in tenant schema ({$count} records)\n";
        } else {
            echo "✗ {$table} table missing in tenant schema\n";
        }
    }
    echo "\n";
    
    // Check authentication setup
    echo "6. Checking authentication setup...\n";
    $testUser = DB::table('users')->where('email', 'admin@system.com')->first();
    if ($testUser) {
        echo "✓ Test user found: {$testUser->name} ({$testUser->email})\n";
        echo "✓ Password hash exists: " . (strlen($testUser->password) > 0 ? 'Yes' : 'No') . "\n";
    } else {
        echo "✗ Test user not found\n";
    }
    
    echo "\n=== Database Health Check Complete ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}