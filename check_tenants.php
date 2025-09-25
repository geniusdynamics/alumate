<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "=== Checking Tenants ===\n";
    
    // Check if tenants table exists
    $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'tenants'");
    
    if (empty($tables)) {
        echo "Tenants table does NOT exist in public schema.\n";
        return;
    }
    
    echo "Tenants table exists in public schema.\n";
    
    // Get all tenants
    $tenants = DB::table('tenants')->get();
    
    echo "\nTotal tenants found: " . count($tenants) . "\n";
    
    if (count($tenants) > 0) {
        echo "\nTenants:\n";
        foreach ($tenants as $tenant) {
            echo "- ID: {$tenant->id}, Name: {$tenant->name}, Status: {$tenant->status}\n";
        }
        
        // Check specifically for tech-institute
        $techInstitute = DB::table('tenants')->where('id', 'tech-institute')->first();
        if ($techInstitute) {
            echo "\ntech-institute tenant found: {$techInstitute->name} (Status: {$techInstitute->status})\n";
        } else {
            echo "\ntech-institute tenant NOT found.\n";
        }
    } else {
        echo "\nNo tenants found in the database.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>