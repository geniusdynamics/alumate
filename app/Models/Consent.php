<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Consent Model
 *
 * Manages user consent for analytics tracking.
 * Supports GDPR/CCPA compliance for analytics data collection.
 */
class Consent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'granted_at',
        'revoked_at',
        'ip_address',
        'criteria',
        'parameters',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'criteria' => 'array',
        'parameters' => 'array',
    ];

    /**
     * Get the user associated with this consent.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active consents.
     */
    public function scopeActive($query)
    {
        return $query->whereNotNull('granted_at')->whereNull('revoked_at');
    }

    /**
     * Scope a query to only include consents for a specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include consents for a specific type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include expired consents (revoked >30 days).
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('revoked_at')
            ->where('revoked_at', '<', now()->subDays(30));
    }

    /**
     * Get the analytics events associated with this consent.
     */
    public function analyticsEvents(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class, 'user_id', 'user_id');
    }

    /**
     * Get the insights associated with this consent.
     */
    public function insights(): HasMany
    {
        return $this->hasMany(Insight::class, 'tenant_id', 'tenant_id');
    }

    /**
     * Check if this consent is currently active.
     */
    public function isActive(): bool
    {
        return ! is_null($this->granted_at) && is_null($this->revoked_at);
    }

    /**
     * Get the valid consent types.
     */
    public static function getValidTypes(): array
    {
        return ['analytics'];
    }
}
