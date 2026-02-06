<?php
// ABOUTME: Laravel Artisan command for migrating from hybrid tenant_id approach to pure schema-based multi-tenancy
// ABOUTME: Handles complete migration process with validation, backup, rollback capabilities, and progress tracking

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use App\Models\Tenant;
use App\Services\TenantContextService;
use Exception;
use Carbon\Carbon;

class MigrateToSchemaTenancy extends Command
{
    protected $signature = 'tenancy:migrate-to-schema 
                            {--dry-run : Run migration in dry-run mode without making changes}
                            {--tenant= : Migrate specific tenant only}
                            {--batch-size=100 : Number of records to process in each batch}
                            {--skip-backup : Skip automatic backup creation}
                            {--force : Force migration without confirmation prompts}
                            {--verify-only : Only verify data integrity without migration}
                            {--rollback-tenant= : Rollback specific tenant from schema to hybrid}';

    protected $description = 'Migrate from hybrid tenant_id approach to pure schema-based multi-tenancy';

    protected TenantContextService $tenantService;
    protected array $migrationLog = [];
    protected string $migrationId;

    public function __construct(TenantContextService $tenantService)
    {
        parent::__construct();
        $this->tenantService = $tenantService;
        $this->migrationId = 'migration_' . now()->format('Y_m_d_H_i_s');
    }

    public function handle(): int
    {
        try {
            $this->info('🚀 Starting Schema-Based Tenancy Migration');
            $this->logMigration('Migration started', ['command_options' => $this->options()]);

            // Handle rollback if requested
            if ($this->option('rollback-tenant')) {
                return $this->handleRollback();
            }

            // Verify prerequisites
            if (!$this->verifyPrerequisites()) {
                return Command::FAILURE;
            }

            // Verify data integrity only
            if ($this->option('verify-only')) {
                return $this->verifyDataIntegrity() ? Command::SUCCESS : Command::FAILURE;
            }

            // Create backup unless skipped
            if (!$this->option('skip-backup') && !$this->option('dry-run')) {
                $this->createBackup();
            }

            // Get tenants to migrate
            $tenants = $this->getTenantsToMigrate();
            
            if ($tenants->isEmpty()) {
                $this->warn('No tenants found to migrate.');
                return Command::SUCCESS;
            }

            // Confirm migration unless forced
            if (!$this->option('force') && !$this->option('dry-run')) {
                if (!$this->confirmMigration($tenants)) {
                    $this->info('Migration cancelled by user.');
                    return Command::SUCCESS;
                }
            }

            // Execute migration
            $this->migrateTenants($tenants);

            $this->info('✅ Migration completed successfully!');
            $this->displayMigrationSummary();
            
            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            $this->logMigration('Migration failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return Command::FAILURE;
        }
    }

    protected function verifyPrerequisites(): bool
    {
        $this->info('🔍 Verifying prerequisites...');

        // Check if tenant table exists
        if (!Schema::hasTable('tenants')) {
            $this->error('Tenants table does not exist. Please run tenant migrations first.');
            return false;
        }

        // Check if tenant migration files exist
        $migrationPath = database_path('migrations/tenant');
        if (!is_dir($migrationPath)) {
            $this->error('Tenant migration directory does not exist: ' . $migrationPath);
            return false;
        }

        // Check required tenant migration files
        $requiredMigrations = [
            'create_students_table.php',
            'create_courses_table.php',
            'create_enrollments_table.php',
            'create_grades_table.php',
            'create_activity_logs_table.php'
        ];

        foreach ($requiredMigrations as $migration) {
            if (!file_exists($migrationPath . '/' . $migration)) {
                $this->error('Required migration file missing: ' . $migration);
                return false;
            }
        }

        // Check database connection
        try {
            DB::connection()->getPdo();
        } catch (Exception $e) {
            $this->error('Database connection failed: ' . $e->getMessage());
            return false;
        }

        // Check PostgreSQL version and schema support
        $version = DB::select('SELECT version()')[0]->version;
        if (!str_contains(strtolower($version), 'postgresql')) {
            $this->error('This migration requires PostgreSQL database.');
            return false;
        }

        $this->info('✅ All prerequisites verified.');
        return true;
    }

    protected function getTenantsToMigrate()
    {
        $query = Tenant::query();

        // Filter by specific tenant if provided
        if ($tenantId = $this->option('tenant')) {
            $query->where('id', $tenantId);
        }

        // Only get tenants that haven't been migrated yet
        $query->where('schema_name', null)
              ->orWhere('is_schema_migrated', false);

        return $query->get();
    }

    protected function confirmMigration($tenants): bool
    {
        $this->warn('⚠️  This will migrate ' . $tenants->count() . ' tenant(s) to schema-based architecture.');
        $this->warn('This operation will:');
        $this->warn('  • Create dedicated schemas for each tenant');
        $this->warn('  • Migrate all tenant data to new schemas');
        $this->warn('  • Update tenant records with schema information');
        $this->warn('  • This process may take significant time for large datasets');
        
        return $this->confirm('Do you want to continue?');
    }

    protected function migrateTenants($tenants): void
    {
        $this->info('🔄 Starting tenant migration...');
        $progressBar = $this->output->createProgressBar($tenants->count());
        $progressBar->start();

        foreach ($tenants as $tenant) {
            try {
                $this->migrateTenant($tenant);
                $progressBar->advance();
            } catch (Exception $e) {
                $this->error("\nFailed to migrate tenant {$tenant->id}: " . $e->getMessage());
                $this->logMigration('Tenant migration failed', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage()
                ]);
                
                if (!$this->confirm("Continue with remaining tenants?")) {
                    break;
                }
            }
        }

        $progressBar->finish();
        $this->newLine();
    }

