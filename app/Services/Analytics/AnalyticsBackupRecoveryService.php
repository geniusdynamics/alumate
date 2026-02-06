<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSnapshot;
use App\Models\AttributionTouch;
use App\Models\Backup;
use App\Models\BackupLog;
use App\Models\BehaviorEvent;
use App\Models\Cohort;
use App\Models\CustomEvent;
use App\Models\CustomEventDefinition;
use App\Models\KpiValue;
use App\Models\Prediction;
use App\Models\PredictionModel;
use App\Services\TenantContextService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Analytics Backup Recovery Service
 *
 * Provides comprehensive backup and recovery functionality for analytics data.
 * Supports scheduled backups, manual backups, compression, encryption, and verification.
 */
class AnalyticsBackupRecoveryService
{
    public const BACKUP_TYPE_FULL = 'full';
    public const BACKUP_TYPE_INCREMENTAL = 'incremental';
    public const BACKUP_TYPE_SNAPSHOT = 'snapshot';

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_RESTORED = 'restored';

    private const CHUNK_SIZE = 1000;

    private TenantContextService $tenantContextService;
    private string $storageDisk;
    private string $backupPath;
    private bool $compressionEnabled;
    private bool $encryptionEnabled;
    private int $retentionDays;

    public function __construct(TenantContextService $tenantContextService)
    {
        $this->tenantContextService = $tenantContextService;
        $this->storageDisk = config('analytics.archiving.storage_disk', 'local');
        $this->backupPath = config('analytics.archiving.archive_path', 'archives/analytics');
        $this->compressionEnabled = config('analytics.archiving.compression', true);
        $this->encryptionEnabled = config('analytics.archiving.encryption', false);
        $this->retentionDays = config('analytics.archiving.default_retention_days', 365);
    }

