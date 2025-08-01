<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxReceipt;
use App\Services\DonationProcessingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class TaxReceiptController extends Controller
{
    public function __construct(
        private DonationProcessingService $donationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = TaxReceipt::with('donor');

        // Filter by donor
        if ($request->has('donor_id')) {
            $query->where('donor_id', $request->donor_id);
        }

        // Filter by tax year
        if ($request->has('tax_year')) {
            $query->where('tax_year', $request->tax_year);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $taxReceipts = $query->orderBy('tax_year', 'desc')
            ->orderBy('receipt_date', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($taxReceipts);
    }

    public function show(TaxReceipt $taxReceipt): JsonResponse
    {
        $this->authorize('view', $taxReceipt);

        $taxReceipt->load('donor');

        return response()->json([
            'receipt' => $taxReceipt,
            'donations' => $taxReceipt->getDonationRecords(),
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'donor_id' => 'required|exists:users,id',
            'tax_year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
        ]);

        // Check if receipt already exists
        $existingReceipt = TaxReceipt::where('donor_id', $validated['donor_id'])
            ->where('tax_year', $validated['tax_year'])
            ->first();

        if ($existingReceipt) {
            return response()->json([
                'message' => 'Tax receipt already exists for this donor and year',
                'receipt' => $existingReceipt,
            ], 409);
        }

        $receipt = $this->donationService->generateTaxReceipt(
            $validated['donor_id'],
            $validated['tax_year']
        );

        if (!$receipt) {
            return response()->json([
                'message' => 'No eligible donations found for the specified year',
            ], 404);
        }

        return response()->json([
            'message' => 'Tax receipt generated successfully',
            'receipt' => $receipt,
        ], 201);
    }

    public function download(TaxReceipt $taxReceipt): Response
    {
        $this->authorize('view', $taxReceipt);

        if (!$taxReceipt->pdf_path || !Storage::disk('private')->exists($taxReceipt->pdf_path)) {
            abort(404, 'Tax receipt PDF not found');
        }

        $taxReceipt->markAsDownloaded();

        return response()->file(
            Storage::disk('private')->path($taxReceipt->pdf_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="Tax_Receipt_' . $taxReceipt->tax_year . '_' . $taxReceipt->receipt_number . '.pdf"',
            ]
        );
    }

    public function userTaxReceipts(Request $request): JsonResponse
    {
        $taxReceipts = TaxReceipt::where('donor_id', $request->user()->id)
            ->orderBy('tax_year', 'desc')
            ->orderBy('receipt_date', 'desc')
            ->paginate(20);

        return response()->json($taxReceipts);
    }

    public function generateForYear(Request $request): JsonResponse
    {
        $this->authorize('create', TaxReceipt::class);

        $validated = $request->validate([
            'tax_year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'donor_ids' => 'nullable|array',
            'donor_ids.*' => 'exists:users,id',
        ]);

        $taxYear = $validated['tax_year'];
        $donorIds = $validated['donor_ids'] ?? [];

        // If no specific donors provided, generate for all eligible donors
        if (empty($donorIds)) {
            $donorIds = \App\Models\CampaignDonation::completed()
                ->whereYear('processed_at', $taxYear)
                ->whereNotNull('donor_id')
                ->distinct()
                ->pluck('donor_id')
                ->toArray();
        }

        $generated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($donorIds as $donorId) {
            try {
                // Check if receipt already exists
                $existingReceipt = TaxReceipt::where('donor_id', $donorId)
                    ->where('tax_year', $taxYear)
                    ->first();

                if ($existingReceipt) {
                    $skipped++;
                    continue;
                }

                $receipt = $this->donationService->generateTaxReceipt($donorId, $taxYear);
                
                if ($receipt) {
                    $generated++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'donor_id' => $donorId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => "Tax receipt generation completed for {$taxYear}",
            'generated' => $generated,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }

    public function resend(TaxReceipt $taxReceipt): JsonResponse
    {
        $this->authorize('view', $taxReceipt);

        if (!$taxReceipt->donor_email) {
            return response()->json([
                'message' => 'No email address available for this donor',
            ], 400);
        }

        try {
            \App\Jobs\SendTaxReceiptEmailJob::dispatch($taxReceipt);

            return response()->json([
                'message' => 'Tax receipt email queued for sending',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to queue tax receipt email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}