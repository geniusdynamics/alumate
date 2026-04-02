<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CustomEvent Model
 *
 * Tracks individual custom event instances with flexible properties.
 * Extends the analytics event system for business-specific event tracking.
 */
class CustomEvent extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'definition_id',
        'user_id',
        'data_json',
        'timestamp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data_json' => 'array',
        'timestamp' => 'datetime',
    ];

    /**
     * Get the tenant that owns this custom event.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the custom event definition for this event.
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(CustomEventDefinition::class, 'definition_id');
    }

    /**
     * Get the user that owns this custom event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include custom events for a specific tenant.
     */
    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include custom events for a specific definition.
     */
    public function scopeByDefinition($query, int $definitionId)
    {
        return $query->where('definition_id', $definitionId);
    }

    /**
     * Scope a query to only include custom events for a specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include custom events within a date range.
     */
    public function scopeByPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }
}
