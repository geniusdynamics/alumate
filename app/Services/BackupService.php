<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BackupCompletedNotification;
use App\Notifications\BackupFailedNotification;
use Carbon\Carbon;

/**
 * BackupService - Comprehensive backup and recovery management for the Alumni Platform
 * 
 * This service provides Laravel-based backup management that integrates with the
 * shell-based backup system for complete system backup and recovery capabilities.
 * 
 * @package App\Services
 * @version 2.0.0
 */
class BackupService
{
    /**
     * Backup types supported by the system
     */
    public const BACKUP_TYPE_DATABASE = 'database';
    public const BACKUP_TYPE_FILES = 'files';
    public const BACKUP_TYPE_CONFIG = 'config';
    public const BACKUP_TYPE_FULL = 'full';

    /**
     * Backup storage providers
     */
    public const STORAGE_LOCAL = 'local';
    public const STORAGE_AWS_S3 = 'aws_s3';
    public const STORAGE_GCP = 'gcp';
    public const STORAGE_AZURE = 'azure';

    /**
     * Backup status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_VERIFIED = 'verified';

    /**
     * Configuration settings
     */
    private array $config;

    /**
     * Default retention policies (in days)
     */
    private array $retentionPolicies = [
        self::BACKUP_TYPE_DATABASE => 30,
        self::BACKUP_TYPE_FILES => 90,
        self::BACKUP_TYPE_CONFIG => 365,
    ];

    /**
     * Create a new BackupService instance
     */
    public function __construct()
    {
        $this->config = config('production.backup', []);
    }

