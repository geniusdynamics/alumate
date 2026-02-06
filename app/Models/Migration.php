<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Migration extends Model
{
    use HasFactory;
    protected $table = 'data_migrations';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'description',
        'type',
        'status',
        'source_version',
        'target_version',
        'migration_data',
        'rollback_enabled',
        'dry_run',
        'schedule',
        'dependencies',
        'error_message',
        'executed_at',
        'rolled_back_at',
    ];

    protected $casts = [
        'migration_data' => 'array',
        'rollback_enabled' => 'boolean',
        'dry_run' => 'boolean',
        'schedule' => 'array',
        'dependencies' => 'array',
        'executed_at' => 'datetime',
        'rolled_back_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the migration
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user that created the migration
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the migration is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the migration can be executed
     */
    public function canExecute(): bool
    {
        return in_array($this->status, ['pending', 'failed']);
    }

    /**
     * Check if the migration can be rolled back
     */
    public function canRollback(): bool
    {
        return $this->status === 'completed' && $this->rollback_enabled && !$this->rolled_back_at;
    }

    /**
     * Check if the migration is rolled back
     */
    public function isRolledBack(): bool
    {
        return $this->status === 'rolled_back';
    }
}