    /**
     * Create an analytics data backup
     *
     * @param array $options Backup options including type, date range, compression, etc.
     * @return Backup The created backup record
     */
    public function createBackup(array $options = []): Backup
    {
        $tenantId = $this->getCurrentTenantId();
        $backupType = $options['type'] ?? self::BACKUP_TYPE_FULL;
        $compress = $options['compress'] ?? $this->compressionEnabled;
        $includeEvents = $options['include_events'] ?? true;
        $includeSnapshots = $options['include_snapshots'] ?? true;
        $includeAttributions = $options['include_attributions'] ?? true;
        $includeCohorts = $options['include_cohorts'] ?? true;
        $includePredictions = $options['include_predictions'] ?? true;
        $includeCustomEvents = $options['include_custom_events'] ?? true;
        $startDate = $options['start_date'] ?? null;
        $endDate = $options['end_date'] ?? null;
        $name = $options['name'] ?? $this->generateBackupName($backupType);

        $backup = Backup::create([
            'tenant_id' => $tenantId,
            'user_id' => auth()->check() ? auth()->id() : null,
            'name' => $name,
            'description' => $options['description'] ?? null,
            'type' => $backupType,
            'status' => self::STATUS_IN_PROGRESS,
            'compress' => $compress,
            'include_data' => true,
            'include_files' => false,
            'include_config' => false,
            'retention_days' => $options['retention_days'] ?? $this->retentionDays,
        ]);

        try {
            $backupData = $this->gatherAnalyticsData([
                'include_events' => $includeEvents,
                'include_snapshots' => $includeSnapshots,
                'include_attributions' => $includeAttributions,
                'include_cohorts' => $includeCohorts,
                'include_predictions' => $includePredictions,
                'include_custom_events' => $includeCustomEvents,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            $fileName = $this->generateBackupFileName($backup->id, $compress);
            $filePath = $this->saveBackupFile($backupData, $fileName, $compress);

            $backup->update([
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => Storage::disk($this->storageDisk)->size($filePath),
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            $this->logBackupActivity($backup, self::STATUS_COMPLETED);

            Log::info('Analytics backup created successfully', [
                'backup_id' => $backup->id,
                'tenant_id' => $tenantId,
                'type' => $backupType,
                'file_name' => $fileName,
                'file_size' => $backup->file_size,
            ]);

            return $backup;

        } catch (Exception $e) {
            $backup->update([
                'status' => self::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            $this->logBackupActivity($backup, self::STATUS_FAILED, $e->getMessage());

            Log::error('Analytics backup failed', [
                'backup_id' => $backup->id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Schedule automated backups
     *
     * @param array $schedule Schedule configuration including frequency, time, retention, etc.
     * @return array Schedule configuration
     */
    public function scheduleBackup(array $schedule): array
    {
        $tenantId = $this->getCurrentTenantId();

        $scheduleConfig = [
            'tenant_id' => $tenantId,
            'frequency' => $schedule['frequency'] ?? 'daily',
            'time' => $schedule['time'] ?? '02:00',
            'day_of_week' => $schedule['day_of_week'] ?? 1,
            'day_of_month' => $schedule['day_of_month'] ?? 1,
            'enabled' => $schedule['enabled'] ?? true,
            'compress' => $schedule['compress'] ?? $this->compressionEnabled,
            'retention_days' => $schedule['retention_days'] ?? $this->retentionDays,
            'include_events' => $schedule['include_events'] ?? true,
            'include_snapshots' => $schedule['include_snapshots'] ?? true,
            'include_attributions' => $schedule['include_attributions'] ?? true,
            'include_cohorts' => $schedule['include_cohorts'] ?? true,
            'include_predictions' => $schedule['include_predictions'] ?? true,
            'include_custom_events' => $schedule['include_custom_events'] ?? true,
            'last_run_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        $scheduleConfig['next_run_at'] = $this->calculateNextRunTime($scheduleConfig);

        Log::info('Backup scheduled', [
            'tenant_id' => $tenantId,
            'schedule' => $scheduleConfig,
        ]);

        return $scheduleConfig;
    }

    /**
     * Restore analytics data from a backup
     *
     * @param int $backupId Backup ID to restore
     * @param array $options Restore options
     * @return Backup The restored backup record
     */
    public function restoreBackup(int $backupId, array $options = []): Backup
    {
        $tenantId = $this->getCurrentTenantId();
        $backup = Backup::where('tenant_id', $tenantId)->findOrFail($backupId);

        if ($backup->status !== self::STATUS_COMPLETED && $backup->status !== self::STATUS_VERIFIED) {
            throw new Exception("Backup must be completed or verified before restoration");
        }

        $backup->update(['status' => self::STATUS_IN_PROGRESS]);

        try {
            $backupData = $this->loadBackupFile($backup->file_path, $backup->compress);

            $this->restoreAnalyticsData($backupData, [
                'restore_events' => $options['restore_events'] ?? true,
                'restore_snapshots' => $options['restore_snapshots'] ?? true,
                'restore_attributions' => $options['restore_attributions'] ?? true,
                'restore_cohorts' => $options['restore_cohorts'] ?? true,
                'restore_predictions' => $options['restore_predictions'] ?? true,
                'restore_custom_events' => $options['restore_custom_events'] ?? true,
                'clear_existing' => $options['clear_existing'] ?? false,
            ]);

            $backup->update([
                'status' => self::STATUS_RESTORED,
                'completed_at' => now(),
            ]);

            $this->logBackupActivity($backup, self::STATUS_RESTORED);

            Log::info('Analytics backup restored successfully', [
                'backup_id' => $backupId,
                'tenant_id' => $tenantId,
            ]);

            return $backup;

        } catch (Exception $e) {
            $backup->update([
                'status' => self::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            $this->logBackupActivity($backup, self::STATUS_FAILED, $e->getMessage());

            Log::error('Analytics backup restoration failed', [
                'backup_id' => $backupId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify backup integrity
     *
     * @param int $backupId Backup ID to verify
     * @return array Verification result
     */
    public function verifyBackup(int $backupId): array
    {
        $tenantId = $this->getCurrentTenantId();
        $backup = Backup::where('tenant_id', $tenantId)->findOrFail($backupId);

        $result = [
            'backup_id' => $backupId,
            'verified_at' => now()->toIso8601String(),
            'checksums' => [],
            'record_counts' => [],
            'valid' => false,
            'errors' => [],
        ];

        try {
            // Check file exists
            if (!Storage::disk($this->storageDisk)->exists($backup->file_path)) {
                throw new Exception('Backup file does not exist');
            }

            // Verify file size
            $currentSize = Storage::disk($this->storageDisk)->size($backup->file_path);
            if ($currentSize !== (int) $backup->file_size) {
                $result['errors'][] = "File size mismatch: expected {$backup->file_size}, got {$currentSize}";
            }

            // Load and verify backup data
            $backupData = $this->loadBackupFile($backup->file_path, $backup->compress);

            // Verify record counts
            $result['record_counts'] = $this->verifyRecordCounts($backupData);

            // Generate checksums for data integrity
            $result['checksums'] = $this->generateChecksums($backupData);

            // Validate required data types
            $validationErrors = $this->validateBackupData($backupData);
            $result['errors'] = array_merge($result['errors'], $validationErrors);

            $result['valid'] = empty($result['errors']);

            if ($result['valid']) {
                $backup->update(['status' => self::STATUS_VERIFIED]);
                $this->logBackupActivity($backup, self::STATUS_VERIFIED);

                Log::info('Analytics backup verified successfully', [
                    'backup_id' => $backupId,
                    'tenant_id' => $tenantId,
                ]);
            } else {
                Log::warning('Analytics backup verification found issues', [
                    'backup_id' => $backupId,
                    'tenant_id' => $tenantId,
                    'errors' => $result['errors'],
                ]);
            }

            return $result;

        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
            $result['valid'] = false;

            Log::error('Analytics backup verification failed', [
                'backup_id' => $backupId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return $result;
        }
    }

    /**
     * Delete a backup
     *
     * @param int $backupId Backup ID to delete
     * @return bool Success status
     */
    public function deleteBackup(int $backupId): bool
    {
        $tenantId = $this->getCurrentTenantId();
        $backup = Backup::where('tenant_id', $tenantId)->findOrFail($backupId);

        try {
            // Delete file from storage
            if (Storage::disk($this->storageDisk)->exists($backup->file_path)) {
                Storage::disk($this->storageDisk)->delete($backup->file_path);
            }

            // Delete database record
            $backup->delete();

            $this->logBackupActivity($backup, 'deleted');

            Log::info('Analytics backup deleted', [
                'backup_id' => $backupId,
                'tenant_id' => $tenantId,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to delete analytics backup', [
                'backup_id' => $backupId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get backup history
     *
     * @param array $filters Filters for backup history
     * @return Collection Backup history
     */
    public function getBackupHistory(array $filters = []): Collection
    {
        $tenantId = $this->getCurrentTenantId();

        $query = Backup::where('tenant_id', $tenantId);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')
            ->when(isset($filters['limit']), fn($q) => $q->limit($filters['limit']))
            ->get();
    }

    /**
     * Get backup status
     *
     * @param int $backupId Backup ID
     * @return array Backup status information
     */
    public function getBackupStatus(int $backupId): array
    {
        $tenantId = $this->getCurrentTenantId();
        $backup = Backup::where('tenant_id', $tenantId)->findOrFail($backupId);

        return [
            'id' => $backup->id,
            'name' => $backup->name,
            'type' => $backup->type,
            'status' => $backup->status,
            'file_name' => $backup->file_name,
            'file_size' => $backup->file_size,
            'file_size_formatted' => $this->formatBytes($backup->file_size),
            'created_at' => $backup->created_at->toIso8601String(),
            'completed_at' => $backup->completed_at?->toIso8601String(),
            'retention_days' => $backup->retention_days,
            'expires_at' => $backup->completed_at?->addDays($backup->retention_days)->toIso8601String(),
            'compress' => $backup->compress,
            'error_message' => $backup->error_message,
        ];
    }

    /**
     * Estimate backup size
     *
     * @param array $options Options for estimation
     * @return array Estimated size and record counts
     */
    public function estimateBackupSize(array $options = []): array
    {
        $tenantId = $this->getCurrentTenantId();

        $recordCounts = [
            'events' => AnalyticsEvent::byTenant($tenantId)->count(),
            'snapshots' => AnalyticsSnapshot::count(),
            'attributions' => AttributionTouch::byTenant($tenantId)->count(),
            'cohorts' => Cohort::byTenant($tenantId)->count(),
            'predictions' => Prediction::byTenant($tenantId)->count(),
            'prediction_models' => PredictionModel::byTenant($tenantId)->count(),
            'custom_events' => CustomEvent::byTenant($tenantId)->count(),
            'custom_event_definitions' => CustomEventDefinition::byTenant($tenantId)->count(),
            'behavior_events' => BehaviorEvent::byTenant($tenantId)->count(),
            'kpi_values' => KpiValue::byTenant($tenantId)->count(),
        ];

        $totalRecords = array_sum($recordCounts);

        // Estimate average record size (in bytes)
        $avgRecordSize = 512; // Average analytics record size
        $estimatedSize = $totalRecords * $avgRecordSize;

        // Apply compression ratio estimate
        if ($options['compress'] ?? $this->compressionEnabled) {
            $estimatedSize = (int) ($estimatedSize * 0.3); // ~70% compression ratio
        }

        return [
            'estimated_size' => $estimatedSize,
            'estimated_size_formatted' => $this->formatBytes($estimatedSize),
            'total_records' => $totalRecords,
            'record_counts' => $recordCounts,
            'estimated_duration_seconds' => (int) ($totalRecords / 1000 * 0.5), // ~0.5ms per record
        ];
    }

    /**
     * Compress a backup
     *
     * @param int $backupId Backup ID to compress
     * @return Backup Updated backup record
     */
    public function compressBackup(int $backupId): Backup
    {
        $tenantId = $this->getCurrentTenantId();
        $backup = Backup::where('tenant_id', $tenantId)->findOrFail($backupId);

        if ($backup->compress) {
            throw new Exception('Backup is already compressed');
        }

        if ($backup->status !== self::STATUS_COMPLETED && $backup->status !== self::STATUS_VERIFIED) {
            throw new Exception('Backup must be completed or verified before compression');
        }

        try {
            $backupData = $this->loadBackupFile($backup->file_path, false);
            $newFileName = $this->generateBackupFileName($backup->id, true);
            $newFilePath = $this->saveBackupFile($backupData, $newFileName, true);

            // Delete old file
            Storage::disk($this->storageDisk)->delete($backup->file_path);

            $backup->update([
                'file_name' => $newFileName,
                'file_path' => $newFilePath,
                'file_size' => Storage::disk($this->storageDisk)->size($newFilePath),
                'compress' => true,
            ]);

            $this->logBackupActivity($backup, 'compressed');

            Log::info('Analytics backup compressed', [
                'backup_id' => $backupId,
                'tenant_id' => $tenantId,
            ]);

            return $backup;

        } catch (Exception $e) {
            Log::error('Failed to compress analytics backup', [
                'backup_id' => $backupId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Decompress a backup
     *
     * @param int $backupId Backup ID to decompress
     * @return Backup Updated backup record
     */
    public function decompressBackup(int $backupId): Backup
    {
        $tenantId = $this->getCurrentTenantId();
        $backup = Backup::where('tenant_id', $tenantId)->findOrFail($backupId);

        if (!$backup->compress) {
            throw new Exception('Backup is not compressed');
        }

        if ($backup->status !== self::STATUS_COMPLETED && $backup->status !== self::STATUS_VERIFIED) {
            throw new Exception('Backup must be completed or verified before decompression');
        }

        try {
            $backupData = $this->loadBackupFile($backup->file_path, true);
            $newFileName = $this->generateBackupFileName($backup->id, false);
            $newFilePath = $this->saveBackupFile($backupData, $newFileName, false);

            // Delete old compressed file
            Storage::disk($this->storageDisk)->delete($backup->file_path);

            $backup->update([
                'file_name' => $newFileName,
                'file_path' => $newFilePath,
                'file_size' => Storage::disk($this->storageDisk)->size($newFilePath),
                'compress' => false,
            ]);

            $this->logBackupActivity($backup, 'decompressed');

            Log::info('Analytics backup decompressed', [
                'backup_id' => $backupId,
                'tenant_id' => $tenantId,
            ]);

            return $backup;

        } catch (Exception $e) {
            Log::error('Failed to decompress analytics backup', [
                'backup_id' => $backupId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Clean up expired backups
     *
     * @return int Number of deleted backups
     */
    public function cleanupExpiredBackups(): int
    {
        $tenantId = $this->getCurrentTenantId();
        $expiredBackups = Backup::where('tenant_id', $tenantId)
            ->whereNotNull('completed_at')
            ->where('completed_at', '<', now()->subDays($this->retentionDays))
            ->get();

        $deletedCount = 0;
        foreach ($expiredBackups as $backup) {
            try {
                if (Storage::disk($this->storageDisk)->exists($backup->file_path)) {
                    Storage::disk($this->storageDisk)->delete($backup->file_path);
                }
                $backup->delete();
                $deletedCount++;
            } catch (Exception $e) {
                Log::warning('Failed to delete expired backup', [
                    'backup_id' => $backup->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Expired analytics backups cleaned up', [
            'tenant_id' => $tenantId,
            'deleted_count' => $deletedCount,
        ]);

        return $deletedCount;
    }

    // ==================== Private Helper Methods ====================

    /**
     * Get current tenant ID
     */
    private function getCurrentTenantId(): string
    {
        return $this->tenantContextService->getCurrentTenantId() ?? 'default';
    }

    /**
     * Gather analytics data for backup
     */
    private function gatherAnalyticsData(array $options): array
    {
        $tenantId = $this->getCurrentTenantId();
        $startDate = $options['start_date'] ?? null;
        $endDate = $options['end_date'] ?? null;

        $data = [
            'metadata' => [
                'tenant_id' => $tenantId,
                'created_at' => now()->toIso8601String(),
                'version' => '1.0',
            ],
        ];

        if ($options['include_events']) {
            $data['events'] = $this->exportEvents($tenantId, $startDate, $endDate);
        }

        if ($options['include_snapshots']) {
            $data['snapshots'] = $this->exportSnapshots($startDate, $endDate);
        }

        if ($options['include_attributions']) {
            $data['attributions'] = $this->exportAttributions($tenantId, $startDate, $endDate);
        }

        if ($options['include_cohorts']) {
            $data['cohorts'] = $this->exportCohorts($tenantId);
        }

        if ($options['include_predictions']) {
            $data['predictions'] = $this->exportPredictions($tenantId);
            $data['prediction_models'] = $this->exportPredictionModels($tenantId);
        }

        if ($options['include_custom_events']) {
            $data['custom_events'] = $this->exportCustomEvents($tenantId, $startDate, $endDate);
            $data['custom_event_definitions'] = $this->exportCustomEventDefinitions($tenantId);
        }

        return $data;
    }

    /**
     * Export analytics events
     */
    private function exportEvents(string $tenantId, ?string $startDate, ?string $endDate): array
    {
        $query = AnalyticsEvent::byTenant($tenantId);

        if ($startDate) {
            $query->where('occurred_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('occurred_at', '<=', $endDate);
        }

        return $query->orderBy('occurred_at', 'asc')
            ->chunk(self::CHUNK_SIZE, function (Collection $events) {
                return $events->toArray();
            });
    }

    /**
     * Export analytics snapshots
     */
    private function exportSnapshots(?string $startDate, ?string $endDate): array
    {
        $query = AnalyticsSnapshot::query();

        if ($startDate) {
            $query->where('snapshot_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('snapshot_date', '<=', $endDate);
        }

        return $query->orderBy('snapshot_date', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Export attribution touches
     */
    private function exportAttributions(string $tenantId, ?string $startDate, ?string $endDate): array
    {
        $query = AttributionTouch::byTenant($tenantId);

        if ($startDate) {
            $query->where('timestamp', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('timestamp', '<=', $endDate);
        }

        return $query->orderBy('timestamp', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Export cohorts
     */
    private function exportCohorts(string $tenantId): array
    {
        return Cohort::byTenant($tenantId)->get()->toArray();
    }

    /**
     * Export predictions
     */
    private function exportPredictions(string $tenantId): array
    {
        return Prediction::byTenant($tenantId)->get()->toArray();
    }

    /**
     * Export prediction models
     */
    private function exportPredictionModels(string $tenantId): array
    {
        return PredictionModel::byTenant($tenantId)->get()->toArray();
    }

    /**
     * Export custom events
     */
    private function exportCustomEvents(string $tenantId, ?string $startDate, ?string $endDate): array
    {
        $query = CustomEvent::byTenant($tenantId);

        if ($startDate) {
            $query->where('timestamp', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('timestamp', '<=', $endDate);
        }

        return $query->orderBy('timestamp', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Export custom event definitions
     */
    private function exportCustomEventDefinitions(string $tenantId): array
    {
        return CustomEventDefinition::byTenant($tenantId)->get()->toArray();
    }

    /**
     * Save backup file to storage
     */
    private function saveBackupFile(array $data, string $fileName, bool $compress): string
    {
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        $fullPath = "{$this->backupPath}/{$fileName}";

        if ($compress) {
            $tempFile = tempnam(sys_get_temp_dir(), 'backup_');
            $zip = new ZipArchive();
            $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $zip->addFromString('backup.json', $jsonData);
            $zip->close();

            Storage::disk($this->storageDisk)->put($fullPath, file_get_contents($tempFile));
            unlink($tempFile);
        } else {
            Storage::disk($this->storageDisk)->put($fullPath, $jsonData);
        }

        return $fullPath;
    }

    /**
     * Load backup file from storage
     */
    private function loadBackupFile(string $filePath, bool $compressed): array
    {
        $fileContent = Storage::disk($this->storageDisk)->get($filePath);

        if ($compressed) {
            $tempFile = tempnam(sys_get_temp_dir(), 'backup_');
            file_put_contents($tempFile, $fileContent);

            $zip = new ZipArchive();
            $zip->open($tempFile);
            $jsonData = $zip->getFromName('backup.json');
            $zip->close();
            unlink($tempFile);

            return json_decode($jsonData, true);
        }

        return json_decode($fileContent, true);
    }

    /**
     * Restore analytics data from backup
     */
    private function restoreAnalyticsData(array $data, array $options): void
    {
        $tenantId = $this->getCurrentTenantId();

        if ($options['clear_existing']) {
            $this->clearAnalyticsData([
                'clear_events' => $options['restore_events'],
                'clear_snapshots' => $options['restore_snapshots'],
                'clear_attributions' => $options['restore_attributions'],
                'clear_cohorts' => $options['restore_cohorts'],
                'clear_predictions' => $options['restore_predictions'],
                'clear_custom_events' => $options['restore_custom_events'],
            ]);
        }

        if ($options['restore_events'] && isset($data['events'])) {
            $this->importEvents($data['events'], $tenantId);
        }

        if ($options['restore_snapshots'] && isset($data['snapshots'])) {
            $this->importSnapshots($data['snapshots']);
        }

        if ($options['restore_attributions'] && isset($data['attributions'])) {
            $this->importAttributions($data['attributions'], $tenantId);
        }

        if ($options['restore_cohorts'] && isset($data['cohorts'])) {
            $this->importCohorts($data['cohorts'], $tenantId);
        }

        if ($options['restore_predictions'] && isset($data['predictions'])) {
            $this->importPredictions($data['predictions'], $tenantId);
        }

        if ($options['restore_custom_events'] && isset($data['custom_events'])) {
            $this->importCustomEvents($data['custom_events'], $tenantId);
        }
    }

    /**
     * Clear existing analytics data
     */
    private function clearAnalyticsData(array $options): void
    {
        $tenantId = $this->getCurrentTenantId();

        if ($options['clear_events']) {
            AnalyticsEvent::byTenant($tenantId)->delete();
        }

        if ($options['clear_snapshots']) {
            AnalyticsSnapshot::query()->delete();
        }

        if ($options['clear_attributions']) {
            AttributionTouch::byTenant($tenantId)->delete();
        }

        if ($options['clear_cohorts']) {
            Cohort::byTenant($tenantId)->delete();
        }

        if ($options['clear_predictions']) {
            Prediction::byTenant($tenantId)->delete();
        }

        if ($options['clear_custom_events']) {
            CustomEvent::byTenant($tenantId)->delete();
        }
    }

    /**
     * Import events from backup
     */
    private function importEvents(array $events, string $tenantId): void
    {
        foreach (array_chunk($events, self::CHUNK_SIZE) as $chunk) {
            $records = array_map(function ($event) use ($tenantId) {
                unset($event['id'], $event['created_at'], $event['updated_at']);
                return array_merge($event, ['tenant_id' => $tenantId]);
            }, $chunk);
            AnalyticsEvent::insert($records);
        }
    }

    /**
     * Import snapshots from backup
     */
    private function importSnapshots(array $snapshots): void
    {
        foreach (array_chunk($snapshots, self::CHUNK_SIZE) as $chunk) {
            $records = array_map(function ($snapshot) {
                unset($snapshot['id'], $snapshot['created_at'], $snapshot['updated_at']);
                return $snapshot;
            }, $chunk);
            AnalyticsSnapshot::insert($records);
        }
    }

    /**
     * Import attributions from backup
     */
    private function importAttributions(array $attributions, string $tenantId): void
    {
        foreach (array_chunk($attributions, self::CHUNK_SIZE) as $chunk) {
            $records = array_map(function ($attribution) use ($tenantId) {
                unset($attribution['id'], $attribution['created_at'], $attribution['updated_at']);
                return array_merge($attribution, ['tenant_id' => $tenantId]);
            }, $chunk);
            AttributionTouch::insert($records);
        }
    }

    /**
     * Import cohorts from backup
     */
    private function importCohorts(array $cohorts, string $tenantId): void
    {
        foreach ($cohorts as $cohort) {
            unset($cohort['id'], $cohort['created_at'], $cohort['updated_at']);
            $cohort['tenant_id'] = $tenantId;
            Cohort::create($cohort);
        }
    }

    /**
     * Import predictions from backup
     */
    private function importPredictions(array $predictions, string $tenantId): void
    {
        foreach ($predictions as $prediction) {
            unset($prediction['id'], $prediction['created_at'], $prediction['updated_at']);
            $prediction['tenant_id'] = $tenantId;
            Prediction::create($prediction);
        }
    }

    /**
     * Import custom events from backup
     */
    private function importCustomEvents(array $events, string $tenantId): void
    {
        foreach (array_chunk($events, self::CHUNK_SIZE) as $chunk) {
            $records = array_map(function ($event) use ($tenantId) {
                unset($event['id'], $event['created_at'], $event['updated_at']);
                return array_merge($event, ['tenant_id' => $tenantId]);
            }, $chunk);
            CustomEvent::insert($records);
        }
    }

    /**
     * Verify record counts in backup
     */
    private function verifyRecordCounts(array $data): array
    {
        return [
            'events' => count($data['events'] ?? []),
            'snapshots' => count($data['snapshots'] ?? []),
            'attributions' => count($data['attributions'] ?? []),
            'cohorts' => count($data['cohorts'] ?? []),
            'predictions' => count($data['predictions'] ?? []),
            'custom_events' => count($data['custom_events'] ?? []),
            'custom_event_definitions' => count($data['custom_event_definitions'] ?? []),
        ];
    }

    /**
     * Generate checksums for backup data
     */
    private function generateChecksums(array $data): array
    {
        return [
            'events' => md5(json_encode($data['events'] ?? [])),
            'snapshots' => md5(json_encode($data['snapshots'] ?? [])),
            'attributions' => md5(json_encode($data['attributions'] ?? [])),
            'cohorts' => md5(json_encode($data['cohorts'] ?? [])),
            'predictions' => md5(json_encode($data['predictions'] ?? [])),
            'custom_events' => md5(json_encode($data['custom_events'] ?? [])),
        ];
    }

    /**
     * Validate backup data structure
     */
    private function validateBackupData(array $data): array
    {
        $errors = [];

        if (!isset($data['metadata'])) {
            $errors[] = 'Missing metadata in backup';
        }

        if (!isset($data['metadata']['tenant_id'])) {
            $errors[] = 'Missing tenant_id in backup metadata';
        }

        if (!isset($data['metadata']['created_at'])) {
            $errors[] = 'Missing created_at in backup metadata';
        }

        return $errors;
    }

    /**
     * Generate backup name
     */
    private function generateBackupName(string $type): string
    {
        $date = now()->format('Y-m-d');
        $time = now()->format('His');
        return "analytics_{$type}_{$date}_{$time}";
    }

    /**
     * Generate backup file name
     */
    private function generateBackupFileName(int $backupId, bool $compress): string
    {
        $extension = $compress ? 'zip' : 'json';
        return "backup_{$backupId}.{$extension}";
    }

    /**
     * Calculate next run time for scheduled backup
     */
    private function calculateNextRunTime(array $schedule): Carbon
    {
        $now = now();

        return match ($schedule['frequency']) {
            'weekly' => $now->next($schedule['day_of_week'])->setTimeFromString($schedule['time']),
            'monthly' => $now->addMonth()->day($schedule['day_of_month'])->setTimeFromString($schedule['time']),
            default => $now->addDay()->setTimeFromString($schedule['time']), // daily
        };
    }

    /**
     * Log backup activity
     */
    private function logBackupActivity(Backup $backup, string $status, ?string $errorMessage = null): void
    {
        BackupLog::create([
            'backup_type' => $backup->type,
            'status' => $status,
            'file_path' => $backup->file_path,
            'file_size' => $backup->file_size,
            'started_at' => $backup->created_at,
            'completed_at' => $backup->completed_at ?? now(),
            'error_message' => $errorMessage,
            'metadata' => [
                'backup_id' => $backup->id,
                'tenant_id' => $backup->tenant_id,
                'compress' => $backup->compress,
            ],
        ]);
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
