<?php
// ABOUTME: Laravel Artisan command for creating comprehensive pre-migration backups
// ABOUTME: Handles database, configuration, and state backups before schema migration

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class BackupPreMigration extends Command
{
    protected $signature = 'backup:pre-migration {--verify : Verify backup after creation} {--compress : Compress backup files}';
    protected $description = 'Create comprehensive backup before schema migration';

    public function handle()
    {
        $this->info('🚀 Starting pre-migration backup process...');
        
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $backupPath = storage_path("backups/pre-migration-{$timestamp}");
        
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        $this->info("📁 Backup directory: {$backupPath}");
        
        try {
            // 1. Database backup
            $this->createDatabaseBackup($backupPath);
            
            // 2. Configuration backup
            $this->createConfigBackup($backupPath);
            
            // 3. Migration state backup
            $this->createMigrationBackup($backupPath);
            
            // 4. Tenant data analysis
            $this->analyzeTenantData($backupPath);
            
            // 5. Git state backup
            $this->createGitStateBackup($backupPath);
            
            // 6. Create manifest
            $this->createManifest($backupPath);
            
            if ($this->option('verify')) {
                $this->verifyBackup($backupPath);
            }
            
            if ($this->option('compress')) {
                $this->compressBackup($backupPath);
            }
            
            $this->info('✅ Pre-migration backup completed successfully!');
            $this->info("📍 Backup location: {$backupPath}");
            
            // Display backup summary
            $this->displayBackupSummary($backupPath);
            
        } catch (\Exception $e) {
            $this->error('❌ Backup failed: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function createDatabaseBackup($backupPath)
    {
        $this->info('💾 Creating database backup...');
        
        $config = config('database.connections.' . config('database.default'));
        $host = $config['host'];
        $port = $config['port'];
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];
        
        // Set PGPASSWORD environment variable
        putenv("PGPASSWORD={$password}");
        
        // Full backup in custom format
        $customBackupFile = $backupPath . '/full_database.backup';
        $command = sprintf(
            'pg_dump -h %s -p %s -U %s -d %s --verbose --clean --if-exists --create --format=custom --file=%s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($customBackupFile)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->error('Database backup failed!');
            $this->error('Output: ' . implode("\n", $output));
            throw new \Exception('Database backup failed');
        }
        
        // SQL format backup (human readable)
        $sqlBackupFile = $backupPath . '/full_database.sql';
        $command = sprintf(
            'pg_dump -h %s -p %s -U %s -d %s --verbose --clean --if-exists --create --format=plain --file=%s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($sqlBackupFile)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->warn('SQL format backup failed, but custom format succeeded');
        }
        
        // Schema-only backup
        $schemaBackupFile = $backupPath . '/schema_only.sql';
        $command = sprintf(
            'pg_dump -h %s -p %s -U %s -d %s --verbose --schema-only --clean --if-exists --create --file=%s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($schemaBackupFile)
        );
        
        exec($command, $output, $returnCode);
        
        // Clear password from environment
        putenv('PGPASSWORD');
        
        $this->info('✓ Database backup created');
    }
    
    private function createConfigBackup($backupPath)
    {
        $this->info('⚙️ Creating configuration backup...');
        
        $configPath = $backupPath . '/config';
        mkdir($configPath, 0755, true);
        
        // Copy config files
        if (is_dir(config_path())) {
            $this->copyDirectory(config_path(), $configPath . '/config');
        }
        
        // Copy environment file
        if (file_exists(base_path('.env'))) {
            copy(base_path('.env'), $configPath . '/.env.backup');
        }
        
        // Copy environment example
        if (file_exists(base_path('.env.example'))) {
            copy(base_path('.env.example'), $configPath . '/.env.example');
        }
        
        // Save current configuration as JSON
        $currentConfig = [
            'database' => config('database'),
            'tenancy' => config('tenancy'),
            'app' => config('app'),
            'cache' => config('cache'),
            'queue' => config('queue'),
        ];
        
        file_put_contents(
            $configPath . '/current_config.json',
            json_encode($currentConfig, JSON_PRETTY_PRINT)
        );
        
        $this->info('✓ Configuration backup created');
    }
    
    private function createMigrationBackup($backupPath)
    {
        $this->info('🔄 Creating migration state backup...');
        
        $migrationPath = $backupPath . '/migrations';
        mkdir($migrationPath, 0755, true);
        
        // Copy migration files
        if (is_dir(database_path('migrations'))) {
            $this->copyDirectory(database_path('migrations'), $migrationPath);
        }
        
        // Get migration status
        try {
            $migrations = DB::table('migrations')->get();
            file_put_contents(
                $migrationPath . '/migration_status.json',
                json_encode($migrations, JSON_PRETTY_PRINT)
            );
        } catch (\Exception $e) {
            $this->warn('Could not retrieve migration status: ' . $e->getMessage());
        }
        
        $this->info('✓ Migration state backup created');
    }
    
    private function analyzeTenantData($backupPath)
    {
        $this->info('🔍 Analyzing tenant data...');
        
        try {
            $tenants = DB::table('tenants')->get();
            $analysis = [
                'tenant_count' => $tenants->count(),
                'tenants' => $tenants->toArray(),
                'table_counts' => $this->getTableCounts(),
                'models_with_tenant_id' => $this->scanForTenantIdModels(),
                'database_size' => $this->getDatabaseSize()
            ];
            
            file_put_contents(
                $backupPath . '/tenant_analysis.json',
                json_encode($analysis, JSON_PRETTY_PRINT)
            );
            
            $this->info("✓ Tenant data analysis completed ({$tenants->count()} tenants found)");
        } catch (\Exception $e) {
            $this->warn('Could not analyze tenant data: ' . $e->getMessage());
        }
    }
    
    private function createGitStateBackup($backupPath)
    {
        $this->info('📝 Creating Git state backup...');
        
        $gitPath = $backupPath . '/git';
        mkdir($gitPath, 0755, true);
        
        // Git information
        $gitInfo = [
            'current_commit' => trim(shell_exec('git rev-parse HEAD') ?: 'unknown'),
            'current_branch' => trim(shell_exec('git branch --show-current') ?: 'unknown'),
            'recent_commits' => explode("\n", trim(shell_exec('git log --oneline -10') ?: '')),
            'git_status' => explode("\n", trim(shell_exec('git status --porcelain') ?: '')),
            'branches' => explode("\n", trim(shell_exec('git branch -a') ?: '')),
            'remotes' => explode("\n", trim(shell_exec('git remote -v') ?: ''))
        ];
        
        file_put_contents(
            $gitPath . '/git_state.json',
            json_encode($gitInfo, JSON_PRETTY_PRINT)
        );
        
        // Save uncommitted changes
        $diff = shell_exec('git diff');
        if ($diff) {
            file_put_contents($gitPath . '/uncommitted_changes.diff', $diff);
        }
        
        $this->info('✓ Git state backup created');
    }
    
    private function getTableCounts()
    {
        try {
            $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
            $counts = [];
            
            foreach ($tables as $table) {
                try {
                    $count = DB::table($table->tablename)->count();
                    $counts[$table->tablename] = $count;
                } catch (\Exception $e) {
                    $counts[$table->tablename] = 'error: ' . $e->getMessage();
                }
            }
            
            return $counts;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    private function scanForTenantIdModels()
    {
        $modelsPath = app_path('Models');
        $modelsWithTenantId = [];
        
        if (!is_dir($modelsPath)) {
            return $modelsWithTenantId;
        }
        
        $files = File::allFiles($modelsPath);
        
        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());
            
            if (strpos($content, 'tenant_id') !== false) {
                $modelsWithTenantId[] = [
                    'file' => $file->getRelativePathname(),
                    'path' => $file->getPathname(),
                    'has_fillable_tenant_id' => strpos($content, "'tenant_id'") !== false,
                    'has_tenant_relationship' => strpos($content, 'belongsTo(Tenant::class)') !== false,
                    'has_global_scope' => strpos($content, 'addGlobalScope') !== false
                ];
            }
        }
        
        return $modelsWithTenantId;
    }
    
    private function getDatabaseSize()
    {
        try {
            $result = DB::select("SELECT pg_size_pretty(pg_database_size(current_database())) as size");
            return $result[0]->size ?? 'unknown';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }
    
    private function createManifest($backupPath)
    {
        $this->info('📋 Creating backup manifest...');
        
        $manifest = [
            'created_at' => Carbon::now()->toISOString(),
            'backup_type' => 'pre-migration',
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'database_config' => [
                'driver' => config('database.default'),
                'host' => config('database.connections.' . config('database.default') . '.host'),
                'port' => config('database.connections.' . config('database.default') . '.port'),
                'database' => config('database.connections.' . config('database.default') . '.database'),
            ],
            'tenancy_config' => config('tenancy'),
            'git_commit' => trim(shell_exec('git rev-parse HEAD') ?: 'unknown'),
            'git_branch' => trim(shell_exec('git branch --show-current') ?: 'unknown'),
            'files' => $this->getDirectoryListing($backupPath),
            'backup_size' => $this->getDirectorySize($backupPath)
        ];
        
        file_put_contents(
            $backupPath . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT)
        );
        
        $this->info('✓ Backup manifest created');
    }
    
    private function verifyBackup($backupPath)
    {
        $this->info('🔍 Verifying backup integrity...');
        
        // Verify database backup
        $backupFile = $backupPath . '/full_database.backup';
        if (file_exists($backupFile)) {
            $command = "pg_restore --list {$backupFile} 2>&1";
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                $this->info('✓ Database backup verification passed');
            } else {
                $this->error('✗ Database backup verification failed');
                throw new \Exception('Backup verification failed');
            }
        }
        
        // Verify essential files exist
        $essentialFiles = [
            'manifest.json',
            'tenant_analysis.json',
            'config/current_config.json'
        ];
        
        foreach ($essentialFiles as $file) {
            if (!file_exists($backupPath . '/' . $file)) {
                $this->error("✗ Essential file missing: {$file}");
                throw new \Exception("Essential backup file missing: {$file}");
            }
        }
        
        $this->info('✓ Backup verification completed');
    }
    
    private function compressBackup($backupPath)
    {
        $this->info('🗜️ Compressing backup...');
        
        $archivePath = $backupPath . '.tar.gz';
        $command = sprintf(
            'tar -czf %s -C %s %s',
            escapeshellarg($archivePath),
            escapeshellarg(dirname($backupPath)),
            escapeshellarg(basename($backupPath))
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            $this->info("✓ Backup compressed to: {$archivePath}");
            $this->info('📦 Compressed size: ' . $this->formatBytes(filesize($archivePath)));
        } else {
            $this->warn('Compression failed, but backup is still available uncompressed');
        }
    }
    
    private function displayBackupSummary($backupPath)
    {
        $this->info('');
        $this->info('📊 Backup Summary:');
        $this->info('================');
        
        if (file_exists($backupPath . '/tenant_analysis.json')) {
            $analysis = json_decode(file_get_contents($backupPath . '/tenant_analysis.json'), true);
            $this->info("🏢 Tenants: {$analysis['tenant_count']}");
            $this->info("📊 Database size: {$analysis['database_size']}");
            $this->info("📁 Models with tenant_id: " . count($analysis['models_with_tenant_id']));
        }
        
        $backupSize = $this->getDirectorySize($backupPath);
        $this->info("💾 Backup size: {$backupSize}");
        
        $this->info('');
        $this->info('🔒 Please store this backup securely before proceeding with migration!');
    }
    
    private function copyDirectory($source, $destination)
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item, $target);
            }
        }
    }
    
    private function getDirectoryListing($path)
    {
        $files = [];
        if (!is_dir($path)) {
            return $files;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            $files[] = [
                'path' => str_replace($path . DIRECTORY_SEPARATOR, '', $file->getPathname()),
                'size' => $file->getSize(),
                'modified' => date('Y-m-d H:i:s', $file->getMTime())
            ];
        }
        
        return $files;
    }
    
    private function getDirectorySize($path)
    {
        $size = 0;
        if (!is_dir($path)) {
            return $this->formatBytes($size);
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            $size += $file->getSize();
        }
        
        return $this->formatBytes($size);
    }
    
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}