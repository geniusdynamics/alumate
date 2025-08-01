<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CampaignDonation;
use App\Models\FundraisingCampaign;
use App\Services\FundraisingService;
use App\Services\DonationProcessingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class CampaignDonationController extends Controller
{
    public function __construct(
        private FundraisingService $fundraisingService,
        private DonationProcessingService $donationProcessingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = CampaignDonation::with(['campaign', 'donor', 'peerFundraiser.user']);

        // Filter by campaign
        if ($request->has('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by donor
        if ($request->has('donor_id')) {
            $query->where('donor_id', $request->donor_id);
        }

        $donations = $query->orderBy('processed_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($donations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_id' => 'required|exists:fundraising_campaigns,id',
            'amount' => 'required|numeric|min:1',
            'currency' => 'string|size:3|in:USD,EUR,GBP',
            'is_anonymous' => 'boolean',
            'donor_name' => 'nullable|string|max:255',
            'donor_email' => 'nullable|email',
            'message' => 'nullable|string|max:1000',
            'payment_method' => 'required|string|in:stripe,paypal,bank_transfer',
            'peer_fundraiser_id' => 'nullable|exists:peer_fundraisers,id',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|string|in:monthly,quarterly,yearly',
            // Payment gateway specific fields
            'payment_method_id' => 'nullable|string', // For Stripe
            'payer_id' => 'nullable|string', // For PayPal
            'reference' => 'nullable|string', // For bank transfer
            'account_number' => 'nullable|string', // For bank transfer
        ]);

        // Add donor information if user is authenticated
        if ($request->user()) {
            $validated['donor_id'] = $request->user()->id;
        }

        // Set default currency
        $validated['currency'] = $validated['currency'] ?? 'USD';

        try {
            // Extract payment data
            $paymentData = [
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'payer_id' => $validated['payer_id'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
            ];

            // Remove payment data from donation data
            unset($validated['payment_method_id'], $validated['payer_id'], $validated['reference'], $validated['account_number']);

            // Process the donation through the new service
            $donation = $this->donationProcessingService->processDonation($validated, $paymentData);

            return response()->json($donation->load(['campaign', 'donor', 'peerFundraiser']), 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Donation processing failed',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(CampaignDonation $donation): JsonResponse
    {
        $donation->load(['campaign', 'donor', 'peerFundraiser.user']);

        return response()->json($donation);
    }

    public function update(Request $request, CampaignDonation $donation): JsonResponse
    {
        $this->authorize('update', $donation);

        $validated = $request->validate([
            'status' => Rule::in(['pending', 'completed', 'failed', 'refunded']),
            'payment_id' => 'nullable|string',
            'payment_data' => 'nullable|array',
            'processed_at' => 'nullable|date',
        ]);

        $donation->update($validated);

        return response()->json($donation->load(['campaign', 'donor', 'peerFundraiser']));
    }

    public function campaignDonations(FundraisingCampaign $campaign): JsonResponse
    {
        $donations = $campaign->donations()
            ->with(['donor', 'peerFundraiser.user'])
            ->completed()
            ->recent()
            ->paginate(20);

        return response()->json($donations);
    }

    public function userDonations(Request $request): JsonResponse
    {
        $donations = CampaignDonation::where('donor_id', $request->user()->id)
            ->with(['campaign', 'peerFundraiser.user'])
            ->orderBy('processed_at', 'desc')
            ->paginate(20);

        return response()->json($donations);
    }

    public function refund(Request $request, CampaignDonation $donation): JsonResponse
    {
        $this->authorize('refund', $donation);

        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01|max:' . $donation->amount,
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $success = $this->donationProcessingService->refundDonation(
                $donation,
                $validated['amount'] ?? null,
                $validated['reason'] ?? 'Refund requested'
            );

            if ($success) {
                return response()->json([
                    'message' => 'Donation refunded successfully',
                    'donation' => $donation->fresh()->load(['campaign', 'donor', 'peerFundraiser']),
                ]);
            }

            return response()->json([
                'message' => 'Refund processing failed',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Refund processing failed',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
