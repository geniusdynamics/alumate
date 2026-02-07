<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'stripe_price_id',
        'stripe_product_id',
        'price_monthly',
        'price_yearly',
        'currency',
        'interval',
        'is_active',
        'is_popular',
        'trial_days',
        'features',
        'limits',
        'display_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'trial_days' => 'integer',
        'features' => 'array',
        'limits' => 'array',
        'display_order' => 'integer',
    ];

    /**
     * Get the features for this plan.
     */
    public function planFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class, 'plan_id');
    }

    /**
     * Get the subscriptions for this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /**
     * Scope to active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to ordered plans.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('price_monthly');
    }

    /**
     * Get plan price for a given interval.
     */
    public function getPrice(string $interval = 'monthly'): float
    {
        return $interval === 'yearly' && $this->price_yearly
            ? (float) $this->price_yearly
            : (float) $this->price_monthly;
    }

    /**
     * Check if the plan has a specific feature.
     */
    public function hasFeature(string $featureKey): bool
    {
        $feature = $this->planFeatures()->where('feature_key', $featureKey)->first();

        if (! $feature) {
            return false;
        }

        return $feature->value_type === 'boolean'
            ? filter_var($feature->value, FILTER_VALIDATE_BOOLEAN)
            : true;
    }

    /**
     * Get feature value.
     */
    public function getFeatureValue(string $featureKey, mixed $default = null): mixed
    {
        $feature = $this->planFeatures()->where('feature_key', $featureKey)->first();

        return $feature ? $feature->getTypedValue() : $default;
    }

    /**
     * Check if plan has unlimited feature.
     */
    public function isUnlimited(string $featureKey): bool
    {
        $value = $this->getFeatureValue($featureKey);

        return $value === 'unlimited' || $value === -1;
    }

    /**
     * Get formatted price display.
     */
    public function getFormattedPrice(string $interval = 'monthly'): string
    {
        $price = $this->getPrice($interval);

        return '$'.number_format($price, 2).'/'.($interval === 'yearly' ? 'year' : 'mo');
    }

    /**
     * Get yearly savings percentage.
     */
    public function getYearlySavingsPercentage(): ?int
    {
        if (! $this->price_yearly || $this->price_monthly <= 0) {
            return null;
        }

        $monthlyCost = $this->price_monthly * 12;
        $savings = $monthlyCost - $this->price_yearly;
        $percentage = ($savings / $monthlyCost) * 100;

        return (int) round($percentage);
    }
}
