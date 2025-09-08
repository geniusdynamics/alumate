<?php
// ABOUTME: Laravel Artisan command for rolling back from pure schema-based tenancy to hybrid tenancy
// ABOUTME: Provides emergency rollback capability to restore tenant_id columns and move data back to public schema

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class RollbackSchemaToHybrid extends Command
{
    protected $signature = 'tenant:rollback-to-hybrid 
                            {tenant_id : The tenant ID to rollback}
                            {--dry-run : Run rollback in dry-run mode without making changes}
                            {--force : Force rollback without confirmation}
                            {--batch-size=1000 : Number of records to process in each batch}
                            {--preserve-schema : Keep tenant schema after rollback}';

    protected $description = 'Rollback a tenant from pure schema-based tenancy to hybrid tenancy (emergency use)';

    private $tenantId;
    private $tenantSchema;
    private $isDryRun;
    private $batchSize;
    private $preserveSchema;
    private $rollbackLog = [];

    // Models that need to be rolled back
    private $modelsToRollback = [
        'BehaviorEvent',
        'BrandColor',
        'BrandFont', 
        'ComponentTheme',
        'EmailAnalytics',
        'EmailCampaign',
        'EmailSequence',
        'Graduate',
        'LandingPage',
        'LandingPageAnalytics',
        'TemplatePerformanceDashboard',
        'TemplateCrmSyncLog',
        'ActivityLog'
    ];

    public function handle()
    {
        $this->tenantId = $this->argument('tenant_id');
        $this->isDryRun = $this->option('dry-run');
        $this->batchSize = (int) $this->option('batch-size');
        $this->preserveSchema = $this->option('preserve-schema');
        $this->tenantSchema = "tenant_{$this->tenantId}";

        $this->error("⚠️ WARNING: This is an emergency rollback operation!");
        $this->error("⚠️ This will move data from schema-based tenancy back to hybrid tenancy.");
        $this->info("Rolling back Tenant ID: {$this->tenantId}");
        $this->info("Source schema: {$this->tenantSchema}");
        
        if ($this->isDryRun) {
            $this->warn("Running in DRY-RUN mode - no changes will be made");
        }

        if (!$this->option('force') && !$this->isDryRun) {
            if (!$this->confirm('Are you absolutely sure you want to proceed with this rollback?')) {
                $this->info('Rollback cancelled.');
                return 0;
            }
        }

        try {
            // Step 1: Validate tenant and schema
            $this->validateTenantAndSchema();

            // Step 2: Ensure public tables have tenant_id columns
            $this->ensureTenantIdColumns();

            // Step 3: Rollback data from tenant schema to public tables
            $this->rollbackData();

            // Step 4: Rollback user data
            $this->rollbackUserData();

            // Step 5: Verify data integrity
            $this->verifyRollbackIntegrity();

            // Step 6: Cleanup (optional)
            if (!$this->preserveSchema) {
                $this->cleanupTenantSchema();
            }

            // Step 7: Generate rollback report
            $this->generateRollbackReport();

            if (!$this->isDryRun) {
                $this->info("✅ Rollback completed successfully!");
            } else {
                $this->info("✅ Rollback dry-run completed successfully!");
            }

        } catch (Exception $e) {
            $this->error("❌ Rollback failed: " . $e->getMessage());
            $this->logError($e);
            return 1;
        }

        return 0;
    }

    private function validateTenantAndSchema()
    {
        $this->info("🔍 Validating tenant and schema...");
        
        // Check tenant exists
        $tenant = DB::table('tenants')->where('id', $this->tenantId)->first();
        
        if (!$tenant) {
            throw new Exception("Tenant with ID {$this->tenantId} not found");
        }

        // Check tenant schema exists
        $schemaExists = DB::select("SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?", [$this->tenantSchema]);
        
        if (empty($schemaExists)) {
            throw new Exception("Tenant schema {$this->tenantSchema} not found");
        }

        $this->info("✅ Tenant and schema validated");
        $this->logStep("Validation", "success", "Tenant {$tenant->name} and schema {$this->tenantSchema} validated");
    }

    private function ensureTenantIdColumns()
    {
        $this->info("🔧 Ensuring tenant_id columns exist in public tables...");
        
        foreach ($this->modelsToRollback as $model) {
            $tableName = $this->getTableNameForModel($model);
            $this->ensureTenantIdColumn($tableName);
        }
        
        $this->info("✅ All tenant_id columns verified");
    }

    private function ensureTenantIdColumn($tableName)
    {
        $this->info("  Checking {$tableName}...");
        
        // Check if table exists in public schema
        $tableExists = DB::select("SELECT table_name FROM information_schema.tables WHERE table_name = ? AND table_schema = 'public'", [$tableName]);
        
        if (empty($tableExists)) {
            $this->warn("  ⚠️ Table {$tableName} not found in public schema, skipping");
            return;
        }
        
        // Check if tenant_id column exists
        $columnExists = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = ? AND column_name = 'tenant_id' AND table_schema = 'public'", [$tableName]);
        
        if (empty($columnExists)) {
            $this->info("  Adding tenant_id column to {$tableName}");
            
            if (!$this->isDryRun) {
                DB::statement("ALTER TABLE public.{$tableName} ADD COLUMN tenant_id VARCHAR(100)");
                DB::statement("CREATE INDEX idx_{$tableName}_tenant_id ON public.{$tableName}(tenant_id)");
            }
            
            $this->logStep("Column addition", "success", "Added tenant_id column to {$tableName}");
        } else {
            $this->info("  ✅ tenant_id column already exists in {$tableName}");
        }
    }

    private function rollbackData()
    {
        $this->info("📦 Rolling back data from tenant schema to public tables...");
        
        foreach ($this->modelsToRollback as $model) {
            $this->rollbackModelData($model);
        }
        
        $this->info("✅ Data rollback completed");
    }

    private function rollbackModelData($model)
    {
        $tableName = $this->getTableNameForModel($model);
        
        $this->info("  Rolling back {$tableName} data...");
        
        if (!$this->isDryRun) {
            // Check if table exists in tenant schema
            $tenantTableExists = DB::select("SELECT table_name FROM information_schema.tables WHERE table_name = ? AND table_schema = ?", [$tableName, $this->tenantSchema]);
            
            if (empty($tenantTableExists)) {
                $this->warn("  ⚠️ Table {$tableName} not found in tenant schema, skipping");
                return;
            }
            
            // Check if public table exists
            $publicTableExists = DB::select("SELECT table_name FROM information_schema.tables WHERE table_name = ? AND table_schema = 'public'", [$tableName]);
            
            if (empty($publicTableExists)) {
                $this->warn("  ⚠️ Table {$tableName} not found in public schema, skipping");
                return;
            }
            
            // Get total count for progress tracking
            $totalCount = DB::table("{$this->tenantSchema}.{$tableName}")->count();
            
            if ($totalCount === 0) {
                $this->info("  ℹ️ No data found in {$this->tenantSchema}.{$tableName}");
                return;
            }
            
            $this->info("  📊 Found {$totalCount} records to rollback");
            
            // First, delete existing records for this tenant in public table
            $deletedCount = DB::table($tableName)->where('tenant_id', $this->tenantId)->delete();
            $this->info("  🗑️ Deleted {$deletedCount} existing records from public.{$tableName}");
            
            // Rollback in batches
            $offset = 0;
            $rolledBackCount = 0;
            
            while ($offset < $totalCount) {
                $records = DB::table("{$this->tenantSchema}.{$tableName}")
                    ->offset($offset)
                    ->limit($this->batchSize)
                    ->get();
                
                if ($records->isEmpty()) {
                    break;
                }
                
                foreach ($records as $record) {
                    $recordArray = (array) $record;
                    $recordArray['tenant_id'] = $this->tenantId; // Add tenant_id back
                    
                    DB::table($tableName)->insert($recordArray);
                    $rolledBackCount++;
                }
                
                $offset += $this->batchSize;
                $this->info("    Rolled back {$rolledBackCount}/{$totalCount} records");
            }
        }
        
        $this->logStep("Data rollback", "success", "Rolled back data for {$tableName}");
    }

    private function rollbackUserData()
    {
        $this->info("👥 Rolling back user data...");
        
        if (!$this->isDryRun) {
            // Get students from tenant schema
            $students = DB::table("{$this->tenantSchema}.students")->get();
            
            foreach ($students as $student) {
                // Update or insert user in public users table
                $globalUser = DB::table('global_users')->where('global_user_id', $student->global_user_id)->first();
                
                if ($globalUser) {
                    DB::table('users')
                        ->updateOrInsert(
                            ['id' => $student->global_user_id],
                            [
                                'email' => $globalUser->email,
                                'tenant_id' => $this->tenantId,
                                'student_number' => $student->student_number,
                                'role' => 'student',
                                'created_at' => $student->created_at,
                                'updated_at' => $student->updated_at
                            ]
                        );
                }
            }
            
            // Remove tenant memberships (optional - keep for audit trail)
            // DB::table('user_tenant_memberships')->where('tenant_id', $this->tenantId)->delete();
        }
        
        $this->logStep("User rollback", "success", "Rolled back user data to hybrid model");
    }

    private function verifyRollbackIntegrity()
    {
        $this->info("🔍 Verifying rollback integrity...");
        
        $issues = [];
        
        foreach ($this->modelsToRollback as $model) {
            $tableName = $this->getTableNameForModel($model);
            
            // Check if table exists in tenant schema
            $tenantTableExists = DB::select("SELECT table_name FROM information_schema.tables WHERE table_name = ? AND table_schema = ?", [$tableName, $this->tenantSchema]);
            
            if (empty($tenantTableExists)) {
                continue;
            }
            
            if (!$this->isDryRun) {
                // Count records in tenant schema
                $tenantCount = DB::table("{$this->tenantSchema}.{$tableName}")->count();
                
                // Count records in public table for this tenant
                $publicCount = DB::table($tableName)->where('tenant_id', $this->tenantId)->count();
                
                if ($tenantCount !== $publicCount) {
                    $issues[] = "Record count mismatch for {$tableName}: Tenant Schema={$tenantCount}, Public={$publicCount}";
                }
            }
        }
        
        if (empty($issues)) {
            $this->info("✅ Rollback integrity verification passed");
            $this->logStep("Rollback integrity", "success", "All rollback integrity checks passed");
        } else {
            foreach ($issues as $issue) {
                $this->error("❌ {$issue}");
            }
            throw new Exception("Rollback integrity verification failed");
        }
    }

    private function cleanupTenantSchema()
    {
        $this->info("🧹 Cleaning up tenant schema...");
        
        if (!$this->isDryRun) {
            if ($this->confirm("Are you sure you want to DROP the tenant schema {$this->tenantSchema}? This cannot be undone.")) {
                DB::statement("DROP SCHEMA {$this->tenantSchema} CASCADE");
                $this->info("✅ Tenant schema {$this->tenantSchema} dropped");
                $this->logStep("Schema cleanup", "success", "Dropped tenant schema {$this->tenantSchema}");
            } else {
                $this->info("ℹ️ Tenant schema preserved");
                $this->logStep("Schema cleanup", "skipped", "User chose to preserve tenant schema");
            }
        } else {
            $this->info("ℹ️ Would drop tenant schema {$this->tenantSchema} (dry-run)");
        }
    }

    private function generateRollbackReport()
    {
        $this->info("📊 Generating rollback report...");
        
        $reportPath = storage_path("logs/tenant_rollback_{$this->tenantId}_" . date('Y-m-d_H-i-s') . ".json");
        
        $report = [
            'tenant_id' => $this->tenantId,
            'tenant_schema' => $this->tenantSchema,
            'rollback_date' => now()->toISOString(),
            'dry_run' => $this->isDryRun,
            'batch_size' => $this->batchSize,
            'preserve_schema' => $this->preserveSchema,
            'rollback_log' => $this->rollbackLog,
            'models_rolled_back' => $this->modelsToRollback,
            'status' => 'completed'
        ];
        
        if (!$this->isDryRun) {
            file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
            $this->info("📄 Rollback report saved to: {$reportPath}");
        } else {
            $this->info("📄 Rollback report (dry-run):");
            $this->line(json_encode($report, JSON_PRETTY_PRINT));
        }
    }

    private function getTableNameForModel($model)
    {
        // Convert model name to table name (simple snake_case conversion)
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $model)) . 's';
    }

    private function logStep($step, $status, $message)
    {
        $this->rollbackLog[] = [
            'step' => $step,
            'status' => $status,
            'message' => $message,
            'timestamp' => now()->toISOString()
        ];
    }

    private function logError($exception)
    {
        $this->rollbackLog[] = [
            'step' => 'error',
            'status' => 'failed',
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => now()->toISOString()
        ];
    }
}