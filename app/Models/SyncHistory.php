<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Sync History Model
 * 
 * Tracks synchronization operations between analytics data sources
 */
class SyncHistory extends Model
{
    protected $fillable = [
        'source',
        'target',
        'date_range_start',
        'date_range_end',
        'status',
        'started_at',
        'completed_at',
        'records_synced',
        'error_message',
        'tenant_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'records_synced' => 'integer',
    ];

    /**
     * Get the tenant that owns this sync history
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to pending syncs
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to in-progress syncs
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope to completed syncs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to failed syncs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to specific tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Calculate duration in seconds
     */
    public function getDurationSecondsAttribute(): ?int
    {
        if (!$this->completed_at) {
            return null;
        }
        return $this->started_at->diffInSeconds($this->completed_at);
    }
}
