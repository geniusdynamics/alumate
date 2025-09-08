<?php
// ABOUTME: Migration rollback service for handling schema-based tenancy migration failures and recovery
// ABOUTME: Provides comprehensive rollback mechanisms and disaster recovery for failed migrations

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class MigrationRollbackService
{
    protected TenantContextService $tenantService;
    protected SchemaUtilityService $schemaUtility;
    protected MigrationValidationService $validationService;
    
    public function __construct(
        TenantContextService $tenantService,
        SchemaUtilityService $schemaUtility,
        MigrationValidationService $validationService
    ) {
        $this->tenantService = $tenantService;
        $this->schemaUtility = $schemaUtility;
        $this->validationService = $validationService;
    }
    
    /**
     * Rollback tenant migration to hybrid model
     */
    public function rollbackTenantMigration(Tenant $tenant, array $options = []): array
    {
        $rollback = [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'schema_name' => $tenant->schema_name,
            'status' => 'pending',
            'steps_completed' => [],
            'errors' => [],
            'warnings' => [],
            'backup_restored' => false,
            'schema_dropped' => false,
            'tenant_updated' => false
        ];
        
        try {
            Log::info('Starting tenant migration rollback', [
                'tenant_id' => $tenant->id,
                'schema_name' => $tenant->schema_name,
                'options' => $options
            ]);
            
            // Step 1: Validate rollback prerequisites
            $this->validateRollbackPrerequisites($tenant, $options);
            $rollback['steps_completed'][] = 'prerequisites_validated';
            
            // Step 2: Create emergency backup of current state
            if ($options['create_emergency_backup'] ?? true) {
                $this->createEmergencyBackup($tenant);
                $rollback['steps_completed'][] = 'emergency_backup_created';
            }
            
            // Step 3: Restore data from backup if available
            if ($options['restore_from_backup'] ?? true) {
                $restored = $this->restoreFromBackup($tenant, $options);
                $rollback['backup_restored'] = $restored;
                if ($restored) {
                    $rollback['steps_completed'][] = 'backup_restored';
                }
            }
            
            // Step 4: Migrate data back to hybrid tables
            if ($options['migrate_data_back'] ?? true) {
                $this->migrateDataBackToHybrid($tenant);
                $rollback['steps_completed'][] = 'data_migrated_back';
            }
            
            // Step 5: Update tenant configuration
            $this->updateTenantForHybridModel($tenant);
            $rollback['tenant_updated'] = true;
            $rollback['steps_completed'][] = 'tenant_updated';
            
            // Step 6: Drop schema (if requested)
            if ($options['drop_schema'] ?? false) {
                $this->dropTenantSchema($tenant);
                $rollback['schema_dropped'] = true;
                $rollback['steps_completed'][] = 'schema_dropped';
            }
            
            // Step 7: Validate rollback success
            $this->validateRollbackSuccess($tenant);
            $rollback['steps_completed'][] = 'rollback_validated';
            
            $rollback['status'] = 'completed';
            
            Log::info('Tenant migration rollback completed successfully', [
                'tenant_id' => $tenant->id,
                'steps_completed' => $rollback['steps_completed']
            ]);
            
        } catch (Exception $e) {
            $rollback['status'] = 'failed';
            $rollback['errors'][] = 'Rollback failed: ' . $e->getMessage();
            
            Log::error('Tenant migration rollback failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'steps_completed' => $rollback['steps_completed']
            ]);
        }
        
        return $rollback;
    }
    
    /**
     * Validate rollback prerequisites
     */
    protected function validateRollbackPrerequisites(Tenant $tenant, array $options): void
    {
        // Check if tenant has schema-based setup
        if (empty($tenant->schema_name)) {
            throw new Exception('Tenant does not have schema-based setup');
        }
        
        // Check if schema exists
        if (!$this->schemaUtility->schemaExists($tenant->schema_name)) {
            throw new Exception("Tenant schema '{$tenant->schema_name}' does not exist");
        }
        
        // Check if hybrid tables exist
        $hybridTables = ['students', 'courses', 'enrollments', 'grades', 'activity_logs'];
        foreach ($hybridTables as $table) {
            if (!$this->tableExists($table)) {
                throw new Exception("Hybrid table '{$table}' does not exist");
            }
            
            if (!$this->columnExists($table, 'tenant_id')) {
                throw new Exception("Hybrid table '{$table}' missing tenant_id column");
            }
        }
        
        // Check for backup availability if restore is requested
        if (($options['restore_from_backup'] ?? true) && !$this->hasBackupAvailable($tenant)) {
            throw new Exception('No backup available for restoration');
        }
    }
    
    /**
     * Create emergency backup of current state
     */
    protected function createEmergencyBackup(Tenant $tenant): void
    {
        $backupPath = "backups/emergency/{$tenant->id}_" . now()->format('Y-m-d_H-i-s');
        
        // Create backup directory
        Storage::makeDirectory($backupPath);
        
        // Backup schema data
        $this->backupSchemaData($tenant, $backupPath);
        
        // Backup tenant configuration
        $tenantData = $tenant->toArray();
        Storage::put("{$backupPath}/tenant_config.json", json_encode($tenantData, JSON_PRETTY_PRINT));
        
        Log::info('Emergency backup created', [
            'tenant_id' => $tenant->id,
            'backup_path' => $backupPath
        ]);
    }
    
    /**
     * Backup schema data to files
     */
    protected function backupSchemaData(Tenant $tenant, string $backupPath): void
    {
        $schemaName = $tenant->schema_name;
        $tables = ['students', 'courses', 'enrollments', 'grades', 'activity_logs'];
        
        $this->tenantService->setTenant($tenant);
        
        try {
            foreach ($tables as $table) {
                $data = DB::table($table)->get();
                $filename = "{$backupPath}/{$table}.json";
                Storage::put($filename, json_encode($data->toArray(), JSON_PRETTY_PRINT));
            }
        } finally {
            $this->tenantService->clearTenant();
        }
    }
    
    /**
     * Restore data from backup
     */
    protected function restoreFromBackup(Tenant $tenant, array $options): bool
    {
        $backupPath = $options['backup_path'] ?? $this->getLatestBackupPath($tenant);
        
        if (!$backupPath || !Storage::exists($backupPath)) {
            Log::warning('No backup found for restoration', ['tenant_id' => $tenant->id]);
            return false;
        }
        
        try {
            // Restore hybrid table data
            $this->restoreHybridTableData($tenant, $backupPath);
            
            Log::info('Data restored from backup', [
                'tenant_id' => $tenant->id,
                'backup_path' => $backupPath
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to restore from backup', [
                'tenant_id' => $tenant->id,
                'backup_path' => $backupPath,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Restore hybrid table data from backup
     */
    protected function restoreHybridTableData(Tenant $tenant, string $backupPath): void
    {
        $tables = ['students', 'courses', 'enrollments', 'grades', 'activity_logs'];
        
        DB::beginTransaction();
        
        try {
            // Clear existing hybrid data for this tenant
            foreach ($tables as $table) {
                DB::table($table)->where('tenant_id', $tenant->id)->delete();
            }
            
            // Restore data from backup files
            foreach ($tables as $table) {
                $backupFile = "{$backupPath}/{$table}.json";
                
                if (Storage::exists($backupFile)) {
                    $data = json_decode(Storage::get($backupFile), true);
                    
                    if (!empty($data)) {
                        // Add tenant_id to each record
                        foreach ($data as &$record) {
                            $record['tenant_id'] = $tenant->id;
                            // Remove any schema-specific fields that don't exist in hybrid tables
                            unset($record['id']); // Let database auto-generate new IDs
                        }
                        
                        // Insert in batches
                        $chunks = array_chunk($data, 1000);
                        foreach ($chunks as $chunk) {
                            DB::table($table)->insert($chunk);
                        }
                    }
                }
            }
            
            DB::commit();
            
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to restore hybrid table data: {$e->getMessage()}");
        }
    }
    
    /**
     * Migrate data back from schema to hybrid tables
     */
    protected function migrateDataBackToHybrid(Tenant $tenant): void
    {
        $schemaName = $tenant->schema_name;
        $tables = ['students', 'courses', 'enrollments', 'grades', 'activity_logs'];
        
        DB::beginTransaction();
        
        try {
            // Clear existing hybrid data for this tenant
            foreach ($tables as $table) {
                DB::table($table)->where('tenant_id', $tenant->id)->delete();
            }
            
            // Migrate data from schema tables to hybrid tables
            foreach ($tables as $table) {
                $this->migrateTableDataBackToHybrid($tenant, $table, $schemaName);
            }
            
            DB::commit();
            
            Log::info('Data migrated back to hybrid tables', [
                'tenant_id' => $tenant->id,
                'schema_name' => $schemaName
            ]);
            
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to migrate data back to hybrid: {$e->getMessage()}");
        }
    }
    
    /**
     * Migrate single table data back to hybrid
     */
    protected function migrateTableDataBackToHybrid(Tenant $tenant, string $table, string $schemaName): void
    {
        // Get data from schema table
        $schemaData = DB::table("{$schemaName}.{$table}")->get();
        
        if ($schemaData->isEmpty()) {
            return;
        }
        
        // Prepare data for hybrid table (add tenant_id)
        $hybridData = $schemaData->map(function ($record) use ($tenant) {
            $record = (array) $record;
            $record['tenant_id'] = $tenant->id;
            return $record;
        })->toArray();
        
        // Insert in batches
        $chunks = array_chunk($hybridData, 1000);
        foreach ($chunks as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
    
    /**
     * Update tenant configuration for hybrid model
     */
    protected function updateTenantForHybridModel(Tenant $tenant): void
    {
        $tenant->update([
            'schema_name' => null,
            'migration_status' => 'rolled_back',
            'migration_completed_at' => null,
            'rollback_completed_at' => now()
        ]);
        
        Log::info('Tenant updated for hybrid model', ['tenant_id' => $tenant->id]);
    }
    
    /**
     * Drop tenant schema
     */
    protected function dropTenantSchema(Tenant $tenant): void
    {
        $schemaName = $tenant->schema_name;
        
        if ($this->schemaUtility->schemaExists($schemaName)) {
            $this->schemaUtility->dropSchema($schemaName);
            
            Log::info('Tenant schema dropped', [
                'tenant_id' => $tenant->id,
                'schema_name' => $schemaName
            ]);
        }
    }
    
    /**
     * Validate rollback success
     */
    protected function validateRollbackSuccess(Tenant $tenant): void
    {
        // Check that tenant configuration is updated
        $tenant->refresh();
        if (!empty($tenant->schema_name)) {
            throw new Exception('Tenant still has schema_name set');
        }
        
        // Check that hybrid data exists
        $hybridTables = ['students', 'courses', 'enrollments', 'grades', 'activity_logs'];
        foreach ($hybridTables as $table) {
            $count = DB::table($table)->where('tenant_id', $tenant->id)->count();
            if ($count === 0) {
                Log::warning("No data found in hybrid table {$table} for tenant {$tenant->id}");
            }
        }
        
        Log::info('Rollback validation completed', ['tenant_id' => $tenant->id]);
    }
    
    /**
     * Rollback all tenants to hybrid model
     */
    public function rollbackAllTenants(array $options = []): array
    {
        $results = [
            'total_tenants' => 0,
            'successful_rollbacks' => 0,
            'failed_rollbacks' => 0,
            'tenant_results' => []
        ];
        
        $tenants = Tenant::whereNotNull('schema_name')->get();
        $results['total_tenants'] = $tenants->count();
        
        foreach ($tenants as $tenant) {
            try {
                $rollbackResult = $this->rollbackTenantMigration($tenant, $options);
                $results['tenant_results'][] = $rollbackResult;
                
                if ($rollbackResult['status'] === 'completed') {
                    $results['successful_rollbacks']++;
                } else {
                    $results['failed_rollbacks']++;
                }
                
            } catch (Exception $e) {
                $results['failed_rollbacks']++;
                $results['tenant_results'][] = [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'status' => 'failed',
                    'errors' => ['Rollback failed: ' . $e->getMessage()]
                ];
                
                Log::error('Tenant rollback failed', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $results;
    }
    
    /**
     * Check if backup is available for tenant
     */
    protected function hasBackupAvailable(Tenant $tenant): bool
    {
        $backupPath = $this->getLatestBackupPath($tenant);
        return $backupPath && Storage::exists($backupPath);
    }
    
    /**
     * Get latest backup path for tenant
     */
    protected function getLatestBackupPath(Tenant $tenant): ?string
    {
        $backupDir = "backups/tenants/{$tenant->id}";
        
        if (!Storage::exists($backupDir)) {
            return null;
        }
        
        $backups = Storage::directories($backupDir);
        if (empty($backups)) {
            return null;
        }
        
        // Return the most recent backup
        rsort($backups);
        return $backups[0];
    }
    
    /**
     * Check if table exists
     */
    protected function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if column exists in table
     */
    protected function columnExists(string $table, string $column): bool
    {
        try {
            return DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Generate rollback report
     */
    public function generateRollbackReport(array $rollbackResults): string
    {
        $report = "# Migration Rollback Report\n\n";
        $report .= "Generated: " . now()->toDateTimeString() . "\n\n";
        
        if (isset($rollbackResults['total_tenants'])) {
            // Multiple tenants rollback
            $report .= "## Summary\n";
            $report .= "- Total Tenants: {$rollbackResults['total_tenants']}\n";
            $report .= "- Successful Rollbacks: {$rollbackResults['successful_rollbacks']}\n";
            $report .= "- Failed Rollbacks: {$rollbackResults['failed_rollbacks']}\n\n";
            
            foreach ($rollbackResults['tenant_results'] as $result) {
                $this->addTenantRollbackToReport($report, $result);
            }
        } else {
            // Single tenant rollback
            $this->addTenantRollbackToReport($report, $rollbackResults);
        }
        
        return $report;
    }
    
    /**
     * Add tenant rollback details to report
     */
    protected function addTenantRollbackToReport(string &$report, array $result): void
    {
        $report .= "## Tenant: {$result['tenant_name']} (ID: {$result['tenant_id']})\n";
        $report .= "**Status:** {$result['status']}\n";
        
        if (isset($result['schema_name'])) {
            $report .= "**Schema:** {$result['schema_name']}\n";
        }
        
        if (!empty($result['steps_completed'])) {
            $report .= "**Steps Completed:**\n";
            foreach ($result['steps_completed'] as $step) {
                $report .= "- {$step}\n";
            }
        }
        
        if (!empty($result['errors'])) {
            $report .= "**Errors:**\n";
            foreach ($result['errors'] as $error) {
                $report .= "- {$error}\n";
            }
        }
        
        if (!empty($result['warnings'])) {
            $report .= "**Warnings:**\n";
            foreach ($result['warnings'] as $warning) {
                $report .= "- {$warning}\n";
            }
        }
        
        $report .= "\n";
    }
}