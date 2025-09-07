<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

class ResetTenantSchemas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:reset-schemas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop and recreate all tenant schemas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Resetting tenant schemas...');
        
        // Get all tenants
        $tenants = Tenant::all();
        
        foreach ($tenants as $tenant) {
            $schemaName = 'tenant' . $tenant->id;
            
            $this->info("Dropping schema: {$schemaName}");
            DB::statement("DROP SCHEMA IF EXISTS \"$schemaName\" CASCADE");
            
            $this->info("Creating schema: {$schemaName}");
            DB::statement("CREATE SCHEMA \"$schemaName\"");
        }
        
        $this->info('All tenant schemas have been reset.');
        $this->info('Now run: php artisan tenants:migrate');
        
        return 0;
    }
}
