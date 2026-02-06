<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Services\TenantContextService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "=== Checking Tenant Schema Tables ===\n";
    
    // Set tenant context
    $tenantContextService = app(TenantContextService::class);
    $tenantContextService->setTenant('tech-institute');
    
    echo "Current schema: " . $tenantContextService->getCurrentSchema() . "\n\n";
    
    // Get all tables in tenant schema
    $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'tenant_tech_institute' ORDER BY table_name");
    
    echo "Tables in tenant schema (" . count($tables) . "):" . "\n";
    foreach ($tables as $table) {
        echo "- {$table->table_name}\n";
    }
    
    // Check specifically for key tables
    $keyTables = ['migrations', 'courses', 'graduates', 'students'];
    echo "\nKey table status:\n";
    
    foreach ($keyTables as $tableName) {
        $exists = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'tenant_tech_institute' AND table_name = ?", [$tableName]);
        $status = !empty($exists) ? 'EXISTS' : 'MISSING';
        echo "- {$tableName}: {$status}\n";
        
        if (!empty($exists) && $tableName === 'migrations') {
            // Show migration records
            $migrations = DB::table('migrations')->orderBy('batch')->get();
            echo "  Migrations run: " . count($migrations) . "\n";
            foreach ($migrations as $migration) {
                echo "    - {$migration->migration} (batch {$migration->batch})\n";
            }
        }
        
        if (!empty($exists) && in_array($tableName, ['courses', 'graduates'])) {
            // Show record count
            $count = DB::table($tableName)->count();
            echo "  Records: {$count}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>