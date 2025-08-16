<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    public function __construct(
        private AchievementService $achievementService
    ) {}

    /**
     * Get all available achievements
     */
    public function index(Request $request): JsonResponse
    {
        $achievements = Achievement::active()
            ->when($request->category, fn ($q, $category) => $q->byCategory($category))
            ->when($request->rarity, fn ($q, $rarity) => $q->byRarity($rarity))
            ->orderBy('category')
            ->orderBy('rarity')
            ->orderBy('name')
            ->get();

        return response()->json([
            'achievements' => $achievements,
            'categories' => Achievement::getCategories(),
            'rarities' => Achievement::getRarities(),
        ]);
    }

    /**
     * Get user's achievements
     */
    public function userAchievements(Request $request, ?User $user = null): JsonResponse
    {
        $user = $user ?? $request->user();

        $achievements = $user->userAchievements()
            ->with('achievement')
            ->orderByDesc('earned_at')
            ->get();

        $stats = $this->achievementService->getUserAchievementStats($user);

        return response()->json([
            'achievements' => $achievements,
            'stats' => $stats,
        ]);
    }

    /**
     * Get achievement leaderboard
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $leaderboard = $this->achievementService->getAchievementLeaderboard($limit);

        return response()->json([
            'leaderboard' => $leaderboard,
        ]);
    }

    /**
     * Check and award achievements for current user
     */
    public function checkAchievements(Request $request): JsonResponse
    {
        $user = $request->user();
        $newAchievements = $this->achievementService->checkAndAwardAchievements($user);

        return response()->json([
            'new_achievements' => $newAchievements,
            'count' => count($newAchievements),
        ]);
    }

    /**
     * Toggle featured status of user achievement
     */
    public function toggleFeatured(Request $request, int $userAchievementId): JsonResponse
    {
        $user = $request->user();

        $userAchievement = $user->userAchievements()
            ->findOrFail($userAchievementId);

        $userAchievement->toggleFeatured();

        return response()->json([
            'success' => true,
            'is_featured' => $userAchievement->is_featured,
        ]);
    }

    /**
     * Get achievement details
     */
    public function show(Achievement $achievement): JsonResponse
    {
        $achievement->load('users');

        $stats = [
            'total_earned' => $achievement->users()->count(),
            'recent_earners' => $achievement->userAchievements()
                ->with('user')
                ->latest('earned_at')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'achievement' => $achievement,
            'stats' => $stats,
        ]);
    }
}
