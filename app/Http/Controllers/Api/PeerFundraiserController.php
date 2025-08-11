<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FundraisingCampaign;
use App\Models\PeerFundraiser;
use App\Services\FundraisingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PeerFundraiserController extends Controller
{
    public function __construct(
        private FundraisingService $fundraisingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = PeerFundraiser::with(['campaign', 'user']);

        // Filter by campaign
        if ($request->has('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $peerFundraisers = $query->orderBy('raised_amount', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($peerFundraisers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_id' => 'required|exists:fundraising_campaigns,id',
            'title' => 'required|string|max:255',
            'personal_message' => 'nullable|string|max:1000',
            'goal_amount' => 'nullable|numeric|min:1',
            'social_links' => 'nullable|array',
            'social_links.*' => 'url',
        ]);

        $campaign = FundraisingCampaign::findOrFail($validated['campaign_id']);

        $peerFundraiser = $this->fundraisingService->createPeerFundraiser(
            $campaign,
            $request->user(),
            $validated
        );

        return response()->json($peerFundraiser->load(['campaign', 'user']), 201);
    }

    public function show(PeerFundraiser $peerFundraiser): JsonResponse
    {
        $peerFundraiser->load([
            'campaign',
            'user',
            'donations' => function ($query) {
                $query->completed()->recent()->with('donor');
            },
        ]);

        return response()->json($peerFundraiser);
    }

    public function update(Request $request, PeerFundraiser $peerFundraiser): JsonResponse
    {
        $this->authorize('update', $peerFundraiser);

        $validated = $request->validate([
            'title' => 'string|max:255',
            'personal_message' => 'nullable|string|max:1000',
            'goal_amount' => 'nullable|numeric|min:1',
            'status' => Rule::in(['active', 'paused', 'completed']),
            'social_links' => 'nullable|array',
            'social_links.*' => 'url',
        ]);

        $peerFundraiser->update($validated);

        return response()->json($peerFundraiser->load(['campaign', 'user']));
    }

    public function destroy(PeerFundraiser $peerFundraiser): JsonResponse
    {
        $this->authorize('delete', $peerFundraiser);

        $peerFundraiser->delete();

        return response()->json(['message' => 'Peer fundraiser deleted successfully']);
    }

    public function campaignPeerFundraisers(FundraisingCampaign $campaign): JsonResponse
    {
        $peerFundraisers = $campaign->peerFundraisers()
            ->with('user')
            ->active()
            ->orderBy('raised_amount', 'desc')
            ->paginate(15);

        return response()->json($peerFundraisers);
    }

    public function userPeerFundraisers(Request $request): JsonResponse
    {
        $peerFundraisers = PeerFundraiser::where('user_id', $request->user()->id)
            ->with('campaign')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($peerFundraisers);
    }

    public function share(PeerFundraiser $peerFundraiser): JsonResponse
    {
        $shareContent = [
            'title' => $peerFundraiser->title,
            'description' => $peerFundraiser->personal_message ?? "Help me support {$peerFundraiser->campaign->title}",
            'url' => $peerFundraiser->share_url,
            'progress' => round($peerFundraiser->progress_percentage),
            'raised' => number_format($peerFundraiser->raised_amount, 2),
            'goal' => $peerFundraiser->goal_amount ? number_format($peerFundraiser->goal_amount, 2) : null,
        ];

        return response()->json($shareContent);
    }
}
