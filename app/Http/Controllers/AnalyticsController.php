<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Store analytics events in batch
     */
    public function storeEvents(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'events' => 'required|array|max:100',
            'events.*.eventName' => 'required|string|max:100',
            'events.*.audience' => 'required|in:individual,institutional',
            'events.*.section' => 'required|string|max:100',
            'events.*.action' => 'required|string|max:100',
            'events.*.value' => 'nullable|numeric',
            'events.*.customData' => 'nullable|array',
            'events.*.timestamp' => 'required|date',
            'sessionId' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $events = $request->input('events');
            $sessionId = $request->input('sessionId');
            $userAgent = $request->header('User-Agent');
            $ipAddress = $request->ip();

            // Process events in chunks for better performance
            $chunks = array_chunk($events, 50);
            
            foreach ($chunks as $chunk) {
                $this->processEventChunk($chunk, $sessionId, $userAgent, $ipAddress);
            }

            // Update session statistics
            $this->updateSessionStats($sessionId, count($events));

            return response()->json([
                'success' => true,
                'processed' => count($events)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to store analytics events', [
                'error' => $e->getMessage(),
                'session_id' => $request->input('sessionId'),
                'events_count' => count($request->input('events', []))
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process events'
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
            'timestamp' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $conversionData = $request->all();
            $conversionData['ip_address'] = $request->ip();
            $conversionData['user_agent'] = $request->header('User-Agent');
            $conversionData['created_at'] = now();

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
                    'session_id' => $conversionData['sessionId']
                ]);
            }

            return response()->json([
                'success' => true,
                'conversion_id' => DB::getPdo()->lastInsertId()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to store conversion', [
                'error' => $e->getMessage(),
                'goal_id' => $request->input('goalId'),
                'session_id' => $request->input('sessionId')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process conversion'
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
            'timestamp' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
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
                'created_at' => now()
            ];

            // Store error in database
            DB::table('analytics_errors')->insert($errorData);

            // Log critical errors
            if (in_array($request->input('errorType'), ['javascript_error', 'unhandled_promise_rejection'])) {
                Log::error('Frontend error tracked', [
                    'error_type' => $request->input('errorType'),
                    'error_data' => $request->input('errorData'),
                    'session_id' => $request->input('sessionId')
                ]);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Failed to store error event', [
                'error' => $e->getMessage(),
                'session_id' => $request->input('sessionId')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process error'
            ], 500);
        }
    }

    /**
     * Get analytics metrics
     */
    public function getMetrics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'audience' => 'required|in:individual,institutional',
            'timeRange.start' => 'nullable|date',
            'timeRange.end' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $audience = $request->input('audience');
            $timeRange = $request->input('timeRange');
            
            $startDate = $timeRange['start'] ?? Carbon::now()->subDays(30);
            $endDate = $timeRange['end'] ?? Carbon::now();

            // Get cached metrics or calculate fresh
            $cacheKey = "analytics_metrics_{$audience}_" . md5($startDate . $endDate);
            
            $metrics = Cache::remember($cacheKey, 300, function () use ($audience, $startDate, $endDate) {
                return $this->calculateMetrics($audience, $startDate, $endDate);
            });

            return response()->json($metrics);

        } catch (\Exception $e) {
            Log::error('Failed to get analytics metrics', [
                'error' => $e->getMessage(),
                'audience' => $request->input('audience')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve metrics'
            ], 500);
        }
    }

    /**
     * Generate analytics report
     */
    public function generateReport(Request $request, string $reportType): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'audience' => 'required|in:individual,institutional',
            'timeRange.start' => 'nullable|date',
            'timeRange.end' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
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
                'audience' => $request->input('audience')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate report'
            ], 500);
        }
    }

    /**
     * Export analytics data
     */
    public function exportData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:json,csv',
            'audience' => 'required|in:individual,institutional',
            'filters' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
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
                'audience' => $request->input('audience')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to export data'
            ], 500);
        }
    }

    /**
     * Get conversion report
     */
    public function getConversionReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'audience' => 'required|in:individual,institutional',
            'timeRange.start' => 'nullable|date',
            'timeRange.end' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
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
                'audience' => $request->input('audience')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate conversion report'
            ], 500);
        }
    }

    /**
     * Process a chunk of events
     */
    private function processEventChunk(array $events, string $sessionId, ?string $userAgent, string $ipAddress): void
    {
        $insertData = [];
        
        foreach ($events as $event) {
            $insertData[] = [
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
                'created_at' => now()
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
        
        Cache::increment($cacheKey . '_events', $eventCount);
        Cache::put($cacheKey . '_last_activity', now(), 3600);
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
                    'views' => $item->views
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
                    'clicks' => $item->clicks
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
            'audienceBreakdown' => $audienceBreakdown
        ];
    }

    /**
     * Generate conversion report
     */
    private function generateConversionReport(string $audience, ?array $timeRange): array
    {
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
                'end' => $endDate
            ]
        ];
    }

    /**
     * Generate engagement report
     */
    private function generateEngagementReport(string $audience, ?array $timeRange): array
    {
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
                'end' => $endDate
            ]
        ];
    }

    /**
     * Generate performance report
     */
    private function generatePerformanceReport(string $audience, ?array $timeRange): array
    {
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
                'end' => $endDate
            ]
        ];
    }

    /**
     * Generate funnel report
     */
    private function generateFunnelReport(string $audience, ?array $timeRange): array
    {
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
                'dropoffRate' => round($dropoffRate, 3)
            ];
            
            $previousCount = $step->unique_sessions;
        }

        return [
            'funnelSteps' => $funnelWithDropoff,
            'timeRange' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ];
    }

    /**
     * Get export data
     */
    private function getExportData(string $audience, array $filters): array
    {
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
            'Content-Disposition' => 'attachment; filename="analytics-export-' . date('Y-m-d-H-i-s') . '.csv"',
        ];

        return response()->stream(function () use ($data) {
            $handle = fopen('php://output', 'w');
            
            // Write CSV headers
            if (!empty($data)) {
                fputcsv($handle, array_keys((array) $data[0]));
                
                // Write data rows
                foreach ($data as $row) {
                    fputcsv($handle, (array) $row);
                }
            }
            
            fclose($handle);
        }, 200, $headers);
    }
}