<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonorProfile;
use App\Models\User;
use App\Services\DonorCrmService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class DonorProfileController extends Controller
{
    public function __construct(
        private DonorCrmService $donorCrmService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'tier' => ['sometimes', Rule::in(['prospect', 'major', 'principal', 'legacy'])],
            'officer_id' => ['sometimes', 'exists:users,id'],
            'capacity_min' => ['sometimes', 'numeric', 'min:0'],
            'capacity_max' => ['sometimes', 'numeric', 'min:0'],
            'giving_interests' => ['sometimes', 'string'],
            'needs_contact' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'string', 'max:255'],
        ]);

        $filters = $request->only([
            'tier', 'officer_id', 'capacity_min', 'capacity_max', 
            'giving_interests', 'needs_contact'
        ]);

        $profiles = $this->donorCrmService->searchDonors($filters);

        // Apply text search if provided
        if ($request->filled('search')) {
            $search = $request->input('search');
            $profiles = $profiles->filter(function ($profile) use ($search) {
                return str_contains(strtolower($profile->user->name), strtolower($search)) ||
                       str_contains(strtolower($profile->notes ?? ''), strtolower($search));
            });
        }

        return response()->json([
            'data' => $profiles->values(),
            'meta' => [
                'total' => $profiles->count(),
                'filters_applied' => array_filter($filters),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'donor_tier' => ['sometimes', Rule::in(['prospect', 'major', 'principal', 'legacy'])],
            'capacity_rating' => ['nullable', 'numeric', 'min:0'],
            'inclination_score' => ['nullable', 'numeric', 'between:0,1'],
            'giving_interests' => ['nullable', 'array'],
            'preferred_contact_methods' => ['nullable', 'array'],
            'preferred_contact_frequency' => ['nullable', 'string'],
            'assigned_officer_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'wealth_indicators' => ['nullable', 'array'],
            'relationship_connections' => ['nullable', 'array'],
            'is_anonymous' => ['boolean'],
            'do_not_contact' => ['boolean'],
        ]);

        $user = User::findOrFail($request->input('user_id'));
        $profile = $this->donorCrmService->createDonorProfile($user, $request->validated());

        return response()->json([
            'data' => $profile->load(['user', 'assignedOfficer']),
            'message' => 'Donor profile created successfully'
        ], 201);
    }

    public function show(DonorProfile $donorProfile): JsonResponse
    {
        $profile = $donorProfile->load([
            'user',
            'assignedOfficer',
            'interactions' => function($query) {
                $query->latest()->limit(10);
            },
            'stewardshipPlans' => function($query) {
                $query->active();
            },
            'majorGiftProspects' => function($query) {
                $query->active();
            }
        ]);

        $insights = $this->donorCrmService->generateDonorInsights($profile);

        return response()->json([
            'data' => $profile,
            'insights' => $insights
        ]);
    }

    public function update(Request $request, DonorProfile $donorProfile): JsonResponse
    {
        $request->validate([
            'donor_tier' => ['sometimes', Rule::in(['prospect', 'major', 'principal', 'legacy'])],
            'capacity_rating' => ['nullable', 'numeric', 'min:0'],
            'inclination_score' => ['nullable', 'numeric', 'between:0,1'],
            'giving_interests' => ['nullable', 'array'],
            'preferred_contact_methods' => ['nullable', 'array'],
            'preferred_contact_frequency' => ['nullable', 'string'],
            'next_contact_date' => ['nullable', 'date', 'after:today'],
            'assigned_officer_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'wealth_indicators' => ['nullable', 'array'],
            'relationship_connections' => ['nullable', 'array'],
            'is_anonymous' => ['boolean'],
            'do_not_contact' => ['boolean'],
        ]);

        $profile = $this->donorCrmService->updateDonorProfile($donorProfile, $request->validated());

        return response()->json([
            'data' => $profile->load(['user', 'assignedOfficer']),
            'message' => 'Donor profile updated successfully'
        ]);
    }

    public function destroy(DonorProfile $donorProfile): JsonResponse
    {
        $donorProfile->delete();

        return response()->json([
            'message' => 'Donor profile deleted successfully'
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $officer = $request->user();
        $dashboard = $this->donorCrmService->getDonorDashboard($officer);

        return response()->json([
            'data' => $dashboard
        ]);
    }

    public function contactsNeedingAttention(Request $request): JsonResponse
    {
        $officer = $request->user();
        $contacts = $this->donorCrmService->getContactsNeedingAttention($officer);

        return response()->json([
            'data' => $contacts
        ]);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'profile_ids' => ['required', 'array'],
            'profile_ids.*' => ['exists:donor_profiles,id'],
            'updates' => ['required', 'array'],
            'updates.assigned_officer_id' => ['nullable', 'exists:users,id'],
            'updates.donor_tier' => ['sometimes', Rule::in(['prospect', 'major', 'principal', 'legacy'])],
            'updates.do_not_contact' => ['sometimes', 'boolean'],
        ]);

        $updated = $this->donorCrmService->bulkUpdateProfiles(
            $request->input('profile_ids'),
            $request->input('updates')
        );

        return response()->json([
            'message' => "Updated {$updated} donor profiles successfully"
        ]);
    }
}
