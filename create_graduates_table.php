<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Services\TenantContextService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "=== Creating Graduates Table in Tenant Schema ===\n";
    
    // Set tenant context
    $tenantContextService = app(TenantContextService::class);
    $tenantContextService->setTenant('tech-institute');
    
    echo "Current schema: " . $tenantContextService->getCurrentSchema() . "\n";
    
    // Create graduates table
    $sql = "
        CREATE TABLE IF NOT EXISTS graduates (
            id BIGSERIAL PRIMARY KEY,
            tenant_id VARCHAR(255) NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            phone VARCHAR(255) NULL,
            address VARCHAR(255) NULL,
            graduation_year INTEGER NOT NULL,
            course_id BIGINT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
            CONSTRAINT graduates_course_id_foreign FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        )
    ";
    
    DB::statement($sql);
    echo "Graduates table created successfully.\n";
    
    // Record the migration as completed
    DB::table('migrations')->insert([
        'migration' => '2024_01_01_000004_create_graduates_table',
        'batch' => 2
    ]);
    echo "Migration recorded: 2024_01_01_000004_create_graduates_table\n";
    
    // Verify table creation
    $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'tenant_tech_institute' AND table_name = 'graduates'");
    
    if (!empty($tables)) {
        echo "\nVerification: Graduates table exists in tenant schema.\n";
        
        // Check table structure
        $columns = DB::select("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_schema = 'tenant_tech_institute' AND table_name = 'graduates' ORDER BY ordinal_position");
        echo "\nTable structure:\n";
        foreach ($columns as $column) {
            echo "- {$column->column_name}: {$column->data_type} (nullable: {$column->is_nullable})\n";
        }
    } else {
        echo "\nERROR: Graduates table was not created.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>