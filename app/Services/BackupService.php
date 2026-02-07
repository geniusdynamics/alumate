<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Backup;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
     * Create a full database backup.
     */
    public function createDatabaseBackup(?string $type = 'manual', ?Tenant $tenant = null): Backup
    {
        $filename = sprintf(
            'db_backup_%s_%s.sql',
            $tenant?->id ?? 'central',
            now()->format('Y-m-d_H-i-s')
        );

        $path = $this->backupPath . '/' . $filename;

        // Ensure backup directory exists
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

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

        if (!$process->isSuccessful()) {
            Log::error('Database backup failed', [
                'error' => $process->getErrorOutput(),
            ]);
            throw new \Exception('Database backup failed: ' . $process->getErrorOutput());
        }

        // Calculate checksum
        $checksum = hash_file('sha256', $path);
        $size = filesize($path);

        // Store backup record
        $backup = Backup::create([
            'type' => 'database',
            'subtype' => $type,
            'filename' => $filename,
            'path' => $path,
            'size' => $size,
            'checksum' => $checksum,
            'tenant_id' => $tenant?->id,
            'status' => 'completed',
            'completed_at' => now(),
            'metadata' => [
                'schema' => $tenant?->schema_name ?? 'central',
                'driver' => 'pgsql',
            ],
        ]);

        // Upload to cloud storage if configured
        $this->uploadToCloud($backup);

        Log::info('Database backup completed', [
            'backup_id' => $backup->id,
            'size' => $size,
            'type' => $type,
        ]);

        return $backup;
    }

    /**
     * Create a files backup.
     */
    public function createFilesBackup(?string $type = 'manual'): Backup
    {
        $filename = sprintf(
            'files_backup_%s.tar.gz',
            now()->format('Y-m-d_H-i-s')
        );

        $path = $this->backupPath . '/' . $filename;
        $storagePath = storage_path('app');

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

        if (!$process->isSuccessful()) {
            Log::error('Files backup failed', [
                'error' => $process->getErrorOutput(),
            ]);
            throw new \Exception('Files backup failed: ' . $process->getErrorOutput());
        }

        $checksum = hash_file('sha256', $path);
        $size = filesize($path);

        $backup = Backup::create([
            'type' => 'files',
            'subtype' => $type,
            'filename' => $filename,
            'path' => $path,
            'size' => $size,
            'checksum' => $checksum,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->uploadToCloud($backup);

        Log::info('Files backup completed', [
            'backup_id' => $backup->id,
            'size' => $size,
        ]);

        return $backup;
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

        // Verify backup integrity
        if ($verify && !$this->verifyBackup($backup)) {
            throw new \Exception('Backup verification failed');
        }

        if ($backup->type === 'database') {
            return $this->restoreDatabase($backup);
        } elseif ($backup->type === 'files') {
            return $this->restoreFiles($backup);
        }

        return false;
    }

    /**
     * Verify backup integrity.
     */
    public function verifyBackup(Backup $backup): bool
    {
        if (!file_exists($backup->path)) {
            // Try to download from cloud
            $this->downloadFromCloud($backup);
        }

        if (!file_exists($backup->path)) {
            Log::error('Backup file not found', [
                'backup_id' => $backup->id,
                'path' => $backup->path,
            ]);
            return false;
        }

        $currentChecksum = hash_file('sha256', $backup->path);
        $isValid = $currentChecksum === $backup->checksum;

        $backup->update([
            'verified_at' => now(),
            'verification_status' => $isValid ? 'valid' : 'invalid',
        ]);

        return $isValid;
    }

    /**
     * Clean up old backups based on retention policy.
     */
    public function cleanupOldBackups(): array
    {
        $results = [
            'deleted' => 0,
            'errors' => [],
        ];

        $retentionDays = config('backup.retention_days', 30);
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $oldBackups = Backup::where('created_at', '<', $cutoffDate)
            ->where('subtype', '!=', 'manual')
            ->get();

        foreach ($oldBackups as $backup) {
            try {
                // Delete local file
                if (file_exists($backup->path)) {
                    unlink($backup->path);
                }

                // Delete from cloud storage
                if ($backup->cloud_path) {
                    Storage::disk(config('backup.cloud_disk', 's3'))
                        ->delete($backup->cloud_path);
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
            'total_size' => Backup::sum('size'),
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
            ],
        ];
    }

    /**
     * Upload backup to cloud storage.
     */
    private function uploadToCloud(Backup $backup): void
    {
        $cloudDisk = config('backup.cloud_disk');
        if (!$cloudDisk) {
            return;
        }

        try {
            $cloudPath = 'backups/' . $backup->filename;
            Storage::disk($cloudDisk)->put($cloudPath, file_get_contents($backup->path));

            $backup->update([
                'cloud_path' => $cloudPath,
                'cloud_disk' => $cloudDisk,
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
     * Download backup from cloud storage.
     */
    private function downloadFromCloud(Backup $backup): void
    {
        if (!$backup->cloud_path || !$backup->cloud_disk) {
            return;
        }

        try {
            $content = Storage::disk($backup->cloud_disk)->get($backup->cloud_path);
            file_put_contents($backup->path, $content);

            Log::info('Backup downloaded from cloud', [
                'backup_id' => $backup->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to download backup from cloud', [
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
        $config = config('database.connections.pgsql');

        $command = [
            'pg_restore',
            '-h', $config['host'],
            '-p', $config['port'],
            '-U', $config['username'],
            '-d', $config['database'],
            '-c', // Clean (drop) database objects before recreating
            '-v',
            $backup->path,
        ];

        $process = new Process($command);
        $process->setEnv(['PGPASSWORD' => $config['password']]);
        $process->setTimeout(3600); // 1 hour timeout
        $process->run();

        if (!$process->isSuccessful()) {
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
        $storagePath = storage_path('app');

        $command = [
            'tar',
            '-xzf',
            $backup->path,
            '-C',
            dirname($storagePath),
        ];

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
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
