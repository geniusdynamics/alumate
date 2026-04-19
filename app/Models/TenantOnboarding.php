<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantOnboarding extends Model
{
    use HasFactory;

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_ABANDONED = 'abandoned';

    public const STATUS_PAUSED = 'paused';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'current_step',
        'total_steps',
        'completed_steps',
        'step_data',
        'status',
        'started_at',
        'completed_at',
        'last_activity_at',
        'expires_at',
        'source',
    ];

    protected $casts = [
        'current_step' => 'integer',
        'total_steps' => 'integer',
        'completed_steps' => 'array',
        'step_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->started_at = now();
            $model->last_activity_at = now();
            $model->expires_at = now()->addDays(7); // 7-day expiration
        });
    }

    /**
     * Get the tenant for this onboarding.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who initiated this onboarding.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if onboarding is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if onboarding is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if onboarding has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentage(): int
    {
        if ($this->total_steps === 0) {
            return 0;
        }

        $completed = count($this->completed_steps ?? []);

        return min(100, (int) (($completed / $this->total_steps) * 100));
    }

    /**
     * Mark a step as completed.
     */
    public function completeStep(int $step, array $data = []): void
    {
        $completedSteps = $this->completed_steps ?? [];

        if (! in_array($step, $completedSteps)) {
            $completedSteps[] = $step;
        }

        $stepData = $this->step_data ?? [];
        $stepData[$step] = array_merge($stepData[$step] ?? [], $data);

        $this->update([
            'current_step' => min($step + 1, $this->total_steps),
            'completed_steps' => $completedSteps,
            'step_data' => $stepData,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Complete the onboarding process.
     */
    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'last_activity_at' => now(),
        ]);

        $this->tenant->update([
            'onboarding_status' => 'completed',
            'onboarding_completed_at' => now(),
        ]);
    }

    /**
     * Abandon the onboarding process.
     */
    public function abandon(): void
    {
        $this->update([
            'status' => self::STATUS_ABANDONED,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Get step data.
     */
    public function getStepData(int $step): array
    {
        return $this->step_data[$step] ?? [];
    }

    /**
     * Get time spent on onboarding in minutes.
     */
    public function getTimeSpent(): int
    {
        if (! $this->started_at) {
            return 0;
        }

        $endTime = $this->completed_at ?? now();

        return (int) $this->started_at->diffInMinutes($endTime);
    }

    /**
     * Scope to active onboardings.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope to completed onboardings.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to expired onboardings.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->where('status', self::STATUS_IN_PROGRESS);
    }
}
