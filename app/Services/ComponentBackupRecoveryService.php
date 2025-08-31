<?php

namespace App\Services;

use App\Models\Component;
use App\Models\ComponentVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ComponentBackupRecoveryService
{
    public function __construct(
        private ComponentVersionService $versionService,
        private ComponentExportImportService $exportImportService
    ) {}

    /**
     * Create a comprehensive backup of a component
     */
    public function createBackup(Component $component, array $options = []): array
    {
        $backupType = $options['type'] ?? 'full'; // full, config_only, versions_only
        $includeAnalytics = $options['include_analytics'] ?? true;
        $storageLocation = $options['storage'] ?? 'local';

        return DB::transaction(function () use ($component, $backupType, $includeAnalytics, $storageLocation) {
            $backupData = [
                'backup_info' => [
                    'id' => uniqid('backup_'),
                    'component_id' => $component->id,
                    'type' => $backupType,
                    'created_at' => now()->toISOString(),
                    'created_by' => auth()->id(),
                    'storage_location' => $storageLocation,
                ],
                'component' => null,
                'versions' => null,
                'analytics' => null,
                'metadata' => [
                    'tenant_id' => $component->tenant_id,
                    'original_slug' => $component->slug,
                    'backup_size' => 0,
                ],
            ];

            // Backup component data
            if (in_array($backupType, ['full', 'config_only'])) {
                $backupData['component'] = $this->backupComponentData($component);
            }

            // Backup version history
            if (in_array($backupType, ['full', 'versions_only'])) {
                $backupData['versions'] = $this->backupVersionHistory($component);
            }

            // Backup analytics data
            if ($includeAnalytics && $backupType === 'full') {
                $backupData['analytics'] = $this->backupAnalyticsData($component);
            }

            // Calculate backup size
            $backupData['metadata']['backup_size'] = strlen(json_encode($backupData));

            // Store backup
            $backupPath = $this->storeBackup($backupData, $storageLocation);
            $backupData['backup_info']['storage_path'] = $backupPath;

            // Create backup record in database
            $this->createBackupRecord($backupData);

            Log::info('Component backup created', [
                'backup_id' => $backupData['backup_info']['id'],
                'component_id' => $component->id,
                'type' => $backupType,
                'size' => $backupData['metadata']['backup_size'],
            ]);

            return $backupData;
        });
    }

    /**
     * Create automatic backup before major operations
     */
    public function createAutomaticBackup(Component $component, string $operation): array
    {
        return $this->createBackup($component, [
            'type' => 'full',
            'include_analytics' => false,
            'metadata' => [
                'automatic' => true,
                'operation' => $operation,
                'trigger' => 'pre_operation',
            ],
        ]);
    }

    /**
     * Restore component from backup
     */
    public function restoreFromBackup(string $backupId, array $options = []): Component
    {
        $overwriteExisting = $options['overwrite_existing'] ?? false;
        $restoreVersions = $options['restore_versions'] ?? true;
        $restoreAnalytics = $options['restore_analytics'] ?? false;

        return DB::transaction(function () use ($backupId, $overwriteExisting, $restoreVersions, $restoreAnalytics) {
            // Load backup data
            $backupData = $this->loadBackup($backupId);
            
            if (!$backupData) {
                throw new \Exception("Backup not found: {$backupId}");
            }

            $componentData = $backupData['component'];
            $originalComponentId = $backupData['backup_info']['component_id'];

            // Check if original component exists
            $existingComponent = Component::find($originalComponentId);
            
            if ($existingComponent && !$overwriteExisting) {
                throw new \Exception("Component exists and overwrite not allowed");
            }

            // Create backup of current state before restore
            if ($existingComponent) {
                $this->createAutomaticBackup($existingComponent, 'pre_restore');
            }

            // Restore component
            if ($existingComponent && $overwriteExisting) {
                $existingComponent->update($this->prepareComponentDataForRestore($componentData));
                $component = $existingComponent;
            } else {
                $component = Component::create($this->prepareComponentDataForRestore($componentData));
            }

            // Restore versions
            if ($restoreVersions && isset($backupData['versions'])) {
                $this->restoreVersionHistory($component, $backupData['versions']);
            }

            // Restore analytics
            if ($restoreAnalytics && isset($backupData['analytics'])) {
                $this->restoreAnalyticsData($component, $backupData['analytics']);
            }

            // Create version entry for the restore
            $this->versionService->createVersion($component, [
                'action' => 'restore_from_backup',
                'backup_id' => $backupId,
                'restored_at' => now()->toISOString(),
            ], "Restored from backup {$backupId}");

            Log::info('Component restored from backup', [
                'backup_id' => $backupId,
                'component_id' => $component->id,
                'restored_by' => auth()->id(),
            ]);

            return $component;
        });
    }

    /**
     * List available backups for a component
     */
    public function listBackups(Component $component): Collection
    {
        return $this->getBackupRecords()
            ->where('component_id', $component->id)
            ->sortByDesc('created_at');
    }

    /**
     * List all backups for a tenant
     */
    public function listTenantBackups(int $tenantId): Collection
    {
        return $this->getBackupRecords()
            ->where('tenant_id', $tenantId)
            ->sortByDesc('created_at');
    }

    /**
     * Delete old backups based on retention policy
     */
    public function cleanupOldBackups(array $retentionPolicy = []): int
    {
        $maxAge = $retentionPolicy['max_age_days'] ?? 90;
        $maxCount = $retentionPolicy['max_count'] ?? 100;
        $keepCritical = $retentionPolicy['keep_critical'] ?? true;

        $cutoffDate = now()->subDays($maxAge);
        $deletedCount = 0;

        // Get all backup records
        $backups = $this->getBackupRecords();

        // Delete by age
        $oldBackups = $backups->filter(function ($backup) use ($cutoffDate, $keepCritical) {
            $backupDate = Carbon::parse($backup['created_at']);
            $isCritical = $backup['metadata']['automatic'] ?? false;
            
            return $backupDate->isBefore($cutoffDate) && (!$keepCritical || !$isCritical);
        });

        foreach ($oldBackups as $backup) {
            if ($this->deleteBackup($backup['id'])) {
                $deletedCount++;
            }
        }

        // Delete excess backups (keep only maxCount most recent)
        $excessBackups = $backups->sortByDesc('created_at')->skip($maxCount);
        
        foreach ($excessBackups as $backup) {
            $isCritical = $backup['metadata']['automatic'] ?? false;
            
            if (!$keepCritical || !$isCritical) {
                if ($this->deleteBackup($backup['id'])) {
                    $deletedCount++;
                }
            }
        }

        Log::info('Backup cleanup completed', [
            'deleted_count' => $deletedCount,
            'retention_policy' => $retentionPolicy,
        ]);

        return $deletedCount;
    }

    /**
     * Verify backup integrity
     */
    public function verifyBackupIntegrity(string $backupId): array
    {
        $verification = [
            'backup_id' => $backupId,
            'verified_at' => now()->toISOString(),
            'is_valid' => false,
            'issues' => [],
            'warnings' => [],
        ];

        try {
            // Load backup data
            $backupData = $this->loadBackup($backupId);
            
            if (!$backupData) {
                $verification['issues'][] = 'Backup file not found or corrupted';
                return $verification;
            }

            // Verify backup structure
            $requiredFields = ['backup_info', 'component', 'metadata'];
            foreach ($requiredFields as $field) {
                if (!isset($backupData[$field])) {
                    $verification['issues'][] = "Missing required field: {$field}";
                }
            }

            // Verify component data
            if (isset($backupData['component'])) {
                $componentIssues = $this->verifyComponentData($backupData['component']);
                $verification['issues'] = array_merge($verification['issues'], $componentIssues);
            }

            // Verify versions data
            if (isset($backupData['versions'])) {
                $versionIssues = $this->verifyVersionsData($backupData['versions']);
                $verification['issues'] = array_merge($verification['issues'], $versionIssues);
            }

            // Check backup age
            $backupAge = now()->diffInDays(Carbon::parse($backupData['backup_info']['created_at']));
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
     * Create scheduled backup for all components
     */
    public function createScheduledBackups(int $tenantId): array
    {
        $components = Component::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $results = [
            'total_components' => $components->count(),
            'successful_backups' => 0,
            'failed_backups' => 0,
            'errors' => [],
        ];

        foreach ($components as $component) {
            try {
                $this->createBackup($component, [
                    'type' => 'full',
                    'include_analytics' => true,
                    'metadata' => [
                        'automatic' => true,
                        'scheduled' => true,
                        'created_at' => now()->toISOString(),
                    ],
                ]);
                
                $results['successful_backups']++;
            } catch (\Exception $e) {
                $results['failed_backups']++;
                $results['errors'][] = [
                    'component_id' => $component->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Scheduled backups completed', $results);

        return $results;
    }

    /**
     * Backup component data
     */
    private function backupComponentData(Component $component): array
    {
        return [
            'id' => $component->id,
            'tenant_id' => $component->tenant_id,
            'theme_id' => $component->theme_id,
            'name' => $component->name,
            'slug' => $component->slug,
            'category' => $component->category,
            'type' => $component->type,
            'description' => $component->description,
            'config' => $component->config,
            'metadata' => $component->metadata,
            'version' => $component->version,
            'is_active' => $component->is_active,
            'usage_count' => $component->usage_count,
            'last_used_at' => $component->last_used_at?->toISOString(),
            'created_at' => $component->created_at->toISOString(),
            'updated_at' => $component->updated_at->toISOString(),
        ];
    }

    /**
     * Backup version history
     */
    private function backupVersionHistory(Component $component): array
    {
        return $component->versions()
            ->with('creator')
            ->orderBy('version_number')
            ->get()
            ->map(function (ComponentVersion $version) {
                return [
                    'version_number' => $version->version_number,
                    'config' => $version->config,
                    'metadata' => $version->metadata,
                    'changes' => $version->changes,
                    'description' => $version->description,
                    'created_by' => $version->created_by,
                    'creator_name' => $version->creator?->name,
                    'created_at' => $version->created_at->toISOString(),
                ];
            })
            ->toArray();
    }

    /**
     * Backup analytics data
     */
    private function backupAnalyticsData(Component $component): array
    {
        return [
            'usage_stats' => $component->getUsageStats(),
            'performance_metrics' => [], // Would be populated by analytics service
            'conversion_data' => [], // Would be populated by analytics service
        ];
    }

    /**
     * Store backup data
     */
    private function storeBackup(array $backupData, string $storageLocation): string
    {
        $backupId = $backupData['backup_info']['id'];
        $filename = "component-backup-{$backupId}.json";
        $path = "backups/components/{$filename}";

        $content = json_encode($backupData, JSON_PRETTY_PRINT);

        switch ($storageLocation) {
            case 'local':
                Storage::disk('local')->put($path, $content);
                break;
            
            case 's3':
                Storage::disk('s3')->put($path, $content);
                break;
            
            default:
                Storage::disk('local')->put($path, $content);
        }

        return $path;
    }

    /**
     * Load backup data
     */
    private function loadBackup(string $backupId): ?array
    {
        $filename = "component-backup-{$backupId}.json";
        $path = "backups/components/{$filename}";

        // Try different storage locations
        $storageDisks = ['local', 's3'];
        
        foreach ($storageDisks as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                $content = Storage::disk($disk)->get($path);
                return json_decode($content, true);
            }
        }

        return null;
    }

    /**
     * Create backup record in database
     */
    private function createBackupRecord(array $backupData): void
    {
        // This would typically store backup metadata in a database table
        // For now, we'll store it in a JSON file
        $recordsPath = 'backups/backup_records.json';
        
        $records = [];
        if (Storage::disk('local')->exists($recordsPath)) {
            $records = json_decode(Storage::disk('local')->get($recordsPath), true) ?? [];
        }

        $records[] = [
            'id' => $backupData['backup_info']['id'],
            'component_id' => $backupData['backup_info']['component_id'],
            'tenant_id' => $backupData['metadata']['tenant_id'],
            'type' => $backupData['backup_info']['type'],
            'size' => $backupData['metadata']['backup_size'],
            'storage_path' => $backupData['backup_info']['storage_path'],
            'created_at' => $backupData['backup_info']['created_at'],
            'created_by' => $backupData['backup_info']['created_by'],
            'metadata' => $backupData['metadata'],
        ];

        Storage::disk('local')->put($recordsPath, json_encode($records, JSON_PRETTY_PRINT));
    }

    /**
     * Get backup records
     */
    private function getBackupRecords(): Collection
    {
        $recordsPath = 'backups/backup_records.json';
        
        if (!Storage::disk('local')->exists($recordsPath)) {
            return collect([]);
        }

        $records = json_decode(Storage::disk('local')->get($recordsPath), true) ?? [];
        return collect($records);
    }

    /**
     * Delete backup
     */
    private function deleteBackup(string $backupId): bool
    {
        try {
            // Delete backup file
            $filename = "component-backup-{$backupId}.json";
            $path = "backups/components/{$filename}";
            
            $deleted = false;
            $storageDisks = ['local', 's3'];
            
            foreach ($storageDisks as $disk) {
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                    $deleted = true;
                }
            }

            // Remove from records
            $this->removeBackupRecord($backupId);

            return $deleted;
        } catch (\Exception $e) {
            Log::error('Failed to delete backup', [
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Remove backup record
     */
    private function removeBackupRecord(string $backupId): void
    {
        $recordsPath = 'backups/backup_records.json';
        
        if (Storage::disk('local')->exists($recordsPath)) {
            $records = json_decode(Storage::disk('local')->get($recordsPath), true) ?? [];
            $records = array_filter($records, fn($record) => $record['id'] !== $backupId);
            Storage::disk('local')->put($recordsPath, json_encode(array_values($records), JSON_PRETTY_PRINT));
        }
    }

    /**
     * Prepare component data for restore
     */
    private function prepareComponentDataForRestore(array $componentData): array
    {
        $restoreData = $componentData;
        
        // Remove fields that shouldn't be restored directly
        unset($restoreData['id']);
        unset($restoreData['created_at']);
        unset($restoreData['updated_at']);
        
        // Convert date strings back to Carbon instances where needed
        if (isset($restoreData['last_used_at']) && $restoreData['last_used_at']) {
            $restoreData['last_used_at'] = Carbon::parse($restoreData['last_used_at']);
        }

        return $restoreData;
    }

    /**
     * Restore version history
     */
    private function restoreVersionHistory(Component $component, array $versions): void
    {
        // Clear existing versions
        $component->versions()->delete();

        // Restore versions
        foreach ($versions as $versionData) {
            ComponentVersion::create([
                'component_id' => $component->id,
                'version_number' => $versionData['version_number'],
                'config' => $versionData['config'] ?? [],
                'metadata' => $versionData['metadata'] ?? [],
                'changes' => $versionData['changes'] ?? [],
                'description' => $versionData['description'],
                'created_by' => $versionData['created_by'],
                'created_at' => Carbon::parse($versionData['created_at']),
            ]);
        }
    }

    /**
     * Restore analytics data
     */
    private function restoreAnalyticsData(Component $component, array $analytics): void
    {
        // Restore usage stats
        if (isset($analytics['usage_stats'])) {
            $component->update([
                'usage_count' => $analytics['usage_stats']['usage_count'] ?? 0,
                'last_used_at' => isset($analytics['usage_stats']['last_used_at']) 
                    ? Carbon::parse($analytics['usage_stats']['last_used_at']) 
                    : null,
            ]);
        }
    }

    /**
     * Verify component data integrity
     */
    private function verifyComponentData(array $componentData): array
    {
        $issues = [];

        $requiredFields = ['id', 'name', 'slug', 'category', 'type'];
        foreach ($requiredFields as $field) {
            if (!isset($componentData[$field]) || empty($componentData[$field])) {
                $issues[] = "Missing or empty component field: {$field}";
            }
        }

        return $issues;
    }

    /**
     * Verify versions data integrity
     */
    private function verifyVersionsData(array $versions): array
    {
        $issues = [];

        foreach ($versions as $index => $version) {
            if (!isset($version['version_number'])) {
                $issues[] = "Version {$index} missing version_number";
            }
            
            if (!isset($version['created_at'])) {
                $issues[] = "Version {$index} missing created_at";
            }
        }

        return $issues;
    }
}