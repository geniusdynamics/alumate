<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'feature_key',
        'feature_name',
        'description',
        'value_type',
        'value',
        'display_format',
    ];

    /**
     * Get the plan that owns this feature.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Get typed value based on value_type.
     */
    public function getTypedValue(): mixed
    {
        return match ($this->value_type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($this->value) ? (int) $this->value : $this->value,
            default => $this->value,
        };
    }

    /**
     * Get display value with formatting.
     */
    public function getDisplayValue(): string
    {
        $value = $this->getTypedValue();

        if ($this->display_format) {
            return sprintf($this->display_format, $value);
        }

        if ($this->value_type === 'boolean') {
            return $value ? '✓ Included' : '✗ Not included';
        }

        if ($value === 'unlimited' || $value === -1) {
            return 'Unlimited';
        }

        return (string) $value;
    }

    /**
     * Check if this feature is a limit.
     */
    public function isLimit(): bool
    {
        return str_contains($this->feature_key, '_limit') ||
               str_contains($this->feature_key, '_max') ||
               str_contains($this->feature_key, '_count');
    }
}
