<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\GoogleAnalyticsService;
use App\Services\Analytics\MatomoService;
use App\Services\Analytics\AnalyticsDataSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * External Integration Controller for managing Google Analytics and Matomo integrations
 */
class ExternalIntegrationController extends Controller
{
    private GoogleAnalyticsService $googleAnalyticsService;
    private MatomoService $matomoService;
    private AnalyticsDataSyncService $dataSyncService;

    public function __construct(
        GoogleAnalyticsService $googleAnalyticsService,
        MatomoService $matomoService,
        AnalyticsDataSyncService $dataSyncService
    ) {
        $this->googleAnalyticsService = $googleAnalyticsService;
        $this->matomoService = $matomoService;
        $this->dataSyncService = $dataSyncService;
    }

    /**
     * Get unified analytics data from all sources
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUnifiedData(Request $request): JsonResponse
    {
        $params = [
            'start_date' => $request->input('start_date', '30daysAgo'),
            'end_date' => $request->input('end_date', 'today'),
            'metrics' => $request->input('metrics', ['sessions', 'users', 'pageviews']),
            'dimensions' => $request->input('dimensions', []),
        ];

        $data = $this->dataSyncService->getUnifiedData($params);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Sync events to external platforms
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function syncEvents(Request $request): JsonResponse
    {
        $request->validate([
            'events' => 'required|array',
            'events.*.name' => 'required|string',
            'events.*.category' => 'nullable|string',
            'events.*.action' => 'nullable|string',
            'events.*.value' => 'nullable|numeric',
            'sync_to_google' => 'nullable|boolean',
            'sync_to_matomo' => 'nullable|boolean',
        ]);

        $events = $request->input('events');
        $options = [
            'sync_to_google' => $request->input('sync_to_google', true),
            'sync_to_matomo' => $request->input('sync_to_matomo', true),
            'tenant_id' => $request->input('tenant_id'),
        ];

        $results = $this->dataSyncService->syncToExternal($events, $options);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Get sync status
     *
     * @return JsonResponse
     */
    public function getSyncStatus(): JsonResponse
    {
        $status = $this->dataSyncService->getSyncStatus();

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Get discrepancies between data sources
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDiscrepancies(Request $request): JsonResponse
    {
        $params = [
            'start_date' => $request->input('start_date', '30daysAgo'),
            'end_date' => $request->input('end_date', 'today'),
            'metrics' => $request->input('metrics', ['sessions', 'users', 'pageviews']),
            'dimensions' => $request->input('dimensions', []),
        ];

        $unifiedData = $this->dataSyncService->getUnifiedData($params);
        $discrepancies = $unifiedData['discrepancies'];

        return response()->json([
            'success' => true,
            'data' => [
                'discrepancies' => $discrepancies,
                'summary' => $unifiedData['summary'],
            ],
        ]);
    }

    /**
     * Resolve discrepancies
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resolveDiscrepancies(Request $request): JsonResponse
    {
        $request->validate([
            'discrepancies' => 'required|array',
            'strategy' => 'nullable|in:average,max,min,internal',
        ]);

        $discrepancies = $request->input('discrepancies');
        $options = [
            'strategy' => $request->input('strategy', 'average'),
        ];

        $results = $this->dataSyncService->resolveDiscrepancies($discrepancies, $options);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Validate integration configuration
     *
     * @return JsonResponse
     */
    public function validateConfiguration(): JsonResponse
    {
        $validation = $this->dataSyncService->validateConfiguration();

        return response()->json([
            'success' => true,
            'data' => $validation,
        ]);
    }

    /**
     * Create Google Analytics goal
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createGoogleAnalyticsGoal(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'event_name' => 'required|string',
            'value' => 'nullable|numeric',
        ]);

        $funnelData = [
            'name' => $request->input('name'),
            'event_name' => $request->input('event_name'),
            'value' => $request->input('value'),
        ];

        $result = $this->googleAnalyticsService->createGoal($funnelData);

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Google Analytics goal',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Create Google Analytics audience
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createGoogleAnalyticsAudience(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'criteria' => 'required|array',
        ]);

        $segmentData = [
            'name' => $request->input('name'),
            'description' => $request->input('description', ''),
            'criteria' => $request->input('criteria'),
        ];

        $result = $this->googleAnalyticsService->createAudience($segmentData);

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Google Analytics audience',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Create Matomo goal
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createMatomoGoal(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'event_category' => 'required|string',
            'event_action' => 'required|string',
            'value' => 'nullable|numeric',
        ]);

        $funnelData = [
            'name' => $request->input('name'),
            'event_category' => $request->input('event_category'),
            'event_action' => $request->input('event_action'),
            'value' => $request->input('value'),
        ];

        $result = $this->matomoService->createGoal($funnelData);

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Matomo goal',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Create Matomo segment
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createMatomoSegment(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'criteria' => 'required|array',
            'enabled_all_users' => 'nullable|boolean',
        ]);

        $segmentData = [
            'name' => $request->input('name'),
            'criteria' => $request->input('criteria'),
            'enabled_all_users' => $request->input('enabled_all_users', false),
        ];

        $result = $this->matomoService->createSegment($segmentData);

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Matomo segment',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get Google Analytics report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getGoogleAnalyticsReport(Request $request): JsonResponse
    {
        $reportRequest = [
            'date_ranges' => $request->input('date_ranges', [
                ['startDate' => '30daysAgo', 'endDate' => 'today'],
            ]),
            'metrics' => $request->input('metrics', []),
            'dimensions' => $request->input('dimensions', []),
            'dimension_filter' => $request->input('dimension_filter'),
            'metric_filter' => $request->input('metric_filter'),
        ];

        $report = $this->googleAnalyticsService->getReport($reportRequest);

        if ($report === null) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Google Analytics report',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get Google Analytics real-time data
     *
     * @return JsonResponse
     */
    public function getGoogleAnalyticsRealtimeData(): JsonResponse
    {
        $data = $this->googleAnalyticsService->getRealtimeData();

        if ($data === null) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Google Analytics real-time data',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get Matomo report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMatomoReport(Request $request): JsonResponse
    {
        $method = $request->input('method', 'API.get');
        $params = $request->input('params', []);

        $report = $this->matomoService->getReport($method, $params);

        if ($report === null) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Matomo report',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get Matomo real-time data
     *
     * @return JsonResponse
     */
    public function getMatomoRealtimeData(): JsonResponse
    {
        $data = $this->matomoService->getRealtimeData();

        if ($data === null) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Matomo real-time data',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Sync goals to Google Analytics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function syncGoogleAnalyticsGoals(Request $request): JsonResponse
    {
        $request->validate([
            'funnels' => 'required|array',
        ]);

        $funnelData = $request->input('funnels');
        $result = $this->googleAnalyticsService->syncGoals($funnelData);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Google Analytics goals sync initiated' : 'Failed to initiate Google Analytics goals sync',
        ]);
    }

    /**
     * Export segments to Google Analytics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportGoogleAnalyticsSegments(Request $request): JsonResponse
    {
        $request->validate([
            'segments' => 'required|array',
        ]);

        $segmentData = $request->input('segments');
        $result = $this->googleAnalyticsService->exportSegments($segmentData);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Google Analytics segments export initiated' : 'Failed to initiate Google Analytics segments export',
        ]);
    }

    /**
     * Sync data to Matomo
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function syncMatomoData(Request $request): JsonResponse
    {
        $request->validate([
            'sync_data' => 'required|array',
        ]);

        $syncData = $request->input('sync_data');
        $result = $this->matomoService->syncData($syncData);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Matomo data sync initiated' : 'Failed to initiate Matomo data sync',
        ]);
    }
}
