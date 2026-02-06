<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TenantContextService;
use Illuminate\Support\Facades\DB;

echo "=== Checking Graduates Table ===\n";

$tenantService = app(TenantContextService::class);
$tenantService->setTenant('tech-institute');

echo "Current schema: " . DB::select('SELECT current_schema()')[0]->current_schema . "\n";

// Check if graduates table exists in tenant schema
$tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = 'graduates'");
echo "Graduates table in tenant schema: " . (count($tables) > 0 ? 'EXISTS' : 'NOT EXISTS') . "\n";

if (count($tables) > 0) {
    echo "\nColumns in tenant graduates table:\n";
    $columns = DB::select("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'graduates' ORDER BY ordinal_position");
    foreach ($columns as $column) {
        echo "  {$column->column_name} ({$column->data_type}, nullable: {$column->is_nullable})\n";
    }
    
    echo "\nForeign key constraints in tenant graduates table:\n";
    $constraints = DB::select("
        SELECT 
            tc.constraint_name, 
            kcu.column_name, 
            ccu.table_name AS foreign_table_name,
            ccu.column_name AS foreign_column_name
        FROM 
            information_schema.table_constraints AS tc 
            JOIN information_schema.key_column_usage AS kcu
              ON tc.constraint_name = kcu.constraint_name
              AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu
              ON ccu.constraint_name = tc.constraint_name
              AND ccu.table_schema = tc.table_schema
        WHERE tc.constraint_type = 'FOREIGN KEY' 
        AND tc.table_name = 'graduates'
        AND tc.table_schema = current_schema()
    ");
    
    if (count($constraints) > 0) {
        foreach ($constraints as $constraint) {
            echo "  {$constraint->constraint_name}: {$constraint->column_name} -> {$constraint->foreign_table_name}.{$constraint->foreign_column_name}\n";
        }
    } else {
        echo "  No foreign key constraints found\n";
    }
} else {
    echo "\nGraduates table does not exist in tenant schema. This means the migration hasn't been run for the tenant.\n";
}

$tenantService->clearContext();