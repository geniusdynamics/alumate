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

    echo 'Current schema: '.$tenantService->getCurrentSchema()."\n";

    // Check if deleted_at column exists
    if (! Schema::hasColumn('courses', 'deleted_at')) {
        echo "Adding deleted_at column to courses table...\n";

        // Add the deleted_at column
        DB::statement('ALTER TABLE courses ADD COLUMN deleted_at TIMESTAMP NULL');

        echo "deleted_at column added successfully!\n";
    } else {
        echo "deleted_at column already exists\n";
    }

    // Verify the column was added
    $columns = Schema::getColumnListing('courses');
    echo 'Updated courses table columns: '.implode(', ', $columns)."\n";

} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
    echo 'Stack trace: '.$e->getTraceAsString()."\n";
}