    protected function migrateTenant(Tenant $tenant): void
    {
        $schemaName = $this->generateSchemaName($tenant);
        
        $this->logMigration('Starting tenant migration', [
            'tenant_id' => $tenant->id,
            'schema_name' => $schemaName
        ]);

        DB::transaction(function () use ($tenant, $schemaName) {
            // Create tenant schema
            $this->createTenantSchema($schemaName);
            
            // Run tenant migrations in the new schema
            $this->runTenantMigrations($schemaName);
            
            // Migrate data from main database to tenant schema
            $this->migrateDataToSchema($tenant, $schemaName);
            
            // Verify data integrity
            $this->verifyTenantData($tenant, $schemaName);
            
            // Update tenant record
            $this->updateTenantRecord($tenant, $schemaName);
        });

        $this->logMigration('Tenant migration completed', [
            'tenant_id' => $tenant->id,
            'schema_name' => $schemaName
        ]);
    }

    protected function generateSchemaName(Tenant $tenant): string
    {
        // Generate schema name based on tenant slug or ID
        $baseName = $tenant->slug ?? 'tenant_' . $tenant->id;
        return 'tenant_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($baseName));
    }

    protected function createTenantSchema(string $schemaName): void
    {
        if (!$this->option('dry-run')) {
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$schemaName}");
            
            // Grant permissions to application user
            $dbUser = config('database.connections.pgsql.username');
            DB::statement("GRANT ALL PRIVILEGES ON SCHEMA {$schemaName} TO {$dbUser}");
            DB::statement("GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA {$schemaName} TO {$dbUser}");
            DB::statement("GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA {$schemaName} TO {$dbUser}");
        }
        
