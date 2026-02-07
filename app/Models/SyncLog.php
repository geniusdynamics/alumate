<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SyncLog Model
 *
 * Tracks synchronization operations between internal analytics and external services.
 * Maintains audit trail for data sync activities with tenant isolation.
 */
class SyncLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'sync_type',
        'status',
        'discrepancies',
        'timestamp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'discrepancies' => 'array',
        'timestamp' => 'datetime',
    ];

    /**
     * Get the tenant that owns this sync log.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope a query to only include sync logs for a specific tenant.
     */
    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include sync logs of a specific type.
     */
    public function scopeBySyncType($query, string $syncType)
    {
        return $query->where('sync_type', $syncType);
    }

    /**
     * Scope a query to only include sync logs with a specific status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include sync logs within a date range.
     */
    public function scopeByDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    /**
     * Get the sync types available.
     */
    public static function getSyncTypes(): array
    {
        return [
            'ga' => 'Google Analytics sync',
            'matomo' => 'Matomo sync',
            'unified' => 'Unified data sync',
            'discrepancy_detection' => 'Discrepancy detection',
        ];
    }

    /**
     * Get the status types available.
     */
    public static function getStatusTypes(): array
    {
        return [
            'success' => 'Sync completed successfully',
            'failed' => 'Sync failed with errors',
            'partial' => 'Sync completed with some issues',
        ];
    }

    /**
     * Check if this sync log indicates a successful operation.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if this sync log indicates a failed operation.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get formatted discrepancies for display.
     */
    public function getFormattedDiscrepancies(): array
    {
        if (empty($this->discrepancies)) {
            return [];
        }

        return array_map(function ($discrepancy) {
            return [
                'metric' => $discrepancy['metric'] ?? 'unknown',
                'source' => $discrepancy['source'] ?? 'unknown',
                'internal_value' => $discrepancy['internal_value'] ?? 0,
                'external_value' => $discrepancy['external_value'] ?? 0,
                'difference_percentage' => $discrepancy['difference_percentage'] ?? 0,
                'threshold_exceeded' => $discrepancy['threshold_exceeded'] ?? false,
            ];
        }, $this->discrepancies);
    }
}
