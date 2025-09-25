<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Services\TenantContextService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "=== Creating Migrations Table in Tenant Schema ===\n";
    
    // Set tenant context
    $tenantContextService = app(TenantContextService::class);
    $tenantContextService->setTenant('tech-institute');
    
    echo "Current schema: " . $tenantContextService->getCurrentSchema() . "\n";
    
    // Create migrations table in tenant schema
    $sql = "
        CREATE TABLE IF NOT EXISTS migrations (
            id SERIAL PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INTEGER NOT NULL
        )
    ";
    
    DB::statement($sql);
    echo "Migrations table created successfully.\n";
    
    // Insert existing migration records to reflect current state
    $existingMigrations = [
        '2024_01_01_000001_create_courses_table',
        '2024_01_01_000002_create_students_table', 
        '2024_01_01_000003_create_enrollments_table',
        '2024_01_01_000005_create_grades_table'
    ];
    
    foreach ($existingMigrations as $index => $migration) {
        DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => 1
        ]);
        echo "Recorded migration: {$migration}\n";
    }
    
    echo "\nMigrations table setup complete.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>