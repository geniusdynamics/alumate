<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_PAST_DUE = 'past_due';

    public const STATUS_TRIALING = 'trialing';

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PAUSED = 'paused';

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'stripe_subscription_id',
        'stripe_customer_id',
        'stripe_price_id',
        'status',
        'current_period_starts_at',
        'current_period_ends_at',
        'trial_starts_at',
        'trial_ends_at',
        'cancelled_at',
        'cancel_at_period_end',
        'payment_method_id',
        'payment_method_brand',
        'payment_method_last_four',
        'latest_invoice_id',
        'latest_invoice_pdf',
        'latest_invoice_amount',
        'latest_invoice_paid_at',
    ];

    protected $casts = [
        'current_period_starts_at' => 'datetime',
        'current_period_ends_at' => 'datetime',
        'trial_starts_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'latest_invoice_amount' => 'decimal:2',
        'latest_invoice_paid_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns this subscription.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the plan for this subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Get the usage records for this subscription.
     */
    public function usage(): HasMany
    {
        return $this->hasMany(SubscriptionUsage::class);
    }

    /**
     * Scope to active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_TRIALING]);
    }

    /**
     * Scope to subscriptions that need renewal.
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('current_period_ends_at', '<=', Carbon::now()->addDays($days))
            ->where('current_period_ends_at', '>', Carbon::now())
            ->where('cancel_at_period_end', false);
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE || $this->isOnTrial();
    }

    /**
     * Check if subscription is on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->status === self::STATUS_TRIALING &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if trial has expired.
     */
    public function trialExpired(): bool
    {
        return $this->status === self::STATUS_TRIALING &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isPast();
    }

    /**
     * Check if subscription is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null || $this->cancel_at_period_end;
    }

    /**
     * Check if subscription is past due.
     */
    public function isPastDue(): bool
    {
        return $this->status === self::STATUS_PAST_DUE;
    }

    /**
     * Check if subscription is unpaid.
     */
    public function isUnpaid(): bool
    {
        return $this->status === self::STATUS_UNPAID;
    }

    /**
     * Get days remaining in current period.
     */
    public function daysRemaining(): int
    {
        if (! $this->current_period_ends_at) {
            return 0;
        }

        return max(0, Carbon::now()->diffInDays($this->current_period_ends_at, false));
    }

    /**
     * Get trial days remaining.
     */
    public function trialDaysRemaining(): int
    {
        if (! $this->isOnTrial() || ! $this->trial_ends_at) {
            return 0;
        }

        return max(0, Carbon::now()->diffInDays($this->trial_ends_at, false));
    }

    /**
     * Get usage for a specific feature.
     */
    public function getUsage(string $featureKey): int
    {
        $usage = $this->usage()->where('feature_key', $featureKey)->first();

        return $usage ? $usage->usage : 0;
    }

    /**
     * Get limit for a specific feature.
     */
    public function getLimit(string $featureKey): int|string
    {
        if (! $this->plan) {
            return 0;
        }

        return $this->plan->getFeatureValue($featureKey, 0);
    }

    /**
     * Check if feature limit is reached.
     */
    public function isLimitReached(string $featureKey): bool
    {
        $limit = $this->getLimit($featureKey);

        if ($limit === 'unlimited' || $limit === -1) {
            return false;
        }

        $usage = $this->getUsage($featureKey);

        return $usage >= (int) $limit;
    }

    /**
     * Increment usage for a feature.
     */
    public function incrementUsage(string $featureKey, int $amount = 1): void
    {
        $usage = $this->usage()->firstOrCreate(
            ['feature_key' => $featureKey],
            [
                'usage' => 0,
                'limit' => $this->getLimit($featureKey),
                'period_starts_at' => $this->current_period_starts_at,
                'period_ends_at' => $this->current_period_ends_at,
            ]
        );

        $usage->increment('usage', $amount);
    }

    /**
     * Reset usage for a new billing period.
     */
    public function resetUsage(): void
    {
        $this->usage()->update([
            'usage' => 0,
            'period_starts_at' => $this->current_period_starts_at,
            'period_ends_at' => $this->current_period_ends_at,
        ]);
    }

    /**
     * Get formatted payment method.
     */
    public function getPaymentMethodDisplay(): ?string
    {
        if (! $this->payment_method_brand || ! $this->payment_method_last_four) {
            return null;
        }

        return ucfirst($this->payment_method_brand).' •••• '.$this->payment_method_last_four;
    }

    /**
     * Get status badge color.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'green',
            self::STATUS_TRIALING => 'blue',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_PAST_DUE => 'yellow',
            self::STATUS_UNPAID => 'red',
            self::STATUS_PAUSED => 'orange',
            default => 'gray',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_TRIALING => 'Trial',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_PAST_DUE => 'Past Due',
            self::STATUS_UNPAID => 'Unpaid',
            self::STATUS_PAUSED => 'Paused',
            default => ucfirst($this->status),
        };
    }
}
