<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Export extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'description',
        'format',
        'status',
        'file_name',
        'file_size',
        'file_path',
        'download_url',
        'metadata',
        'page_ids',
        'options',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'page_ids' => 'array',
        'options' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the export
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user that created the export
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the export is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the export is expired
     */
    public function isExpired(): bool
    {
        if (! $this->completed_at) {
            return false;
        }

        // Exports expire after 7 days
        return $this->completed_at->addDays(7)->isPast();
    }
}
