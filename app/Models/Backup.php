<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'description',
        'type',
        'status',
        'file_name',
        'file_size',
        'file_path',
        'download_url',
        'include_data',
        'include_files',
        'include_config',
        'compress',
        'encryption',
        'schedule',
        'retention_days',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'include_data' => 'boolean',
        'include_files' => 'boolean',
        'include_config' => 'boolean',
        'compress' => 'boolean',
        'encryption' => 'array',
        'schedule' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the backup
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user that created the backup
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the backup is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the backup is expired
     */
    public function isExpired(): bool
    {
        if (!$this->completed_at || !$this->retention_days) {
            return false;
        }

        return $this->completed_at->addDays($this->retention_days)->isPast();
    }
}