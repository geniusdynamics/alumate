<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TenantContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    // Set tenant context
    $tenantService = app(TenantContextService::class);
    $tenantService->setTenant('tech-institute');
    
    echo "Current schema: " . $tenantService->getCurrentSchema() . "\n";
    
    // Check if courses table exists
    if (Schema::hasTable('courses')) {
        echo "Courses table exists\n";
        
        // Get table columns
        $columns = Schema::getColumnListing('courses');
        echo "Courses table columns: " . implode(', ', $columns) . "\n";
        
        // Check specifically for deleted_at
        if (in_array('deleted_at', $columns)) {
            echo "deleted_at column EXISTS in courses table\n";
        } else {
            echo "deleted_at column MISSING from courses table\n";
        }
        
        // Check migrations table
        if (Schema::hasTable('migrations')) {
            echo "\nMigrations applied:\n";
            $migrations = DB::table('migrations')->orderBy('batch')->get();
            foreach ($migrations as $migration) {
                echo "- {$migration->migration} (batch: {$migration->batch})\n";
            }
        }
    } else {
        echo "Courses table does NOT exist\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}