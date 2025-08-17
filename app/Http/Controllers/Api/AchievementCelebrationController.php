<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AchievementCelebration;
use App\Models\UserAchievement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AchievementCelebrationController extends Controller
{
    /**
     * Get recent achievement celebrations
     */
    public function index(Request $request): JsonResponse
    {
        $celebrations = AchievementCelebration::with([
            'userAchievement.user',
            'userAchievement.achievement',
            'congratulations.user',
        ])
            ->public()
            ->latest()
            ->paginate(20);

        return response()->json($celebrations);
    }

    /**
     * Congratulate an achievement
     */
    public function congratulate(Request $request, AchievementCelebration $celebration): JsonResponse
    {
        $request->validate([
            'message' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        try {
            $congratulation = $celebration->addCongratulation(
                $user,
                $request->message
            );

            return response()->json([
                'success' => true,
                'congratulation' => $congratulation->load('user'),
                'congratulations_count' => $celebration->congratulations_count,
            ]);

        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'message' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * Remove congratulation
     */
    public function removeCongratulation(Request $request, AchievementCelebration $celebration): JsonResponse
    {
        $user = $request->user();

        $removed = $celebration->removeCongratulation($user);

        if (! $removed) {
            return response()->json([
                'success' => false,
                'message' => 'Congratulation not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'congratulations_count' => $celebration->congratulations_count,
        ]);
    }

    /**
     * Get congratulations for a celebration
     */
    public function congratulations(AchievementCelebration $celebration): JsonResponse
    {
        $congratulations = $celebration->congratulations()
            ->with('user')
            ->latest()
            ->paginate(20);

        return response()->json($congratulations);
    }

    /**
     * Create a manual celebration
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'user_achievement_id' => 'required|exists:user_achievements,id',
            'message' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        $userAchievement = UserAchievement::where('id', $request->user_achievement_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $celebration = $userAchievement->createCelebration(
            'manual',
            $request->message
        );

        return response()->json([
            'success' => true,
            'celebration' => $celebration->load([
                'userAchievement.achievement',
                'congratulations.user',
            ]),
        ]);
    }
}
