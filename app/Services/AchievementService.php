<?php

namespace App\Services;

use App\Jobs\ProcessAchievementCelebrationJob;
use App\Models\Achievement;
use App\Models\AchievementCelebration;
use App\Models\Post;
use App\Models\User;
use App\Models\UserAchievement;
use App\Notifications\AchievementEarnedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AchievementService
{
    /**
     * Check and award achievements for a user
     */
    public function checkAndAwardAchievements(User $user): array
    {
        $newAchievements = [];

        $achievements = Achievement::active()
            ->autoAwarded()
            ->get();

        foreach ($achievements as $achievement) {
            if ($this->shouldAwardAchievement($user, $achievement)) {
                $userAchievement = $this->awardAchievement($user, $achievement);
                if ($userAchievement) {
                    $newAchievements[] = $userAchievement;
                }
            }
        }

        return $newAchievements;
    }

    /**
     * Check if a user should be awarded a specific achievement
     */
    public function shouldAwardAchievement(User $user, Achievement $achievement): bool
    {
        // Check if user already has this achievement
        if ($user->achievements()->where('achievement_id', $achievement->id)->exists()) {
            return false;
        }

        // Check if user meets the criteria
        return $achievement->checkCriteria($user);
    }

    /**
     * Award an achievement to a user
     */
    public function awardAchievement(User $user, Achievement $achievement, array $metadata = []): ?UserAchievement
    {
        try {
            DB::beginTransaction();

            // Create the user achievement record
            $userAchievement = UserAchievement::create([
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
                'earned_at' => now(),
                'metadata' => $metadata,
                'is_featured' => $achievement->rarity === Achievement::RARITY_RARE ||
                               $achievement->rarity === Achievement::RARITY_EPIC ||
                               $achievement->rarity === Achievement::RARITY_LEGENDARY,
                'is_notified' => false,
            ]);

            // Create automatic celebration
            $celebration = $this->createCelebration($userAchievement);

            // Send notification to user
            $user->notify(new AchievementEarnedNotification($userAchievement));

            // Queue job to handle social sharing and community celebration
            ProcessAchievementCelebrationJob::dispatch($celebration);

            DB::commit();

            Log::info('Achievement awarded', [
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
                'achievement_name' => $achievement->name,
            ]);

            return $userAchievement;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to award achievement', [
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create a celebration for a user achievement
     */
    public function createCelebration(UserAchievement $userAchievement, string $type = 'automatic'): AchievementCelebration
    {
        $message = $this->generateCelebrationMessage($userAchievement);

        return $userAchievement->createCelebration($type, $message, [
            'achievement_rarity' => $userAchievement->achievement->rarity,
            'achievement_category' => $userAchievement->achievement->category,
            'points_awarded' => $userAchievement->achievement->points,
        ]);
    }

    /**
     * Generate a celebration message for an achievement
     */
    private function generateCelebrationMessage(UserAchievement $userAchievement): string
    {
        $user = $userAchievement->user;
        $achievement = $userAchievement->achievement;

        $messages = [
            'career' => [
                "🎉 {$user->name} just unlocked the '{$achievement->name}' achievement! Their career journey continues to inspire us all.",
                "👏 Congratulations to {$user->name} for earning the '{$achievement->name}' badge! Another milestone in their professional growth.",
                "🌟 {$user->name} has achieved '{$achievement->name}'! Their dedication to career excellence is truly remarkable.",
            ],
            'education' => [
                "📚 {$user->name} just earned the '{$achievement->name}' achievement! Continuous learning at its finest.",
                "🎓 Kudos to {$user->name} for unlocking '{$achievement->name}'! Education never stops paying dividends.",
                "💡 {$user->name} has achieved '{$achievement->name}'! Their commitment to learning is inspiring.",
            ],
            'community' => [
                "🤝 {$user->name} just earned the '{$achievement->name}' achievement! Thank you for being such an active community member.",
                "🌍 Congratulations to {$user->name} for achieving '{$achievement->name}'! Community engagement at its best.",
                "💪 {$user->name} has unlocked '{$achievement->name}'! Their contributions make our community stronger.",
            ],
            'milestone' => [
                "🏆 {$user->name} just reached a major milestone with the '{$achievement->name}' achievement!",
                "🎯 Congratulations to {$user->name} for earning '{$achievement->name}'! Another significant milestone achieved.",
                "⭐ {$user->name} has achieved '{$achievement->name}'! Celebrating this important milestone with them.",
            ],
            'special' => [
                "✨ {$user->name} just unlocked the rare '{$achievement->name}' achievement! This is something truly special.",
                "🎊 Congratulations to {$user->name} for earning the exclusive '{$achievement->name}' badge!",
                "🌟 {$user->name} has achieved the coveted '{$achievement->name}'! This is a remarkable accomplishment.",
            ],
        ];

        $categoryMessages = $messages[$achievement->category] ?? $messages['milestone'];

        return $categoryMessages[array_rand($categoryMessages)];
    }

    /**
     * Create a social post for achievement celebration
     */
    public function createAchievementPost(AchievementCelebration $celebration): Post
    {
        $user = $celebration->recipient;
        $achievement = $celebration->achievement;

        $content = $celebration->message;

        // Add achievement details
        $content .= "\n\n🏆 Achievement: {$achievement->name}";
        $content .= "\n📝 {$achievement->description}";

        if ($achievement->rarity !== Achievement::RARITY_COMMON) {
            $rarityEmoji = match ($achievement->rarity) {
                Achievement::RARITY_UNCOMMON => '🥉',
                Achievement::RARITY_RARE => '🥈',
                Achievement::RARITY_EPIC => '🥇',
                Achievement::RARITY_LEGENDARY => '💎',
                default => '🏅'
            };
            $content .= "\n{$rarityEmoji} Rarity: ".ucfirst($achievement->rarity);
        }

        $post = Post::create([
            'user_id' => $user->id,
            'content' => $content,
            'post_type' => 'achievement',
            'visibility' => 'public',
            'metadata' => [
                'achievement_id' => $achievement->id,
                'user_achievement_id' => $celebration->user_achievement_id,
                'celebration_id' => $celebration->id,
                'is_achievement_post' => true,
            ],
        ]);

        // Update celebration with post reference
        $celebration->update(['post_id' => $post->id]);

        return $post;
    }

    /**
     * Get achievement statistics for a user
     */
    public function getUserAchievementStats(User $user): array
    {
        $achievements = $user->userAchievements()->with('achievement')->get();

        $stats = [
            'total_achievements' => $achievements->count(),
            'total_points' => $achievements->sum(fn ($ua) => $ua->achievement->points),
            'by_category' => [],
            'by_rarity' => [],
            'recent_achievements' => $achievements->sortByDesc('earned_at')->take(5)->values(),
            'featured_achievements' => $achievements->where('is_featured', true)->values(),
        ];

        // Group by category
        foreach (Achievement::getCategories() as $key => $label) {
            $categoryAchievements = $achievements->filter(fn ($ua) => $ua->achievement->category === $key);
            $stats['by_category'][$key] = [
                'label' => $label,
                'count' => $categoryAchievements->count(),
                'points' => $categoryAchievements->sum(fn ($ua) => $ua->achievement->points),
            ];
        }

        // Group by rarity
        foreach (Achievement::getRarities() as $key => $label) {
            $rarityAchievements = $achievements->filter(fn ($ua) => $ua->achievement->rarity === $key);
            $stats['by_rarity'][$key] = [
                'label' => $label,
                'count' => $rarityAchievements->count(),
                'points' => $rarityAchievements->sum(fn ($ua) => $ua->achievement->points),
            ];
        }

        return $stats;
    }

    /**
     * Get leaderboard data
     */
    public function getAchievementLeaderboard(int $limit = 10): array
    {
        $topUsers = User::select('users.*')
            ->selectRaw('COUNT(user_achievements.id) as achievement_count')
            ->selectRaw('SUM(achievements.points) as total_points')
            ->leftJoin('user_achievements', 'users.id', '=', 'user_achievements.user_id')
            ->leftJoin('achievements', 'user_achievements.achievement_id', '=', 'achievements.id')
            ->groupBy('users.id')
            ->orderByDesc('total_points')
            ->orderByDesc('achievement_count')
            ->limit($limit)
            ->get();

        return $topUsers->map(function ($user) {
            return [
                'user' => $user,
                'achievement_count' => $user->achievement_count ?? 0,
                'total_points' => $user->total_points ?? 0,
                'recent_achievements' => $user->userAchievements()
                    ->with('achievement')
                    ->latest('earned_at')
                    ->limit(3)
                    ->get(),
            ];
        })->toArray();
    }

    /**
     * Manually award an achievement to a user
     */
    public function manuallyAwardAchievement(User $user, Achievement $achievement, User $awardedBy, array $metadata = []): ?UserAchievement
    {
        // Check if user already has this achievement
        if ($user->achievements()->where('achievement_id', $achievement->id)->exists()) {
            throw new \Exception('User already has this achievement');
        }

        $metadata['awarded_by'] = $awardedBy->id;
        $metadata['awarded_manually'] = true;

        return $this->awardAchievement($user, $achievement, $metadata);
    }

    /**
     * Revoke an achievement from a user
     */
    public function revokeAchievement(User $user, Achievement $achievement): bool
    {
        try {
            DB::beginTransaction();

            $userAchievement = $user->userAchievements()
                ->where('achievement_id', $achievement->id)
                ->first();

            if (! $userAchievement) {
                return false;
            }

            // Delete associated celebrations and congratulations
            $userAchievement->celebrations()->each(function ($celebration) {
                $celebration->congratulations()->delete();
                $celebration->delete();
            });

            // Delete the user achievement
            $userAchievement->delete();

            DB::commit();

            Log::info('Achievement revoked', [
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
                'achievement_name' => $achievement->name,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to revoke achievement', [
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
