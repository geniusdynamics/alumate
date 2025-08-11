<?php

require_once '../../vendor/autoload.php';

$app = require_once '../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Delete the problematic migration record
DB::table('migrations')->where('migration', '2024_01_01_000002_create_permission_tables')->delete();
echo "Migration record deleted\n";

// Clear config cache
Artisan::call('config:clear');
echo "Config cleared\n";