        $this->info("  📁 Created schema: {$schemaName}");
    }

    protected function runTenantMigrations(string $schemaName): void
    {
        if ($this->option('dry-run')) {
            $this->info("  🔄 [DRY RUN] Would run migrations in schema: {$schemaName}");
            return;
        }

        // Set search path to tenant schema
        DB::statement("SET search_path TO {$schemaName}, public");
        
        // Run tenant-specific migrations
        $migrationPath = database_path('migrations/tenant');
        $migrations = glob($migrationPath . '/*.php');
        
        foreach ($migrations as $migrationFile) {
            $this->runMigrationFile($migrationFile, $schemaName);
        }
        
        // Reset search path
        DB::statement('SET search_path TO public');
        
        $this->info("  ✅ Migrations completed for schema: {$schemaName}");
    }

    protected function runMigrationFile(string $migrationFile, string $schemaName): void
    {
        $migration = include $migrationFile;
        
        // Temporarily set schema context
        DB::statement("SET search_path TO {$schemaName}, public");
        
        try {
            $migration->up();
        } finally {
            DB::statement('SET search_path TO public');
        }
    }

    protected function migrateDataToSchema(Tenant $tenant, string $schemaName): void
    {
        $batchSize = (int) $this->option('batch-size');
        
        // Tables to migrate with their tenant_id column
        $tablesToMigrate = [
            'students' => 'tenant_id',
            'courses' => 'tenant_id', 
            'enrollments' => 'tenant_id',
            'grades' => 'tenant_id',
            'activity_logs' => 'tenant_id'
        ];

        foreach ($tablesToMigrate as $table => $tenantColumn) {
            $this->migrateTableData($table, $tenantColumn, $tenant->id, $schemaName, $batchSize);
        }
    }

    protected function migrateTableData(string $table, string $tenantColumn, int $tenantId, string $schemaName, int $batchSize): void
    {
        if (!Schema::hasTable($table)) {
            $this->warn("  ⚠️  Table {$table} does not exist, skipping...");
            return;
        }

        $totalRecords = DB::table($table)->where($tenantColumn, $tenantId)->count();
        
        if ($totalRecords === 0) {
            $this->info("  📊 No records to migrate for table: {$table}");
            return;
        }

        $this->info("  🔄 Migrating {$totalRecords} records from {$table}...");
        
        if ($this->option('dry-run')) {
            $this->info("  [DRY RUN] Would migrate {$totalRecords} records to {$schemaName}.{$table}");
            return;
        }

        $offset = 0;
        while ($offset < $totalRecords) {
            $records = DB::table($table)
                ->where($tenantColumn, $tenantId)
                ->offset($offset)
                ->limit($batchSize)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            // Insert records into tenant schema (excluding tenant_id column)
            foreach ($records as $record) {
                $recordArray = (array) $record;
                unset($recordArray[$tenantColumn]); // Remove tenant_id column
                
                DB::table("{$schemaName}.{$table}")->insert($recordArray);
            }

            $offset += $batchSize;
        }
        
        $this->info("  ✅ Migrated {$totalRecords} records to {$schemaName}.{$table}");
    }

    protected function verifyTenantData(Tenant $tenant, string $schemaName): void
    {
        $this->info("  🔍 Verifying data integrity for {$schemaName}...");
        
        $tablesToVerify = ['students', 'courses', 'enrollments', 'grades', 'activity_logs'];
        
        foreach ($tablesToVerify as $table) {
            if (!Schema::hasTable($table)) continue;
            
            $originalCount = DB::table($table)->where('tenant_id', $tenant->id)->count();
            $migratedCount = DB::table("{$schemaName}.{$table}")->count();
            
            if ($originalCount !== $migratedCount) {
                throw new Exception("Data verification failed for {$table}: Original({$originalCount}) != Migrated({$migratedCount})");
            }
        }
        
        $this->info("  ✅ Data integrity verified for {$schemaName}");
    }

    protected function updateTenantRecord(Tenant $tenant, string $schemaName): void
    {
        if (!$this->option('dry-run')) {
            $tenant->update([
                'schema_name' => $schemaName,
                'is_schema_migrated' => true,
                'schema_migrated_at' => now()
            ]);
        }
        
        $this->info("  ✅ Updated tenant record with schema information");
    }

    protected function createBackup(): void
    {
        $this->info('💾 Creating database backup...');
        
        try {
            Artisan::call('backup:create', [
                '--type' => 'full',
                '--compress' => true
            ]);
            
            $this->info('✅ Backup created successfully.');
            
            // Verify backup was created
            $this->verifyBackup();
            
        } catch (Exception $e) {
            $this->warn('⚠️  Backup creation failed: ' . $e->getMessage());
            
            if (!$this->confirm('Continue without backup?')) {
                throw new Exception('Migration cancelled due to backup failure.');
            }
        }
    }

    /**
     * Verify backup was created successfully
     */
    protected function verifyBackup(): void
    {
        $this->info('🔍 Verifying backup...');
        
        try {
            // Check if backup log entry exists
            $backupLog = DB::table('backup_logs')
                ->where('status', 'completed')
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$backupLog) {
                throw new Exception('No backup log entry found.');
            }
            
            // Check if backup file exists
            if ($backupLog->file_path) {
                $storagePath = storage_path('app/' . $backupLog->file_path);
                if (!file_exists($storagePath)) {
                    throw new Exception('Backup file does not exist: ' . $backupLog->file_path);
                }
                
                $fileSize = filesize($storagePath);
                if ($fileSize === 0) {
                    throw new Exception('Backup file is empty: ' . $backupLog->file_path);
                }
                
                $this->info('✅ Backup verified successfully.');
                $this->info('  File: ' . $backupLog->file_path);
                $this->info('  Size: ' . $this->formatBytes($fileSize));
                
                $this->logMigration('Backup verified', [
                    'backup_id' => $backupLog->id,
                    'file_path' => $backupLog->file_path,
                    'file_size' => $fileSize
                ]);
            } else {
                $this->warn('⚠️  Backup file path not found in log entry.');
            }
            
        } catch (Exception $e) {
            $this->error('❌ Backup verification failed: ' . $e->getMessage());
            $this->logMigration('Backup verification failed', ['error' => $e->getMessage()]);
            
            if (!$this->confirm('Backup verification failed. Continue anyway?')) {
                throw new Exception('Migration cancelled due to backup verification failure.');
            }
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    protected function verifyDataIntegrity(): bool
    {
        $this->info('🔍 Verifying data integrity across all tenants...');
        
        $tenants = Tenant::where('is_schema_migrated', true)->get();
        $issues = [];
        
        foreach ($tenants as $tenant) {
            try {
                $this->verifyTenantData($tenant, $tenant->schema_name);
            } catch (Exception $e) {
                $issues[] = "Tenant {$tenant->id}: " . $e->getMessage();
            }
        }
        
        if (empty($issues)) {
            $this->info('✅ All data integrity checks passed.');
            return true;
        } else {
            $this->error('❌ Data integrity issues found:');
            foreach ($issues as $issue) {
                $this->error('  • ' . $issue);
            }
            return false;
        }
    }

    protected function handleRollback(): int
    {
        $tenantId = $this->option('rollback-tenant');
        
        $this->warn('🔄 Starting rollback for tenant: ' . $tenantId);
        $this->logMigration('Rollback started', ['tenant_id' => $tenantId]);

        try {
            // Step 1: Verify tenant exists and is schema-migrated
            if (!$this->verifyTenantForRollback($tenantId)) {
                return Command::FAILURE;
            }

            $tenant = Tenant::findOrFail($tenantId);
            $schemaName = $tenant->schema_name;

            // Step 2: Create backup of current state
            if (!$this->option('skip-backup')) {
                $this->createRollbackBackup($tenant);
            }

            // Step 3: Confirm rollback unless forced
            if (!$this->option('force')) {
                $this->warn('⚠️  This will rollback tenant ' . $tenantId . ' from schema-based to hybrid tenancy.');
                $this->warn('This operation will:');
                $this->warn('  • Copy all data from tenant schema back to main tables');
                $this->warn('  • Add tenant_id column to all records');
                $this->warn('  • Update tenant record to remove schema information');
                $this->warn('  • Drop the tenant schema');
                
                if (!$this->confirm('Do you want to continue with the rollback?')) {
                    $this->info('Rollback cancelled by user.');
                    return Command::SUCCESS;
                }
            }

            // Step 4: Execute rollback in transaction
            DB::transaction(function () use ($tenant, $schemaName) {
                // Copy data from tenant schema back to main tables
                $this->rollbackDataFromSchema($tenant, $schemaName);
                
                // Verify data integrity after rollback
                $this->verifyRollbackData($tenant, $schemaName);
                
                // Update tenant record
                $this->updateTenantRecordForRollback($tenant);
                
                // Drop tenant schema
                $this->dropTenantSchema($schemaName);
            });

            $this->info('✅ Rollback completed successfully for tenant: ' . $tenantId);
            $this->logMigration('Rollback completed', [
                'tenant_id' => $tenantId,
                'schema_name' => $schemaName
            ]);

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error('❌ Rollback failed: ' . $e->getMessage());
            $this->logMigration('Rollback failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Verify tenant exists and is eligible for rollback
     */
    protected function verifyTenantForRollback(int $tenantId): bool
    {
        $this->info('🔍 Verifying tenant for rollback...');

        // Check if tenant exists
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error('Tenant not found: ' . $tenantId);
            return false;
        }

        // Check if tenant is schema-migrated
        if (!$tenant->schema_name || !$tenant->is_schema_migrated) {
            $this->error('Tenant is not schema-migrated: ' . $tenantId);
            $this->error('This tenant cannot be rolled back.');
            return false;
        }

        // Check if tenant schema exists
        $schemaExists = DB::select("SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?", [$tenant->schema_name]);
        if (empty($schemaExists)) {
            $this->error('Tenant schema does not exist: ' . $tenant->schema_name);
            return false;
        }

        // Check if main tables exist and have tenant_id column
        $tablesToCheck = ['students', 'courses', 'enrollments', 'grades', 'activity_logs'];
        foreach ($tablesToCheck as $table) {
            if (!Schema::hasTable($table)) {
                $this->error('Main table does not exist: ' . $table);
                return false;
            }

            $hasTenantIdColumn = Schema::hasColumn($table, 'tenant_id');
            if (!$hasTenantIdColumn) {
                $this->error('Main table missing tenant_id column: ' . $table);
                return false;
            }
        }

        $this->info('✅ Tenant verified for rollback.');
        return true;
    }

    /**
     * Create backup before rollback
     */
    protected function createRollbackBackup(Tenant $tenant): void
    {
        $this->info('💾 Creating pre-rollback backup...');
        
        try {
            Artisan::call('backup:create', [
                '--type' => 'full',
                '--compress' => true
            ]);
            
            $this->info('✅ Pre-rollback backup created successfully.');
            $this->logMigration('Pre-rollback backup created', ['tenant_id' => $tenant->id]);
        } catch (Exception $e) {
            $this->warn('⚠️  Backup creation failed: ' . $e->getMessage());
            
            if (!$this->confirm('Continue with rollback without backup?')) {
                throw new Exception('Rollback cancelled due to backup failure.');
            }
        }
    }

    /**
     * Rollback data from tenant schema back to main tables
     */
    protected function rollbackDataFromSchema(Tenant $tenant, string $schemaName): void
    {
        $batchSize = (int) $this->option('batch-size');
        
        // Tables to rollback with their tenant_id column
        $tablesToRollback = [
            'students' => 'tenant_id',
            'courses' => 'tenant_id', 
            'enrollments' => 'tenant_id',
            'grades' => 'tenant_id',
            'activity_logs' => 'tenant_id'
        ];

        foreach ($tablesToRollback as $table => $tenantColumn) {
            $this->rollbackTableData($table, $tenantColumn, $tenant->id, $schemaName, $batchSize);
        }
    }

    /**
     * Rollback data for a specific table
     */
    protected function rollbackTableData(string $table, string $tenantColumn, int $tenantId, string $schemaName, int $batchSize): void
    {
        // Check if schema table exists
        $schemaTableExists = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name = ?", [$schemaName, $table]);
        if (empty($schemaTableExists)) {
            $this->warn("  ⚠️  Schema table {$schemaName}.{$table} does not exist, skipping...");
            return;
        }

        $totalRecords = DB::table("{$schemaName}.{$table}")->count();
        
        if ($totalRecords === 0) {
            $this->info("  📊 No records to rollback for table: {$table}");
            return;
        }

        $this->info("  🔄 Rolling back {$totalRecords} records from {$schemaName}.{$table}...");

        // Delete existing records for this tenant from main table
        $deletedCount = DB::table($table)->where($tenantColumn, $tenantId)->delete();
        $this->info("  🗑️  Deleted {$deletedCount} existing records from main table: {$table}");

        $offset = 0;
        $insertedCount = 0;
        
        while ($offset < $totalRecords) {
            $records = DB::table("{$schemaName}.{$table}")
                ->offset($offset)
                ->limit($batchSize)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            // Insert records into main table with tenant_id
            foreach ($records as $record) {
                $recordArray = (array) $record;
                $recordArray[$tenantColumn] = $tenantId; // Add tenant_id column
                
                DB::table($table)->insert($recordArray);
                $insertedCount++;
            }

            $offset += $batchSize;
        }
        
        $this->info("  ✅ Rolled back {$insertedCount} records to main table: {$table}");
    }

    /**
     * Verify data integrity after rollback
     */
    protected function verifyRollbackData(Tenant $tenant, string $schemaName): void
    {
        $this->info("  🔍 Verifying rollback data integrity for tenant {$tenant->id}...");
        
        $tablesToVerify = ['students', 'courses', 'enrollments', 'grades', 'activity_logs'];
        
        foreach ($tablesToVerify as $table) {
            // Check if schema table exists
            $schemaTableExists = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name = ?", [$schemaName, $table]);
            if (empty($schemaTableExists)) {
                $this->warn("  ⚠️  Schema table {$schemaName}.{$table} does not exist, skipping verification...");
                continue;
            }
            
            $schemaCount = DB::table("{$schemaName}.{$table}")->count();
            $mainCount = DB::table($table)->where('tenant_id', $tenant->id)->count();
            
            if ($schemaCount !== $mainCount) {
                throw new Exception("Rollback verification failed for {$table}: Schema({$schemaCount}) != Main({$mainCount})");
            }
        }
        
        $this->info("  ✅ Rollback data integrity verified for tenant {$tenant->id}");
    }

    /**
     * Update tenant record after rollback
     */
    protected function updateTenantRecordForRollback(Tenant $tenant): void
    {
        $tenant->update([
            'schema_name' => null,
            'is_schema_migrated' => false,
            'schema_migrated_at' => null
        ]);
        
        $this->info("  ✅ Updated tenant record, removed schema information");
    }

    /**
     * Drop tenant schema
     */
    protected function dropTenantSchema(string $schemaName): void
    {
        $this->info("  🗑️  Dropping schema: {$schemaName}");
        
        DB::statement("DROP SCHEMA IF EXISTS {$schemaName} CASCADE");
        
        $this->info("  ✅ Dropped schema: {$schemaName}");
    }

    protected function logMigration(string $message, array $context = []): void
    {
        $logEntry = [
            'timestamp' => now()->toISOString(),
            'migration_id' => $this->migrationId,
            'message' => $message,
            'context' => $context
        ];
        
        $this->migrationLog[] = $logEntry;
        
        // Also log to Laravel log
        logger()->info('Schema Migration: ' . $message, $context);
    }

    protected function displayMigrationSummary(): void
    {
        $this->info('\n📊 Migration Summary:');
        $this->info('Migration ID: ' . $this->migrationId);
        $this->info('Total log entries: ' . count($this->migrationLog));
        
        // Save detailed log to file
        $logFile = storage_path('logs/schema-migration-' . $this->migrationId . '.json');
        file_put_contents($logFile, json_encode($this->migrationLog, JSON_PRETTY_PRINT));
        
        $this->info('Detailed log saved to: ' . $logFile);
    }
}