    /**
     * Create a full system backup
     * 
     * @param array $options Backup options
     * @return array Backup result
     */
    public function createFullBackup(array $options = []): array
    {
        $results = [
            'type' => self::BACKUP_TYPE_FULL,
            'started_at' => now()->toISOString(),
            'status' => self::STATUS_IN_PROGRESS,
            'database' => null,
            'files' => null,
            'config' => null,
            'errors' => [],
        ];

        Log::info('Starting full system backup', ['options' => $options]);

        try {
            // Create database backup
            $results['database'] = $this->createDatabaseBackup($options);

            // Create files backup
            $results['files'] = $this->createFilesBackup($options);

            // Create config backup
            $results['config'] = $this->createConfigBackup($options);

            // Cleanup old backups
            $this->cleanupOldBackups();

            // Verify all backups
            $results['verification'] = $this->verifyAllBackups($results);

            $results['status'] = self::STATUS_COMPLETED;
            $results['completed_at'] = now()->toISOString();

            Log::info('Full backup completed successfully', [
                'database_backup' => $results['database']['path'] ?? null,
                'files_backup' => $results['files']['path'] ?? null,
                'config_backup' => $results['config']['path'] ?? null,
            ]);

            // Send success notification
            $this->sendBackupNotification($results, true);

        } catch (\Exception $e) {
            $results['status'] = self::STATUS_FAILED;
            $results['errors'][] = $e->getMessage();
            $results['completed_at'] = now()->toISOString();

            Log::error('Full backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Send failure notification
            $this->sendBackupNotification($results, false);
        }

        // Store backup metadata
        $this->storeBackupMetadata($results);

        return $results;
    }

    /**
     * Create a database backup
     * 
     * @param array $options Backup options
     * @return array Backup result
     */
    public function createDatabaseBackup(array $options = []): array
    {
        $result = [
            'type' => self::BACKUP_TYPE_DATABASE,
            'started_at' => now()->toISOString(),
            'status' => self::STATUS_IN_PROGRESS,
        ];

        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "database_{$timestamp}.sql.gz";
            $path = $this->getBackupPath(self::BACKUP_TYPE_DATABASE, $filename);

            // Ensure backup directory exists
            Storage::disk($this->getStorageDisk())->makeDirectory($this->getBackupPath(self::BACKUP_TYPE_DATABASE));

            // Execute pg_dump via shell
            $command = $this->buildDatabaseBackupCommand($path);
            $output = $this->executeCommand($command);

            if ($output['success']) {
                // Upload to remote storage if configured
                $remotePath = $this->uploadToRemoteStorage($path, self::BACKUP_TYPE_DATABASE);

                $result['status'] = self::STATUS_COMPLETED;
                $result['completed_at'] = now()->toISOString();
                $result['path'] = $path;
                $result['size'] = Storage::disk($this->getStorageDisk())->size($path);
                $result['remote_path'] = $remotePath;
                $result['checksum'] = $this->calculateChecksum($path);

                Log::info('Database backup completed', ['path' => $path]);
            } else {
                throw new \Exception('Database backup command failed: ' . ($output['error'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            $result['status'] = self::STATUS_FAILED;
            $result['error'] = $e->getMessage();
            Log::error('Database backup failed', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Create a files backup
     * 
     * @param array $options Backup options
     * @return array Backup result
     */
    public function createFilesBackup(array $options = []): array
    {
        $result = [
            'type' => self::BACKUP_TYPE_FILES,
            'started_at' => now()->toISOString(),
            'status' => self::STATUS_IN_PROGRESS,
        ];

        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "files_{$timestamp}.tar.gz";
            $path = $this->getBackupPath(self::BACKUP_TYPE_FILES, $filename);

            // Ensure backup directory exists
            Storage::disk($this->getStorageDisk())->makeDirectory($this->getBackupPath(self::BACKUP_TYPE_FILES));

            // Create tar archive of storage directories
            $storagePath = storage_path();
            $command = "tar -czf {$path} -C " . dirname($storagePath) . " " . basename($storagePath) . " --exclude='*.log' --exclude='*.tmp' --exclude='node_modules' --exclude='vendor' 2>&1";
            $output = $this->executeCommand($command);

            if ($output['success'] && file_exists($path)) {
                // Upload to remote storage if configured
                $remotePath = $this->uploadToRemoteStorage($path, self::BACKUP_TYPE_FILES);

                $result['status'] = self::STATUS_COMPLETED;
                $result['completed_at'] = now()->toISOString();
                $result['path'] = $path;
                $result['size'] = filesize($path);
                $result['remote_path'] = $remotePath;
                $result['checksum'] = $this->calculateChecksum($path);

                Log::info('Files backup completed', ['path' => $path]);
            } else {
                throw new \Exception('Files backup command failed: ' . ($output['error'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            $result['status'] = self::STATUS_FAILED;
            $result['error'] = $e->getMessage();
            Log::error('Files backup failed', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Create a configuration backup
     * 
     * @param array $options Backup options
     * @return array Backup result
     */
    public function createConfigBackup(array $options = []): array
    {
        $result = [
            'type' => self::BACKUP_TYPE_CONFIG,
            'started_at' => now()->toISOString(),
            'status' => self::STATUS_IN_PROGRESS,
        ];

        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "config_{$timestamp}.tar.gz";
            $path = $this->getBackupPath(self::BACKUP_TYPE_CONFIG, $filename);

            // Ensure backup directory exists
            Storage::disk($this->getStorageDisk())->makeDirectory($this->getBackupPath(self::BACKUP_TYPE_CONFIG));

            // Create tar archive of configuration files
            $appPath = base_path();
            $configFiles = [
                '.env*',
                'config/',
                'infrastructure/',
                'docker-compose*.yml',
            ];

            $command = "tar -czf {$path} -C {$appPath} " . implode(' ', $configFiles) . " --exclude='*.log' --exclude='*.tmp' 2>&1";
            $output = $this->executeCommand($command);

            if ($output['success'] && file_exists($path)) {
                // Upload to remote storage if configured
                $remotePath = $this->uploadToRemoteStorage($path, self::BACKUP_TYPE_CONFIG);

                $result['status'] = self::STATUS_COMPLETED;
                $result['completed_at'] = now()->toISOString();
                $result['path'] = $path;
                $result['size'] = filesize($path);
                $result['remote_path'] = $remotePath;
                $result['checksum'] = $this->calculateChecksum($path);

                Log::info('Configuration backup completed', ['path' => $path]);
            } else {
                throw new \Exception('Configuration backup command failed: ' . ($output['error'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            $result['status'] = self::STATUS_FAILED;
            $result['error'] = $e->getMessage();
            Log::error('Configuration backup failed', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Restore database from backup
     * 
     * @param string $backupPath Path to backup file
     * @param array $options Restore options
     * @return array Restore result
     */
    public function restoreDatabase(string $backupPath, array $options = []): array
    {
        $result = [
            'type' => self::BACKUP_TYPE_DATABASE,
            'restore_from' => $backupPath,
            'started_at' => now()->toISOString(),
            'status' => self::STATUS_IN_PROGRESS,
        ];

        try {
            // Verify backup file exists
            if (!file_exists($backupPath)) {
                throw new \Exception("Backup file not found: {$backupPath}");
            }

            // Verify backup integrity
            $verification = $this->verifyBackup($backupPath);
            if (!$verification['is_valid']) {
                throw new \Exception("Backup verification failed: " . implode(', ', $verification['issues']));
            }

            // Put application in maintenance mode
            $this->putApplicationInMaintenanceMode();

            // Execute restore command
            $command = $this->buildDatabaseRestoreCommand($backupPath);
            $output = $this->executeCommand($command);

            if ($output['success']) {
                // Clear application caches
                $this->clearApplicationCaches();
                $this->putApplicationOutOfMaintenanceMode();

                $result['status'] = self::STATUS_COMPLETED;
                $result['completed_at'] = now()->toISOString();
                $result['message'] = 'Database restored successfully';

                Log::info('Database restore completed', ['backup' => $backupPath]);
            } else {
                throw new \Exception('Database restore command failed: ' . ($output['error'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            $this->putApplicationOutOfMaintenanceMode();
            $result['status'] = self::STATUS_FAILED;
            $result['error'] = $e->getMessage();
            Log::error('Database restore failed', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Restore files from backup
     * 
     * @param string $backupPath Path to backup file
     * @param array $options Restore options
     * @return array Restore result
     */
    public function restoreFiles(string $backupPath, array $options = []): array
    {
        $result = [
            'type' => self::BACKUP_TYPE_FILES,
            'restore_from' => $backupPath,
            'started_at' => now()->toISOString(),
            'status' => self::STATUS_IN_PROGRESS,
        ];

        try {
            // Verify backup file exists
            if (!file_exists($backupPath)) {
                throw new \Exception("Backup file not found: {$backupPath}");
            }

            // Create temporary extraction directory
            $tempDir = storage_path('temp_restore_' . now()->format('Y-m-d_H-i-s'));
            mkdir($tempDir, 0755, true);

            // Extract archive
            $command = "tar -xzf {$backupPath} -C {$tempDir} 2>&1";
            $output = $this->executeCommand($command);

            if ($output['success']) {
                // Copy files to storage directory
                $storagePath = storage_path();
                $this->copyFilesRecursively("{$tempDir}/storage", $storagePath);

                // Clean up temporary directory
                $this->removeDirectory($tempDir);

                $result['status'] = self::STATUS_COMPLETED;
                $result['completed_at'] = now()->toISOString();
                $result['message'] = 'Files restored successfully';

                Log::info('Files restore completed', ['backup' => $backupPath]);
            } else {
                $this->removeDirectory($tempDir);
                throw new \Exception('Files restore command failed: ' . ($output['error'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            $result['status'] = self::STATUS_FAILED;
            $result['error'] = $e->getMessage();
            Log::error('Files restore failed', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Verify backup integrity
     * 
     * @param string $backupPath Path to backup file
     * @return array Verification result
     */
    public function verifyBackup(string $backupPath): array
    {
        $verification = [
            'backup_path' => $backupPath,
            'verified_at' => now()->toISOString(),
            'is_valid' => false,
            'issues' => [],
            'warnings' => [],
        ];

        try {
            // Check file exists
            if (!file_exists($backupPath)) {
                $verification['issues'][] = 'Backup file not found';
                return $verification;
            }

            // Check file size (minimum 1KB)
            $fileSize = filesize($backupPath);
            $minSize = 1024;

            if ($fileSize < $minSize) {
                $verification['issues'][] = "Backup file too small: {$fileSize} bytes (minimum {$minSize} bytes)";
            }

            // Verify compression for gz files
            if (pathinfo($backupPath, PATHINFO_EXTENSION) === 'gz') {
                $gzipTest = $this->executeCommand("gzip -t {$backupPath} 2>&1");
                if (!$gzipTest['success']) {
                    $verification['issues'][] = 'Compressed backup file is corrupted';
                }
            }

            // Calculate and verify checksum
            $storedChecksum = $this->getStoredChecksum($backupPath);
            if ($storedChecksum && $storedChecksum !== $this->calculateChecksum($backupPath)) {
                $verification['issues'][] = 'Checksum verification failed - file may be corrupted';
            }

            // Check backup age
            $backupAge = now()->diffInDays(Carbon::parse(date('Y-m-d H:i:s', filemtime($backupPath))));
            if ($backupAge > 365) {
                $verification['warnings'][] = "Backup is very old ({$backupAge} days)";
            }

            $verification['is_valid'] = empty($verification['issues']);

        } catch (\Exception $e) {
            $verification['issues'][] = "Verification failed: {$e->getMessage()}";
        }

        return $verification;
    }

    /**
     * Verify all recent backups
     * 
     * @param array $recentBackups Recent backup results
     * @return array Verification result
     */
    public function verifyAllBackups(array $recentBackups): array
    {
        $result = [
            'verified_at' => now()->toISOString(),
            'backups_verified' => [],
            'issues' => [],
        ];

        foreach (['database', 'files', 'config'] as $type) {
            if (isset($recentBackups[$type]['path'])) {
                $verification = $this->verifyBackup($recentBackups[$type]['path']);
                $result['backups_verified'][$type] = $verification;

                if (!$verification['is_valid']) {
                    $result['issues'][] = "{$type} backup verification failed";
                }
            }
        }

        return $result;
    }

    /**
     * Cleanup old backups based on retention policy
     * 
     * @param array $retentionPolicy Custom retention policy
     * @return array Cleanup result
     */
    public function cleanupOldBackups(array $retentionPolicy = []): array
    {
        $result = [
            'started_at' => now()->toISOString(),
            'deleted_backups' => [],
            'total_space_freed' => 0,
        ];

        $policy = array_merge($this->retentionPolicies, $retentionPolicy);

        foreach ($policy as $backupType => $retentionDays) {
            $backups = $this->listBackups($backupType);
            $cutoffDate = now()->subDays($retentionDays);

            foreach ($backups as $backup) {
                if ($backup['created_at'] < $cutoffDate->toISOString()) {
                    try {
                        $filePath = $backup['path'];
                        $fileSize = file_exists($filePath) ? filesize($filePath) : 0;

                        if (Storage::disk($this->getStorageDisk())->exists($filePath)) {
                            Storage::disk($this->getStorageDisk())->delete($filePath);
                            $result['deleted_backups'][] = $filePath;
                            $result['total_space_freed'] += $fileSize;
                        }

                        // Also delete from remote storage
                        if (isset($backup['remote_path'])) {
                            $this->deleteFromRemoteStorage($backup['remote_path']);
                        }

                        Log::info('Old backup deleted', ['path' => $filePath, 'type' => $backupType]);
                    } catch (\Exception $e) {
                        Log::error('Failed to delete old backup', ['error' => $e->getMessage()]);
                    }
                }
            }
        }

        $result['completed_at'] = now()->toISOString();
        $result['count'] = count($result['deleted_backups']);

        return $result;
    }

    /**
     * List all backups
     * 
     * @param string $type Backup type filter
     * @return array List of backups
     */
    public function listBackups(string $type = null): array
    {
        $backups = [];
        $backupTypes = $type ? [$type] : [self::BACKUP_TYPE_DATABASE, self::BACKUP_TYPE_FILES, self::BACKUP_TYPE_CONFIG];

        foreach ($backupTypes as $backupType) {
            $path = $this->getBackupPath($backupType);

            if (Storage::disk($this->getStorageDisk())->exists($path)) {
                $files = Storage::disk($this->getStorageDisk())->files($path);

                foreach ($files as $file) {
                    $backups[] = [
                        'type' => $backupType,
                        'path' => $file,
                        'filename' => basename($file),
                        'size' => Storage::disk($this->getStorageDisk())->size($file),
                        'created_at' => Carbon::parse(
                            Storage::disk($this->getStorageDisk())->lastModified($file)
                        )->toISOString(),
                    ];
                }
            }
        }

        // Sort by creation date descending
        usort($backups, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return $backups;
    }

    /**
     * Get backup status and statistics
     * 
     * @return array Backup status
     */
    public function getBackupStatus(): array
    {
        $status = [
            'generated_at' => now()->toISOString(),
            'backups' => [],
            'total_size' => 0,
            'total_count' => 0,
        ];

        foreach ([self::BACKUP_TYPE_DATABASE, self::BACKUP_TYPE_FILES, self::BACKUP_TYPE_CONFIG] as $type) {
            $backups = $this->listBackups($type);
            $typeSize = array_sum(array_column($backups, 'size'));

            $status['backups'][$type] = [
                'count' => count($backups),
                'size' => $typeSize,
                'oldest_backup' => $backups[array_key_last($backups)]['created_at'] ?? null,
                'newest_backup' => $backups[0]['created_at'] ?? null,
            ];

            $status['total_size'] += $typeSize;
            $status['total_count'] += count($backups);
        }

        // Add retention information
        $status['retention'] = $this->retentionPolicies;

        return $status;
    }

    /**
     * Execute scheduled backup tasks
     * 
     * This method is called by Laravel's scheduler
     * 
     * @return array Execution result
     */
    public function runScheduledBackup(): array
    {
        $result = [
            'executed_at' => now()->toISOString(),
            'backups' => [],
        ];

        // Get current hour
        $currentHour = now()->hour;

        // Database backup (daily at 1 AM)
        if ($currentHour === 1) {
            $result['backups']['database'] = $this->createDatabaseBackup();
        }

        // Files backup (daily at 2 AM)
        if ($currentHour === 2) {
            $result['backups']['files'] = $this->createFilesBackup();
        }

        // Config backup (weekly on Monday at 3 AM)
        if ($currentHour === 3 && now()->dayOfWeek === Carbon::MONDAY) {
            $result['backups']['config'] = $this->createConfigBackup();
        }

        // Cleanup (daily at 5 AM)
        if ($currentHour === 5) {
            $result['cleanup'] = $this->cleanupOldBackups();
        }

        return $result;
    }

    // ============================================================================
    // Private Helper Methods
    // ============================================================================

    /**
     * Get the storage disk for backups
     */
    private function getStorageDisk(): string
    {
        return $this->config['storage_disk'] ?? 'local';
    }

    /**
     * Get backup path for a type
     */
    private function getBackupPath(string $type, string $filename = ''): string
    {
        $basePath = "backups/{$type}";
        return $filename ? "{$basePath}/{$filename}" : $basePath;
    }

    /**
     * Build database backup command
     */
    private function buildDatabaseBackupCommand(string $outputPath): string
    {
        $host = config('database.connections.pgsql.host', 'db');
        $port = config('database.connections.pgsql.port', '5432');
        $database = config('database.connections.pgsql.database', 'alumni_platform');
        $username = config('database.connections.pgsql.username', '');
        $password = config('database.connections.pgsql.password', '');

        $pgPassword = $password ? "PGPASSWORD='{$password}' " : '';

        return "{$pgPassword}pg_dump -h {$host} -U {$username} -d {$database} --format=custom --compress=9 --no-owner --no-privileges --file='{$outputPath}' 2>&1";
    }

    /**
     * Build database restore command
     */
    private function buildDatabaseRestoreCommand(string $backupPath): string
    {
        $host = config('database.connections.pgsql.host', 'db');
        $port = config('database.connections.pgsql.port', '5432');
        $database = config('database.connections.pgsql.database', 'alumni_platform');
        $username = config('database.connections.pgsql.username', '');
        $password = config('database.connections.pgsql.password', '');

        $pgPassword = $password ? "PGPASSWORD='{$password}' " : '';

        return "{$pgPassword}pg_restore -h {$host} -U {$username} -d {$database} --clean --if-exists --verbose '{$backupPath}' 2>&1";
    }

    /**
     * Execute shell command
     */
    private function executeCommand(string $command): array
    {
        $output = [];
        $returnVar = 0;

        exec($command, $output, $returnVar);

        return [
            'success' => $returnVar === 0,
            'output' => implode("\n", $output),
            'error' => $returnVar !== 0 ? implode("\n", $output) : null,
        ];
    }

    /**
     * Upload backup to remote storage
     */
    private function uploadToRemoteStorage(string $localPath, string $backupType): ?string
    {
        $provider = $this->config['provider'] ?? 'aws_s3';

        if ($provider === self::STORAGE_AWS_S3 && config('filesystems.disks.s3.key')) {
            $bucket = $this->config['aws']['bucket'] ?? config('filesystems.disks.s3.bucket');
            $key = "backups/{$backupType}/" . basename($localPath);

            try {
                Storage::disk('s3')->put($key, file_get_contents($localPath), [
                    'StorageClass' => 'STANDARD_IA',
                ]);

                return "s3://{$bucket}/{$key}";
            } catch (\Exception $e) {
                Log::warning('Failed to upload backup to S3', ['error' => $e->getMessage()]);
            }
        }

        return null;
    }

    /**
     * Delete from remote storage
     */
    private function deleteFromRemoteStorage(string $remotePath): bool
    {
        if (str_starts_with($remotePath, 's3://')) {
            $key = str_replace('s3://' . config('filesystems.disks.s3.bucket') . '/', '', $remotePath);
            try {
                return Storage::disk('s3')->delete($key);
            } catch (\Exception $e) {
                Log::warning('Failed to delete backup from S3', ['error' => $e->getMessage()]);
            }
        }

        return false;
    }

    /**
     * Calculate file checksum
     */
    private function calculateChecksum(string $filePath): string
    {
        return hash_file('sha256', $filePath);
    }

    /**
     * Get stored checksum for a backup
     */
    private function getStoredChecksum(string $backupPath): ?string
    {
        $checksumPath = $backupPath . '.sha256';

        if (file_exists($checksumPath)) {
            return trim(file_get_contents($checksumPath));
        }

        return null;
    }

    /**
     * Put application in maintenance mode
     */
    private function putApplicationInMaintenanceMode(): void
    {
        $this->executeCommand('php ' . base_path('artisan') . ' down --message="Backup restoration in progress" --retry=60');
    }

    /**
     * Take application out of maintenance mode
     */
    private function putApplicationOutOfMaintenanceMode(): void
    {
        $this->executeCommand('php ' . base_path('artisan') . ' up');
    }

    /**
     * Clear application caches
     */
    private function clearApplicationCaches(): void
    {
        $commands = ['cache:clear', 'config:clear', 'route:clear', 'view:clear', 'event:clear'];

        foreach ($commands as $command) {
            $this->executeCommand('php ' . base_path('artisan') . ' ' . $command);
        }
    }

    /**
     * Copy files recursively
     */
    private function copyFilesRecursively(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $dir = opendir($source);

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = "{$source}/{$file}";
            $destPath = "{$destination}/{$file}";

            if (is_dir($sourcePath)) {
                $this->copyFilesRecursively($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }

        closedir($dir);
    }

    /**
     * Remove directory recursively
     */
    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = array_diff(scandir($path), ['.', '..']);

        foreach ($files as $file) {
            $filePath = "{$path}/{$file}";
            is_dir($filePath) ? $this->removeDirectory($filePath) : unlink($filePath);
        }

        rmdir($path);
    }

    /**
     * Store backup metadata
     */
    private function storeBackupMetadata(array $backupData): void
    {
        $metadataPath = 'backups/metadata.json';
        $metadata = [];

        if (Storage::disk($this->getStorageDisk())->exists($metadataPath)) {
            $metadata = json_decode(Storage::disk($this->getStorageDisk())->get($metadataPath), true) ?? [];
        }

        $metadata[] = [
            'id' => uniqid('backup_'),
            'type' => $backupData['type'],
            'status' => $backupData['status'],
            'started_at' => $backupData['started_at'],
            'completed_at' => $backupData['completed_at'] ?? null,
            'database_backup' => $backupData['database']['path'] ?? null,
            'files_backup' => $backupData['files']['path'] ?? null,
            'config_backup' => $backupData['config']['path'] ?? null,
            'errors' => $backupData['errors'] ?? [],
        ];

        Storage::disk($this->getStorageDisk())->put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
    }

    /**
     * Send backup notification
     */
    private function sendBackupNotification(array $backupData, bool $success): void
    {
        $recipients = config('production.alerts.channels.email.recipients', []);

        if (!empty($recipients)) {
            $notification = $success
                ? new BackupCompletedNotification($backupData)
                : new BackupFailedNotification($backupData);

            try {
                Notification::route('mail', $recipients)->notify($notification);
            } catch (\Exception $e) {
                Log::warning('Failed to send backup notification', ['error' => $e->getMessage()]);
            }
        }
    }
}
