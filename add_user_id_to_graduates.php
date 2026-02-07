<?php

require_once 'vendor/autoload.php';

use App\Services\TenantContextService;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "=== Adding user_id Column to Graduates Table ===\n";

    // Set tenant context
    $tenantContextService = app(TenantContextService::class);
    $tenantContextService->setTenant('tech-institute');

    echo 'Current schema: '.$tenantContextService->getCurrentSchema()."\n";

    // Check if user_id column already exists
    $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_schema = 'tenant_tech_institute' AND table_name = 'graduates' AND column_name = 'user_id'");

    if (empty($columns)) {
        // Add user_id column
        $sql = 'ALTER TABLE graduates ADD COLUMN user_id BIGINT NULL';
        DB::statement($sql);
        echo "user_id column added to graduates table.\n";

        // Add foreign key constraint to users table in public schema
        $constraintSql = 'ALTER TABLE graduates ADD CONSTRAINT graduates_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL';
        DB::statement($constraintSql);
        echo "Foreign key constraint added for user_id.\n";
    } else {
        echo "user_id column already exists.\n";
    }

    // Verify the column was added
    $columns = DB::select("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_schema = 'tenant_tech_institute' AND table_name = 'graduates' ORDER BY ordinal_position");
    echo "\nUpdated table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column->column_name}: {$column->data_type} (nullable: {$column->is_nullable})\n";
    }

} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
    echo 'Stack trace: '.$e->getTraceAsString()."\n";
}
