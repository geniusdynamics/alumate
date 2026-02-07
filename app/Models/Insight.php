<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Insight Model
 *
 * Stores AI-generated insights and recommendations for analytics.
 * Supports tenant isolation and effectiveness tracking.
 */
class Insight extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'type',
        'data',
        'status',
        'effectiveness_score',
        'tracked_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'effectiveness_score' => 'decimal:2',
        'tracked_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns this insight.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope a query to only include insights for a specific tenant.
     */
    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include insights of a specific type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include active insights.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include insights of a specific status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if this insight is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if this insight has been implemented.
     */
    public function isImplemented(): bool
    {
        return $this->status === 'implemented';
    }

    /**
     * Check if this insight has been dismissed.
     */
    public function isDismissed(): bool
    {
        return $this->status === 'dismissed';
    }

    /**
     * Get the insight types available.
     */
    public static function getInsightTypes(): array
    {
        return [
            'trend' => 'Trend Analysis',
            'recommendation' => 'Actionable Recommendation',
        ];
    }

    /**
     * Get the insight statuses available.
     */
    public static function getInsightStatuses(): array
    {
        return [
            'active' => 'Active',
            'dismissed' => 'Dismissed',
            'implemented' => 'Implemented',
        ];
    }
}
