<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FundraisingCampaign;
use App\Services\FundraisingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FundraisingCampaignController extends Controller
{
    public function __construct(
        private FundraisingService $fundraisingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = FundraisingCampaign::with(['creator', 'institution'])
            ->withCount(['donations', 'peerFundraisers']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by institution
        if ($request->has('institution_id')) {
            $query->where('institution_id', $request->institution_id);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        $campaigns = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($campaigns);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'story' => 'nullable|string',
            'goal_amount' => 'required|numeric|min:1',
            'currency' => 'string|size:3|in:USD,EUR,GBP',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'type' => 'required|string|in:general,scholarship,emergency,project',
            'media_urls' => 'nullable|array',
            'media_urls.*' => 'url',
            'settings' => 'nullable|array',
            'allow_peer_fundraising' => 'boolean',
            'show_donor_names' => 'boolean',
            'allow_anonymous_donations' => 'boolean',
            'thank_you_message' => 'nullable|string',
            'institution_id' => 'nullable|exists:institutions,id',
        ]);

        $campaign = $this->fundraisingService->createCampaign($validated, $request->user());

        return response()->json($campaign->load(['creator', 'institution']), 201);
    }

    public function show(FundraisingCampaign $campaign): JsonResponse
    {
        $campaign->load([
            'creator',
            'institution',
            'donations' => function ($query) {
                $query->completed()->recent()->limit(10);
            },
            'updates' => function ($query) {
                $query->published()->recent();
            },
            'peerFundraisers' => function ($query) {
                $query->active()->with('user');
            },
        ]);

        $analytics = $this->fundraisingService->getCampaignAnalytics($campaign);

        return response()->json([
            'campaign' => $campaign,
            'analytics' => $analytics,
        ]);
    }

    public function update(Request $request, FundraisingCampaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'string',
            'story' => 'nullable|string',
            'goal_amount' => 'numeric|min:1',
            'currency' => 'string|size:3|in:USD,EUR,GBP',
            'start_date' => 'date',
            'end_date' => 'date|after:start_date',
            'status' => Rule::in(['draft', 'active', 'paused', 'completed', 'cancelled']),
            'type' => 'string|in:general,scholarship,emergency,project',
            'media_urls' => 'nullable|array',
            'media_urls.*' => 'url',
            'settings' => 'nullable|array',
            'allow_peer_fundraising' => 'boolean',
            'show_donor_names' => 'boolean',
            'allow_anonymous_donations' => 'boolean',
            'thank_you_message' => 'nullable|string',
        ]);

        $campaign = $this->fundraisingService->updateCampaign($campaign, $validated);

        return response()->json($campaign->load(['creator', 'institution']));
    }

    public function destroy(FundraisingCampaign $campaign): JsonResponse
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return response()->json(['message' => 'Campaign deleted successfully']);
    }

    public function analytics(FundraisingCampaign $campaign): JsonResponse
    {
        $analytics = $this->fundraisingService->getCampaignAnalytics($campaign);
        $topDonors = $this->fundraisingService->getTopDonors($campaign);
        $recentDonations = $this->fundraisingService->getRecentDonations($campaign);

        return response()->json([
            'analytics' => $analytics,
            'top_donors' => $topDonors,
            'recent_donations' => $recentDonations,
        ]);
    }

    public function share(FundraisingCampaign $campaign): JsonResponse
    {
        $shareContent = $this->fundraisingService->generateSocialShareContent($campaign);

        return response()->json($shareContent);
    }
}
