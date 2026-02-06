<?php
// ABOUTME: Laravel Artisan command for migrating tenant_id based data to schema-based tenancy
// ABOUTME: Handles the core migration process from hybrid to pure schema-based architecture

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant;
use Exception;

class MigrateTenantToSchema extends Command
{
    protected $signature = 'tenant:migrate-to-schema 
                           {--tenant=* : Specific tenant IDs to migrate (optional)}
                           {--dry-run : Run migration in dry-run mode without making changes}
                           {--batch-size=1000 : Number of records to process in each batch}
                           {--verify : Verify data integrity after migration}
                           {--rollback : Rollback to tenant_id based structure}';

    protected $description = 'Migrate from tenant_id columns to schema-based tenancy';

    private $dryRun = false;
    private $batchSize = 1000;
    private $migrationLog = [];

    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        $this->batchSize = (int) $this->option('batch-size');

        $this->info('Starting tenant migration to schema-based architecture...');
        $this->info('Dry run mode: ' . ($this->dryRun ? 'ENABLED' : 'DISABLED'));

        if ($this->option('rollback')) {
            return $this->handleRollback();
        }

        try {
            // Step 1: Validate prerequisites
            $this->validatePrerequisites();

            // Step 2: Get tenants to migrate
            $tenants = $this->getTenantsToMigrate();

            if ($tenants->isEmpty()) {
                $this->warn('No tenants found to migrate.');
                return 0;
            }

            $this->info("Found {$tenants->count()} tenants to migrate.");

            // Step 3: Create backup if not dry run
            if (!$this->dryRun) {
                $this->createPreMigrationBackup();
            }

            // Step 4: Migrate each tenant
            $progressBar = $this->output->createProgressBar($tenants->count());
            $progressBar->start();

            foreach ($tenants as $tenant) {
                $this->migrateTenant($tenant);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Step 5: Verify migration if requested
            if ($this->option('verify')) {
                $this->verifyMigration($tenants);
            }

            // Step 6: Display summary
            $this->displayMigrationSummary();

            $this->info('Migration completed successfully!');
            return 0;

        } catch (Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    private function validatePrerequisites()
    {
        $this->info('Validating prerequisites...');

        // Check if tenants table exists
        if (!Schema::hasTable('tenants')) {
            throw new Exception('Tenants table not found. Please ensure tenant management is set up.');
        }

        // Check database permissions
        try {
            DB::statement('CREATE SCHEMA IF NOT EXISTS test_schema_permissions');
            DB::statement('DROP SCHEMA test_schema_permissions');
        } catch (Exception $e) {
            throw new Exception('Insufficient database permissions to create schemas: ' . $e->getMessage());
        }

        // Check for existing schema conflicts
        $existingSchemas = $this->getExistingSchemas();
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $schemaName = $this->generateSchemaName($tenant);
            if (in_array($schemaName, $existingSchemas)) {
                if (!$this->confirm("Schema '{$schemaName}' already exists. Continue?")) {
                    throw new Exception('Migration cancelled due to schema conflicts.');
                }
            }
        }

        $this->info('Prerequisites validated successfully.');
    }

    private function getTenantsToMigrate()
    {
        $tenantIds = $this->option('tenant');

        if (!empty($tenantIds)) {
            return Tenant::whereIn('id', $tenantIds)->get();
        }

        return Tenant::where('status', 'active')->get();
    }

    private function migrateTenant($tenant)
    {
        $schemaName = $this->generateSchemaName($tenant);
        
        $this->info("\nMigrating tenant: {$tenant->name} (ID: {$tenant->id}) to schema: {$schemaName}");

        try {
            DB::transaction(function () use ($tenant, $schemaName) {
                // Step 1: Create tenant schema
                $this->createTenantSchema($schemaName);

                // Step 2: Create tables in tenant schema
                $this->createTenantTables($schemaName);

                // Step 3: Migrate data from main tables to tenant schema
                $this->migrateTenantData($tenant, $schemaName);

                // Step 4: Update tenant record with schema information
                $this->updateTenantRecord($tenant, $schemaName);

                // Step 5: Verify data integrity
                $this->verifyTenantData($tenant, $schemaName);
            });

            $this->migrationLog[] = [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'schema_name' => $schemaName,
                'status' => 'success',
                'migrated_at' => now()
            ];

        } catch (Exception $e) {
            $this->error("Failed to migrate tenant {$tenant->name}: " . $e->getMessage());
            
            $this->migrationLog[] = [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'schema_name' => $schemaName,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'migrated_at' => now()
            ];

            throw $e;
        }
    }

    private function createTenantSchema($schemaName)
    {
        if ($this->dryRun) {
            $this->line("[DRY RUN] Would create schema: {$schemaName}");
            return;
        }

        $this->line("Creating schema: {$schemaName}");
        DB::statement("CREATE SCHEMA IF NOT EXISTS {$schemaName}");
        
        // Set appropriate permissions
        DB::statement("GRANT USAGE ON SCHEMA {$schemaName} TO authenticated");
        DB::statement("GRANT CREATE ON SCHEMA {$schemaName} TO authenticated");
    }

    private function createTenantTables($schemaName)
    {
        if ($this->dryRun) {
            $this->line("[DRY RUN] Would create tables in schema: {$schemaName}");
            return;
        }

        $this->line("Creating tables in schema: {$schemaName}");
        
        // Set search path to tenant schema
        DB::statement("SET search_path TO {$schemaName}");

        // Run tenant-specific migrations
        $migrationFiles = $this->getTenantMigrationFiles();
        
        foreach ($migrationFiles as $migrationFile) {
            $this->line("Running migration: {$migrationFile}");
            $this->runMigrationFile($migrationFile, $schemaName);
        }

        // Reset search path
        DB::statement("SET search_path TO public");
    }

    private function migrateTenantData($tenant, $schemaName)
    {
        $this->line("Migrating data for tenant: {$tenant->name}");

        $tablesToMigrate = $this->getTablesToMigrate();

        foreach ($tablesToMigrate as $table) {
            $this->migrateTenantTableData($tenant, $schemaName, $table);
        }
    }

    private function migrateTenantTableData($tenant, $schemaName, $table)
    {
        $this->line("Migrating table: {$table}");

        if ($this->dryRun) {
            $count = DB::table($table)->where('tenant_id', $tenant->id)->count();
            $this->line("[DRY RUN] Would migrate {$count} records from {$table}");
            return;
        }

        // Get total count for progress tracking
        $totalRecords = DB::table($table)->where('tenant_id', $tenant->id)->count();
        
        if ($totalRecords === 0) {
            $this->line("No records to migrate for table: {$table}");
            return;
        }

        $this->line("Migrating {$totalRecords} records from {$table}");

        // Migrate in batches
        $offset = 0;
        while ($offset < $totalRecords) {
            $records = DB::table($table)
                ->where('tenant_id', $tenant->id)
                ->offset($offset)
                ->limit($this->batchSize)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            // Insert into tenant schema
            $this->insertRecordsIntoTenantSchema($schemaName, $table, $records);

            $offset += $this->batchSize;
            $this->line("Migrated {$offset}/{$totalRecords} records from {$table}");
        }
    }

    private function insertRecordsIntoTenantSchema($schemaName, $table, $records)
    {
        // Prepare records for insertion (remove tenant_id)
        $cleanedRecords = $records->map(function ($record) {
            $recordArray = (array) $record;
            unset($recordArray['tenant_id']);
            return $recordArray;
        })->toArray();

        // Insert into tenant schema
        DB::statement("SET search_path TO {$schemaName}");
        DB::table($table)->insert($cleanedRecords);
        DB::statement("SET search_path TO public");
    }

    private function updateTenantRecord($tenant, $schemaName)
    {
        if ($this->dryRun) {
            $this->line("[DRY RUN] Would update tenant record with schema: {$schemaName}");
            return;
        }

        $tenant->update([
            'schema_name' => $schemaName,
            'migration_status' => 'completed',
            'migrated_at' => now()
        ]);
    }

    private function verifyTenantData($tenant, $schemaName)
    {
        $this->line("Verifying data integrity for tenant: {$tenant->name}");

        $tablesToVerify = $this->getTablesToMigrate();
        
        foreach ($tablesToVerify as $table) {
            $originalCount = DB::table($table)->where('tenant_id', $tenant->id)->count();
            
            DB::statement("SET search_path TO {$schemaName}");
            $migratedCount = DB::table($table)->count();
            DB::statement("SET search_path TO public");

            if ($originalCount !== $migratedCount) {
                throw new Exception("Data verification failed for table {$table}. Original: {$originalCount}, Migrated: {$migratedCount}");
            }

            $this->line("✓ {$table}: {$migratedCount} records verified");
        }
    }

    private function generateSchemaName($tenant)
    {
        // Generate schema name based on tenant slug or ID
        $baseName = $tenant->slug ?? 'tenant_' . $tenant->id;
        return 'tenant_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($baseName));
    }

    private function getTablesToMigrate()
    {
        // Tables that have tenant_id and need to be migrated
        return [
            'students',
            'courses',
            'enrollments',
            'grades',
            'landing_pages',
            'email_campaigns',
            'brand_colors',
            'brand_fonts',
            'component_themes',
            'email_analytics',
            'landing_page_analytics',
            'template_performance_dashboards',
            'activity_logs',
            'graduates',
            'email_sequences',
            'behavior_events',
            'template_crm_sync_logs'
        ];
    }

    private function getTenantMigrationFiles()
    {
        // Return list of migration files for tenant schema
        return [
            'create_students_table.php',
            'create_courses_table.php',
            'create_enrollments_table.php',
            'create_grades_table.php',
            'create_landing_pages_table.php',
            'create_email_campaigns_table.php',
            'create_brand_colors_table.php',
            'create_brand_fonts_table.php',
            'create_component_themes_table.php',
            'create_email_analytics_table.php',
            'create_landing_page_analytics_table.php',
            'create_template_performance_dashboards_table.php',
            'create_activity_logs_table.php',
            'create_graduates_table.php',
            'create_email_sequences_table.php',
            'create_behavior_events_table.php',
            'create_template_crm_sync_logs_table.php'
        ];
    }

    private function runMigrationFile($migrationFile, $schemaName)
    {
        // This would run the actual migration file
        // For now, we'll use a simplified approach
        $migrationPath = database_path('migrations/tenant/' . $migrationFile);
        
        if (file_exists($migrationPath)) {
            // Include and run the migration
            // This is a simplified version - in practice, you'd use Laravel's migration runner
            $this->call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--database' => 'tenant'
            ]);
        }
    }

    private function getExistingSchemas()
    {
        $schemas = DB::select("
            SELECT schema_name 
            FROM information_schema.schemata 
            WHERE schema_name NOT IN ('information_schema', 'pg_catalog', 'pg_toast')
        ");

        return array_column($schemas, 'schema_name');
    }

    private function createPreMigrationBackup()
    {
        $this->info('Creating pre-migration backup...');
        
        $this->call('backup:pre-migration', [
            '--type' => 'schema-migration'
        ]);
    }

    private function verifyMigration($tenants)
    {
        $this->info('\nVerifying migration integrity...');
        
        foreach ($tenants as $tenant) {
            $schemaName = $this->generateSchemaName($tenant);
            $this->verifyTenantData($tenant, $schemaName);
        }
        
        $this->info('Migration verification completed successfully.');
    }

    private function displayMigrationSummary()
    {
        $this->info('\n=== Migration Summary ===');
        
        $successful = collect($this->migrationLog)->where('status', 'success')->count();
        $failed = collect($this->migrationLog)->where('status', 'failed')->count();
        
        $this->info("Successful migrations: {$successful}");
        $this->info("Failed migrations: {$failed}");
        
        if ($failed > 0) {
            $this->error('\nFailed migrations:');
            foreach ($this->migrationLog as $log) {
                if ($log['status'] === 'failed') {
                    $this->error("- {$log['tenant_name']}: {$log['error']}");
                }
            }
        }
        
        // Save migration log
        $logFile = storage_path('logs/tenant-migration-' . now()->format('Y-m-d-H-i-s') . '.json');
        file_put_contents($logFile, json_encode($this->migrationLog, JSON_PRETTY_PRINT));
        $this->info("\nMigration log saved to: {$logFile}");
    }

    private function handleRollback()
    {
        $this->warn('Rollback functionality not yet implemented.');
        $this->info('To rollback, you would need to:');
        $this->info('1. Restore from pre-migration backup');
        $this->info('2. Update tenant records to remove schema information');
        $this->info('3. Drop tenant schemas');
        
        return 1;
    }
}