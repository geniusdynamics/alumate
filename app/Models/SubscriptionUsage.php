<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionUsage extends Model
{
    use HasFactory;

    protected $table = 'subscription_usage';

    protected $fillable = [
        'subscription_id',
        'feature_key',
        'usage',
        'limit',
        'period_starts_at',
        'period_ends_at',
    ];

    protected $casts = [
        'usage' => 'integer',
        'limit' => 'integer',
        'period_starts_at' => 'datetime',
        'period_ends_at' => 'datetime',
    ];

    /**
     * Get the subscription that owns this usage record.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get usage percentage.
     */
    public function usagePercentage(): float
    {
        if ($this->limit === 0 || $this->limit === 'unlimited' || $this->limit === -1) {
            return 0;
        }

        return min(100, ($this->usage / $this->limit) * 100);
    }

    /**
     * Check if limit is reached.
     */
    public function isLimitReached(): bool
    {
        if ($this->limit === 'unlimited' || $this->limit === -1) {
            return false;
        }

        return $this->usage >= $this->limit;
    }

    /**
     * Check if approaching limit (80% or more).
     */
    public function isApproachingLimit(float $threshold = 0.8): bool
    {
        if ($this->limit === 'unlimited' || $this->limit === -1) {
            return false;
        }

        return ($this->usage / $this->limit) >= $threshold;
    }

    /**
     * Get remaining usage.
     */
    public function remaining(): int|string
    {
        if ($this->limit === 'unlimited' || $this->limit === -1) {
            return 'unlimited';
        }

        return max(0, $this->limit - $this->usage);
    }

    /**
     * Get formatted usage display.
     */
    public function getFormattedUsage(): string
    {
        $limit = $this->limit === -1 ? '∞' : $this->limit;
        return "{$this->usage} / {$limit}";
    }
}
