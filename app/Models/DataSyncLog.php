<?php
// ABOUTME: Eloquent model for data_sync_logs table in schema-based tenancy architecture
// ABOUTME: Tracks synchronization operations within tenant schemas for consistency and monitoring

namespace App\Models;

use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class DataSyncLog extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'data_sync_logs';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        // 'tenant_id', // Removed for schema-based tenancy
        'sync_type',
        'source_table',
        'target_table',
        'source_record_id',
        'target_record_id',
        'operation',
        'sync_direction',
        'status',
        'started_at',
        'completed_at',
        'failed_at',
        'retry_count',
        'max_retries',
        'sync_data',
        'error_message',
        'error_details',
        'metadata',
        'batch_id',
        'priority',
        'tags',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'sync_data' => 'array',
        'error_details' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
    ];

    /**
     * Available sync types.
     */
    public const SYNC_TYPES = [
        'user_sync' => 'User Synchronization',
        'course_sync' => 'Course Synchronization',
        'enrollment_sync' => 'Enrollment Synchronization',
        'membership_sync' => 'Membership Synchronization',
        'analytics_sync' => 'Analytics Synchronization',
        'audit_sync' => 'Audit Trail Synchronization',
        'schema_sync' => 'Schema Synchronization',
        'permission_sync' => 'Permission Synchronization',
        'configuration_sync' => 'Configuration Synchronization',
        'backup_sync' => 'Backup Synchronization',
        'migration_sync' => 'Migration Synchronization',
        'cleanup_sync' => 'Cleanup Synchronization',
    ];

    /**
     * Available operations.
     */
    public const OPERATIONS = [
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'restore' => 'Restore',
        'merge' => 'Merge',
        'split' => 'Split',
        'migrate' => 'Migrate',
        'replicate' => 'Replicate',
        'validate' => 'Validate',
        'reconcile' => 'Reconcile',
        'cleanup' => 'Cleanup',
        'archive' => 'Archive',
    ];

    /**
     * Available sync directions.
     */
    public const SYNC_DIRECTIONS = [
        'global_to_tenant' => 'Global to Tenant',
        'tenant_to_global' => 'Tenant to Global',
        'bidirectional' => 'Bidirectional',
        'cross_tenant' => 'Cross Tenant',
        'internal' => 'Internal',
    ];

    /**
     * Available statuses.
     */
    public const STATUSES = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
        'retrying' => 'Retrying',
        'partial' => 'Partial Success',
        'skipped' => 'Skipped',
    ];

    /**
     * Available priorities.
     */
    public const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'critical' => 'Critical',
        'urgent' => 'Urgent',
    ];

    /**
     * Tables that require synchronization.
     */
    public const SYNC_TABLES = [
        'global_users' => 'users',
        'global_courses' => 'courses',
        'user_tenant_memberships' => 'user_roles',
        'tenant_course_offerings' => 'course_instances',
        'super_admin_analytics' => 'tenant_analytics',
        'audit_trail' => 'tenant_audit_logs',
    ];

    /**
     * Get the current tenant context for this sync log.
     */
    public function getCurrentTenant()
    {
        return TenantContextService::getCurrentTenant();
    }

    /**
     * Get the sync type display name.
     */
    public function getSyncTypeDisplayNameAttribute(): string
    {
        return self::SYNC_TYPES[$this->sync_type] ?? ucfirst(str_replace('_', ' ', $this->sync_type));
    }

    /**
     * Get the operation display name.
     */
    public function getOperationDisplayNameAttribute(): string
    {
        return self::OPERATIONS[$this->operation] ?? ucfirst(str_replace('_', ' ', $this->operation));
    }

    /**
     * Get the sync direction display name.
     */
    public function getSyncDirectionDisplayNameAttribute(): string
    {
        return self::SYNC_DIRECTIONS[$this->sync_direction] ?? ucfirst(str_replace('_', ' ', $this->sync_direction));
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * Get the priority display name.
     */
    public function getPriorityDisplayNameAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucfirst(str_replace('_', ' ', $this->priority));
    }

    /**
     * Get the duration of the sync operation.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? $this->failed_at ?? now();
        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        $duration = $this->duration;
        
        if (is_null($duration)) {
            return 'N/A';
        }

        if ($duration < 60) {
            return $duration . 's';
        }

        if ($duration < 3600) {
            return round($duration / 60, 1) . 'm';
        }

        return round($duration / 3600, 1) . 'h';
    }

    /**
     * Check if sync is currently running.
     */
    public function isRunning(): bool
    {
        return in_array($this->status, ['pending', 'in_progress', 'retrying']);
    }

    /**
     * Check if sync completed successfully.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if sync failed.
     */
    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'cancelled']);
    }

    /**
     * Check if sync can be retried.
     */
    public function canRetry(): bool
    {
        return $this->isFailed() && 
               $this->retry_count < $this->max_retries &&
               !in_array($this->status, ['cancelled']);
    }

    /**
     * Get the success rate for this sync type.
     */
    public function getSuccessRateAttribute(): float
    {
        $total = self::where('sync_type', $this->sync_type)
                    ->count();
        
        if ($total === 0) {
            return 0;
        }

        $successful = self::where('sync_type', $this->sync_type)
                         ->where('status', 'completed')
                         ->count();

        return ($successful / $total) * 100;
    }

    /**
     * Get the average duration for this sync type.
     */
    public function getAverageDurationAttribute(): float
    {
        $completedSyncs = self::where('sync_type', $this->sync_type)
                             ->where('status', 'completed')
                             ->whereNotNull('started_at')
                             ->whereNotNull('completed_at')
                             ->get();

        if ($completedSyncs->isEmpty()) {
            return 0;
        }

        $totalDuration = $completedSyncs->sum(function ($sync) {
            return $sync->started_at->diffInSeconds($sync->completed_at);
        });

        return $totalDuration / $completedSyncs->count();
    }

    /**
     * Get sync statistics.
     */
    public function getSyncStatsAttribute(): array
    {
        $stats = $this->sync_data['stats'] ?? [];
        
        return array_merge([
            'records_processed' => 0,
            'records_created' => 0,
            'records_updated' => 0,
            'records_deleted' => 0,
            'records_skipped' => 0,
            'records_failed' => 0,
            'data_size_bytes' => 0,
            'checksum' => null,
        ], $stats);
    }

    /**
     * Get conflict information.
     */
    public function getConflictsAttribute(): array
    {
        return $this->sync_data['conflicts'] ?? [];
    }

    /**
     * Get validation errors.
     */
    public function getValidationErrorsAttribute(): array
    {
        return $this->sync_data['validation_errors'] ?? [];
    }

    /**
     * Scope to filter by sync type.
     */
    public function scopeSyncType($query, string $syncType)
    {
        return $query->where('sync_type', $syncType);
    }

    /**
     * Scope to filter by operation.
     */
    public function scopeOperation($query, string $operation)
    {
        return $query->where('operation', $operation);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by tenant (legacy compatibility).
     */
    public function scopeByTenant($query, string $tenantId = null)
    {
        // In schema-based tenancy, data is already isolated by schema
        return $query;
    }

    /**
     * Scope to filter by sync direction.
     */
    public function scopeSyncDirection($query, string $direction)
    {
        return $query->where('sync_direction', $direction);
    }

    /**
     * Scope to filter by priority.
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to filter by batch.
     */
    public function scopeBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, Carbon $startDate = null, Carbon $endDate = null)
    {
        if ($startDate) {
            $query->where('started_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('started_at', '<=', $endDate);
        }
        return $query;
    }

    /**
     * Scope to filter running syncs.
     */
    public function scopeRunning($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress', 'retrying']);
    }

    /**
     * Scope to filter completed syncs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to filter failed syncs.
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'cancelled']);
    }

    /**
     * Scope to filter retryable syncs.
     */
    public function scopeRetryable($query)
    {
        return $query->where('status', 'failed')
                     ->whereRaw('retry_count < max_retries');
    }

    /**
     * Scope to filter recent syncs.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>=', now()->subHours($hours))
                     ->orderBy('started_at', 'desc');
    }

    /**
     * Scope to filter by source table.
     */
    public function scopeSourceTable($query, string $table)
    {
        return $query->where('source_table', $table);
    }

    /**
     * Scope to filter by target table.
     */
    public function scopeTargetTable($query, string $table)
    {
        return $query->where('target_table', $table);
    }

    /**
     * Scope to search sync logs.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('source_table', 'ILIKE', "%{$search}%")
              ->orWhere('target_table', 'ILIKE', "%{$search}%")
              ->orWhere('source_record_id', 'ILIKE', "%{$search}%")
              ->orWhere('target_record_id', 'ILIKE', "%{$search}%")
              ->orWhere('batch_id', 'ILIKE', "%{$search}%")
              ->orWhere('error_message', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * Start a sync operation.
     */
    public function start(): self
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'failed_at' => null,
        ]);

        return $this;
    }

    /**
     * Mark sync as completed.
     */
    public function complete(array $stats = []): self
    {
        $syncData = $this->sync_data ?? [];
        $syncData['stats'] = array_merge($syncData['stats'] ?? [], $stats);

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'sync_data' => $syncData,
        ]);

        return $this;
    }

    /**
     * Mark sync as failed.
     */
    public function fail(string $errorMessage, array $errorDetails = []): self
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
        ]);

        return $this;
    }

    /**
     * Retry the sync operation.
     */
    public function retry(): self
    {
        if (!$this->canRetry()) {
            throw new \Exception('Sync cannot be retried');
        }

        $this->update([
            'status' => 'retrying',
            'retry_count' => $this->retry_count + 1,
            'started_at' => now(),
            'failed_at' => null,
            'error_message' => null,
            'error_details' => null,
        ]);

        return $this;
    }

    /**
     * Cancel the sync operation.
     */
    public function cancel(string $reason = null): self
    {
        $metadata = $this->metadata ?? [];
        if ($reason) {
            $metadata['cancellation_reason'] = $reason;
        }

        $this->update([
            'status' => 'cancelled',
            'failed_at' => now(),
            'metadata' => $metadata,
        ]);

        return $this;
    }

    /**
     * Add conflict information.
     */
    public function addConflict(array $conflict): self
    {
        $syncData = $this->sync_data ?? [];
        $syncData['conflicts'] = $syncData['conflicts'] ?? [];
        $syncData['conflicts'][] = array_merge($conflict, [
            'detected_at' => now()->toISOString(),
        ]);

        $this->update(['sync_data' => $syncData]);

        return $this;
    }

    /**
     * Add validation error.
     */
    public function addValidationError(array $error): self
    {
        $syncData = $this->sync_data ?? [];
        $syncData['validation_errors'] = $syncData['validation_errors'] ?? [];
        $syncData['validation_errors'][] = array_merge($error, [
            'detected_at' => now()->toISOString(),
        ]);

        $this->update(['sync_data' => $syncData]);

        return $this;
    }

    /**
     * Update sync statistics.
     */
    public function updateStats(array $stats): self
    {
        $syncData = $this->sync_data ?? [];
        $syncData['stats'] = array_merge($syncData['stats'] ?? [], $stats);

        $this->update(['sync_data' => $syncData]);

        return $this;
    }

    /**
     * Create a new sync log entry.
     */
    public static function createSync(
        string $syncType,
        string $operation,
        string $sourceTable,
        string $targetTable,
        array $options = []
    ): self {
        return self::create(array_merge([
            'sync_type' => $syncType,
            'source_table' => $sourceTable,
            'target_table' => $targetTable,
            'operation' => $operation,
            'sync_direction' => $options['sync_direction'] ?? 'global_to_tenant',
            'status' => 'pending',
            'retry_count' => 0,
            'max_retries' => $options['max_retries'] ?? 3,
            'priority' => $options['priority'] ?? 'normal',
            'batch_id' => $options['batch_id'] ?? null,
            'metadata' => $options['metadata'] ?? [],
            'tags' => $options['tags'] ?? [],
        ], $options));
    }

    /**
     * Get sync statistics for current tenant schema.
     */
    public static function getSyncStatistics(int $days = 30): array
    {
        $query = self::query();
        
        $query->where('started_at', '>=', now()->subDays($days));
        
        $total = $query->count();
        $completed = $query->where('status', 'completed')->count();
        $failed = $query->whereIn('status', ['failed', 'cancelled'])->count();
        $running = $query->whereIn('status', ['pending', 'in_progress', 'retrying'])->count();
        
        $successRate = $total > 0 ? ($completed / $total) * 100 : 0;
        
        $avgDuration = $query->where('status', 'completed')
                            ->whereNotNull('started_at')
                            ->whereNotNull('completed_at')
                            ->get()
                            ->avg(function ($sync) {
                                return $sync->started_at->diffInSeconds($sync->completed_at);
                            }) ?? 0;
        
        $bySyncType = $query->groupBy('sync_type')
                           ->selectRaw('sync_type, count(*) as count, 
                                       sum(case when status = "completed" then 1 else 0 end) as completed')
                           ->get()
                           ->keyBy('sync_type');
        
        return [
            'total_syncs' => $total,
            'completed_syncs' => $completed,
            'failed_syncs' => $failed,
            'running_syncs' => $running,
            'success_rate' => round($successRate, 2),
            'average_duration' => round($avgDuration, 2),
            'by_sync_type' => $bySyncType->toArray(),
        ];
    }

    /**
     * Clean up old sync logs.
     */
    public static function cleanup(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return self::where('started_at', '<', $cutoffDate)
                  ->whereIn('status', ['completed', 'failed', 'cancelled'])
                  ->delete();
    }

    /**
     * Get pending syncs for processing.
     */
    public static function getPendingSyncs(int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('status', 'pending')
                  ->orderBy('priority', 'desc')
                  ->orderBy('created_at', 'asc')
                  ->limit($limit)
                  ->get();
    }

    /**
     * Get failed syncs that can be retried.
     */
    public static function getRetryableSyncs(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return self::retryable()
                  ->orderBy('priority', 'desc')
                  ->orderBy('failed_at', 'asc')
                  ->limit($limit)
                  ->get();
    }

    /**
     * Create a batch of sync operations.
     */
    public static function createBatch(
        array $syncs,
        string $batchId = null
    ): \Illuminate\Database\Eloquent\Collection {
        $batchId = $batchId ?? \Illuminate\Support\Str::uuid()->toString();
        
        $syncLogs = collect();
        
        foreach ($syncs as $sync) {
            $sync['batch_id'] = $batchId;
            $syncLogs->push(self::createSync(
                $sync['sync_type'],
                $sync['operation'],
                $sync['source_table'],
                $sync['target_table'],
                $sync
            ));
        }
        
        return $syncLogs;
    }

    /**
     * Get batch status.
     */
    public static function getBatchStatus(string $batchId): array
    {
        $syncs = self::where('batch_id', $batchId)->get();
        
        if ($syncs->isEmpty()) {
            return ['status' => 'not_found'];
        }
        
        $total = $syncs->count();
        $completed = $syncs->where('status', 'completed')->count();
        $failed = $syncs->whereIn('status', ['failed', 'cancelled'])->count();
        $running = $syncs->whereIn('status', ['pending', 'in_progress', 'retrying'])->count();
        
        $status = 'in_progress';
        if ($completed === $total) {
            $status = 'completed';
        } elseif ($failed > 0 && $running === 0) {
            $status = 'failed';
        } elseif ($running === 0) {
            $status = 'partial';
        }
        
        return [
            'status' => $status,
            'total' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'running' => $running,
            'progress' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
        ];
    }
}