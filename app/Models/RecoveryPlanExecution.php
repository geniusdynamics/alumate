<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Analytics Disaster Recovery Plan Execution
 *
 * Represents an execution of a disaster recovery plan.
 */
class RecoveryPlanExecution extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ROLLED_BACK = 'rolled_back';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'recovery_plan_id',
        'tenant_id',
        'status',
        'started_at',
        'completed_at',
        'duration_seconds',
        'steps_completed',
        'steps_total',
        'data_restored_count',
        'data_loss_count',
        'errors',
        'warnings',
        'rollback_performed',
        'rollback_reason',
        'initiated_by',
        'execution_type',
        'source_system',
        'target_system',
        'verification_results',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'integer',
        'steps_completed' => 'integer',
        'steps_total' => 'integer',
        'data_restored_count' => 'integer',
        'data_loss_count' => 'integer',
        'errors' => 'array',
        'warnings' => 'array',
        'rollback_performed' => 'boolean',
        'verification_results' => 'array',
    ];

    /**
     * Get the recovery plan
     */
    public function recoveryPlan(): BelongsTo
    {
        return $this->belongsTo(RecoveryPlan::class);
    }

    /**
     * Get the tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if execution is currently running
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if execution has completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if execution has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Calculate progress percentage
     */
    public function getProgressPercentage(): int
    {
        if ($this->steps_total === 0) {
            return 0;
        }

        return (int) (($this->steps_completed / $this->steps_total) * 100);
    }
}
