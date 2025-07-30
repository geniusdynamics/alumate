<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'saved_search_id',
        'frequency',
        'is_active',
        'last_sent_at',
        'next_send_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
        'next_send_at' => 'datetime'
    ];

    const FREQUENCIES = [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly'
    ];

    /**
     * Get the user that owns the search alert
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the saved search for this alert
     */
    public function savedSearch(): BelongsTo
    {
        return $this->belongsTo(SavedSearch::class);
    }

    /**
     * Calculate the next send time based on frequency
     */
    public function calculateNextSendTime(): void
    {
        $lastSent = $this->last_sent_at ?? now();
        
        $nextSend = match ($this->frequency) {
            'daily' => $lastSent->addDay(),
            'weekly' => $lastSent->addWeek(),
            'monthly' => $lastSent->addMonth(),
            default => $lastSent->addDay()
        };
        
        $this->update(['next_send_at' => $nextSend]);
    }

    /**
     * Mark alert as sent
     */
    public function markAsSent(): void
    {
        $this->update(['last_sent_at' => now()]);
        $this->calculateNextSendTime();
    }

    /**
     * Check if alert is due to be sent
     */
    public function isDue(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        if (!$this->next_send_at) {
            return true; // First time sending
        }
        
        return $this->next_send_at <= now();
    }

    /**
     * Scope for active alerts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for due alerts
     */
    public function scopeDue($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('next_send_at')
                  ->orWhere('next_send_at', '<=', now());
            });
    }
}