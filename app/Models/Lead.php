<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'job_title',
        'lead_type',
        'source',
        'status',
        'score',
        'priority',
        'utm_data',
        'form_data',
        'behavioral_data',
        'notes',
        'assigned_to',
        'last_contacted_at',
        'qualified_at',
        'crm_id',
        'synced_at',
    ];

    protected $casts = [
        'utm_data' => 'array',
        'form_data' => 'array',
        'behavioral_data' => 'array',
        'last_contacted_at' => 'datetime',
        'qualified_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the user assigned to this lead
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get all activities for this lead
     */
    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the full name of the lead
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Check if lead is qualified
     */
    public function isQualified(): bool
    {
        return ! is_null($this->qualified_at);
    }

    /**
     * Check if lead is hot (high priority and high score)
     */
    public function isHot(): bool
    {
        return $this->priority === 'high' && $this->score >= 80;
    }

    /**
     * Update lead score
     */
    public function updateScore(int $points, ?string $reason = null): void
    {
        $oldScore = $this->score;
        $this->score = max(0, min(100, $this->score + $points));
        $this->save();

        // Log the score change
        $this->activities()->create([
            'type' => 'score_change',
            'subject' => 'Score updated',
            'description' => "Score changed from {$oldScore} to {$this->score}".($reason ? " - {$reason}" : ''),
            'metadata' => [
                'old_score' => $oldScore,
                'new_score' => $this->score,
                'points_added' => $points,
                'reason' => $reason,
            ],
            'created_by' => auth()->id() ?? 1,
        ]);
    }

    /**
     * Update lead status
     */
    public function updateStatus(string $newStatus, ?string $reason = null): void
    {
        $oldStatus = $this->status;
        $this->status = $newStatus;

        if ($newStatus === 'qualified' && ! $this->qualified_at) {
            $this->qualified_at = now();
        }

        $this->save();

        // Log the status change
        $this->activities()->create([
            'type' => 'status_change',
            'subject' => 'Status updated',
            'description' => "Status changed from {$oldStatus} to {$newStatus}".($reason ? " - {$reason}" : ''),
            'metadata' => [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
            ],
            'created_by' => auth()->id() ?? 1,
        ]);
    }

    /**
     * Add activity to lead
     */
    public function addActivity(string $type, string $subject, ?string $description = null, array $metadata = []): LeadActivity
    {
        return $this->activities()->create([
            'type' => $type,
            'subject' => $subject,
            'description' => $description,
            'metadata' => $metadata,
            'created_by' => auth()->id() ?? 1,
        ]);
    }

    /**
     * Scope for leads by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for leads by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('lead_type', $type);
    }

    /**
     * Scope for leads by priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for hot leads
     */
    public function scopeHot($query)
    {
        return $query->where('priority', 'high')->where('score', '>=', 80);
    }

    /**
     * Scope for qualified leads
     */
    public function scopeQualified($query)
    {
        return $query->whereNotNull('qualified_at');
    }

    /**
     * Scope for unassigned leads
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    /**
     * Scope for leads assigned to user
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope for leads needing follow-up
     */
    public function scopeNeedsFollowUp($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_contacted_at')
                ->orWhere('last_contacted_at', '<', now()->subDays(7));
        })->whereNotIn('status', ['closed_won', 'closed_lost']);
    }
}
