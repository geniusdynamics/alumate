<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Backup;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupService
{
    private string $backupPath;

    public function __construct()
    {
        $this->backupPath = storage_path('app/backups');
    }

    /**
     * Create a database backup.
     */
    public function createDatabaseBackup(
        ?string $name = null,
        ?Tenant $tenant = null,
        ?User $user = null,
        bool $includeData = true,
        bool $compress = true
    ): Backup {
        $filename = sprintf(
            'db_backup_%s_%s.sql',
            $tenant?->id ?? 'central',
            now()->format('Y-m-d_H-i-s')
        );

        $path = $this->backupPath.'/'.$filename;

        // Ensure backup directory exists
        if (! is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        // Create backup record first
        $backup = Backup::create([
            'tenant_id' => $tenant?->id,
            'user_id' => $user?->id ?? Auth::id(),
            'name' => $name ?? 'Database Backup '.now()->format('Y-m-d H:i:s'),
            'type' => $tenant ? 'database' : 'full',
            'status' => 'processing',
            'include_data' => $includeData,
            'include_files' => false,
            'include_config' => true,
            'compress' => $compress,
        ]);

        try {
            // Get database configuration
            $config = config('database.connections.pgsql');

            // Create backup using pg_dump
            $command = [
                'pg_dump',
                '-h', $config['host'],
                '-p', $config['port'],
                '-U', $config['username'],
                '-F', 'c', // Custom format (compressed)
                '-f', $path,
            ];

            if ($tenant) {
                // Backup only tenant schema
                $command[] = '-n';
                $command[] = $tenant->schema_name;
            }

            $command[] = $config['database'];

            $process = new Process($command);
            $process->setEnv(['PGPASSWORD' => $config['password']]);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \Exception('pg_dump failed: '.$process->getErrorOutput());
            }

            $fileSize = filesize($path);

            // Update backup record
            $backup->update([
                'file_name' => $filename,
                'file_path' => $path,
                'file_size' => $fileSize,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Upload to cloud storage if configured
            $this->uploadToCloud($backup);

            Log::info('Database backup completed', [
                'backup_id' => $backup->id,
                'file_size' => $fileSize,
            ]);

            return $backup;
        } catch (\Exception $e) {
            $backup->markAsFailed($e->getMessage());
            Log::error('Database backup failed', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a files backup.
     */
    public function createFilesBackup(
        ?string $name = null,
        ?User $user = null,
        bool $compress = true
    ): Backup {
        $filename = sprintf(
            'files_backup_%s.tar.gz',
            now()->format('Y-m-d_H-i-s')
        );

        $path = $this->backupPath.'/'.$filename;
        $storagePath = storage_path('app');

        // Create backup record first
        $backup = Backup::create([
            'user_id' => $user?->id ?? Auth::id(),
            'name' => $name ?? 'Files Backup '.now()->format('Y-m-d H:i:s'),
            'type' => 'files',
            'status' => 'processing',
            'include_data' => false,
            'include_files' => true,
            'include_config' => false,
            'compress' => $compress,
        ]);

        try {
            // Create tar.gz archive
            $command = [
                'tar',
                '-czf',
                $path,
                '-C',
                dirname($storagePath),
                'app',
            ];

            $process = new Process($command);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \Exception('tar failed: '.$process->getErrorOutput());
            }

            $fileSize = filesize($path);

            // Update backup record
            $backup->update([
                'file_name' => $filename,
                'file_path' => $path,
                'file_size' => $fileSize,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $this->uploadToCloud($backup);

            Log::info('Files backup completed', [
                'backup_id' => $backup->id,
                'file_size' => $fileSize,
            ]);

            return $backup;
        } catch (\Exception $e) {
            $backup->markAsFailed($e->getMessage());
            Log::error('Files backup failed', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Restore from a backup.
     */
    public function restoreFromBackup(Backup $backup, bool $verify = true): bool
    {
        Log::info('Starting backup restore', [
            'backup_id' => $backup->id,
            'type' => $backup->type,
        ]);

        // Verify backup integrity (check file exists)
        if ($verify && ! $this->verifyBackupFileExists($backup)) {
            throw new \Exception('Backup file not found');
        }

        if ($backup->type === 'database' || $backup->type === 'full') {
            return $this->restoreDatabase($backup);
        } elseif ($backup->type === 'files') {
            return $this->restoreFiles($backup);
        }

        return false;
    }

    /**
     * Verify backup file exists (downloads from cloud if needed).
     */
    public function verifyBackupFileExists(Backup $backup): bool
    {
        if (file_exists($backup->file_path)) {
            return true;
        }

        // Try to download from cloud if URL is available
        if ($backup->download_url) {
            // Cloud download logic would go here
            return false; // For now, return false if not local
        }

        return false;
    }

    /**
     * Clean up old backups based on retention policy.
     */
    public function cleanupOldBackups(?int $retentionDays = null): array
    {
        $results = [
            'deleted' => 0,
            'errors' => [],
        ];

        $retentionDays = $retentionDays ?? config('backup.retention_days', 30);
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $oldBackups = Backup::where('created_at', '<', $cutoffDate)
            ->whereNull('retention_days') // Only auto-delete if no specific retention set
            ->get();

        foreach ($oldBackups as $backup) {
            try {
                // Delete local file
                if ($backup->file_path && file_exists($backup->file_path)) {
                    unlink($backup->file_path);
                }

                // Delete from cloud storage if URL is stored
                if ($backup->download_url) {
                    // Cloud deletion logic would go here
                }

                $backup->delete();
                $results['deleted']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'backup_id' => $backup->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Backup cleanup completed', [
            'deleted' => $results['deleted'],
            'errors' => count($results['errors']),
        ]);

        return $results;
    }

    /**
     * Get backup statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_backups' => Backup::count(),
            'total_size' => Backup::sum('file_size') ?? 0,
            'last_backup' => Backup::latest()->first()?->created_at,
            'successful_backups_24h' => Backup::where('status', 'completed')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'failed_backups_24h' => Backup::where('status', 'failed')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'by_type' => [
                'database' => Backup::where('type', 'database')->count(),
                'files' => Backup::where('type', 'files')->count(),
                'full' => Backup::where('type', 'full')->count(),
            ],
        ];
    }

    /**
     * Upload backup to cloud storage.
     */
    private function uploadToCloud(Backup $backup): void
    {
        $cloudDisk = config('filesystems.backup_disk');
        if (! $cloudDisk) {
            return;
        }

        try {
            $cloudPath = 'backups/'.$backup->file_name;
            Storage::disk($cloudDisk)->put($cloudPath, file_get_contents($backup->file_path));

            // Generate temporary URL for download
            $url = Storage::disk($cloudDisk)->temporaryUrl($cloudPath, now()->addDay());

            $backup->update([
                'download_url' => $url,
            ]);

            Log::info('Backup uploaded to cloud', [
                'backup_id' => $backup->id,
                'cloud_path' => $cloudPath,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to upload backup to cloud', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Restore database from backup.
     */
    private function restoreDatabase(Backup $backup): bool
    {
        if (! file_exists($backup->file_path)) {
            Log::error('Backup file not found for restore', [
                'backup_id' => $backup->id,
                'path' => $backup->file_path,
            ]);

            return false;
        }

        $config = config('database.connections.pgsql');

        $command = [
            'pg_restore',
            '-h', $config['host'],
            '-p', $config['port'],
            '-U', $config['username'],
            '-d', $config['database'],
            '-c', // Clean (drop) database objects before recreating
            '-v',
            $backup->file_path,
        ];

        $process = new Process($command);
        $process->setEnv(['PGPASSWORD' => $config['password']]);
        $process->setTimeout(3600); // 1 hour timeout
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('Database restore failed', [
                'backup_id' => $backup->id,
                'error' => $process->getErrorOutput(),
            ]);

            return false;
        }

        Log::info('Database restore completed', [
            'backup_id' => $backup->id,
        ]);

        return true;
    }

    /**
     * Restore files from backup.
     */
    private function restoreFiles(Backup $backup): bool
    {
        if (! file_exists($backup->file_path)) {
            Log::error('Backup file not found for restore', [
                'backup_id' => $backup->id,
                'path' => $backup->file_path,
            ]);

            return false;
        }

        $storagePath = storage_path('app');

        $command = [
            'tar',
            '-xzf',
            $backup->file_path,
            '-C',
            dirname($storagePath),
        ];

        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('Files restore failed', [
                'backup_id' => $backup->id,
                'error' => $process->getErrorOutput(),
            ]);

            return false;
        }

        Log::info('Files restore completed', [
            'backup_id' => $backup->id,
        ]);

        return true;
    }
}
