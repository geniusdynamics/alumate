<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    protected $fillable = [
        'lead_id',
        'type',
        'subject',
        'description',
        'metadata',
        'outcome',
        'scheduled_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the lead this activity belongs to
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the user who created this activity
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if activity is completed
     */
    public function isCompleted(): bool
    {
        return ! is_null($this->completed_at);
    }

    /**
     * Check if activity is scheduled
     */
    public function isScheduled(): bool
    {
        return ! is_null($this->scheduled_at);
    }

    /**
     * Mark activity as completed
     */
    public function markCompleted(?string $outcome = null): void
    {
        $this->update([
            'completed_at' => now(),
            'outcome' => $outcome,
        ]);
    }

    /**
     * Scope for activities by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for completed activities
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Scope for scheduled activities
     */
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at');
    }

    /**
     * Scope for overdue activities
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotNull('scheduled_at')
            ->whereNull('completed_at')
            ->where('scheduled_at', '<', now());
    }
}
