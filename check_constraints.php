<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TenantContextService;
use Illuminate\Support\Facades\DB;

echo "=== Checking Foreign Key Constraints ===\n";

$tenantService = app(TenantContextService::class);
$tenantService->setTenant('tech-institute');

echo "Current schema: " . DB::select('SELECT current_schema()')[0]->current_schema . "\n\n";

// Check foreign key constraints in the tenant schema
$constraints = DB::select("
    SELECT 
        tc.constraint_name, 
        tc.table_name, 
        kcu.column_name, 
        ccu.table_name AS foreign_table_name,
        ccu.column_name AS foreign_column_name,
        tc.table_schema
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

echo "Foreign key constraints for graduates table in tenant schema:\n";
foreach ($constraints as $constraint) {
    echo "  Constraint: {$constraint->constraint_name}\n";
    echo "    Table: {$constraint->table_schema}.{$constraint->table_name}.{$constraint->column_name}\n";
    echo "    References: {$constraint->table_schema}.{$constraint->foreign_table_name}.{$constraint->foreign_column_name}\n\n";
}

// Also check if there are any constraints in the public schema
echo "\nChecking constraints in public schema:\n";
$publicConstraints = DB::select("
    SELECT 
        tc.constraint_name, 
        tc.table_name, 
        kcu.column_name, 
        ccu.table_name AS foreign_table_name,
        ccu.column_name AS foreign_column_name,
        tc.table_schema
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
    AND tc.table_schema = 'public'
");

foreach ($publicConstraints as $constraint) {
    echo "  Constraint: {$constraint->constraint_name}\n";
    echo "    Table: {$constraint->table_schema}.{$constraint->table_name}.{$constraint->column_name}\n";
    echo "    References: {$constraint->table_schema}.{$constraint->foreign_table_name}.{$constraint->foreign_column_name}\n\n";
}

// Check what tables exist in both schemas
echo "\nTables in tenant schema:\n";
$tenantTables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = current_schema() ORDER BY table_name");
foreach ($tenantTables as $table) {
    echo "  - {$table->table_name}\n";
}

echo "\nTables in public schema:\n";
$publicTables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
foreach ($publicTables as $table) {
    echo "  - {$table->table_name}\n";
}

$tenantService->clearContext();