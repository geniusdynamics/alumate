<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonationAcknowledgment extends Model
{
    use HasFactory;

    protected $fillable = [
        'donation_id',
        'type',
        'status',
        'recipient_info',
        'message',
        'template_used',
        'personalization_data',
        'scheduled_at',
        'sent_at',
        'delivered_at',
        'delivery_metadata',
        'retry_count',
        'failure_reason',
    ];

    protected $casts = [
        'recipient_info' => 'array',
        'personalization_data' => 'array',
        'delivery_metadata' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function donation(): BelongsTo
    {
        return $this->belongsTo(CampaignDonation::class, 'donation_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeScheduledBefore($query, $datetime)
    {
        return $query->where('scheduled_at', '<=', $datetime);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsDelivered(array $metadata = []): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'delivery_metadata' => $metadata,
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 3;
    }

    public function scheduleRetry(): void
    {
        if ($this->canRetry()) {
            $this->update([
                'status' => 'pending',
                'scheduled_at' => now()->addMinutes(30 * $this->retry_count), // Exponential backoff
            ]);
        }
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'email' => 'Email Thank You',
            'letter' => 'Thank You Letter',
            'phone' => 'Phone Call',
            'public_recognition' => 'Public Recognition',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function getRecipientNameAttribute(): string
    {
        return $this->recipient_info['name'] ?? 'Unknown';
    }

    public function getRecipientEmailAttribute(): string
    {
        return $this->recipient_info['email'] ?? '';
    }
}