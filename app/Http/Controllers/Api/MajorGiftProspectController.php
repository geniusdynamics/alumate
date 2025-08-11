<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonorProfile;
use App\Models\MajorGiftProspect;
use App\Models\User;
use App\Services\DonorCrmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MajorGiftProspectController extends Controller
{
    public function __construct(
        private DonorCrmService $donorCrmService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'donor_profile_id' => ['sometimes', 'exists:donor_profiles,id'],
            'stage' => ['sometimes', Rule::in(['identification', 'qualification', 'cultivation', 'solicitation', 'stewardship', 'closed_won', 'closed_lost'])],
            'assigned_officer_id' => ['sometimes', 'exists:users,id'],
            'closing_soon_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'high_probability' => ['sometimes', 'boolean'],
            'active_only' => ['sometimes', 'boolean'],
        ]);

        $query = MajorGiftProspect::with(['donorProfile.user', 'assignedOfficer']);

        if ($request->filled('donor_profile_id')) {
            $query->where('donor_profile_id', $request->input('donor_profile_id'));
        }

        if ($request->filled('stage')) {
            $query->byStage($request->input('stage'));
        }

        if ($request->filled('assigned_officer_id')) {
            $query->byOfficer($request->input('assigned_officer_id'));
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        if ($request->filled('closing_soon_days')) {
            $query->closingSoon($request->input('closing_soon_days'));
        }

        if ($request->boolean('high_probability')) {
            $query->highProbability();
        }

        $prospects = $query->orderBy('expected_close_date')
            ->orderBy('ask_amount', 'desc')
            ->paginate(20);

        return response()->json($prospects);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'donor_profile_id' => ['required', 'exists:donor_profiles,id'],
            'prospect_name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'ask_amount' => ['required', 'numeric', 'min:0'],
            'purpose' => ['required', 'string'],
            'stage' => ['required', Rule::in(['identification', 'qualification', 'cultivation', 'solicitation', 'stewardship'])],
            'probability' => ['numeric', 'between:0,1'],
            'expected_close_date' => ['nullable', 'date', 'after:today'],
            'assigned_officer_id' => ['required', 'exists:users,id'],
            'stakeholders' => ['nullable', 'array'],
            'proposal_details' => ['nullable', 'array'],
            'next_steps' => ['nullable', 'string'],
            'barriers' => ['nullable', 'array'],
            'motivations' => ['nullable', 'array'],
        ]);

        $profile = DonorProfile::findOrFail($request->input('donor_profile_id'));
        $officer = User::findOrFail($request->input('assigned_officer_id'));

        $prospect = $this->donorCrmService->createMajorGiftProspect(
            $profile,
            $officer,
            $request->validated()
        );

        return response()->json([
            'data' => $prospect->load(['donorProfile.user', 'assignedOfficer']),
            'message' => 'Major gift prospect created successfully',
        ], 201);
    }

    public function show(MajorGiftProspect $majorGiftProspect): JsonResponse
    {
        return response()->json([
            'data' => $majorGiftProspect->load(['donorProfile.user', 'assignedOfficer']),
        ]);
    }

    public function update(Request $request, MajorGiftProspect $majorGiftProspect): JsonResponse
    {
        $request->validate([
            'prospect_name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'ask_amount' => ['sometimes', 'numeric', 'min:0'],
            'purpose' => ['sometimes', 'string'],
            'stage' => ['sometimes', Rule::in(['identification', 'qualification', 'cultivation', 'solicitation', 'stewardship', 'closed_won', 'closed_lost'])],
            'probability' => ['sometimes', 'numeric', 'between:0,1'],
            'expected_close_date' => ['nullable', 'date'],
            'assigned_officer_id' => ['sometimes', 'exists:users,id'],
            'stakeholders' => ['nullable', 'array'],
            'proposal_details' => ['nullable', 'array'],
            'next_steps' => ['nullable', 'string'],
            'barriers' => ['nullable', 'array'],
            'motivations' => ['nullable', 'array'],
        ]);

        $majorGiftProspect->update(array_merge($request->validated(), [
            'last_activity_date' => now(),
        ]));

        return response()->json([
            'data' => $majorGiftProspect->fresh(['donorProfile.user', 'assignedOfficer']),
            'message' => 'Major gift prospect updated successfully',
        ]);
    }

    public function destroy(MajorGiftProspect $majorGiftProspect): JsonResponse
    {
        $majorGiftProspect->delete();

        return response()->json([
            'message' => 'Major gift prospect deleted successfully',
        ]);
    }

    public function moveToNextStage(MajorGiftProspect $majorGiftProspect): JsonResponse
    {
        $majorGiftProspect->moveToNextStage();

        return response()->json([
            'data' => $majorGiftProspect->fresh(),
            'message' => 'Prospect moved to next stage successfully',
        ]);
    }

    public function closeAsWon(Request $request, MajorGiftProspect $majorGiftProspect): JsonResponse
    {
        $request->validate([
            'actual_amount' => ['nullable', 'numeric', 'min:0'],
            'close_notes' => ['nullable', 'string'],
        ]);

        $majorGiftProspect->closeAsWon(
            $request->input('actual_amount'),
            $request->input('close_notes')
        );

        return response()->json([
            'data' => $majorGiftProspect->fresh(),
            'message' => 'Prospect closed as won successfully',
        ]);
    }

    public function closeAsLost(Request $request, MajorGiftProspect $majorGiftProspect): JsonResponse
    {
        $request->validate([
            'close_notes' => ['nullable', 'string'],
        ]);

        $majorGiftProspect->closeAsLost($request->input('close_notes'));

        return response()->json([
            'data' => $majorGiftProspect->fresh(),
            'message' => 'Prospect closed as lost',
        ]);
    }

    public function pipeline(Request $request): JsonResponse
    {
        $officer = $request->user();
        $pipeline = $this->donorCrmService->getProspectPipeline($officer);

        $summary = [
            'total_prospects' => $pipeline->flatten()->count(),
            'total_value' => $pipeline->flatten()->sum('ask_amount'),
            'weighted_value' => $pipeline->flatten()->sum('weighted_value'),
            'by_stage' => $pipeline->map(function ($prospects, $stage) {
                return [
                    'count' => $prospects->count(),
                    'value' => $prospects->sum('ask_amount'),
                    'weighted_value' => $prospects->sum('weighted_value'),
                ];
            }),
        ];

        return response()->json([
            'data' => $pipeline,
            'summary' => $summary,
        ]);
    }

    public function closingSoon(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);

        $prospects = MajorGiftProspect::closingSoon($days)
            ->with(['donorProfile.user', 'assignedOfficer'])
            ->orderBy('expected_close_date')
            ->get();

        return response()->json([
            'data' => $prospects,
        ]);
    }
}
