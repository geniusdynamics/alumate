<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ConsentLog Model
 *
 * Audit trail for all consent-related activities to ensure GDPR/CCPA compliance.
 * Tracks consent creation, updates, withdrawals, and data deletion activities.
 */
class ConsentLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'performed_by',
        'timestamp',
        'reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'timestamp' => 'datetime',
    ];

    /**
     * Get the tenant that owns this consent log.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user associated with this consent log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the performer who executed this action.
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Scope a query to only include consent logs for a specific tenant.
     */
    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include consent logs for a specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include consent logs for a specific action.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to order consent logs for audit trail (newest first).
     */
    public function scopeAuditTrail($query)
    {
        return $query->orderBy('timestamp', 'desc');
    }

    /**
     * Get the available consent log actions.
     */
    public static function getLogActions(): array
    {
        return [
            'consent_granted' => 'User granted consent',
            'consent_updated' => 'User updated consent preferences',
            'consent_withdrawn' => 'User withdrew consent',
            'data_deleted' => 'User data deleted due to consent withdrawal',
            'consent_version_updated' => 'Consent version updated',
        ];
    }
}
