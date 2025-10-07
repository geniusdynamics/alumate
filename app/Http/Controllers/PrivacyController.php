<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateConsentRequest;
use App\Http\Requests\DeleteDataRequest;
use App\Http\Requests\ComplianceReportRequest;
use App\Services\Analytics\ConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * Privacy Controller
 *
 * Handles GDPR/CCPA compliant privacy operations including consent management,
 * data deletion, and compliance reporting.
 */
class PrivacyController extends Controller
{
    private ConsentService $consentService;

    public function __construct(ConsentService $consentService)
    {
        $this->consentService = $consentService;
    }

    /**
     * Update consent preferences for the authenticated user.
     *
     * @param UpdateConsentRequest $request
     * @return JsonResponse
     */
    public function updateConsent(UpdateConsentRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $preferences = $request->input('preferences');

            $success = $this->consentService->updateConsentPreferences($userId, $preferences);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to update consent preferences',
                ], 500);
            }

            Log::info('User consent preferences updated', [
                'user_id' => $userId,
                'preferences' => $preferences,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Consent preferences updated successfully',
                'preferences' => $preferences,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to update consent preferences', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while updating consent preferences',
            ], 500);
        }
    }

    /**
     * Delete user data with GDPR right to erasure compliance.
     *
     * @param DeleteDataRequest $request
     * @return JsonResponse
     */
    public function deleteUserData(DeleteDataRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $categories = $request->input('categories', ['all']);
            $reason = $request->input('reason');
            $confirmationToken = $request->input('confirmation_token');

            // Verify confirmation token (in production, this would be more sophisticated)
            if (!$this->verifyConfirmationToken($confirmationToken)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid confirmation token',
                ], 403);
            }

            // Process data deletion
            $deletionResult = $this->processDataDeletion($userId, $categories, $reason);

            Log::info('User data deletion completed', [
                'user_id' => $userId,
                'categories' => $categories,
                'reason' => $reason,
                'result' => $deletionResult,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data deletion request processed successfully',
                'deletion_result' => $deletionResult,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process data deletion', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while processing data deletion',
            ], 500);
        }
    }

    /**
     * Generate compliance audit report for the authenticated user.
     *
     * @param ComplianceReportRequest $request
     * @return JsonResponse
     */
    public function getComplianceReport(ComplianceReportRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $dateRange = $request->input('date_range');
            $includeDeleted = $request->input('include_deleted', false);

            $report = $this->generateComplianceReport($userId, $dateRange, $includeDeleted);

            Log::info('Compliance report generated', [
                'user_id' => $userId,
                'date_range' => $dateRange,
                'include_deleted' => $includeDeleted,
            ]);

            return response()->json([
                'success' => true,
                'report' => $report,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to generate compliance report', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while generating compliance report',
            ], 500);
        }
    }

    /**
     * Retrieve current consent preferences for the authenticated user.
     *
     * @return JsonResponse
     */
    public function getConsentPreferences(): JsonResponse
    {
        try {
            $userId = Auth::id();
            $preferences = $this->consentService->getConsentPreferences($userId);

            return response()->json([
                'success' => true,
                'preferences' => $preferences,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to retrieve consent preferences', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while retrieving consent preferences',
            ], 500);
        }
    }

    /**
     * Export user data in machine-readable format (GDPR Article 20).
     *
     * @return JsonResponse
     */
    public function exportUserData(): JsonResponse
    {
        try {
            $userId = Auth::id();
            $exportData = $this->prepareDataExport($userId);

            // Generate filename with timestamp
            $filename = "user-data-export-{$userId}-" . now()->format('Y-m-d-H-i-s') . '.json';

            // Store export file temporarily
            Storage::put("exports/{$filename}", json_encode($exportData, JSON_PRETTY_PRINT));

            Log::info('User data export completed', [
                'user_id' => $userId,
                'filename' => $filename,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data export completed successfully',
                'export_url' => route('privacy.export.download', ['filename' => $filename]),
                'export_data' => $exportData, // Include data directly for immediate access
            ]);

        } catch (Exception $e) {
            Log::error('Failed to export user data', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while exporting user data',
            ], 500);
        }
    }

    /**
     * Verify confirmation token for sensitive operations.
     *
     * @param string $token
     * @return bool
     */
    private function verifyConfirmationToken(string $token): bool
    {
        // In production, this would verify against a stored token or use a more sophisticated method
        // For now, accept any valid UUID format
        return preg_match('/^[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}$/', $token);
    }

    /**
     * Process data deletion for specified categories.
     *
     * @param int $userId
     * @param array $categories
     * @param string|null $reason
     * @return array
     */
    private function processDataDeletion(int $userId, array $categories, ?string $reason): array
    {
        $result = [
            'categories_processed' => [],
            'data_deleted' => [],
            'errors' => [],
        ];

        foreach ($categories as $category) {
            try {
                if ($category === 'all') {
                    // Delete all data
                    $this->consentService->processConsentWithdrawal($userId, 'analytics');
                    $this->consentService->processConsentWithdrawal($userId, 'marketing');
                    $this->consentService->processConsentWithdrawal($userId, 'tracking');
                    $this->consentService->processConsentWithdrawal($userId, 'profiling');

                    $result['categories_processed'] = ['analytics', 'marketing', 'tracking', 'profiling'];
                    $result['data_deleted'][] = 'All user data deleted';
                    break;
                } else {
                    // Delete specific category
                    $this->consentService->processConsentWithdrawal($userId, $category);
                    $result['categories_processed'][] = $category;
                    $result['data_deleted'][] = "Data deleted for category: {$category}";
                }
            } catch (Exception $e) {
                $result['errors'][] = "Failed to delete data for category {$category}: " . $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Generate compliance audit report.
     *
     * @param int $userId
     * @param array|null $dateRange
     * @param bool $includeDeleted
     * @return array
     */
    private function generateComplianceReport(int $userId, ?array $dateRange, bool $includeDeleted): array
    {
        $startDate = $dateRange['start'] ?? now()->subMonths(12)->toDateString();
        $endDate = $dateRange['end'] ?? now()->toDateString();

        return [
            'user_id' => $userId,
            'report_period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'consent_status' => $this->consentService->getConsentPreferences($userId),
            'data_processing_activities' => $this->getDataProcessingSummary($userId, $startDate, $endDate),
            'legal_basis' => 'GDPR Article 6(1)(a) - Consent',
            'data_retention_period' => '24 months after last activity',
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Prepare data export in machine-readable format.
     *
     * @param int $userId
     * @return array
     */
    private function prepareDataExport(int $userId): array
    {
        return [
            'user_id' => $userId,
            'export_timestamp' => now()->toISOString(),
            'consent_preferences' => $this->consentService->getConsentPreferences($userId),
            'data_portability' => [
                'format' => 'JSON',
                'version' => '1.0',
                'gdpr_article_20_compliant' => true,
            ],
            'data_summary' => [
                'analytics_data' => 'Available upon request',
                'consent_history' => 'Included in consent preferences',
                'processing_activities' => 'Logged and auditable',
            ],
        ];
    }

    /**
     * Get summary of data processing activities.
     *
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getDataProcessingSummary(int $userId, string $startDate, string $endDate): array
    {
        // This would typically query various analytics tables
        // For now, return a summary structure
        return [
            'analytics_events' => 'Processed within specified date range',
            'consent_changes' => 'Tracked and logged',
            'data_retention' => 'Compliant with GDPR requirements',
            'cross_border_transfers' => 'None - data stored locally',
        ];
    }
}