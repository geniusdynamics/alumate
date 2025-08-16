<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonorProfile;
use App\Models\DonorStewardshipPlan;
use App\Services\DonorCrmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DonorStewardshipPlanController extends Controller
{
    public function __construct(
        private DonorCrmService $donorCrmService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'donor_profile_id' => ['sometimes', 'exists:donor_profiles,id'],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'completed', 'paused'])],
            'priority' => ['sometimes', 'integer', 'between:1,3'],
            'assigned_to' => ['sometimes', 'exists:users,id'],
            'upcoming_ask_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
        ]);

        $query = DonorStewardshipPlan::with(['donorProfile.user', 'creator', 'assignedTo']);

        if ($request->filled('donor_profile_id')) {
            $query->where('donor_profile_id', $request->input('donor_profile_id'));
        }

        if ($request->filled('status')) {
            $query->byStatus($request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->byPriority($request->input('priority'));
        }

        if ($request->filled('assigned_to')) {
            $query->byAssignee($request->input('assigned_to'));
        }

        if ($request->filled('upcoming_ask_days')) {
            $query->upcomingAsk($request->input('upcoming_ask_days'));
        }

        $plans = $query->orderBy('priority')
            ->orderBy('target_ask_date')
            ->paginate(20);

        return response()->json($plans);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'donor_profile_id' => ['required', 'exists:donor_profiles,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'goals' => ['nullable', 'array'],
            'strategies' => ['nullable', 'array'],
            'milestones' => ['nullable', 'array'],
            'target_gift_amount' => ['nullable', 'numeric', 'min:0'],
            'target_gift_purpose' => ['nullable', 'string'],
            'target_ask_date' => ['nullable', 'date', 'after:start_date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['integer', 'between:1,3'],
            'notes' => ['nullable', 'string'],
        ]);

        $profile = DonorProfile::findOrFail($request->input('donor_profile_id'));
        $plan = $this->donorCrmService->createStewardshipPlan(
            $profile,
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'data' => $plan->load(['donorProfile.user', 'creator', 'assignedTo']),
            'message' => 'Stewardship plan created successfully',
        ], 201);
    }

    public function show(DonorStewardshipPlan $donorStewardshipPlan): JsonResponse
    {
        return response()->json([
            'data' => $donorStewardshipPlan->load(['donorProfile.user', 'creator', 'assignedTo']),
        ]);
    }

    public function update(Request $request, DonorStewardshipPlan $donorStewardshipPlan): JsonResponse
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'completed', 'paused'])],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'goals' => ['nullable', 'array'],
            'strategies' => ['nullable', 'array'],
            'milestones' => ['nullable', 'array'],
            'target_gift_amount' => ['nullable', 'numeric', 'min:0'],
            'target_gift_purpose' => ['nullable', 'string'],
            'target_ask_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['sometimes', 'integer', 'between:1,3'],
            'notes' => ['nullable', 'string'],
        ]);

        $donorStewardshipPlan->update($request->validated());

        return response()->json([
            'data' => $donorStewardshipPlan->fresh(['donorProfile.user', 'creator', 'assignedTo']),
            'message' => 'Stewardship plan updated successfully',
        ]);
    }

    public function destroy(DonorStewardshipPlan $donorStewardshipPlan): JsonResponse
    {
        $donorStewardshipPlan->delete();

        return response()->json([
            'message' => 'Stewardship plan deleted successfully',
        ]);
    }

    public function markMilestoneComplete(Request $request, DonorStewardshipPlan $donorStewardshipPlan): JsonResponse
    {
        $request->validate([
            'milestone_index' => ['required', 'integer', 'min:0'],
        ]);

        $donorStewardshipPlan->markMilestoneComplete($request->input('milestone_index'));

        return response()->json([
            'data' => $donorStewardshipPlan->fresh(),
            'message' => 'Milestone marked as complete',
        ]);
    }

    public function upcomingAsks(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);

        $plans = DonorStewardshipPlan::upcomingAsk($days)
            ->with(['donorProfile.user', 'assignedTo'])
            ->orderBy('target_ask_date')
            ->get();

        return response()->json([
            'data' => $plans,
        ]);
    }
}
