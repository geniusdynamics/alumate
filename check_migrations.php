<?php

require_once 'vendor/autoload.php';

use App\Services\TenantContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Set tenant context
    $tenantContext = app(TenantContextService::class);
    $tenantContext->setTenant('tech-institute');
    
    echo "=== Checking Migration Status ===\n";
    echo "Current schema: " . DB::select('SELECT current_schema()')[0]->current_schema . "\n";
    
    // Check if migrations table exists
    if (Schema::hasTable('migrations')) {
        echo "\nMigrations table exists in tenant schema.\n";
        
        // Get all migrations
        $migrations = DB::table('migrations')->orderBy('batch')->get();
        
        echo "\nMigrations run in tenant schema:\n";
        foreach ($migrations as $migration) {
            echo "- {$migration->migration} (batch: {$migration->batch})\n";
        }
        
        // Check specifically for graduates table migration
        $graduatesMigration = DB::table('migrations')
            ->where('migration', 'like', '%create_graduates_table%')
            ->first();
            
        if ($graduatesMigration) {
            echo "\nGraduates table migration found: {$graduatesMigration->migration}\n";
        } else {
            echo "\nGraduates table migration NOT found in migrations table.\n";
        }
    } else {
        echo "\nMigrations table does NOT exist in tenant schema.\n";
    }
    
    // Double-check if graduates table actually exists
    if (Schema::hasTable('graduates')) {
        echo "\nGraduates table EXISTS in tenant schema.\n";
    } else {
        echo "\nGraduates table does NOT exist in tenant schema.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}