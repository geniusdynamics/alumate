<?php
// ABOUTME: NotificationLog model for schema-based multi-tenancy without tenant_id column
// ABOUTME: Tracks notification delivery status and errors within tenant-specific schemas

namespace App\Models;

use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        // 'tenant_id', // Removed for schema-based tenancy
        'notification_id',
        'channel',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply tenant context for schema-based multi-tenancy
        static::addGlobalScope('tenant', function ($builder) {
            // Schema-based tenancy: data isolation handled at database schema level
            // No additional filtering needed as each tenant has separate schema
            app(TenantContextService::class)->applyTenantContext($builder);
        });
    }

    // Relationships
    public function getCurrentTenant()
    {
        return app(TenantContextService::class)->getCurrentTenant();
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(\Illuminate\Notifications\DatabaseNotification::class, 'notification_id');
    }

    // Scopes
    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Helper methods
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
