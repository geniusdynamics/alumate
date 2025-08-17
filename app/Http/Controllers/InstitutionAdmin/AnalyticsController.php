<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Get graduate outcome analytics data.
     */
    public function getGraduateOutcomes(Request $request): JsonResponse
    {
        // For simplicity, we fetch the latest snapshot.
        // A more advanced implementation could allow filtering by date.
        $snapshot = AnalyticsSnapshot::where('type', 'graduate_outcomes')
            ->latest('date')
            ->first();

        if (!$snapshot) {
            // Optionally, generate one on the fly if none exists
            // Or return an empty state
            return response()->json(['message' => 'No analytics data available yet.'], 404);
        }

        return response()->json($snapshot->data);
    }

    /**
     * Get course ROI analytics data.
     */
    public function getCourseRoi(Request $request): JsonResponse
    {
        $snapshot = AnalyticsSnapshot::where('type', 'course_roi')
            ->latest('date')
            ->first();

        if (!$snapshot) {
            return response()->json(['message' => 'No course ROI data available yet.'], 404);
        }

        return response()->json($snapshot->data);
    }
}
