<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ForumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ForumAnalyticsController extends Controller
{
    public function __construct(
        private ForumService $forumService
    ) {}

    /**
     * Get forum statistics and analytics.
     */
    public function getStatistics(): JsonResponse
    {
        $user = Auth::user();

        // Check if user has admin permissions
        if (! $user->hasRole(['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions to view analytics.',
            ], 403);
        }

        $statistics = $this->forumService->getForumStatistics();

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }
}
