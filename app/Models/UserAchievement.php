<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserAchievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'achievement_id',
        'earned_at',
        'metadata',
        'is_featured',
        'is_notified',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'metadata' => 'array',
        'is_featured' => 'boolean',
        'is_notified' => 'boolean',
    ];

    /**
     * Get the user who earned this achievement
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the achievement that was earned
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    /**
     * Get the celebrations for this user achievement
     */
    public function celebrations(): HasMany
    {
        return $this->hasMany(AchievementCelebration::class);
    }

    /**
     * Scope to get featured achievements
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get unnotified achievements
     */
    public function scopeUnnotified($query)
    {
        return $query->where('is_notified', false);
    }

    /**
     * Mark as notified
     */
    public function markAsNotified(): void
    {
        $this->update(['is_notified' => true]);
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(): void
    {
        $this->update(['is_featured' => ! $this->is_featured]);
    }

    /**
     * Create a celebration for this achievement
     */
    public function createCelebration(string $type = 'automatic', ?string $message = null, array $data = []): AchievementCelebration
    {
        return $this->celebrations()->create([
            'celebration_type' => $type,
            'message' => $message,
            'celebration_data' => $data,
            'is_public' => true,
        ]);
    }
}
