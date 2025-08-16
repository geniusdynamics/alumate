<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CareerMilestone;
use App\Models\CareerTimeline;
use App\Models\User;
use App\Services\CareerTimelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CareerTimelineController extends Controller
{
    public function __construct(
        private CareerTimelineService $careerTimelineService
    ) {}

    /**
     * Get career timeline for a user
     */
    public function index(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $viewer = $request->user();

        $timeline = $this->careerTimelineService->getTimelineForUser($user, $viewer);

        return response()->json([
            'success' => true,
            'data' => $timeline,
        ]);
    }

    /**
     * Store a new career entry
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'start_date' => 'required|date|before_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'description' => 'nullable|string|max:2000',
            'is_current' => 'boolean',
            'achievements' => 'nullable|array',
            'achievements.*' => 'string|max:500',
            'location' => 'nullable|string|max:255',
            'company_logo_url' => 'nullable|url|max:500',
            'industry' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|in:full-time,part-time,contract,internship,freelance',
        ]);

        try {
            $careerEntry = $this->careerTimelineService->addCareerEntry($validated, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Career entry added successfully',
                'data' => $careerEntry->load('user'),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update a career entry
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'company' => 'sometimes|required|string|max:255',
            'title' => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date|before_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'description' => 'nullable|string|max:2000',
            'is_current' => 'boolean',
            'achievements' => 'nullable|array',
            'achievements.*' => 'string|max:500',
            'location' => 'nullable|string|max:255',
            'company_logo_url' => 'nullable|url|max:500',
            'industry' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|in:full-time,part-time,contract,internship,freelance',
        ]);

        try {
            $careerEntry = $this->careerTimelineService->updateCareerEntry($id, $validated, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Career entry updated successfully',
                'data' => $careerEntry->load('user'),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a career entry
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $careerEntry = CareerTimeline::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $careerEntry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Career entry deleted successfully',
        ]);
    }

    /**
     * Add a career milestone
     */
    public function addMilestone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(CareerMilestone::getTypes()))],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'date' => 'required|date|before_or_equal:today',
            'visibility' => ['required', Rule::in(array_keys(CareerMilestone::getVisibilityOptions()))],
            'company' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
            'is_featured' => 'boolean',
        ]);

        $milestone = $this->careerTimelineService->addMilestone($validated, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Milestone added successfully',
            'data' => $milestone->load('user'),
        ], 201);
    }

    /**
     * Update a milestone
     */
    public function updateMilestone(Request $request, int $id): JsonResponse
    {
        $milestone = CareerMilestone::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'type' => ['sometimes', 'required', Rule::in(array_keys(CareerMilestone::getTypes()))],
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'date' => 'sometimes|required|date|before_or_equal:today',
            'visibility' => ['sometimes', 'required', Rule::in(array_keys(CareerMilestone::getVisibilityOptions()))],
            'company' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
            'is_featured' => 'boolean',
        ]);

        $milestone->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Milestone updated successfully',
            'data' => $milestone->fresh()->load('user'),
        ]);
    }

    /**
     * Delete a milestone
     */
    public function destroyMilestone(Request $request, int $id): JsonResponse
    {
        $milestone = CareerMilestone::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $milestone->delete();

        return response()->json([
            'success' => true,
            'message' => 'Milestone deleted successfully',
        ]);
    }

    /**
     * Get career suggestions for the authenticated user
     */
    public function suggestions(Request $request): JsonResponse
    {
        $suggestions = $this->careerTimelineService->suggestCareerGoals($request->user());

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }

    /**
     * Get milestone types and visibility options
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'milestone_types' => CareerMilestone::getTypes(),
                'visibility_options' => CareerMilestone::getVisibilityOptions(),
                'employment_types' => [
                    'full-time' => 'Full-time',
                    'part-time' => 'Part-time',
                    'contract' => 'Contract',
                    'internship' => 'Internship',
                    'freelance' => 'Freelance',
                ],
            ],
        ]);
    }
}
