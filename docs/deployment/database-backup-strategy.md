# Database Backup Strategy for Schema Migration

## Overview

Before migrating from hybrid tenancy (tenant_id columns) to pure schema-based multi-tenancy, we must create comprehensive backups to ensure data safety and enable rollback capabilities.

## Backup Requirements

### 1. Full Database Backup
- Complete PostgreSQL database dump
- Include all schemas, tables, data, indexes, and constraints
- Preserve permissions and ownership
- Include stored procedures and functions

### 2. Schema-Specific Backups
- Central database schema backup
- Individual tenant schema backups
- Public schema backup (if used)

### 3. Configuration Backups
- Laravel configuration files
- Environment files (.env)
- Database connection configurations
- Tenancy package configurations

### 4. Code State Backup
- Git repository state
- Current branch snapshot
- Migration files state

## Backup Implementation

### Pre-Migration Backup Script

Create a comprehensive backup script that handles all aspects:

```bash
#!/bin/bash
# backup-pre-migration.sh

set -e  # Exit on any error

# Configuration
BACKUP_DIR="./backups/pre-migration-$(date +%Y%m%d_%H%M%S)"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_DATABASE}"
DB_USER="${DB_USERNAME}"

# Create backup directory
mkdir -p "$BACKUP_DIR"

echo "Starting pre-migration backup process..."
echo "Backup directory: $BACKUP_DIR"

# 1. Full database backup
echo "Creating full database backup..."
pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
    --verbose --clean --if-exists --create --format=custom \
    --file="$BACKUP_DIR/full_database.backup"

# 2. SQL format backup (human readable)
echo "Creating SQL format backup..."
pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
    --verbose --clean --if-exists --create --format=plain \
    --file="$BACKUP_DIR/full_database.sql"

# 3. Schema-only backup
echo "Creating schema-only backup..."
pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
    --verbose --schema-only --clean --if-exists --create \
    --file="$BACKUP_DIR/schema_only.sql"

# 4. Data-only backup
echo "Creating data-only backup..."
pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
    --verbose --data-only --disable-triggers \
    --file="$BACKUP_DIR/data_only.sql"

# 5. Individual table backups for critical tables
echo "Creating individual table backups..."
CRITICAL_TABLES=("tenants" "users" "domains" "graduates" "courses")
for table in "${CRITICAL_TABLES[@]}"; do
    echo "Backing up table: $table"
    pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
        --table="$table" --data-only --disable-triggers \
        --file="$BACKUP_DIR/table_${table}.sql"
done

# 6. Tenant-specific data backup
echo "Creating tenant-specific backups..."
psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -t -c \
    "SELECT id, name FROM tenants ORDER BY id;" > "$BACKUP_DIR/tenant_list.txt"

# 7. Configuration files backup
echo "Backing up configuration files..."
mkdir -p "$BACKUP_DIR/config"
cp -r config/ "$BACKUP_DIR/config/"
cp .env "$BACKUP_DIR/config/.env.backup" 2>/dev/null || echo "No .env file found"
cp .env.example "$BACKUP_DIR/config/.env.example" 2>/dev/null || echo "No .env.example found"

# 8. Migration files backup
echo "Backing up migration files..."
mkdir -p "$BACKUP_DIR/migrations"
cp -r database/migrations/ "$BACKUP_DIR/migrations/"

# 9. Git state backup
echo "Backing up Git state..."
git log --oneline -10 > "$BACKUP_DIR/git_recent_commits.txt"
git status > "$BACKUP_DIR/git_status.txt"
git diff > "$BACKUP_DIR/git_uncommitted_changes.diff" 2>/dev/null || echo "No uncommitted changes"
git branch -a > "$BACKUP_DIR/git_branches.txt"
echo "$(git rev-parse HEAD)" > "$BACKUP_DIR/git_current_commit.txt"

# 10. Database statistics
echo "Collecting database statistics..."
psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c \
    "SELECT schemaname, tablename, n_tup_ins, n_tup_upd, n_tup_del FROM pg_stat_user_tables ORDER BY schemaname, tablename;" \
    > "$BACKUP_DIR/table_statistics.txt"

# 11. Create backup manifest
echo "Creating backup manifest..."
cat > "$BACKUP_DIR/backup_manifest.txt" << EOF
Backup Created: $(date)
Database: $DB_NAME
Host: $DB_HOST:$DB_PORT
User: $DB_USER
Git Commit: $(git rev-parse HEAD)
Git Branch: $(git branch --show-current)
Laravel Version: $(php artisan --version)
PHP Version: $(php --version | head -n 1)
PostgreSQL Version: $(psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT version();")

Files in this backup:
$(ls -la "$BACKUP_DIR")

Tenant Count: $(psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT COUNT(*) FROM tenants;")
Total Tables: $(psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema NOT IN ('information_schema', 'pg_catalog');")
EOF

# 12. Compress backup
echo "Compressing backup..."
tar -czf "${BACKUP_DIR}.tar.gz" -C "$(dirname "$BACKUP_DIR")" "$(basename "$BACKUP_DIR")"

echo "Backup completed successfully!"
echo "Backup location: ${BACKUP_DIR}.tar.gz"
echo "Backup size: $(du -h "${BACKUP_DIR}.tar.gz" | cut -f1)"

# 13. Verify backup integrity
echo "Verifying backup integrity..."
if pg_restore --list "$BACKUP_DIR/full_database.backup" > /dev/null 2>&1; then
    echo "✓ Custom format backup is valid"
else
    echo "✗ Custom format backup verification failed"
    exit 1
fi

echo "Pre-migration backup process completed successfully!"
echo "Please store this backup in a secure location before proceeding with migration."
```

