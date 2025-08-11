<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AchievementCelebration extends Model
{
    use HasFactory;

    const TYPE_AUTOMATIC = 'automatic';

    const TYPE_MANUAL = 'manual';

    const TYPE_MILESTONE = 'milestone';

    protected $fillable = [
        'user_achievement_id',
        'post_id',
        'celebration_type',
        'message',
        'celebration_data',
        'is_public',
        'congratulations_count',
    ];

    protected $casts = [
        'celebration_data' => 'array',
        'is_public' => 'boolean',
    ];

    /**
     * Get the user achievement being celebrated
     */
    public function userAchievement(): BelongsTo
    {
        return $this->belongsTo(UserAchievement::class);
    }

    /**
     * Get the associated social post
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the congratulations for this celebration
     */
    public function congratulations(): HasMany
    {
        return $this->hasMany(AchievementCongratulation::class);
    }

    /**
     * Get the user who earned the achievement
     */
    public function getRecipientAttribute(): User
    {
        return $this->userAchievement->user;
    }

    /**
     * Get the achievement being celebrated
     */
    public function getAchievementAttribute(): Achievement
    {
        return $this->userAchievement->achievement;
    }

    /**
     * Scope to get public celebrations
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get celebrations by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('celebration_type', $type);
    }

    /**
     * Increment congratulations count
     */
    public function incrementCongratulations(): void
    {
        $this->increment('congratulations_count');
    }

    /**
     * Decrement congratulations count
     */
    public function decrementCongratulations(): void
    {
        $this->decrement('congratulations_count');
    }

    /**
     * Check if a user has congratulated this celebration
     */
    public function hasUserCongratulated(User $user): bool
    {
        return $this->congratulations()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Add congratulation from a user
     */
    public function addCongratulation(User $user, ?string $message = null): AchievementCongratulation
    {
        if ($this->hasUserCongratulated($user)) {
            throw new \Exception('User has already congratulated this achievement');
        }

        $congratulation = $this->congratulations()->create([
            'user_id' => $user->id,
            'message' => $message,
        ]);

        $this->incrementCongratulations();

        return $congratulation;
    }

    /**
     * Remove congratulation from a user
     */
    public function removeCongratulation(User $user): bool
    {
        $congratulation = $this->congratulations()
            ->where('user_id', $user->id)
            ->first();

        if ($congratulation) {
            $congratulation->delete();
            $this->decrementCongratulations();

            return true;
        }

        return false;
    }
}
