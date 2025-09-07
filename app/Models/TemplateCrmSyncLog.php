<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateCrmSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'template_crm_integration_id',
        'template_id',
        'sync_type',
        'crm_provider',
        'crm_record_id',
        'status',
        'sync_data',
        'response_data',
        'error_message',
        'retry_count',
        'synced_at',
    ];

    protected $casts = [
        'sync_data' => 'array',
        'response_data' => 'array',
        'synced_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply tenant scoping automatically for multi-tenant isolation
        static::addGlobalScope('tenant', function ($builder) {
            // Check if we're in a multi-tenant context
            if (config('database.multi_tenant', false)) {
                try {
                    // In production, apply tenant filter based on current tenant context
                    if (tenant() && tenant()->id) {
                        $builder->where('tenant_id', tenant()->id);
                    }
                } catch (\Exception $e) {
                    // Skip tenant scoping in test environment
                }
            }
        });
    }

    /**
     * Scope query to specific tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope query by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope query by sync type
     */
    public function scopeBySyncType($query, string $syncType)
    {
        return $query->where('sync_type', $syncType);
    }

    /**
     * Scope query by CRM provider
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('crm_provider', $provider);
    }

    /**
     * Scope query for failed syncs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope query for successful syncs
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Get the tenant that owns the sync log
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the CRM integration for this sync log
     */
    public function crmIntegration(): BelongsTo
    {
        return $this->belongsTo(TemplateCrmIntegration::class, 'template_crm_integration_id');
    }

    /**
     * Get the template for this sync log
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Check if sync can be retried
     */
    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 3;
    }

    /**
     * Increment retry count
     */
    public function incrementRetryCount(): void
    {
        $this->increment('retry_count');
    }

    /**
     * Mark sync as successful
     */
    public function markSuccessful(array $responseData = null): void
    {
        $this->update([
            'status' => 'success',
            'response_data' => $responseData,
            'synced_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Mark sync as failed
     */
    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Get sync duration in seconds
     */
    public function getSyncDuration(): ?float
    {
        if (!$this->synced_at) {
            return null;
        }

        return $this->synced_at->diffInSeconds($this->created_at);
    }

    /**
     * Get formatted sync data for logging
     */
    public function getFormattedSyncData(): array
    {
        return [
            'id' => $this->id,
            'template_id' => $this->template_id,
            'crm_provider' => $this->crm_provider,
            'sync_type' => $this->sync_type,
            'status' => $this->status,
            'crm_record_id' => $this->crm_record_id,
            'retry_count' => $this->retry_count,
            'created_at' => $this->created_at,
            'synced_at' => $this->synced_at,
            'duration' => $this->getSyncDuration(),
            'error' => $this->error_message,
        ];
    }
}
