<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DonorStewardshipPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'donor_profile_id',
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'goals',
        'strategies',
        'milestones',
        'target_gift_amount',
        'target_gift_purpose',
        'target_ask_date',
        'created_by',
        'assigned_to',
        'priority',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'target_ask_date' => 'date',
        'goals' => 'array',
        'strategies' => 'array',
        'milestones' => 'array',
        'target_gift_amount' => 'decimal:2',
        'priority' => 'integer',
    ];

    public function donorProfile(): BelongsTo
    {
        return $this->belongsTo(DonorProfile::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByAssignee($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUpcomingAsk($query, $days = 30)
    {
        return $query->where('target_ask_date', '<=', now()->addDays($days))
            ->where('target_ask_date', '>=', now());
    }

    // Helper methods
    public function getPriorityLabelAttribute()
    {
        return match ($this->priority) {
            1 => 'High',
            2 => 'Medium',
            3 => 'Low',
            default => 'Unknown'
        };
    }

    public function getProgressPercentageAttribute()
    {
        if (! $this->milestones || empty($this->milestones)) {
            return 0;
        }

        $completed = collect($this->milestones)->where('completed', true)->count();
        $total = count($this->milestones);

        return $total > 0 ? round(($completed / $total) * 100) : 0;
    }

    public function getIsOverdueAttribute()
    {
        return $this->target_ask_date && $this->target_ask_date < now() && $this->status !== 'completed';
    }

    public function getDaysUntilAskAttribute()
    {
        if (! $this->target_ask_date) {
            return null;
        }

        return now()->diffInDays($this->target_ask_date, false);
    }

    public function markMilestoneComplete($milestoneIndex)
    {
        $milestones = $this->milestones;
        if (isset($milestones[$milestoneIndex])) {
            $milestones[$milestoneIndex]['completed'] = true;
            $milestones[$milestoneIndex]['completed_at'] = now()->toISOString();
            $this->update(['milestones' => $milestones]);
        }
    }
}