### Laravel Artisan Backup Command

Create a Laravel command for easier backup management:

```php
<?php
// app/Console/Commands/BackupPreMigration.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupPreMigration extends Command
{
    protected $signature = 'backup:pre-migration {--verify : Verify backup after creation}';
    protected $description = 'Create comprehensive backup before schema migration';

    public function handle()
    {
        $this->info('Starting pre-migration backup process...');
        
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $backupPath = storage_path("backups/pre-migration-{$timestamp}");
        
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        $this->info("Backup directory: {$backupPath}");
        
        // 1. Database backup
        $this->createDatabaseBackup($backupPath);
        
        // 2. Configuration backup
        $this->createConfigBackup($backupPath);
        
        // 3. Migration state backup
        $this->createMigrationBackup($backupPath);
        
        // 4. Tenant data analysis
        $this->analyzeTenantData($backupPath);
        
        // 5. Create manifest
        $this->createManifest($backupPath);
        
        if ($this->option('verify')) {
            $this->verifyBackup($backupPath);
        }
        
        $this->info('Pre-migration backup completed successfully!');
        $this->info("Backup location: {$backupPath}");
        
        return 0;
    }
    
    private function createDatabaseBackup($backupPath)
    {
        $this->info('Creating database backup...');
        
        $config = config('database.connections.' . config('database.default'));
        $host = $config['host'];
        $port = $config['port'];
        $database = $config['database'];
        $username = $config['username'];
        
        // Full backup
        $command = sprintf(
            'pg_dump -h %s -p %s -U %s -d %s --verbose --clean --if-exists --create --format=custom --file=%s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($backupPath . '/full_database.backup')
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->error('Database backup failed!');
            throw new \Exception('Database backup failed');
        }
        
        $this->info('✓ Database backup created');
    }
    
    private function createConfigBackup($backupPath)
    {
        $this->info('Creating configuration backup...');
        
        $configPath = $backupPath . '/config';
        mkdir($configPath, 0755, true);
        
        // Copy config files
        $this->copyDirectory(config_path(), $configPath . '/config');
        
        // Copy environment file
        if (file_exists(base_path('.env'))) {
            copy(base_path('.env'), $configPath . '/.env.backup');
        }
        
        $this->info('✓ Configuration backup created');
    }
    
    private function createMigrationBackup($backupPath)
    {
        $this->info('Creating migration state backup...');
        
        $migrationPath = $backupPath . '/migrations';
        mkdir($migrationPath, 0755, true);
        
        // Copy migration files
        $this->copyDirectory(database_path('migrations'), $migrationPath);
        
        // Get migration status
        $migrations = DB::table('migrations')->get();
        file_put_contents(
            $migrationPath . '/migration_status.json',
            json_encode($migrations, JSON_PRETTY_PRINT)
        );
        
        $this->info('✓ Migration state backup created');
    }
    
    private function analyzeTenantData($backupPath)
    {
        $this->info('Analyzing tenant data...');
        
        $tenants = DB::table('tenants')->get();
        $analysis = [
            'tenant_count' => $tenants->count(),
            'tenants' => $tenants->toArray(),
            'models_with_tenant_id' => $this->getModelsWithTenantId(),
            'table_counts' => $this->getTableCounts()
        ];
        
        file_put_contents(
            $backupPath . '/tenant_analysis.json',
            json_encode($analysis, JSON_PRETTY_PRINT)
        );
        
        $this->info('✓ Tenant data analysis completed');
    }
    
    private function getModelsWithTenantId()
    {
        // This would scan model files for tenant_id usage
        // Implementation depends on your specific needs
        return [];
    }
    
    private function getTableCounts()
    {
        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        $counts = [];
        
        foreach ($tables as $table) {
            $count = DB::table($table->tablename)->count();
            $counts[$table->tablename] = $count;
        }
        
        return $counts;
    }
    
    private function createManifest($backupPath)
    {
        $manifest = [
            'created_at' => Carbon::now()->toISOString(),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'database_config' => config('database.connections.' . config('database.default')),
            'tenancy_config' => config('tenancy'),
            'git_commit' => trim(shell_exec('git rev-parse HEAD')),
            'git_branch' => trim(shell_exec('git branch --show-current')),
            'files' => $this->getDirectoryListing($backupPath)
        ];
        
        file_put_contents(
            $backupPath . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT)
        );
        
        $this->info('✓ Backup manifest created');
    }
    
    private function verifyBackup($backupPath)
    {
        $this->info('Verifying backup integrity...');
        
        // Verify database backup
        $backupFile = $backupPath . '/full_database.backup';
        $command = "pg_restore --list {$backupFile}";
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            $this->info('✓ Database backup verification passed');
        } else {
            $this->error('✗ Database backup verification failed');
            throw new \Exception('Backup verification failed');
        }
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
                mkdir($target, 0755, true);
            } else {
                copy($item, $target);
            }
        }
    }
    
    private function getDirectoryListing($path)
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            $files[] = [
                'path' => $file->getPathname(),
                'size' => $file->getSize(),
                'modified' => date('Y-m-d H:i:s', $file->getMTime())
            ];
        }
        
        return $files;
    }
}
```

