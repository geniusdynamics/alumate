<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'credentials',
        'is_active',
        'last_sync_at',
        'sync_status',
        'sync_error',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Get the user that owns the calendar connection
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only active connections
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by provider
     */
    public function scopeProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    /**
     * Check if the connection needs to be refreshed
     */
    public function needsRefresh(): bool
    {
        if (! $this->last_sync_at) {
            return true;
        }

        return $this->last_sync_at->diffInHours(now()) > 24;
    }

    /**
     * Mark sync as successful
     */
    public function markSyncSuccessful(): void
    {
        $this->update([
            'last_sync_at' => now(),
            'sync_status' => 'success',
            'sync_error' => null,
        ]);
    }

    /**
     * Mark sync as failed
     */
    public function markSyncFailed(string $error): void
    {
        $this->update([
            'sync_status' => 'failed',
            'sync_error' => $error,
        ]);
    }
}
