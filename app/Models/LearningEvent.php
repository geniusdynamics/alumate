<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * LearningEvent Model
 *
 * Tracks individual learning interactions and events for detailed analytics.
 */
class LearningEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'learning_progress_id',
        'event_type',
        'event_data',
        'timestamp',
        'duration',
        'metadata',
    ];

    protected $casts = [
        'event_data' => 'json',
        'timestamp' => 'datetime',
        'metadata' => 'json',
        'duration' => 'integer',
    ];

    /**
     * Get the learning progress this event belongs to
     */
    public function learningProgress(): BelongsTo
    {
        return $this->belongsTo(LearningProgress::class);
    }

    /**
     * Scope by tenant
     */
    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope by event type
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }
}