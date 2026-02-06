<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormBuilder;
use App\Models\FormSubmission;
use App\Services\CrmIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormSubmissionController extends Controller
{
    public function __construct(
        private CrmIntegrationService $crmService
    ) {}

    /**
     * Display a listing of form submissions.
     */
    public function index(Request $request, FormBuilder $form): JsonResponse
    {
        $submissions = $form->submissions()
            ->when($request->status, fn($query) => $query->where('status', $request->status))
            ->when($request->crm_sync_status, fn($query) => $query->where('crm_sync_status', $request->crm_sync_status))
            ->when($request->date_from, fn($query) => $query->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($query) => $query->whereDate('created_at', '<=', $request->date_to))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($submissions);
    }

    /**
     * Get form analytics.
     */
    public function analytics(Request $request, FormBuilder $form): JsonResponse
    {
        $dateFrom = $request->date_from ?? now()->subDays(30)->toDateString();
        $dateTo = $request->date_to ?? now()->toDateString();

        $totalSubmissions = $form->submissions()
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->count();

        $successfulSubmissions = $form->submissions()
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->where('status', 'processed')
            ->count();

        $crmSyncedSubmissions = $form->submissions()
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->where('crm_sync_status', 'synced')
            ->count();

        $failedCrmSync = $form->submissions()
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->where('crm_sync_status', 'failed')
            ->count();

        // Daily submission counts
        $dailySubmissions = $form->submissions()
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top referrer sources
        $topReferrers = $form->submissions()
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->whereNotNull('referrer_url')
            ->selectRaw('referrer_url, COUNT(*) as count')
            ->groupBy('referrer_url')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // UTM source analysis
        $utmSources = $form->submissions()
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->whereNotNull('utm_source')
            ->selectRaw('utm_source, utm_medium, utm_campaign, COUNT(*) as count')
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json([
            'summary' => [
                'total_submissions' => $totalSubmissions,
                'successful_submissions' => $successfulSubmissions,
                'success_rate' => $totalSubmissions > 0 ? round(($successfulSubmissions / $totalSubmissions) * 100, 2) : 0,
                'crm_synced' => $crmSyncedSubmissions,
                'crm_sync_rate' => $totalSubmissions > 0 ? round(($crmSyncedSubmissions / $totalSubmissions) * 100, 2) : 0,
                'failed_crm_sync' => $failedCrmSync
            ],
            'daily_submissions' => $dailySubmissions,
            'top_referrers' => $topReferrers,
            'utm_sources' => $utmSources
        ]);
    }

    /**
     * Retry CRM sync for a failed submission.
     */
    public function retryCrmSync(FormBuilder $form, FormSubmission $submission): JsonResponse
    {
        if ($submission->form_id !== $form->id) {
            return response()->json([
                'message' => 'Submission does not belong to this form'
            ], 422);
        }

        if ($submission->crm_sync_status === 'synced') {
            return response()->json([
                'message' => 'Submission is already synced to CRM'
            ], 422);
        }

        $success = $this->crmService->syncFormSubmissionToCrm($submission);

        if ($success) {
            return response()->json([
                'message' => 'CRM sync retry successful',
                'submission' => $submission->fresh()
            ]);
        } else {
            return response()->json([
                'message' => 'CRM sync retry failed',
                'submission' => $submission->fresh()
            ], 422);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // This method is not used as submissions are created via FormBuilderController::submit
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // This method is not used in the current implementation
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // This method is not used in the current implementation
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // This method is not used in the current implementation
    }
}
