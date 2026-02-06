<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Analytics Disaster Recovery Plan
 *
 * Represents a disaster recovery plan for analytics data.
 */
class RecoveryPlan extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_TESTING = 'testing';
    public const STATUS_EXECUTING = 'executing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ARCHIVED = 'archived';

    public const PRIORITY_CRITICAL = 'critical';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_LOW = 'low';

    public const TYPE_FULL_RECOVERY = 'full_recovery';
    public const TYPE_PARTIAL_RECOVERY = 'partial_recovery';
    public const TYPE_POINT_IN_TIME = 'point_in_time';
    public const TYPE_FAILOVER = 'failover';
    public const TYPE_FAILBACK = 'failback';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'description',
        'type',
        'status',
        'priority',
        'backup_id',
        'target_backup_id',
        'steps',
        'configuration',
        'test_results',
        'execution_log',
        'scheduled_at',
        'started_at',
        'completed_at',
        'estimated_duration_minutes',
        'actual_duration_minutes',
        'data_loss_estimate',
        'last_validated_at',
        'last_tested_at',
        'failure_count',
        'is_automatic',
        'notify_on_completion',
        'notification_channels',
    ];

    protected $casts = [
        'steps' => 'array',
        'configuration' => 'array',
        'test_results' => 'array',
        'execution_log' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_duration_minutes' => 'integer',
        'actual_duration_minutes' => 'integer',
        'last_validated_at' => 'datetime',
        'last_tested_at' => 'datetime',
        'failure_count' => 'integer',
        'is_automatic' => 'boolean',
        'notify_on_completion' => 'boolean',
        'notification_channels' => 'array',
    ];

    /**
     * Get the tenant that owns the recovery plan
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user that created the recovery plan
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the primary backup for this recovery plan
     */
    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class, 'backup_id');
    }

    /**
     * Get the target backup for this recovery plan
     */
    public function targetBackup(): BelongsTo
    {
        return $this->belongsTo(Backup::class, 'target_backup_id');
    }

    /**
     * Get recovery plan executions
     */
    public function executions(): HasMany
    {
        return $this->hasMany(RecoveryPlanExecution::class);
    }

    /**
     * Check if the plan is in draft status
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the plan is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the plan is currently being executed
     */
    public function isExecuting(): bool
    {
        return $this->status === self::STATUS_EXECUTING;
    }

    /**
     * Check if the plan has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the plan is critical priority
     */
    public function isCritical(): bool
    {
        return $this->priority === self::PRIORITY_CRITICAL;
    }
}
