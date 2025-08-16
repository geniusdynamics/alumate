<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventConnectionRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'recommended_user_id',
        'match_score',
        'match_reasons',
        'shared_attributes',
        'status',
        'recommended_at',
        'viewed_at',
        'acted_on_at',
    ];

    protected $casts = [
        'match_reasons' => 'array',
        'shared_attributes' => 'array',
        'match_score' => 'decimal:2',
        'recommended_at' => 'datetime',
        'viewed_at' => 'datetime',
        'acted_on_at' => 'datetime',
    ];

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recommendedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommended_user_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeViewed($query)
    {
        return $query->where('status', 'viewed');
    }

    public function scopeConnected($query)
    {
        return $query->where('status', 'connected');
    }

    public function scopeDismissed($query)
    {
        return $query->where('status', 'dismissed');
    }

    public function scopeHighMatch($query, float $threshold = 70.0)
    {
        return $query->where('match_score', '>=', $threshold);
    }

    public function scopeOrderByScore($query)
    {
        return $query->orderByDesc('match_score');
    }

    // Helper methods
    public function markAsViewed(): void
    {
        if ($this->status === 'pending') {
            $this->update([
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);
        }
    }

    public function markAsConnected(): void
    {
        $this->update([
            'status' => 'connected',
            'acted_on_at' => now(),
        ]);
    }

    public function dismiss(): void
    {
        $this->update([
            'status' => 'dismissed',
            'acted_on_at' => now(),
        ]);
    }

    public function getMatchScorePercentage(): int
    {
        return (int) round($this->match_score);
    }

    public function getMatchLevel(): string
    {
        return match (true) {
            $this->match_score >= 90 => 'Excellent',
            $this->match_score >= 80 => 'Very Good',
            $this->match_score >= 70 => 'Good',
            $this->match_score >= 60 => 'Fair',
            default => 'Low'
        };
    }

    public function getMatchReasonsSummary(): string
    {
        $reasons = $this->match_reasons ?? [];

        return implode(', ', array_slice($reasons, 0, 3));
    }

    public function hasSharedAttribute(string $attribute): bool
    {
        $attributes = $this->shared_attributes ?? [];

        return isset($attributes[$attribute]) && ! empty($attributes[$attribute]);
    }

    public function getSharedAttributeValue(string $attribute): mixed
    {
        $attributes = $this->shared_attributes ?? [];

        return $attributes[$attribute] ?? null;
    }

    public function getDaysOld(): int
    {
        return $this->recommended_at->diffInDays(now());
    }

    public function isExpired(int $days = 30): bool
    {
        return $this->getDaysOld() > $days;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isViewed(): bool
    {
        return $this->status === 'viewed';
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    public function isDismissed(): bool
    {
        return $this->status === 'dismissed';
    }

    public function isActedUpon(): bool
    {
        return in_array($this->status, ['connected', 'dismissed']);
    }
}
