<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    use HasFactory;

    const CATEGORY_CAREER = 'career';

    const CATEGORY_EDUCATION = 'education';

    const CATEGORY_COMMUNITY = 'community';

    const CATEGORY_MILESTONE = 'milestone';

    const CATEGORY_SPECIAL = 'special';

    const RARITY_COMMON = 'common';

    const RARITY_UNCOMMON = 'uncommon';

    const RARITY_RARE = 'rare';

    const RARITY_EPIC = 'epic';

    const RARITY_LEGENDARY = 'legendary';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'badge_image',
        'category',
        'rarity',
        'criteria',
        'points',
        'is_active',
        'is_auto_awarded',
    ];

    protected $casts = [
        'criteria' => 'array',
        'is_active' => 'boolean',
        'is_auto_awarded' => 'boolean',
    ];

    /**
     * Get the users who have earned this achievement
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot(['earned_at', 'metadata', 'is_featured', 'is_notified'])
            ->withTimestamps();
    }

    /**
     * Get the user achievements for this achievement
     */
    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    /**
     * Get all available categories
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_CAREER => 'Career',
            self::CATEGORY_EDUCATION => 'Education',
            self::CATEGORY_COMMUNITY => 'Community',
            self::CATEGORY_MILESTONE => 'Milestone',
            self::CATEGORY_SPECIAL => 'Special',
        ];
    }

    /**
     * Get all available rarities
     */
    public static function getRarities(): array
    {
        return [
            self::RARITY_COMMON => 'Common',
            self::RARITY_UNCOMMON => 'Uncommon',
            self::RARITY_RARE => 'Rare',
            self::RARITY_EPIC => 'Epic',
            self::RARITY_LEGENDARY => 'Legendary',
        ];
    }

    /**
     * Get the rarity color for UI display
     */
    public function getRarityColorAttribute(): string
    {
        return match ($this->rarity) {
            self::RARITY_COMMON => 'gray',
            self::RARITY_UNCOMMON => 'green',
            self::RARITY_RARE => 'blue',
            self::RARITY_EPIC => 'purple',
            self::RARITY_LEGENDARY => 'yellow',
            default => 'gray'
        };
    }

    /**
     * Get the category icon
     */
    public function getCategoryIconAttribute(): string
    {
        return match ($this->category) {
            self::CATEGORY_CAREER => 'briefcase',
            self::CATEGORY_EDUCATION => 'academic-cap',
            self::CATEGORY_COMMUNITY => 'users',
            self::CATEGORY_MILESTONE => 'flag',
            self::CATEGORY_SPECIAL => 'star',
            default => 'award'
        };
    }

    /**
     * Scope to get active achievements
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get auto-awarded achievements
     */
    public function scopeAutoAwarded($query)
    {
        return $query->where('is_auto_awarded', true);
    }

    /**
     * Scope to get achievements by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get achievements by rarity
     */
    public function scopeByRarity($query, string $rarity)
    {
        return $query->where('rarity', $rarity);
    }

    /**
     * Check if a user meets the criteria for this achievement
     */
    public function checkCriteria(User $user): bool
    {
        if (! $this->is_active || ! $this->is_auto_awarded) {
            return false;
        }

        $criteria = $this->criteria;

        // Handle different types of criteria
        switch ($criteria['type'] ?? null) {
            case 'milestone_count':
                return $this->checkMilestoneCount($user, $criteria);
            case 'connection_count':
                return $this->checkConnectionCount($user, $criteria);
            case 'post_engagement':
                return $this->checkPostEngagement($user, $criteria);
            case 'career_progression':
                return $this->checkCareerProgression($user, $criteria);
            case 'profile_completion':
                return $this->checkProfileCompletion($user, $criteria);
            case 'community_participation':
                return $this->checkCommunityParticipation($user, $criteria);
            default:
                return false;
        }
    }

    /**
     * Check milestone count criteria
     */
    private function checkMilestoneCount(User $user, array $criteria): bool
    {
        $count = $user->careerMilestones()->count();
        $required = $criteria['count'] ?? 1;

        if (isset($criteria['milestone_type'])) {
            $count = $user->careerMilestones()
                ->where('type', $criteria['milestone_type'])
                ->count();
        }

        return $count >= $required;
    }

    /**
     * Check connection count criteria
     */
    private function checkConnectionCount(User $user, array $criteria): bool
    {
        $count = $user->connections()->where('status', 'accepted')->count();
        $required = $criteria['count'] ?? 1;

        return $count >= $required;
    }

    /**
     * Check post engagement criteria
     */
    private function checkPostEngagement(User $user, array $criteria): bool
    {
        $posts = $user->posts();

        if (isset($criteria['min_likes'])) {
            $posts = $posts->where('like_count', '>=', $criteria['min_likes']);
        }

        if (isset($criteria['min_posts'])) {
            return $posts->count() >= $criteria['min_posts'];
        }

        return $posts->exists();
    }

    /**
     * Check career progression criteria
     */
    private function checkCareerProgression(User $user, array $criteria): bool
    {
        $promotions = $user->careerMilestones()
            ->where('type', 'promotion')
            ->count();

        $required = $criteria['promotions'] ?? 1;

        return $promotions >= $required;
    }

    /**
     * Check profile completion criteria
     */
    private function checkProfileCompletion(User $user, array $criteria): bool
    {
        $completionScore = 0;
        $maxScore = 100;

        // Basic profile info
        if ($user->name) {
            $completionScore += 10;
        }
        if ($user->email) {
            $completionScore += 10;
        }
        if ($user->bio) {
            $completionScore += 15;
        }
        if ($user->avatar_url) {
            $completionScore += 10;
        }
        if ($user->location) {
            $completionScore += 10;
        }

        // Career info
        if ($user->careerTimelines()->exists()) {
            $completionScore += 20;
        }
        if ($user->careerMilestones()->exists()) {
            $completionScore += 15;
        }

        // Social connections
        if ($user->connections()->where('status', 'accepted')->exists()) {
            $completionScore += 10;
        }

        $requiredScore = $criteria['completion_percentage'] ?? 80;

        return $completionScore >= $requiredScore;
    }

    /**
     * Check community participation criteria
     */
    private function checkCommunityParticipation(User $user, array $criteria): bool
    {
        $posts = $user->posts()->count();
        $comments = $user->postEngagements()->where('type', 'comment')->count();

        $totalActivity = $posts + $comments;
        $required = $criteria['activity_count'] ?? 5;

        return $totalActivity >= $required;
    }
}
