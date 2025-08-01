<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonorInteraction;
use App\Models\DonorProfile;
use App\Services\DonorCrmService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class DonorInteractionController extends Controller
{
    public function __construct(
        private DonorCrmService $donorCrmService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'donor_profile_id' => ['sometimes', 'exists:donor_profiles,id'],
            'type' => ['sometimes', Rule::in(['call', 'email', 'meeting', 'event', 'letter', 'visit', 'proposal', 'other'])],
            'outcome' => ['sometimes', Rule::in(['positive', 'neutral', 'negative', 'follow_up_needed'])],
            'needs_follow_up' => ['sometimes', 'boolean'],
            'recent_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
        ]);

        $query = DonorInteraction::with(['donorProfile.user', 'user']);

        if ($request->filled('donor_profile_id')) {
            $query->where('donor_profile_id', $request->input('donor_profile_id'));
        }

        if ($request->filled('type')) {
            $query->byType($request->input('type'));
        }

        if ($request->filled('outcome')) {
            $query->byOutcome($request->input('outcome'));
        }

        if ($request->boolean('needs_follow_up')) {
            $query->needsFollowUp();
        }

        if ($request->filled('recent_days')) {
            $query->recent($request->input('recent_days'));
        }

        $interactions = $query->orderBy('interaction_date', 'desc')->paginate(20);

        return response()->json($interactions);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'donor_profile_id' => ['required', 'exists:donor_profiles,id'],
            'type' => ['required', Rule::in(['call', 'email', 'meeting', 'event', 'letter', 'visit', 'proposal', 'other'])],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'participants' => ['nullable', 'array'],
            'outcome' => ['nullable', Rule::in(['positive', 'neutral', 'negative', 'follow_up_needed'])],
            'interaction_date' => ['required', 'date'],
            'duration' => ['nullable', 'date_format:H:i'],
            'attachments' => ['nullable', 'array'],
            'follow_up_actions' => ['nullable', 'array'],
            'next_follow_up_date' => ['nullable', 'date', 'after:interaction_date'],
            'potential_gift_amount' => ['nullable', 'numeric', 'min:0'],
            'private_notes' => ['nullable', 'string'],
        ]);

        $profile = DonorProfile::findOrFail($request->input('donor_profile_id'));
        $interaction = $this->donorCrmService->logInteraction(
            $profile,
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'data' => $interaction->load(['donorProfile.user', 'user']),
            'message' => 'Interaction logged successfully'
        ], 201);
    }

    public function show(DonorInteraction $donorInteraction): JsonResponse
    {
        return response()->json([
            'data' => $donorInteraction->load(['donorProfile.user', 'user'])
        ]);
    }

    public function update(Request $request, DonorInteraction $donorInteraction): JsonResponse
    {
        $request->validate([
            'type' => ['sometimes', Rule::in(['call', 'email', 'meeting', 'event', 'letter', 'visit', 'proposal', 'other'])],
            'subject' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'participants' => ['nullable', 'array'],
            'outcome' => ['nullable', Rule::in(['positive', 'neutral', 'negative', 'follow_up_needed'])],
            'interaction_date' => ['sometimes', 'date'],
            'duration' => ['nullable', 'date_format:H:i'],
            'attachments' => ['nullable', 'array'],
            'follow_up_actions' => ['nullable', 'array'],
            'next_follow_up_date' => ['nullable', 'date'],
            'potential_gift_amount' => ['nullable', 'numeric', 'min:0'],
            'private_notes' => ['nullable', 'string'],
        ]);

        $donorInteraction->update($request->validated());

        // Update profile contact dates if interaction date changed
        if ($request->filled('interaction_date') || $request->filled('next_follow_up_date')) {
            $donorInteraction->donorProfile->update([
                'last_contact_date' => $request->input('interaction_date', $donorInteraction->interaction_date),
                'next_contact_date' => $request->input('next_follow_up_date'),
            ]);
        }

        return response()->json([
            'data' => $donorInteraction->fresh(['donorProfile.user', 'user']),
            'message' => 'Interaction updated successfully'
        ]);
    }

    public function destroy(DonorInteraction $donorInteraction): JsonResponse
    {
        $donorInteraction->delete();

        return response()->json([
            'message' => 'Interaction deleted successfully'
        ]);
    }

    public function followUpReminders(Request $request): JsonResponse
    {
        $interactions = DonorInteraction::needsFollowUp()
            ->with(['donorProfile.user', 'user'])
            ->orderBy('next_follow_up_date')
            ->get();

        return response()->json([
            'data' => $interactions
        ]);
    }
}
