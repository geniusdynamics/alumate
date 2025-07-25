<?php

namespace App\Console\Commands;

use App\Models\BackupLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class CreateSystemBackup extends Command
{
    protected $signature = 'backup:create {--type=full : Type of backup (full, incremental, differential)} {--compress : Compress the backup file}';
    protected $description = 'Create a system backup including database and files';

    public function handle()
    {
        $type = $this->option('type');
        $compress = $this->option('compress');
        
        $this->info("Starting {$type} backup...");
        
        $backupLog = BackupLog::create([
            'backup_type' => $type,
            'status' => 'started',
            'started_at' => now(),
        ]);

        try {
            $backupPath = $this->createBackup($type, $compress);
            
            $fileSize = Storage::size($backupPath);
            
            $backupLog->update([
                'status' => 'completed',
                'completed_at' => now(),
                'file_path' => $backupPath,
                'file_size' => $fileSize,
                'metadata' => [
                    'compressed' => $compress,
                    'database_size' => $this->getDatabaseSize(),
                    'files_count' => $this->getFilesCount(),
                ],
            ]);

            $this->info("Backup completed successfully!");
            $this->info("File: {$backupPath}");
            $this->info("Size: " . $this->formatBytes($fileSize));

            // Clean up old backups
            $this->cleanupOldBackups();

        } catch (\Exception $e) {
            $backupLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->error("Backup failed: " . $e->getMessage());
            Log::error("Backup failed", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            return 1;
        }

        return 0;
    }

    private function createBackup($type, $compress)
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupDir = "backups/{$timestamp}";
        
        Storage::makeDirectory($backupDir);

        // Create database backup
        $this->info("Backing up database...");
        $dbBackupPath = $this->createDatabaseBackup($backupDir);

        // Create files backup based on type
        $this->info("Backing up files...");
        $filesBackupPath = $this->createFilesBackup($backupDir, $type);

        // Create backup manifest
        $manifest = [
            'created_at' => now()->toISOString(),
            'type' => $type,
            'database_backup' => $dbBackupPath,
            'files_backup' => $filesBackupPath,
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
        ];

        Storage::put("{$backupDir}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));

        // Compress if requested
        if ($compress) {
            $this->info("Compressing backup...");
            $compressedPath = $this->compressBackup($backupDir);
            
            // Clean up uncompressed files
            Storage::deleteDirectory($backupDir);
            
            return $compressedPath;
        }

        return $backupDir;
    }

    private function createDatabaseBackup($backupDir)
    {
        $config = config('database.connections.' . config('database.default'));
        $filename = "database_" . now()->format('Y-m-d_H-i-s') . ".sql";
        $backupPath = "{$backupDir}/{$filename}";

        if ($config['driver'] === 'mysql') {
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
                $config['host'],
                $config['port'],
                $config['username'],
                $config['password'],
                $config['database'],
                storage_path("app/{$backupPath}")
            );
        } elseif ($config['driver'] === 'pgsql') {
            $command = sprintf(
                'PGPASSWORD=%s pg_dump --host=%s --port=%s --username=%s --dbname=%s > %s',
                $config['password'],
                $config['host'],
                $config['port'],
                $config['username'],
                $config['database'],
                storage_path("app/{$backupPath}")
            );
        } else {
            throw new \Exception("Unsupported database driver: " . $config['driver']);
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception("Database backup failed: " . $process->getErrorOutput());
        }

        return $backupPath;
    }

    private function createFilesBackup($backupDir, $type)
    {
        $filename = "files_" . now()->format('Y-m-d_H-i-s') . ".tar";
        $backupPath = "{$backupDir}/{$filename}";
        $fullBackupPath = storage_path("app/{$backupPath}");

        // Directories to backup
        $directories = [
            'storage/app/public',
            'storage/app/uploads',
            '.env',
            'config/',
        ];

        // Create tar archive
        $command = "tar -cf {$fullBackupPath}";
        
        foreach ($directories as $dir) {
            if (file_exists(base_path($dir))) {
                $command .= " -C " . base_path() . " {$dir}";
            }
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception("Files backup failed: " . $process->getErrorOutput());
        }

        return $backupPath;
    }

    private function compressBackup($backupDir)
    {
        $compressedFilename = basename($backupDir) . ".tar.gz";
        $compressedPath = "backups/{$compressedFilename}";
        $fullCompressedPath = storage_path("app/{$compressedPath}");

        $command = "tar -czf {$fullCompressedPath} -C " . storage_path("app") . " {$backupDir}";
        
        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception("Compression failed: " . $process->getErrorOutput());
        }

        return $compressedPath;
    }

    private function cleanupOldBackups()
    {
        $retentionDays = config('backup.retention_days', 30);
        $cutoffDate = now()->subDays($retentionDays);

        $oldBackups = BackupLog::where('created_at', '<', $cutoffDate)
            ->where('status', 'completed')
            ->get();

        foreach ($oldBackups as $backup) {
            if ($backup->file_path && Storage::exists($backup->file_path)) {
                Storage::delete($backup->file_path);
                $this->info("Deleted old backup: {$backup->file_path}");
            }
            $backup->delete();
        }
    }

    private function getDatabaseSize()
    {
        try {
            $config = config('database.connections.' . config('database.default'));
            
            if ($config['driver'] === 'mysql') {
                $result = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'size_mb' FROM information_schema.tables WHERE table_schema = ?", [$config['database']]);
                return $result[0]->size_mb . ' MB';
            } elseif ($config['driver'] === 'pgsql') {
                $result = DB::select("SELECT pg_size_pretty(pg_database_size(?)) as size", [$config['database']]);
                return $result[0]->size;
            }
        } catch (\Exception $e) {
            return 'Unknown';
        }

        return 'Unknown';
    }

    private function getFilesCount()
    {
        try {
            $count = 0;
            $directories = ['storage/app/public', 'storage/app/uploads'];
            
            foreach ($directories as $dir) {
                $fullPath = base_path($dir);
                if (is_dir($fullPath)) {
                    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullPath));
                    $count += iterator_count($iterator);
                }
            }
            
            return $count;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}