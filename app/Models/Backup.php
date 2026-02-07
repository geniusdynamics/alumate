<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'subtype',
        'filename',
        'path',
        'cloud_path',
        'cloud_disk',
        'size',
        'checksum',
        'tenant_id',
        'status',
        'completed_at',
        'verified_at',
        'verification_status',
        'metadata',
        'error_message',
    ];

    protected $casts = [
        'size' => 'integer',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant for this backup.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Format size for display.
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Check if backup is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if backup is verified.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'valid';
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'completed' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            'running' => 'blue',
            default => 'gray',
        };
    }
}
