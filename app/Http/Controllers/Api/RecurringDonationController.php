<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecurringDonation;
use App\Services\DonationProcessingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RecurringDonationController extends Controller
{
    public function __construct(
        private DonationProcessingService $donationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = RecurringDonation::with(['campaign', 'donor', 'originalDonation']);

        // Filter by donor
        if ($request->has('donor_id')) {
            $query->where('donor_id', $request->donor_id);
        }

        // Filter by campaign
        if ($request->has('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by frequency
        if ($request->has('frequency')) {
            $query->where('frequency', $request->frequency);
        }

        $recurringDonations = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($recurringDonations);
    }

    public function show(RecurringDonation $recurringDonation): JsonResponse
    {
        $recurringDonation->load([
            'campaign',
            'donor',
            'originalDonation',
            'donations' => function ($query) {
                $query->orderBy('processed_at', 'desc');
            }
        ]);

        return response()->json($recurringDonation);
    }

    public function update(Request $request, RecurringDonation $recurringDonation): JsonResponse
    {
        $this->authorize('update', $recurringDonation);

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:1',
            'frequency' => 'sometimes|string|in:monthly,quarterly,yearly',
            'status' => 'sometimes|string|in:active,paused,cancelled',
        ]);

        $recurringDonation->update($validated);

        // Update next payment date if frequency changed
        if (isset($validated['frequency'])) {
            $recurringDonation->updateNextPaymentDate();
        }

        return response()->json($recurringDonation->load(['campaign', 'donor']));
    }

    public function cancel(Request $request, RecurringDonation $recurringDonation): JsonResponse
    {
        $this->authorize('update', $recurringDonation);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $success = $this->donationService->cancelRecurringDonation(
            $recurringDonation,
            $validated['reason'] ?? 'Cancelled by donor'
        );

        if ($success) {
            return response()->json([
                'message' => 'Recurring donation cancelled successfully',
                'recurring_donation' => $recurringDonation->fresh(),
            ]);
        }

        return response()->json([
            'message' => 'Failed to cancel recurring donation',
        ], 500);
    }

    public function pause(RecurringDonation $recurringDonation): JsonResponse
    {
        $this->authorize('update', $recurringDonation);

        $recurringDonation->pause();

        return response()->json([
            'message' => 'Recurring donation paused successfully',
            'recurring_donation' => $recurringDonation,
        ]);
    }

    public function resume(RecurringDonation $recurringDonation): JsonResponse
    {
        $this->authorize('update', $recurringDonation);

        $recurringDonation->resume();

        return response()->json([
            'message' => 'Recurring donation resumed successfully',
            'recurring_donation' => $recurringDonation,
        ]);
    }

    public function userRecurringDonations(Request $request): JsonResponse
    {
        $recurringDonations = RecurringDonation::where('donor_id', $request->user()->id)
            ->with(['campaign', 'originalDonation'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($recurringDonations);
    }

    public function dueForPayment(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RecurringDonation::class);

        $recurringDonations = RecurringDonation::dueForPayment()
            ->with(['campaign', 'donor'])
            ->paginate($request->get('per_page', 50));

        return response()->json($recurringDonations);
    }
}