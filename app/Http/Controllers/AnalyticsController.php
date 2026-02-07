<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompareCohortsRequest;
use App\Http\Requests\CreateCohortRequest;
use App\Http\Requests\CustomTrackRequest;
use App\Http\Requests\DefineEventRequest;
use App\Http\Requests\MatomoTrackRequest;
use App\Http\Requests\SyncGoalsRequest;
use App\Http\Requests\SyncRunRequest;
use App\Http\Requests\TrackTouchRequest;
use App\Models\Cohort;
use App\Services\Analytics\AttributionService;
use App\Services\Analytics\CohortAnalysisService;
use App\Services\Analytics\CustomEventService;
use App\Services\Analytics\MatomoService;
use App\Services\Analytics\SyncService;
use App\Services\TenantContextService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AnalyticsController extends Controller
{
    protected TenantContextService $tenantContextService;

    public function __construct(TenantContextService $tenantContextService)
    {
        $this->tenantContextService = $tenantContextService;
    }

    /**
     * Store analytics events in batch
     */
    public function storeEvents(Request $request): JsonResponse
    {
        // Check analytics consent
        $consentService = app(\App\Services\Analytics\ConsentService::class);
        if (! $consentService->hasConsent()) {
            \Illuminate\Support\Facades\Log::info('Analytics events access denied - no consent', [
                'ip' => $request->ip(),
                'session_id' => $request->input('sessionId'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Analytics consent required',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'events' => 'required|array|max:100',
            'events.*.eventName' => 'required|string|max:100',
            'events.*.audience' => 'required|in:individual,institutional',
            'events.*.section' => 'required|string|max:100',
            'events.*.action' => 'required|string|max:100',
            'events.*.value' => 'nullable|numeric',
            'events.*.customData' => 'nullable|array',
            'events.*.timestamp' => 'required|date',
            'sessionId' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $events = $request->input('events');
            $sessionId = $request->input('sessionId');
            $userAgent = $request->header('User-Agent');
            $ipAddress = $request->ip();

            // Process events in chunks for better performance
            $chunks = array_chunk($events, 50);
            $tenantId = $this->getTenantIdForInsert();

            foreach ($chunks as $chunk) {
                $this->processEventChunk($chunk, $sessionId, $userAgent, $ipAddress, $tenantId);
            }

            // Update session statistics
            $this->updateSessionStats($sessionId, count($events));

            return response()->json([
                'success' => true,
                'processed' => count($events),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to store analytics events', [
                'error' => $e->getMessage(),
                'session_id' => $request->input('sessionId'),
                'events_count' => count($request->input('events', [])),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process events',
            ], 500);
        }
    }

    /**
     * Store high-priority conversion event
     */
    public function storeConversion(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'goalId' => 'required|string|max:100',
            'goalName' => 'required|string|max:200',
            'goalType' => 'required|string|max:100',
            'value' => 'required|numeric|min:0',
            'trackingCode' => 'required|string|max:100',
            'audience' => 'required|in:individual,institutional',
            'sessionId' => 'required|string|max:100',
            'userId' => 'nullable|string|max:100',
            'timestamp' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $conversionData = $request->all();
            $conversionData['ip_address'] = $request->ip();
            $conversionData['user_agent'] = $request->header('User-Agent');
            $conversionData['created_at'] = now();
            $conversionData['tenant_id'] = $this->getTenantIdForInsert();

            // Store conversion in database
            DB::table('analytics_conversions')->insert($conversionData);

            // Update conversion cache for real-time metrics
            $this->updateConversionCache($conversionData);

            // Log high-value conversions
            if ($conversionData['value'] >= 100) {
                Log::info('High-value conversion tracked', [
                    'goal_id' => $conversionData['goalId'],
                    'value' => $conversionData['value'],
                    'audience' => $conversionData['audience'],
                    'session_id' => $conversionData['sessionId'],
                ]);
            }

            return response()->json([
                'success' => true,
                'conversion_id' => DB::getPdo()->lastInsertId(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to store conversion', [
                'error' => $e->getMessage(),
                'goal_id' => $request->input('goalId'),
                'session_id' => $request->input('sessionId'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process conversion',
            ], 500);
        }
    }

    /**
     * Store error event
     */
    public function storeError(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'errorType' => 'required|string|max:100',
            'errorData' => 'required|array',
            'sessionId' => 'required|string|max:100',
            'timestamp' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $errorData = [
                'error_type' => $request->input('errorType'),
                'error_data' => json_encode($request->input('errorData')),
                'session_id' => $request->input('sessionId'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'timestamp' => $request->input('timestamp'),
                'created_at' => now(),
                'tenant_id' => $this->getTenantIdForInsert(),
            ];

            // Store error in database
            DB::table('analytics_errors')->insert($errorData);

            // Log critical errors
            if (in_array($request->input('errorType'), ['javascript_error', 'unhandled_promise_rejection'])) {
                Log::error('Frontend error tracked', [
                    'error_type' => $request->input('errorType'),
                    'error_data' => $request->input('errorData'),
                    'session_id' => $request->input('sessionId'),
                ]);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Failed to store error event', [
                'error' => $e->getMessage(),
                'session_id' => $request->input('sessionId'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process error',
            ], 500);
        }
    }

    /**
     * Get analytics metrics
     */
    public function getMetrics(Request $request): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        // Check analytics consent
        $consentService = app(\App\Services\Analytics\ConsentService::class);
        if (! $consentService->hasConsent()) {
            \Illuminate\Support\Facades\Log::info('Analytics metrics access denied - no consent', [
                'ip' => $request->ip(),
                'audience' => $request->input('audience'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Analytics consent required',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'audience' => 'required|in:individual,institutional',
            'timeRange.start' => 'nullable|date',
            'timeRange.end' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $audience = $request->input('audience');
            $timeRange = $request->input('timeRange');

            $startDate = $timeRange['start'] ?? Carbon::now()->subDays(30);
            $endDate = $timeRange['end'] ?? Carbon::now();

            // Get cached metrics or calculate fresh
            $cacheKey = "analytics_metrics_{$audience}_".md5($startDate.$endDate);

            $metrics = Cache::remember($cacheKey, 300, function () use ($audience, $startDate, $endDate) {
                return $this->calculateMetrics($audience, $startDate, $endDate);
            });

            return response()->json($metrics);

        } catch (\Exception $e) {
            Log::error('Failed to get analytics metrics', [
                'error' => $e->getMessage(),
                'audience' => $request->input('audience'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve metrics',
            ], 500);
        }
    }

    /**
     * Generate analytics report
     */
    public function generateReport(Request $request, string $reportType): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        $validator = Validator::make($request->all(), [
            'audience' => 'required|in:individual,institutional',
            'timeRange.start' => 'nullable|date',
            'timeRange.end' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $audience = $request->input('audience');
            $timeRange = $request->input('timeRange');

            $report = match ($reportType) {
                'conversion' => $this->generateConversionReport($audience, $timeRange),
                'engagement' => $this->generateEngagementReport($audience, $timeRange),
                'performance' => $this->generatePerformanceReport($audience, $timeRange),
                'funnel' => $this->generateFunnelReport($audience, $timeRange),
                default => throw new \InvalidArgumentException("Unknown report type: {$reportType}")
            };

            return response()->json($report);

        } catch (\Exception $e) {
            Log::error('Failed to generate report', [
                'error' => $e->getMessage(),
                'report_type' => $reportType,
                'audience' => $request->input('audience'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate report',
            ], 500);
        }
    }

    /**
     * Export analytics data
     */
    public function exportData(Request $request): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        $validator = Validator::make($request->all(), [
            'format' => 'required|in:json,csv',
            'audience' => 'required|in:individual,institutional',
            'filters' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $format = $request->input('format');
            $audience = $request->input('audience');
            $filters = $request->input('filters', []);

            $data = $this->getExportData($audience, $filters);

            if ($format === 'csv') {
                return $this->exportToCsv($data);
            } else {
                return response()->json($data);
            }

        } catch (\Exception $e) {
            Log::error('Failed to export data', [
                'error' => $e->getMessage(),
                'format' => $request->input('format'),
                'audience' => $request->input('audience'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to export data',
            ], 500);
        }
    }

    /**
     * Get conversion report
     */
    public function getConversionReport(Request $request): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        $validator = Validator::make($request->all(), [
            'audience' => 'required|in:individual,institutional',
            'timeRange.start' => 'nullable|date',
            'timeRange.end' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $audience = $request->input('audience');
            $timeRange = $request->input('timeRange');

            $report = $this->generateConversionReport($audience, $timeRange);

            return response()->json($report);

        } catch (\Exception $e) {
            Log::error('Failed to get conversion report', [
                'error' => $e->getMessage(),
                'audience' => $request->input('audience'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate conversion report',
            ], 500);
        }
    }

    /**
     * Process a chunk of events
     */
    private function processEventChunk(array $events, string $sessionId, ?string $userAgent, string $ipAddress, string $tenantId): void
    {
        $insertData = [];

        foreach ($events as $event) {
            $insertData[] = [
                'tenant_id' => $tenantId,
                'event_name' => $event['eventName'],
                'audience' => $event['audience'],
                'section' => $event['section'],
                'action' => $event['action'],
                'value' => $event['value'] ?? null,
                'custom_data' => json_encode($event['customData'] ?? []),
                'session_id' => $sessionId,
                'user_agent' => $userAgent,
                'ip_address' => $ipAddress,
                'timestamp' => $event['timestamp'],
                'created_at' => now(),
            ];
        }

        // Batch insert for better performance
        DB::table('analytics_events')->insert($insertData);

        // Update real-time metrics cache
        $this->updateEventCache($events, $sessionId);
    }

    /**
     * Update session statistics
     */
    private function updateSessionStats(string $sessionId, int $eventCount): void
    {
        $cacheKey = "session_stats_{$sessionId}";

        Cache::increment($cacheKey.'_events', $eventCount);
        Cache::put($cacheKey.'_last_activity', now(), 3600);
    }

    /**
     * Update conversion cache for real-time metrics
     */
    private function updateConversionCache(array $conversionData): void
    {
        $audience = $conversionData['audience'];
        $goalId = $conversionData['goalId'];

        // Update daily conversion counts
        $dateKey = Carbon::parse($conversionData['timestamp'])->format('Y-m-d');
        Cache::increment("conversions_{$audience}_{$dateKey}", 1);
        Cache::increment("conversions_{$audience}_{$goalId}_{$dateKey}", 1);

        // Update total conversion value
        Cache::increment("conversion_value_{$audience}_{$dateKey}", $conversionData['value']);
    }

    /**
     * Update event cache for real-time metrics
     */
    private function updateEventCache(array $events, string $sessionId): void
    {
        foreach ($events as $event) {
            $audience = $event['audience'];
            $eventName = $event['eventName'];
            $dateKey = Carbon::parse($event['timestamp'])->format('Y-m-d');

            // Update event counts
            Cache::increment("events_{$audience}_{$eventName}_{$dateKey}", 1);

            // Update page view counts
            if ($eventName === 'page_view') {
                $page = $event['customData']['page'] ?? 'unknown';
                Cache::increment("page_views_{$audience}_{$page}_{$dateKey}", 1);
            }

            // Update CTA click counts
            if ($eventName === 'cta_click') {
                $action = $event['action'];
                Cache::increment("cta_clicks_{$audience}_{$action}_{$dateKey}", 1);
            }
        }
    }

    /**
     * Calculate analytics metrics
     */
    private function calculateMetrics(string $audience, string $startDate, string $endDate): array
    {
        // Ensure tenant context is applied
        $this->ensureTenantContext();

        // Page views
        $pageViews = DB::table('analytics_events')
            ->where('audience', $audience)
            ->where('event_name', 'page_view')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->count();

        // Unique visitors (based on session_id)
        $uniqueVisitors = DB::table('analytics_events')
            ->where('audience', $audience)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->distinct('session_id')
            ->count();

        // Average session duration
        $avgSessionDuration = DB::table('analytics_events')
            ->where('audience', $audience)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->whereJsonContains('custom_data->sessionDuration', '>', 0)
            ->avg(DB::raw("CAST(JSON_EXTRACT(custom_data, '$.sessionDuration') AS UNSIGNED)"));

        // Bounce rate (sessions with only one page view)
        $singlePageSessions = DB::table('analytics_events')
            ->select('session_id')
            ->where('audience', $audience)
            ->where('event_name', 'page_view')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->groupBy('session_id')
            ->havingRaw('COUNT(*) = 1')
            ->count();

        $bounceRate = $uniqueVisitors > 0 ? $singlePageSessions / $uniqueVisitors : 0;

        // Conversion rate
        $conversions = DB::table('analytics_conversions')
            ->where('audience', $audience)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->count();

        $conversionRate = $uniqueVisitors > 0 ? $conversions / $uniqueVisitors : 0;

        // Top pages
        $topPages = DB::table('analytics_events')
            ->select(DB::raw("JSON_EXTRACT(custom_data, '$.page') as page"), DB::raw('COUNT(*) as views'))
            ->where('audience', $audience)
            ->where('event_name', 'page_view')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->groupBy('page')
            ->orderByDesc('views')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'page' => trim($item->page, '"'),
                    'views' => $item->views,
                ];
            })
            ->toArray();

        // Top CTAs
        $topCTAs = DB::table('analytics_events')
            ->select('action', DB::raw('COUNT(*) as clicks'))
            ->where('audience', $audience)
            ->where('event_name', 'cta_click')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->groupBy('action')
            ->orderByDesc('clicks')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'cta' => $item->action,
                    'clicks' => $item->clicks,
                ];
            })
            ->toArray();

        // Audience breakdown
        $audienceBreakdown = DB::table('analytics_events')
            ->select('audience', DB::raw('COUNT(DISTINCT session_id) as visitors'))
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->groupBy('audience')
            ->pluck('visitors', 'audience')
            ->toArray();

        return [
            'pageViews' => $pageViews,
            'uniqueVisitors' => $uniqueVisitors,
            'averageSessionDuration' => round($avgSessionDuration / 1000, 2), // Convert to seconds
            'bounceRate' => round($bounceRate, 3),
            'conversionRate' => round($conversionRate, 3),
            'topPages' => $topPages,
            'topCTAs' => $topCTAs,
            'audienceBreakdown' => $audienceBreakdown,
        ];
    }

    /**
     * Generate conversion report
     */
    private function generateConversionReport(string $audience, ?array $timeRange): array
    {
        // Ensure tenant context is applied
        $this->ensureTenantContext();

        $startDate = $timeRange['start'] ?? Carbon::now()->subDays(30);
        $endDate = $timeRange['end'] ?? Carbon::now();

        // Conversion funnel
        $funnelSteps = DB::table('analytics_events')
            ->select('action', DB::raw('COUNT(DISTINCT session_id) as unique_sessions'))
            ->where('audience', $audience)
            ->where('event_name', 'funnel_step')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->groupBy('action')
            ->orderBy('unique_sessions', 'desc')
            ->get()
            ->toArray();

        // Conversion goals performance
        $goalPerformance = DB::table('analytics_conversions')
            ->select('goalId', 'goalName', DB::raw('COUNT(*) as conversions'), DB::raw('SUM(value) as total_value'))
            ->where('audience', $audience)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->groupBy('goalId', 'goalName')
            ->orderByDesc('conversions')
            ->get()
            ->toArray();

        // Daily conversion trends
        $dailyConversions = DB::table('analytics_conversions')
            ->select(DB::raw('DATE(timestamp) as date'), DB::raw('COUNT(*) as conversions'))
            ->where('audience', $audience)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        return [
            'funnelSteps' => $funnelSteps,
            'goalPerformance' => $goalPerformance,
            'dailyConversions' => $dailyConversions,
            'timeRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ];
    }

    /**
     * Generate engagement report
     */
    private function generateEngagementReport(string $audience, ?array $timeRange): array
    {
        // Ensure tenant context is applied
        $this->ensureTenantContext();

        $startDate = $timeRange['start'] ?? Carbon::now()->subDays(30);
        $endDate = $timeRange['end'] ?? Carbon::now();

        // Section engagement
        $sectionEngagement = DB::table('analytics_events')
            ->select('section', DB::raw('COUNT(*) as views'), DB::raw('AVG(CAST(JSON_EXTRACT(custom_data, "$.timeSpent") AS UNSIGNED)) as avg_time'))
            ->where('audience', $audience)
            ->where('event_name', 'section_view')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->groupBy('section')
            ->orderByDesc('views')
            ->get()
            ->toArray();

        // Scroll depth analysis
        $scrollDepth = DB::table('analytics_events')
            ->select(DB::raw('CAST(JSON_EXTRACT(custom_data, "$.percentage") AS UNSIGNED) as depth'), DB::raw('COUNT(*) as count'))
            ->where('audience', $audience)
            ->where('event_name', 'scroll_depth')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->groupBy('depth')
            ->orderBy('depth')
            ->get()
            ->toArray();

        return [
            'sectionEngagement' => $sectionEngagement,
            'scrollDepth' => $scrollDepth,
            'timeRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ];
    }

    /**
     * Generate performance report
     */
    private function generatePerformanceReport(string $audience, ?array $timeRange): array
    {
        // Ensure tenant context is applied
        $this->ensureTenantContext();

        $startDate = $timeRange['start'] ?? Carbon::now()->subDays(30);
        $endDate = $timeRange['end'] ?? Carbon::now();

        // Page performance metrics
        $pagePerformance = DB::table('analytics_events')
            ->select(
                DB::raw('JSON_EXTRACT(custom_data, "$.page") as page'),
                DB::raw('AVG(CAST(JSON_EXTRACT(custom_data, "$.loadTime") AS UNSIGNED)) as avg_load_time'),
                DB::raw('AVG(CAST(JSON_EXTRACT(custom_data, "$.firstContentfulPaint") AS UNSIGNED)) as avg_fcp'),
                DB::raw('COUNT(*) as samples')
            )
            ->where('audience', $audience)
            ->where('event_name', 'page_performance')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->groupBy('page')
            ->having('samples', '>=', 10) // Only include pages with sufficient data
            ->get()
            ->toArray();

        // Error analysis
        $errorAnalysis = DB::table('analytics_errors')
            ->select('error_type', DB::raw('COUNT(*) as count'))
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->groupBy('error_type')
            ->orderByDesc('count')
            ->get()
            ->toArray();

        return [
            'pagePerformance' => $pagePerformance,
            'errorAnalysis' => $errorAnalysis,
            'timeRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ];
    }

    /**
     * Generate funnel report
     */
    private function generateFunnelReport(string $audience, ?array $timeRange): array
    {
        // Ensure tenant context is applied
        $this->ensureTenantContext();

        $startDate = $timeRange['start'] ?? Carbon::now()->subDays(30);
        $endDate = $timeRange['end'] ?? Carbon::now();

        // Get funnel steps in order
        $funnelData = DB::table('analytics_events')
            ->select(
                DB::raw('JSON_EXTRACT(custom_data, "$.stepName") as step_name'),
                DB::raw('JSON_EXTRACT(custom_data, "$.stepOrder") as step_order'),
                DB::raw('COUNT(DISTINCT session_id) as unique_sessions')
            )
            ->where('audience', $audience)
            ->where('event_name', 'funnel_step')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->groupBy('step_name', 'step_order')
            ->orderBy('step_order')
            ->get()
            ->toArray();

        // Calculate drop-off rates
        $funnelWithDropoff = [];
        $previousCount = null;

        foreach ($funnelData as $step) {
            $dropoffRate = $previousCount ? 1 - ($step->unique_sessions / $previousCount) : 0;

            $funnelWithDropoff[] = [
                'stepName' => trim($step->step_name, '"'),
                'stepOrder' => $step->step_order,
                'uniqueSessions' => $step->unique_sessions,
                'dropoffRate' => round($dropoffRate, 3),
            ];

            $previousCount = $step->unique_sessions;
        }

        return [
            'funnelSteps' => $funnelWithDropoff,
            'timeRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ];
    }

    /**
     * Get export data
     */
    private function getExportData(string $audience, array $filters): array
    {
        // Ensure tenant context is applied
        $this->ensureTenantContext();

        $query = DB::table('analytics_events')
            ->where('audience', $audience);

        // Apply filters
        if (isset($filters['startDate'])) {
            $query->where('timestamp', '>=', $filters['startDate']);
        }

        if (isset($filters['endDate'])) {
            $query->where('timestamp', '<=', $filters['endDate']);
        }

        if (isset($filters['eventName'])) {
            $query->where('event_name', $filters['eventName']);
        }

        if (isset($filters['section'])) {
            $query->where('section', $filters['section']);
        }

        return $query->orderBy('timestamp', 'desc')
            ->limit(10000) // Limit export size
            ->get()
            ->toArray();
    }

    /**
     * Export data to CSV
     */
    private function exportToCsv(array $data): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="analytics-export-'.date('Y-m-d-H-i-s').'.csv"',
        ];

        return response()->stream(function () use ($data) {
            $handle = fopen('php://output', 'w');

            // Write CSV headers
            if (! empty($data)) {
                fputcsv($handle, array_keys((array) $data[0]));

                // Write data rows
                foreach ($data as $row) {
                    fputcsv($handle, (array) $row);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }

    // ========================================
    // Cohort Analysis Methods
    // ========================================

    /**
     * Create a new cohort
     */
    public function createCohort(CreateCohortRequest $request): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        try {
            $cohortAnalysisService = app(CohortAnalysisService::class);

            $cohortData = [
                'acquisition_date' => $request->input('grouping_criteria.params.date_range.start') ?? now()->subDays(30)->toDateString(),
                'source' => $request->input('grouping_criteria.params.sources.0') ?? null,
                'characteristics' => $request->input('grouping_criteria.params.filters', []),
            ];

            $result = $cohortAnalysisService->createCohort($cohortData);

            // Create persistent cohort record
            $cohort = Cohort::create([
                'tenant_id' => $this->getCurrentTenantId(),
                'name' => $request->input('name'),
                'criteria' => [
                    'type' => $request->input('grouping_criteria.type'),
                    'params' => $request->input('grouping_criteria.params'),
                ],
                'status' => 'active',
                'created_by' => auth()->id() ?? 1,
            ]);

            return response()->json([
                'success' => true,
                'cohort' => [
                    'id' => $cohort->id,
                    'cohort_id' => $result['cohort_id'],
                    'name' => $cohort->name,
                    'user_count' => $result['user_count'],
                    'criteria' => $cohort->criteria,
                    'created_at' => $cohort->created_at,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create cohort', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create cohort',
            ], 500);
        }
    }

    /**
     * Get cohort metrics and time series data
     */
    public function getCohort(string $cohortId): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        try {
            $cohort = Cohort::byTenant($this->getCurrentTenantId())
                ->where('id', $cohortId)
                ->first();

            if (! $cohort) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cohort not found',
                ], 404);
            }

            $cohortAnalysisService = app(CohortAnalysisService::class);

            // Get retention data for multiple periods
            $retentionData = [];
            for ($days = 1; $days <= 30; $days += 7) {
                $retentionData[] = [
                    'days' => $days,
                    'retention_rate' => $cohortAnalysisService->calculateRetention($cohort->id, $days),
                ];
            }

            $engagement = $cohortAnalysisService->calculateEngagement($cohort->id);
            $conversion = $cohortAnalysisService->calculateConversionRates($cohort->id);
            $insights = $cohortAnalysisService->generateInsights($cohort->id);

            return response()->json([
                'success' => true,
                'cohort' => [
                    'id' => $cohort->id,
                    'name' => $cohort->name,
                    'criteria' => $cohort->criteria,
                    'status' => $cohort->status,
                    'user_count' => $cohort->criteria_summary,
                    'created_at' => $cohort->created_at,
                    'created_by' => $cohort->creator->name ?? 'Unknown',
                ],
                'metrics' => [
                    'retention' => $retentionData,
                    'engagement' => $engagement,
                    'conversion' => $conversion,
                ],
                'insights' => $insights,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get cohort data', [
                'cohort_id' => $cohortId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve cohort data',
            ], 500);
        }
    }

    /**
     * Compare multiple cohorts with statistical analysis
     */
    public function compareCohorts(CompareCohortsRequest $request): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        try {
            $cohortIds = $request->input('cohort_ids');
            $metrics = $request->input('metrics', ['retention', 'engagement']);

            $cohortAnalysisService = app(CohortAnalysisService::class);
            $comparison = $cohortAnalysisService->compareCohorts($cohortIds);

            // Enhance comparison with additional metrics if requested
            if (in_array('conversion', $metrics)) {
                foreach ($comparison['cohorts'] as &$cohort) {
                    $cohort['conversion'] = $cohortAnalysisService->calculateConversionRates($cohort['cohort_id']);
                }
            }

            return response()->json([
                'success' => true,
                'comparison' => $comparison,
                'metadata' => [
                    'cohort_count' => count($cohortIds),
                    'metrics' => $metrics,
                    'time_range' => $request->input('time_range'),
                    'statistical_significance_included' => $request->input('include_statistical_significance', true),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to compare cohorts', [
                'cohort_ids' => $request->input('cohort_ids'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to compare cohorts',
            ], 500);
        }
    }

    /**
     * List cohorts with filtering and pagination
     */
    public function listCohorts(Request $request): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        try {
            $query = Cohort::byTenant($this->getCurrentTenantId())
                ->with('creator:id,name,email');

            // Apply filters
            if ($request->has('status')) {
                $query->byStatus($request->input('status'));
            }

            if ($request->has('created_by')) {
                $query->byCreator($request->input('created_by'));
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');

            if (in_array($sortBy, ['name', 'status', 'created_at'])) {
                $query->orderBy($sortBy, $sortDirection);
            }

            // Paginate results
            $perPage = min($request->input('per_page', 20), 100);
            $cohorts = $query->paginate($perPage);

            // Enhance with basic metrics
            $cohortAnalysisService = app(CohortAnalysisService::class);
            $cohorts->getCollection()->transform(function ($cohort) use ($cohortAnalysisService) {
                try {
                    $cohort->retention_7d = $cohortAnalysisService->calculateRetention($cohort->id, 7);
                    $cohort->retention_30d = $cohortAnalysisService->calculateRetention($cohort->id, 30);
                    $cohort->engagement_score = $cohortAnalysisService->calculateEngagement($cohort->id)['engagement_score'];
                } catch (\Exception $e) {
                    // Set defaults if calculation fails
                    $cohort->retention_7d = 0.0;
                    $cohort->retention_30d = 0.0;
                    $cohort->engagement_score = 0.0;
                }

                return $cohort;
            });

            return response()->json([
                'success' => true,
                'cohorts' => $cohorts,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to list cohorts', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve cohorts',
            ], 500);
        }
    }

    /**
     * Get current tenant ID
     *
     * @return string|null Returns the current tenant ID or null if not set
     *
     * @throws \Exception If tenant context is not available
     */
    private function getCurrentTenantId(): ?string
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        if (! $tenantId) {
            Log::warning('Tenant context not available in AnalyticsController', [
                'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
                'user_id' => auth()->id(),
            ]);
            throw new \Exception('Tenant context not available. Please ensure you are accessing the application through a valid tenant context.');
        }

        return $tenantId;
    }

    /**
     * Ensure tenant context is applied to database queries
     * This method ensures that all queries are executed in the correct tenant schema
     *
     * @throws \Exception If tenant context is not available
     */
    private function ensureTenantContext(): void
    {
        $tenantId = $this->getCurrentTenantId();
        $schema = $this->tenantContextService->getCurrentSchema();

        if ($schema) {
            // Switch to tenant schema for all queries
            $this->tenantContextService->switchToTenantSchema($schema);
        }

        Log::debug('Tenant context applied for analytics queries', [
            'tenant_id' => $tenantId,
            'schema' => $schema,
        ]);
    }

    /**
     * Validate tenant isolation for cross-tenant access prevention
     * This method should be called at the start of any method that retrieves tenant-specific data
     *
     * @throws \Exception If tenant context is not valid or user doesn't have access
     */
    private function validateTenantIsolation(): void
    {
        $tenantId = $this->getCurrentTenantId();

        if (! $tenantId) {
            throw new \Exception('Tenant context is required for this operation');
        }

        // Validate that the current user has access to this tenant
        if (! $this->tenantContextService->validateTenantAccess($tenantId)) {
            Log::warning('Tenant access validation failed', [
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
            ]);
            throw new \Exception('You do not have access to this tenant\'s data');
        }
    }

    /**
     * Get the current tenant ID for insert operations
     *
     * @return string The current tenant ID
     */
    private function getTenantIdForInsert(): string
    {
        return $this->getCurrentTenantId();
    }

    // ========================================
    // Attribution Analysis Methods
    // ========================================

    /**
     * Track a touchpoint in a user's attribution journey
     */
    public function trackTouchpoint(TrackTouchRequest $request): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        try {
            $attributionService = app(AttributionService::class);

            $touchpointData = [
                'user_id' => auth()->id() ?? 1, // Default to user 1 if not authenticated
                'session_id' => $request->input('session_id'),
                'touch_type' => $request->input('touch_type'),
                'channel' => $request->input('channel'),
                'value' => $request->input('value', 50.0),
                'metadata' => $request->input('metadata', []),
                'conversion_value' => $request->input('conversion_value'),
                'timestamp' => now(),
            ];

            $touchpoint = $attributionService->trackTouchpoint($touchpointData);

            return response()->json([
                'success' => true,
                'touchpoint' => [
                    'id' => $touchpoint->id,
                    'channel' => $touchpoint->channel,
                    'touch_type' => $touchpoint->touch_type,
                    'timestamp' => $touchpoint->timestamp,
                    'value' => $touchpoint->value,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to track touchpoint', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to track touchpoint',
            ], 500);
        }
    }

    /**
     * Get user attribution journey with model comparisons
     */
    public function getUserAttribution(int $userId): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        try {
            $attributionService = app(AttributionService::class);

            // Get attribution for all models
            $models = ['first-click', 'last-click', 'linear', 'time-decay'];
            $attributions = [];

            foreach ($models as $model) {
                $attributions[$model] = $attributionService->calculateAttribution($userId, $model);
            }

            return response()->json([
                'success' => true,
                'user_id' => $userId,
                'attributions' => $attributions,
                'model_comparison' => $this->compareAttributionModels($attributions),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user attribution', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve user attribution data',
            ], 500);
        }
    }

    /**
     * Get channel performance metrics with ROI analysis
     */
    public function getChannelPerformance(): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        try {
            $attributionService = app(AttributionService::class);

            // Get performance for last 90 days
            $endDate = now();
            $startDate = $endDate->copy()->subDays(90);

            $channels = ['google', 'facebook', 'linkedin', 'organic', 'direct', 'email'];
            $performance = [];

            foreach ($channels as $channel) {
                $contribution = $attributionService->getChannelContribution(
                    $channel,
                    $startDate,
                    $endDate
                );

                if (! empty($contribution)) {
                    $roi = $attributionService->calculateChannelROI($channel);
                    $performance[$channel] = array_merge($contribution, [
                        'roi' => round($roi, 2),
                        'roi_category' => $this->categorizeROI($roi),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'performance' => $performance,
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
                'summary' => $this->calculatePerformanceSummary($performance),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get channel performance', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve channel performance data',
            ], 500);
        }
    }

    /**
     * Get budget allocation recommendations based on performance
     */
    public function getBudgetRecommendations(): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        try {
            $attributionService = app(AttributionService::class);
            $recommendations = $attributionService->generateBudgetRecommendations();

            if (empty($recommendations)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No budget recommendation data available',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'recommendations' => $recommendations,
                'insights' => $this->generateBudgetInsights($recommendations),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get budget recommendations', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve budget recommendations',
            ], 500);
        }
    }

    /**
     * Compare attribution models and identify differences
     */
    private function compareAttributionModels(array $attributions): array
    {
        $comparison = [];

        // Compare channel weights across models
        foreach (['first-click', 'last-click', 'linear', 'time-decay'] as $model) {
            if (isset($attributions[$model]['attribution'])) {
                $comparison[$model] = [
                    'total_weight' => array_sum($attributions[$model]['attribution']),
                    'channel_weights' => $attributions[$model]['attribution'],
                ];
            }
        }

        // Find model differences
        $linearWeights = $attributions['linear']['attribution'] ?? [];
        $differences = [];

        foreach (['first-click', 'last-click', 'time-decay'] as $model) {
            if (isset($attributions[$model]['attribution'])) {
                $modelWeights = $attributions[$model]['attribution'];
                $diff = [];

                foreach (array_unique(array_merge(array_keys($linearWeights), array_keys($modelWeights))) as $channel) {
                    $linearWeight = $linearWeights[$channel] ?? 0;
                    $modelWeight = $modelWeights[$channel] ?? 0;
                    $diff[$channel] = round($modelWeight - $linearWeight, 3);
                }

                $differences[$model.'_vs_linear'] = $diff;
            }
        }

        return [
            'model_weights' => $comparison,
            'differences' => $differences,
        ];
    }

    /**
     * Categorize ROI values
     */
    private function categorizeROI(float $roi): string
    {
        if ($roi >= 3.0) {
            return 'excellent';
        } elseif ($roi >= 2.0) {
            return 'good';
        } elseif ($roi >= 1.0) {
            return 'fair';
        } elseif ($roi >= 0.5) {
            return 'poor';
        } else {
            return 'negative';
        }
    }

    /**
     * Calculate performance summary statistics
     */
    private function calculatePerformanceSummary(array $performance): array
    {
        $totalConversions = array_sum(array_column($performance, 'total_conversions'));
        $totalValue = array_sum(array_column($performance, 'total_conversion_value'));
        $avgEngagement = array_sum(array_column($performance, 'average_engagement')) / count($performance);

        $roiCategories = array_count_values(array_column($performance, 'roi_category'));

        return [
            'total_conversions' => $totalConversions,
            'total_conversion_value' => round($totalValue, 2),
            'average_engagement' => round($avgEngagement, 2),
            'roi_distribution' => $roiCategories,
            'best_performing_channel' => $this->findBestChannel($performance),
        ];
    }

    /**
     * Find the best performing channel based on ROI
     */
    private function findBestChannel(array $performance): ?string
    {
        $bestChannel = null;
        $bestROI = -1;

        foreach ($performance as $channel => $data) {
            if ($data['roi'] > $bestROI) {
                $bestROI = $data['roi'];
                $bestChannel = $channel;
            }
        }

        return $bestChannel;
    }

    /**
     * Generate insights from budget recommendations
     */
    private function generateBudgetInsights(array $recommendations): array
    {
        $insights = [];

        foreach ($recommendations['channels'] as $channel => $data) {
            if ($data['change_percentage'] > 15) {
                $insights[] = "Consider increasing {$channel} budget by {$data['change_percentage']}% due to strong ROI performance.";
            } elseif ($data['change_percentage'] < -10) {
                $insights[] = "Consider decreasing {$channel} budget by ".abs($data['change_percentage']).'% due to poor ROI performance.';
            }
        }

        if ($recommendations['overall_change_percentage'] > 10) {
            $insights[] = "Overall budget increase of {$recommendations['overall_change_percentage']}% recommended for better performance.";
        }

        return $insights;
    }

    // ========================================
    // Custom Event Methods
    // ========================================

    /**
     * Define a new custom event with JSON schema validation.
     */
    public function defineCustomEvent(DefineEventRequest $request): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        try {
            $customEventService = app(CustomEventService::class);

            $eventDefinition = [
                'event_name' => $request->input('event_name'),
                'schema' => $request->input('schema'),
                'description' => $request->input('description'),
                'category' => $request->input('category'),
                'validation_rules' => $request->input('validation_rules', []),
                'created_by' => auth()->id(),
            ];

            $definition = $customEventService->defineEvent($eventDefinition);

            return response()->json([
                'success' => true,
                'event_definition' => [
                    'id' => $definition->id,
                    'event_name' => $definition->event_name,
                    'schema' => $definition->schema,
                    'description' => $definition->description,
                    'category' => $definition->category,
                    'created_at' => $definition->created_at,
                    'created_by' => $definition->created_by,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to define custom event', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to define custom event',
            ], 500);
        }
    }

    /**
     * Track a custom event instance with validation.
     */
    public function trackCustomEvent(CustomTrackRequest $request): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        try {
            $customEventService = app(CustomEventService::class);

            $eventData = $request->input('properties');
            $context = array_merge($request->input('context', []), [
                'user_id' => $request->input('user_id'),
                'session_id' => $request->input('session_id'),
            ]);

            $success = $customEventService->trackCustomEvent(
                $request->input('event_name'),
                $eventData,
                $context
            );

            if (! $success) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to track custom event',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Custom event tracked successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to track custom event', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to track custom event',
            ], 500);
        }
    }

    /**
     * Get custom event analysis and insights.
     */
    public function getEventAnalysis(string $eventName): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        try {
            $customEventService = app(CustomEventService::class);

            // Get event insights
            $insights = $customEventService->generateEventInsights($eventName);

            if (empty($insights)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Event not found or no data available',
                ], 404);
            }

            // Get behavior flows
            $behaviorFlows = $customEventService->analyzeBehaviorFlows($eventName);

            // Get funnel analysis (if applicable)
            $funnelData = $customEventService->calculateCustomFunnels([$eventName]);

            return response()->json([
                'success' => true,
                'event_name' => $eventName,
                'insights' => $insights,
                'behavior_flows' => $behaviorFlows,
                'funnel_analysis' => $funnelData,
                'generated_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get event analysis', [
                'event_name' => $eventName,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve event analysis',
            ], 500);
        }
    }

    /**
     * List all custom events with filtering and pagination.
     */
    public function listCustomEvents(Request $request): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        try {
            $query = \App\Models\CustomEventDefinition::byTenant($this->getCurrentTenantId());

            // Apply filters
            if ($request->has('category')) {
                $query->where('category', $request->input('category'));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('event_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');

            if (in_array($sortBy, ['event_name', 'category', 'created_at'])) {
                $query->orderBy($sortBy, $sortDirection);
            }

            // Paginate results
            $perPage = min($request->input('per_page', 20), 100);
            $events = $query->paginate($perPage);

            // Enhance with basic statistics
            $customEventService = app(CustomEventService::class);
            $events->getCollection()->transform(function ($event) use ($customEventService) {
                try {
                    $stats = $customEventService->generateEventInsights($event->event_name);
                    $event->total_events = $stats['statistics']['total_events'] ?? 0;
                    $event->unique_users = $stats['statistics']['unique_users'] ?? 0;
                    $event->last_tracked = \App\Models\CustomEventTracking::byTenant($this->getCurrentTenantId())
                        ->byEventName($event->event_name)
                        ->latest('occurred_at')
                        ->value('occurred_at');
                } catch (\Exception $e) {
                    $event->total_events = 0;
                    $event->unique_users = 0;
                    $event->last_tracked = null;
                }

                return $event;
            });

            return response()->json([
                'success' => true,
                'events' => $events,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to list custom events', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve custom events',
            ], 500);
        }
    }

    // ========================================
    // Matomo Analytics Methods
    // ========================================

    /**
     * Track event in Matomo
     */
    public function trackMatomoEvent(MatomoTrackRequest $request): JsonResponse
    {
        try {
            $matomoService = app(MatomoService::class);

            $success = $matomoService->trackEvent($request->input('event_data'));

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Event tracked successfully' : 'Failed to track event',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to track Matomo event', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to track event',
            ], 500);
        }
    }

    /**
     * Sync goals with Matomo
     */
    public function syncMatomoGoals(SyncGoalsRequest $request): JsonResponse
    {
        try {
            $matomoService = app(MatomoService::class);

            $success = $matomoService->syncGoals($request->input('funnel_id'));

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Goals synced successfully' : 'Failed to sync goals',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync Matomo goals', [
                'error' => $e->getMessage(),
                'funnel_id' => $request->input('funnel_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to sync goals',
            ], 500);
        }
    }

    /**
     * Export segments from Matomo
     */
    public function getMatomoSegments(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $matomoService = app(MatomoService::class);

            $filters = $request->input('date_range', []);
            $segments = $matomoService->exportSegments($filters);

            return response()->json([
                'success' => true,
                'segments' => $segments,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to export Matomo segments', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to export segments',
            ], 500);
        }
    }

    // ========================================
    // Data Synchronization Methods
    // ========================================

    /**
     * Run data synchronization
     */
    public function runSync(SyncRunRequest $request): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        try {
            $syncService = app(SyncService::class);
            $tenantId = $this->getCurrentTenantId();

            $sources = $request->input('sources', ['ga', 'matomo']);
            $timeRange = $request->input('time_range', []);

            $result = $syncService->syncData($tenantId, $sources, $timeRange);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Data synchronization completed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to run data sync', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to run data synchronization',
            ], 500);
        }
    }

    /**
     * Get synchronization status
     */
    public function getSyncStatus(Request $request): JsonResponse
    {
        // Validate tenant isolation first
        $this->validateTenantIsolation();

        $validator = Validator::make($request->all(), [
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $syncService = app(SyncService::class);
            $tenantId = $this->getCurrentTenantId();

            // Get current sync status
            $status = $syncService->monitorSync($tenantId);

            // Get recent sync logs
            $timeRange = $request->input('date_range', []);
            $startDate = $timeRange['start'] ?? now()->subDays(7)->toDateString();
            $endDate = $timeRange['end'] ?? now()->toDateString();

            $recentLogs = \App\Models\SyncLog::byTenant($tenantId)
                ->byDateRange($startDate, $endDate)
                ->orderBy('timestamp', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'sync_type' => $log->sync_type,
                        'status' => $log->status,
                        'timestamp' => $log->timestamp,
                        'discrepancies_count' => is_array($log->discrepancies) ? count($log->discrepancies) : 0,
                    ];
                });

            return response()->json([
                'success' => true,
                'status' => $status,
                'recent_logs' => $recentLogs,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get sync status', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve sync status',
            ], 500);
        }
    }
}