## Backup Verification Checklist

### 1. Database Backup Verification
- [ ] Custom format backup can be listed with `pg_restore --list`
- [ ] SQL backup is valid SQL syntax
- [ ] All tables are included
- [ ] All data is included
- [ ] All indexes and constraints are included

### 2. Configuration Verification
- [ ] All config files copied
- [ ] Environment variables backed up
- [ ] Database connections preserved
- [ ] Tenancy configuration saved

### 3. Migration State Verification
- [ ] All migration files copied
- [ ] Migration status recorded
- [ ] Current schema state documented

### 4. Git State Verification
- [ ] Current commit hash recorded
- [ ] Branch information saved
- [ ] Uncommitted changes captured

## Backup Storage Recommendations

### 1. Local Storage
- Store in `storage/backups/` directory
- Use timestamped directories
- Compress large backups

### 2. Remote Storage
- Upload to cloud storage (S3, Google Cloud, etc.)
- Use encrypted storage
- Maintain multiple copies

### 3. Retention Policy
- Keep pre-migration backups for at least 30 days
- Archive successful migration backups
- Document backup locations

## Restoration Procedures

### Full Database Restoration
```bash
# Restore from custom format
pg_restore -h localhost -p 5432 -U username -d database_name \
    --verbose --clean --if-exists backup_file.backup

# Restore from SQL format
psql -h localhost -p 5432 -U username -d database_name < backup_file.sql
```

### Selective Table Restoration
```bash
# Restore specific table
pg_restore -h localhost -p 5432 -U username -d database_name \
    --table=table_name backup_file.backup
```

### Configuration Restoration
```bash
# Restore configuration files
cp -r backup/config/* config/
cp backup/config/.env.backup .env
```

## Emergency Rollback Plan

1. **Stop Application**: Put application in maintenance mode
2. **Restore Database**: Use full database backup
3. **Restore Configuration**: Restore config files
4. **Reset Git State**: Checkout original commit
5. **Run Migrations**: Ensure database state matches code
6. **Test Application**: Verify functionality
7. **Remove Maintenance**: Bring application back online

## Backup Automation

### Scheduled Backups
```bash
# Add to crontab for daily backups
0 2 * * * /path/to/backup-script.sh
```

### Pre-Deployment Hooks
```bash
# Add to deployment script
php artisan backup:pre-migration --verify
```

This comprehensive backup strategy ensures data safety and provides multiple recovery options during the schema migration process.