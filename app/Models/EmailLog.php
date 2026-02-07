<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Email Log Model
 *
 * Tracks all email sending activity including status, bounces, opens, and clicks.
 * Supports multi-tenant email delivery tracking.
 */
class EmailLog extends Model
{
    use HasFactory;

    /**
     * Email status constants
     */
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_BOUNCED = 'bounced';
    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_QUEUED,
        self::STATUS_SENT,
        self::STATUS_DELIVERED,
        self::STATUS_BOUNCED,
        self::STATUS_FAILED,
    ];

    /**
     * Bounce type constants
     */
    public const BOUNCE_TYPE_HARD = 'hard';
    public const BOUNCE_TYPE_SOFT = 'soft';
    public const BOUNCE_TYPE_TRANSIENT = 'transient';

    protected $fillable = [
        'recipient_email',
        'sender_email',
        'subject',
        'template',
        'status',
        'provider',
        'provider_id',
        'bounce_type',
        'bounce_reason',
        'opened_at',
        'clicked_at',
        'sent_at',
        'delivered_at',
        'failed_at',
        'error_message',
        'retry_count',
        'next_retry_at',
        'metadata',
        'tracking_id',
        'tenant_id',
        'user_id',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'metadata' => 'array',
        'retry_count' => 'integer',
    ];

    /**
     * Get the tenant this email belongs to
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user this email was sent to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Get sent emails
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope: Get delivered emails
     */
    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    /**
     * Scope: Get bounced emails
     */
    public function scopeBounced(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_BOUNCED);
    }

    /**
     * Scope: Get failed emails
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: Get queued emails
     */
    public function scopeQueued(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_QUEUED);
    }

    /**
     * Scope: Get opened emails
     */
    public function scopeOpened(Builder $query): Builder
    {
        return $query->whereNotNull('opened_at');
    }

    /**
     * Scope: Get clicked emails
     */
    public function scopeClicked(Builder $query): Builder
    {
        return $query->whereNotNull('clicked_at');
    }

    /**
     * Scope: Get emails ready for retry
     */
    public function scopeReadyForRetry(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED)
            ->where('retry_count', '<', 5)
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            });
    }

    /**
     * Scope: Get emails by status
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Get emails by provider
     */
    public function scopeByProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope: Get emails by date range
     */
    public function scopeByDateRange(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Check if email was sent
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT ||
            $this->status === self::STATUS_DELIVERED ||
            $this->status === self::STATUS_BOUNCED;
    }

    /**
     * Check if email was delivered
     */
    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * Check if email bounced
     */
    public function isBounced(): bool
    {
        return $this->status === self::STATUS_BOUNCED;
    }

    /**
     * Check if email failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if email is queued
     */
    public function isQueued(): bool
    {
        return $this->status === self::STATUS_QUEUED;
    }

    /**
     * Check if email was opened
     */
    public function isOpened(): bool
    {
        return ! is_null($this->opened_at);
    }

    /**
     * Check if email was clicked
     */
    public function isClicked(): bool
    {
        return ! is_null($this->clicked_at);
    }

    /**
     * Mark email as sent
     */
    public function markAsSent(?string $providerId = null): void
    {
        $updates = [
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ];

        if ($providerId) {
            $updates['provider_id'] = $providerId;
        }

        $this->update($updates);
    }

    /**
     * Mark email as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark email as bounced
     */
    public function markAsBounced(string $bounceType, ?string $bounceReason = null): void
    {
        $this->update([
            'status' => self::STATUS_BOUNCED,
            'bounce_type' => $bounceType,
            'bounce_reason' => $bounceReason,
        ]);
    }

    /**
     * Mark email as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Record email open
     */
    public function recordOpen(array $metadata = []): void
    {
        if (! $this->isOpened()) {
            $this->update([
                'opened_at' => now(),
                'metadata' => array_merge($this->metadata ?? [], ['open_metadata' => $metadata]),
            ]);
        }
    }

    /**
     * Record email click
     */
    public function recordClick(array $metadata = []): void
    {
        if (! $this->isClicked()) {
            $this->update([
                'clicked_at' => now(),
                'metadata' => array_merge($this->metadata ?? [], ['click_metadata' => $metadata]),
            ]);
        }
    }

    /**
     * Increment retry count and set next retry time
     */
    public function incrementRetry(): void
    {
        $retryCount = $this->retry_count + 1;
        $nextRetry = now()->addMinutes($this->calculateBackoffMinutes($retryCount));

        $this->update([
            'retry_count' => $retryCount,
            'next_retry_at' => $nextRetry,
        ]);
    }

    /**
     * Calculate exponential backoff minutes
     */
    protected function calculateBackoffMinutes(int $retryCount): int
    {
        return (int) min(pow(2, $retryCount) * 5, 1440); // Max 24 hours
    }

    /**
     * Get time to open in minutes
     */
    public function getTimeToOpen(): ?int
    {
        if (! $this->sent_at || ! $this->opened_at) {
            return null;
        }

        return $this->sent_at->diffInMinutes($this->opened_at);
    }

    /**
     * Get time to click in minutes
     */
    public function getTimeToClick(): ?int
    {
        if (! $this->sent_at || ! $this->clicked_at) {
            return null;
        }

        return $this->sent_at->diffInMinutes($this->clicked_at);
    }

    /**
     * Get time to delivery in minutes
     */
    public function getTimeToDelivery(): ?int
    {
        if (! $this->sent_at || ! $this->delivered_at) {
            return null;
        }

        return $this->sent_at->diffInMinutes($this->delivered_at);
    }

    /**
     * Get validation rules for the model
     */
    public static function getValidationRules(): array
    {
        return [
            'recipient_email' => 'required|email|max:255',
            'sender_email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'template' => 'nullable|string|max:255',
            'status' => 'required|in:' . implode(',', self::STATUSES),
            'provider' => 'required|string|max:50',
            'provider_id' => 'nullable|string|max:255',
            'bounce_type' => 'nullable|in:' . implode(',', [self::BOUNCE_TYPE_HARD, self::BOUNCE_TYPE_SOFT, self::BOUNCE_TYPE_TRANSIENT]),
            'bounce_reason' => 'nullable|string',
            'metadata' => 'nullable|array',
            'tracking_id' => 'nullable|string|unique:email_logs,tracking_id',
        ];
    }
}
