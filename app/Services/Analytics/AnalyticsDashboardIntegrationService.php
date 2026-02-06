<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Services\TenantContextService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Throwable;
use Exception;

/**
 * Analytics Dashboard Integration Service
 *
 * Provides comprehensive analytics dashboard integration capabilities including
 * dashboard data aggregation, widget management, and dashboard CRUD operations.
 * Implements proper tenant isolation for secure multi-tenant operations.
 */
class AnalyticsDashboardIntegrationService
{
    private const DASHBOARD_CACHE_KEY = 'analytics_dashboards';
    private const DASHBOARD_CACHE_TTL = 3600; // 1 hour
    private const MAX_DASHBOARDS = 100;
    private const MAX_WIDGETS_PER_DASHBOARD = 50;

    private TenantContextService $tenantContextService;
    private AnalyticsMetricsCollectionService $metricsCollectionService;
    private array $dashboardsStorage = [];
    private array $dashboardConfig;

    /**
     * Widget types
     */
    public const WIDGET_TYPE_METRIC_CARD = 'metric_card';
    public const WIDGET_TYPE_CHART = 'chart';
    public const WIDGET_TYPE_TABLE = 'table';
    public const WIDGET_TYPE_GAUGE = 'gauge';
    public const WIDGET_TYPE_MAP = 'map';
    public const WIDGET_TYPE_LIST = 'list';

    /**
     * Chart types
     */
    public const CHART_TYPE_LINE = 'line';
    public const CHART_TYPE_BAR = 'bar';
    public const CHART_TYPE_PIE = 'pie';
    public const CHART_TYPE_AREA = 'area';
    public const CHART_TYPE_SCATTER = 'scatter';

    /**
     * Date range presets
     */
    public const DATE_RANGE_TODAY = 'today';
    public const DATE_RANGE_YESTERDAY = 'yesterday';
    public const DATE_RANGE_LAST_7_DAYS = 'last_7_days';
    public const DATE_RANGE_LAST_30_DAYS = 'last_30_days';
    public const DATE_RANGE_LAST_90_DAYS = 'last_90_days';
    public const DATE_RANGE_THIS_MONTH = 'this_month';
    public const DATE_RANGE_LAST_MONTH = 'last_month';
    public const DATE_RANGE_THIS_YEAR = 'this_year';
    public const DATE_RANGE_CUSTOM = 'custom';

    /**
     * Aggregation types
     */
    public const AGGREGATION_SUM = 'sum';
    public const AGGREGATION_AVG = 'avg';
    public const AGGREGATION_MIN = 'min';
    public const AGGREGATION_MAX = 'max';
    public const AGGREGATION_COUNT = 'count';

    /**
     * @param TenantContextService $tenantContextService
     * @param AnalyticsMetricsCollectionService $metricsCollectionService
     * @param array $dashboardConfig Dashboard configuration
     */
    public function __construct(
        TenantContextService $tenantContextService,
        AnalyticsMetricsCollectionService $metricsCollectionService,
        array $dashboardConfig = []
    ) {
        $this->tenantContextService = $tenantContextService;
        $this->metricsCollectionService = $metricsCollectionService;
        $this->dashboardConfig = array_merge($this->getDefaultConfig(), $dashboardConfig);
    }

    // ==================== Dashboard CRUD Operations ====================

    /**
     * Get dashboard data for a specific dashboard and date range
     *
     * @param string $dashboardId Dashboard ID
     * @param array $dateRange Date range with 'start' and 'end' keys
     * @return array Dashboard data with widgets
     */
    public function getDashboardData(string $dashboardId, array $dateRange = []): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        
        $dashboard = $this->getDashboardById($dashboardId);
        
        if (!$dashboard) {
            throw new Exception("Dashboard not found: {$dashboardId}");
        }

        $dateRange = $this->resolveDateRange($dateRange);
        
        $dashboardData = [
            'id' => $dashboard['id'],
            'name' => $dashboard['name'],
            'description' => $dashboard['description'],
            'configuration' => $dashboard['configuration'],
            'date_range' => $dateRange,
            'widgets' => [],
            'generated_at' => now()->toIso8601String(),
        ];

        // Get widget data for each widget
        foreach ($dashboard['widgets'] ?? [] as $widget) {
            $widgetData = $this->getWidgetDataInternal($widget, $dateRange);
            $dashboardData['widgets'][] = $widgetData;
        }

        Log::info("Dashboard data retrieved", [
            'dashboard_id' => $dashboardId,
            'tenant_id' => $tenantId,
            'widget_count' => count($dashboardData['widgets']),
            'date_range' => $dateRange,
        ]);

        return $dashboardData;
    }

    /**
     * Get widget data for a specific widget
     *
     * @param string $widgetId Widget ID
     * @param array $dateRange Date range with 'start' and 'end' keys
     * @return array Widget data
     */
    public function getWidgetData(string $widgetId, array $dateRange = []): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        
        // Find widget across all dashboards
        $widget = $this->findWidgetById($widgetId);
        
        if (!$widget) {
            throw new Exception("Widget not found: {$widgetId}");
        }

        $dateRange = $this->resolveDateRange($dateRange);
        $widgetData = $this->getWidgetDataInternal($widget, $dateRange);

        Log::info("Widget data retrieved", [
            'widget_id' => $widgetId,
            'tenant_id' => $tenantId,
            'widget_type' => $widget['type'],
            'date_range' => $dateRange,
        ]);

        return $widgetData;
    }

    /**
     * Aggregate dashboard data across multiple dimensions
     *
     * @param string $dashboardId Dashboard ID
     * @param array $dateRange Date range with 'start' and 'end' keys
     * @param string $aggregation Aggregation type (sum, avg, min, max, count)
     * @return array Aggregated dashboard data
     */
    public function aggregateDashboardData(
        string $dashboardId,
        array $dateRange = [],
        string $aggregation = self::AGGREGATION_SUM
    ): array {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        
        $dashboard = $this->getDashboardById($dashboardId);
        
        if (!$dashboard) {
            throw new Exception("Dashboard not found: {$dashboardId}");
        }

        $dateRange = $this->resolveDateRange($dateRange);
        
        $aggregatedData = [
            'dashboard_id' => $dashboardId,
            'dashboard_name' => $dashboard['name'],
            'aggregation' => $aggregation,
            'date_range' => $dateRange,
            'metrics' => [],
            'aggregated_at' => now()->toIso8601String(),
        ];

        // Aggregate data for each widget
        foreach ($dashboard['widgets'] ?? [] as $widget) {
            $widgetData = $this->getWidgetDataInternal($widget, $dateRange);
            $value = $widgetData['value'] ?? 0;
            
            $aggregatedData['metrics'][] = [
                'widget_id' => $widget['id'],
                'widget_title' => $widget['title'],
                'widget_type' => $widget['type'],
                'metric_name' => $widget['metric'] ?? null,
                'value' => $value,
            ];
        }

        // Apply aggregation
        $values = array_column($aggregatedData['metrics'], 'value');
        $aggregatedData['aggregated_value'] = $this->aggregateValues($values, $aggregation);
        $aggregatedData['total_widgets'] = count($aggregatedData['metrics']);
        $aggregatedData['statistics'] = $this->calculateStatistics($values);

        Log::info("Dashboard data aggregated", [
            'dashboard_id' => $dashboardId,
            'tenant_id' => $tenantId,
            'aggregation' => $aggregation,
            'total_metrics' => $aggregatedData['total_widgets'],
        ]);

        return $aggregatedData;
    }

    /**
     * Refresh dashboard data (clear cache and recalculate)
     *
     * @param string $dashboardId Dashboard ID
     * @return array Refreshed dashboard data
     */
    public function refreshDashboard(string $dashboardId): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        
        $dashboard = $this->getDashboardById($dashboardId);
        
        if (!$dashboard) {
            throw new Exception("Dashboard not found: {$dashboardId}");
        }

        // Clear dashboard cache
        $cacheKey = $this->getDashboardCacheKey($dashboardId);
        Cache::forget($cacheKey);

        // Refresh each widget
        foreach ($dashboard['widgets'] ?? [] as $widget) {
            $this->refreshWidgetCache($widget);
        }

        // Get fresh dashboard data
        $dateRange = $this->getDefaultDateRange();
        $dashboardData = $this->getDashboardData($dashboardId, $dateRange);
        $dashboardData['refreshed_at'] = now()->toIso8601String();

        Log::info("Dashboard refreshed", [
            'dashboard_id' => $dashboardId,
            'tenant_id' => $tenantId,
        ]);

        return $dashboardData;
    }

    /**
     * Create a new dashboard
     *
     * @param array $dashboard Dashboard data (name, description, configuration, widgets)
     * @return array Created dashboard
     */
    public function createDashboard(array $dashboard): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        
        // Validate dashboard name
        if (empty($dashboard['name'])) {
            throw new Exception('Dashboard name is required');
        }

        // Check max dashboards limit
        $dashboards = $this->getAllDashboards();
        if (count($dashboards) >= self::MAX_DASHBOARDS) {
            throw new Exception('Maximum number of dashboards reached');
        }

        $newDashboard = [
            'id' => uniqid('dashboard_', true),
            'tenant_id' => $tenantId,
            'name' => $dashboard['name'],
            'description' => $dashboard['description'] ?? null,
            'configuration' => $dashboard['configuration'] ?? $this->getDefaultConfiguration(),
            'widgets' => $this->processWidgets($dashboard['widgets'] ?? []),
            'is_default' => $dashboard['is_default'] ?? false,
            'is_active' => $dashboard['is_active'] ?? true,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        // If this is a default dashboard, unset other defaults
        if ($newDashboard['is_default']) {
            $this->unsetDefaultDashboards();
        }

        $this->dashboardsStorage[] = $newDashboard;
        $this->updateDashboardsStorage();

        Log::info("Dashboard created", [
            'dashboard_id' => $newDashboard['id'],
            'tenant_id' => $tenantId,
            'name' => $newDashboard['name'],
            'widget_count' => count($newDashboard['widgets']),
        ]);

        return $newDashboard;
    }

    /**
     * Update an existing dashboard
     *
     * @param string $dashboardId Dashboard ID
     * @param array $updates Dashboard updates
     * @return array Updated dashboard
     */
    public function updateDashboard(string $dashboardId, array $updates): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        
        $dashboardIndex = $this->findDashboardIndexById($dashboardId);
        
        if ($dashboardIndex === null) {
            throw new Exception("Dashboard not found: {$dashboardId}");
        }

        $dashboard = &$this->dashboardsStorage[$dashboardIndex];

        // Update allowed fields
        if (isset($updates['name'])) {
            $dashboard['name'] = $updates['name'];
        }
        if (isset($updates['description'])) {
            $dashboard['description'] = $updates['description'];
        }
        if (isset($updates['configuration'])) {
            $dashboard['configuration'] = array_merge($dashboard['configuration'], $updates['configuration']);
        }
        if (isset($updates['widgets'])) {
            $dashboard['widgets'] = $this->processWidgets($updates['widgets']);
        }
        if (array_key_exists('is_default', $updates)) {
            if ($updates['is_default'] && !$dashboard['is_default']) {
                $this->unsetDefaultDashboards();
            }
            $dashboard['is_default'] = $updates['is_default'];
        }
        if (array_key_exists('is_active', $updates)) {
            $dashboard['is_active'] = $updates['is_active'];
        }

        $dashboard['updated_at'] = now()->toIso8601String();

        // Clear cache
        $cacheKey = $this->getDashboardCacheKey($dashboardId);
        Cache::forget($cacheKey);

        $this->updateDashboardsStorage();

        Log::info("Dashboard updated", [
            'dashboard_id' => $dashboardId,
            'tenant_id' => $tenantId,
            'updates' => array_keys($updates),
        ]);

        return $dashboard;
    }

    /**
     * Delete a dashboard
     *
     * @param string $dashboardId Dashboard ID
     * @return bool Success status
     */
    public function deleteDashboard(string $dashboardId): bool
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        
        $dashboardIndex = $this->findDashboardIndexById($dashboardId);
        
        if ($dashboardIndex === null) {
            throw new Exception("Dashboard not found: {$dashboardId}");
        }

        $dashboard = $this->dashboardsStorage[$dashboardIndex];
        
        // Clear widget caches
        foreach ($dashboard['widgets'] ?? [] as $widget) {
            $this->clearWidgetCache($widget);
        }

        // Clear dashboard cache
        $cacheKey = $this->getDashboardCacheKey($dashboardId);
        Cache::forget($cacheKey);

        // Remove dashboard
        array_splice($this->dashboardsStorage, $dashboardIndex, 1);
        $this->updateDashboardsStorage();

        Log::info("Dashboard deleted", [
            'dashboard_id' => $dashboardId,
            'tenant_id' => $tenantId,
        ]);

        return true;
    }

    // ==================== Widget Management ====================

    /**
     * Add a widget to a dashboard
     *
     * @param string $dashboardId Dashboard ID
     * @param array $widget Widget data (type, title, metric, configuration)
     * @return array Added widget
     */
    public function addWidget(string $dashboardId, array $widget): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        
        $dashboardIndex = $this->findDashboardIndexById($dashboardId);
        
        if ($dashboardIndex === null) {
            throw new Exception("Dashboard not found: {$dashboardId}");
        }

        $dashboard = &$this->dashboardsStorage[$dashboardIndex];

        // Check max widgets limit
        if (count($dashboard['widgets']) >= self::MAX_WIDGETS_PER_DASHBOARD) {
            throw new Exception('Maximum number of widgets per dashboard reached');
        }

        // Validate widget type
        $validTypes = [
            self::WIDGET_TYPE_METRIC_CARD,
            self::WIDGET_TYPE_CHART,
            self::WIDGET_TYPE_TABLE,
            self::WIDGET_TYPE_GAUGE,
            self::WIDGET_TYPE_MAP,
            self::WIDGET_TYPE_LIST,
        ];

        if (!in_array($widget['type'] ?? '', $validTypes)) {
            throw new Exception('Invalid widget type: ' . ($widget['type'] ?? 'unknown'));
        }

        $newWidget = [
            'id' => uniqid('widget_', true),
            'type' => $widget['type'],
            'title' => $widget['title'] ?? 'Untitled Widget',
            'metric' => $widget['metric'] ?? null,
            'configuration' => $widget['configuration'] ?? [],
            'position' => $widget['position'] ?? ['x' => 0, 'y' => 0, 'w' => 4, 'h' => 3],
            'chart_type' => $widget['chart_type'] ?? null,
            'data_source' => $widget['data_source'] ?? null,
            'filters' => $widget['filters'] ?? [],
            'created_at' => now()->toIso8601String(),
        ];

        $dashboard['widgets'][] = $newWidget;
        $dashboard['updated_at'] = now()->toIso8601String();

        $this->updateDashboardsStorage();

        Log::info("Widget added to dashboard", [
            'dashboard_id' => $dashboardId,
            'widget_id' => $newWidget['id'],
            'tenant_id' => $tenantId,
            'widget_type' => $newWidget['type'],
        ]);

        return $newWidget;
    }

    /**
     * Remove a widget from a dashboard
     *
     * @param string $dashboardId Dashboard ID
     * @param string $widgetId Widget ID
     * @return bool Success status
     */
    public function removeWidget(string $dashboardId, string $widgetId): bool
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        
        $dashboardIndex = $this->findDashboardIndexById($dashboardId);
        
        if ($dashboardIndex === null) {
            throw new Exception("Dashboard not found: {$dashboardId}");
        }

        $dashboard = &$this->dashboardsStorage[$dashboardIndex];

        $widgetIndex = $this->findWidgetIndexById($dashboard, $widgetId);
        
        if ($widgetIndex === null) {
            throw new Exception("Widget not found: {$widgetId}");
        }

        $widget = $dashboard['widgets'][$widgetIndex];
        
        // Clear widget cache
        $this->clearWidgetCache($widget);

        // Remove widget
        array_splice($dashboard['widgets'], $widgetIndex, 1);
        $dashboard['updated_at'] = now()->toIso8601String();

        $this->updateDashboardsStorage();

        Log::info("Widget removed from dashboard", [
            'dashboard_id' => $dashboardId,
            'widget_id' => $widgetId,
            'tenant_id' => $tenantId,
        ]);

        return true;
    }

    /**
     * Get dashboard templates
     *
     * @return array Dashboard templates
     */
    public function getDashboardTemplates(): array
    {
        return [
            [
                'id' => 'template_overview',
                'name' => 'Overview Dashboard',
                'description' => 'Comprehensive overview with key metrics and trends',
                'widgets' => [
                    [
                        'type' => self::WIDGET_TYPE_METRIC_CARD,
                        'title' => 'Total Users',
                        'metric' => 'total_users',
                        'position' => ['x' => 0, 'y' => 0, 'w' => 3, 'h' => 2],
                    ],
                    [
                        'type' => self::WIDGET_TYPE_METRIC_CARD,
                        'title' => 'Active Sessions',
                        'metric' => 'active_sessions',
                        'position' => ['x' => 3, 'y' => 0, 'w' => 3, 'h' => 2],
                    ],
                    [
                        'type' => self::WIDGET_TYPE_METRIC_CARD,
                        'title' => 'Conversion Rate',
                        'metric' => 'conversion_rate',
                        'position' => ['x' => 6, 'y' => 0, 'w' => 3, 'h' => 2],
                    ],
                    [
                        'type' => self::WIDGET_TYPE_METRIC_CARD,
                        'title' => 'Revenue',
                        'metric' => 'total_revenue',
                        'position' => ['x' => 9, 'y' => 0, 'w' => 3, 'h' => 2],
                    ],
                    [
                        'type' => self::WIDGET_TYPE_CHART,
                        'title' => 'User Growth',
                        'metric' => 'user_registration_trend',
                        'chart_type' => self::CHART_TYPE_LINE,
                        'position' => ['x' => 0, 'y' => 2, 'w' => 6, 'h' => 4],
                    ],
                    [
                        'type' => self::WIDGET_TYPE_CHART,
                        'title' => 'Revenue Trend',
                        'metric' => 'revenue_trend',
                        'chart_type' => self::CHART_TYPE_AREA,
                        'position' => ['x' => 6, 'y' => 2, 'w' => 6, 'h' => 4],
                    ],
                ],
            ],
            [
                'id' => 'template_engagement',
                'name' => 'Engagement Dashboard',
                'description' => 'Track user engagement and activity metrics',
                'widgets' => [
                    [
                        'type' => self::WIDGET_TYPE_METRIC_CARD,
                        'title' => 'Daily Active Users',
                        'metric' => 'daily_active_users',
                        'position' => ['x' => 0, 'y' => 0, 'w' => 4, 'h' => 2],
                    ],
                    [
                        'type' => self::WIDGET_TYPE_METRIC_CARD,
                        'title' => 'Avg Session Duration',
                        'metric' => 'avg_session_duration',
                        'position' => ['x' => 4, 'y' => 0, 'w' => 4, 'h' => 2],
                    ],
                    [
                        'type' => self::WIDGET_TYPE_METRIC_CARD,
                        'title' => 'Page Views',
                        'metric' => 'total_page_views',
                        'position' => ['x' => 8, 'y' => 0, 'w' => 4, 'h' => 2],
                    ],
                    [
                        'type' => self::WIDGET_TYPE_CHART,
                        'title' => 'Activity by Hour',
                        'metric' => 'activity_by_hour',
                        'chart_type' => self::CHART_TYPE_BAR,
                        'position' => ['x' => 0, 'y' => 2, 'w' => 12, 'h' => 5],
                    ],
                ],
            ],
            [
                'id' => 'template_performance',
                'name' => 'Performance Dashboard',
                'description' => 'System performance and monitoring metrics',
                'widgets' => [
                    [
                        'type' => self::WIDGET_TYPE_GAUGE,
                        'title' => 'CPU Usage',
                        'metric' => 'cpu_usage',
                        'position' => ['x' => 0, 'y' => 0, 'w' => 4, 'h' => 3],
                    ],
                    [
                        'type' => self::WIDGET_TYPE_GAUGE,
                        'title' => 'Memory Usage',
                        'metric' => 'memory_usage',
                        'position' => ['x' => 4, 'y' => 0, 'w' => 4, 'h' => 3],
                    ],
                    [
                        'type' => self::WIDGET_TYPE_GAUGE,
                        'title' => 'Disk Usage',
                        'metric' => 'disk_usage',
                        'position' => ['x' => 8, 'y' => 0, 'w' => 4, 'h' => 3],
                    ],
                    [
                        'type' => self::WIDGET_TYPE_CHART,
                        'title' => 'Response Time Trend',
                        'metric' => 'response_time_trend',
                        'chart_type' => self::CHART_TYPE_LINE,
                        'position' => ['x' => 0, 'y' => 3, 'w' => 12, 'h' => 4],
                    ],
                ],
            ],
            [
                'id' => 'template_sales',
                'name' => 'Sales Dashboard',
                'description' => 'Sales metrics and revenue tracking',
                'widgets' => [
                    [
                        'type' => self::WIDGET_TYPE_METRIC_CARD,
                        'title' => 'Total Sales',
                        'metric' => 'total_sales',
                        'position' => ['x' => 0, 'y' => 0, 'w' => 3, 'h' => 2],
                    ],
                    [
                        'type' => self::WIDGET_TYPE_METRIC_CARD,
                        'title' => 'New Customers',
                        'metric' => 'new_customers',
                        'position' => ['x' => 3, 'y' => 0, 'w' => 3, 'h' => 2],
                    ],
                    [
                        'type' => self::WIDGET_TYPE_METRIC_CARD,
                        'title' => 'Avg Order Value',
                        'metric' => 'avg_order_value',
                        'position' => ['x' => 6, 'y' => 0, 'w' => 3, 'h' => 2],
                    ],
                    [
                        'type' => self::WIDGET_TYPE_METRIC_CARD,
                        'title' => 'Conversion Rate',
                        'metric' => 'sales_conversion_rate',
                        'position' => ['x' => 9, 'y' => 0, 'w' => 3, 'h' => 2],
                    ],
                    [
                        'type' => self::WIDGET_TYPE_CHART,
                        'title' => 'Sales by Category',
                        'metric' => 'sales_by_category',
                        'chart_type' => self::CHART_TYPE_PIE,
                        'position' => ['x' => 0, 'y' => 2, 'w' => 6, 'h' => 5],
                    ],
                    [
                        'type' => self::WIDGET_TYPE_CHART,
                        'title' => 'Sales Trend',
                        'metric' => 'sales_trend',
                        'chart_type' => self::CHART_TYPE_BAR,
                        'position' => ['x' => 6, 'y' => 2, 'w' => 6, 'h' => 5],
                    ],
                ],
            ],
        ];
    }

    // ==================== Dashboard Listing ====================

    /**
     * Get all dashboards for the current tenant
     *
     * @param bool $activeOnly Only return active dashboards
     * @return array List of dashboards
     */
    public function getAllDashboards(bool $activeOnly = false): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        $dashboards = $this->getDashboardsFromStorage();

        // Filter by tenant
        $dashboards = array_filter($dashboards, function ($dashboard) use ($tenantId) {
            return ($dashboard['tenant_id'] ?? null) === $tenantId;
        });

        // Filter active only if requested
        if ($activeOnly) {
            $dashboards = array_filter($dashboards, function ($dashboard) {
                return $dashboard['is_active'] ?? true;
            });
        }

        // Re-index array
        return array_values($dashboards);
    }

    /**
     * Get a dashboard by ID
     *
     * @param string $dashboardId Dashboard ID
     * @return array|null Dashboard data or null if not found
     */
    public function getDashboardById(string $dashboardId): ?array
    {
        $dashboards = $this->getAllDashboards();

        foreach ($dashboards as $dashboard) {
            if ($dashboard['id'] === $dashboardId) {
                return $dashboard;
            }
        }

        return null;
    }

    // ==================== Private Helper Methods ====================

    /**
     * Get the default configuration
     *
     * @return array Default configuration
     */
    private function getDefaultConfig(): array
    {
        return [
            'max_dashboards' => self::MAX_DASHBOARDS,
            'max_widgets_per_dashboard' => self::MAX_WIDGETS_PER_DASHBOARD,
            'cache_ttl' => self::DASHBOARD_CACHE_TTL,
            'default_date_range' => self::DATE_RANGE_LAST_30_DAYS,
            'auto_refresh' => true,
            'refresh_interval' => 300, // 5 minutes
        ];
    }

    /**
     * Get default dashboard configuration
     *
     * @return array Default configuration
     */
    private function getDefaultConfiguration(): array
    {
        return [
            'refresh_interval' => 300,
            'date_range' => self::DATE_RANGE_LAST_30_DAYS,
            'layout' => 'grid',
            'theme' => 'light',
            'show_header' => true,
            'show_filters' => true,
        ];
    }

    /**
     * Get default date range
     *
     * @return array Default date range
     */
    private function getDefaultDateRange(): array
    {
        return [
            'start' => now()->subDays(30)->startOfDay()->toIso8601String(),
            'end' => now()->endOfDay()->toIso8601String(),
            'preset' => self::DATE_RANGE_LAST_30_DAYS,
        ];
    }

    /**
     * Resolve date range from input
     *
     * @param array $dateRange Input date range
     * @return array Resolved date range
     */
    private function resolveDateRange(array $dateRange): array
    {
        if (empty($dateRange)) {
            return $this->getDefaultDateRange();
        }

        // Handle preset values
        if (isset($dateRange['preset'])) {
            return $this->getDateRangeFromPreset($dateRange['preset']);
        }

        return [
            'start' => $dateRange['start'] ?? now()->subDays(30)->startOfDay()->toIso8601String(),
            'end' => $dateRange['end'] ?? now()->endOfDay()->toIso8601String(),
            'preset' => self::DATE_RANGE_CUSTOM,
        ];
    }

    /**
     * Get date range from preset
     *
     * @param string $preset Preset name
     * @return array Date range
     */
    private function getDateRangeFromPreset(string $preset): array
    {
        $now = now();
        
        return match ($preset) {
            self::DATE_RANGE_TODAY => [
                'start' => $now->startOfDay()->toIso8601String(),
                'end' => $now->endOfDay()->toIso8601String(),
                'preset' => $preset,
            ],
            self::DATE_RANGE_YESTERDAY => [
                'start' => $now->subDay()->startOfDay()->toIso8601String(),
                'end' => $now->subDay()->endOfDay()->toIso8601String(),
                'preset' => $preset,
            ],
            self::DATE_RANGE_LAST_7_DAYS => [
                'start' => $now->subDays(7)->startOfDay()->toIso8601String(),
                'end' => $now->endOfDay()->toIso8601String(),
                'preset' => $preset,
            ],
            self::DATE_RANGE_LAST_30_DAYS => [
                'start' => $now->subDays(30)->startOfDay()->toIso8601String(),
                'end' => $now->endOfDay()->toIso8601String(),
                'preset' => $preset,
            ],
            self::DATE_RANGE_LAST_90_DAYS => [
                'start' => $now->subDays(90)->startOfDay()->toIso8601String(),
                'end' => $now->endOfDay()->toIso8601String(),
                'preset' => $preset,
            ],
            self::DATE_RANGE_THIS_MONTH => [
                'start' => $now->startOfMonth()->toIso8601String(),
                'end' => $now->endOfDay()->toIso8601String(),
                'preset' => $preset,
            ],
            self::DATE_RANGE_LAST_MONTH => [
                'start' => $now->subMonth()->startOfMonth()->toIso8601String(),
                'end' => $now->subMonth()->endOfMonth()->toIso8601String(),
                'preset' => $preset,
            ],
            self::DATE_RANGE_THIS_YEAR => [
                'start' => $now->startOfYear()->toIso8601String(),
                'end' => $now->endOfDay()->toIso8601String(),
                'preset' => $preset,
            ],
            default => $this->getDefaultDateRange(),
        };
    }

    /**
     * Process widgets and add IDs if missing
     *
     * @param array $widgets Widgets to process
     * @return array Processed widgets
     */
    private function processWidgets(array $widgets): array
    {
        return array_map(function ($widget) {
            if (!isset($widget['id'])) {
                $widget['id'] = uniqid('widget_', true);
            }
            if (!isset($widget['created_at'])) {
                $widget['created_at'] = now()->toIso8601String();
            }
            return $widget;
        }, $widgets);
    }

    /**
     * Get widget data internally
     *
     * @param array $widget Widget configuration
     * @param array $dateRange Date range
     * @return array Widget data
     */
    private function getWidgetDataInternal(array $widget, array $dateRange): array
    {
        $cacheKey = $this->getWidgetCacheKey($widget['id'] ?? uniqid());
        
        // Try to get from cache
        $cachedData = Cache::get($cacheKey);
        if ($cachedData && isset($cachedData['cached_at'])) {
            $cacheAge = now()->diffInSeconds(now()->parse($cachedData['cached_at']));
            if ($cacheAge < ($this->dashboardConfig['cache_ttl'] ?? self::DASHBOARD_CACHE_TTL)) {
                return $cachedData;
            }
        }

        // Fetch fresh data based on widget type
        $widgetData = [
            'id' => $widget['id'],
            'type' => $widget['type'],
            'title' => $widget['title'],
            'metric' => $widget['metric'] ?? null,
            'chart_type' => $widget['chart_type'] ?? null,
            'date_range' => $dateRange,
            'cached_at' => now()->toIso8601String(),
        ];

        // Get metric data if metric is specified
        if (!empty($widget['metric'])) {
            try {
                $metricData = $this->metricsCollectionService->calculateMetric(
                    $widget['metric'],
                    [
                        'since' => $dateRange['start'],
                        'until' => $dateRange['end'],
                        'aggregation' => self::AGGREGATION_SUM,
                    ]
                );
                $widgetData['value'] = $metricData['result'] ?? 0;
                $widgetData['statistics'] = $metricData['statistics'] ?? [];
            } catch (Throwable $e) {
                $widgetData['value'] = 0;
                $widgetData['error'] = $e->getMessage();
            }
        } else {
            $widgetData['value'] = $this->getDefaultValueForWidgetType($widget['type']);
        }

        // Add chart data if applicable
        if ($widget['type'] === self::WIDGET_TYPE_CHART && !empty($widget['metric'])) {
            try {
                $trends = $this->metricsCollectionService->getMetricTrends(
                    $widget['metric'],
                    $dateRange,
                    AnalyticsMetricsCollectionService::TIME_GRAIN_DAY
                );
                $widgetData['chart_data'] = $trends['trends'] ?? [];
            } catch (Throwable $e) {
                $widgetData['chart_data'] = [];
                $widgetData['error'] = $e->getMessage();
            }
        }

        // Cache the widget data
        Cache::put($cacheKey, $widgetData, $this->dashboardConfig['cache_ttl'] ?? self::DASHBOARD_CACHE_TTL);

        return $widgetData;
    }

    /**
     * Get default value for widget type
     *
     * @param string $widgetType Widget type
     * @return mixed Default value
     */
    private function getDefaultValueForWidgetType(string $widgetType): mixed
    {
        return match ($widgetType) {
            self::WIDGET_TYPE_METRIC_CARD, self::WIDGET_TYPE_GAUGE => 0,
            self::WIDGET_TYPE_CHART => [],
            self::WIDGET_TYPE_TABLE => ['headers' => [], 'rows' => []],
            self::WIDGET_TYPE_LIST => [],
            self::WIDGET_TYPE_MAP => ['markers' => []],
            default => null,
        };
    }

    /**
     * Find widget by ID across all dashboards
     *
     * @param string $widgetId Widget ID
     * @return array|null Widget configuration or null
     */
    private function findWidgetById(string $widgetId): ?array
    {
        $dashboards = $this->getAllDashboards();

        foreach ($dashboards as $dashboard) {
            foreach ($dashboard['widgets'] ?? [] as $widget) {
                if ($widget['id'] === $widgetId) {
                    return $widget;
                }
            }
        }

        return null;
    }

    /**
     * Find widget index in dashboard
     *
     * @param array $dashboard Dashboard
     * @param string $widgetId Widget ID
     * @return int|null Widget index or null
     */
    private function findWidgetIndexById(array &$dashboard, string $widgetId): ?int
    {
        foreach ($dashboard['widgets'] ?? [] as $index => $widget) {
            if ($widget['id'] === $widgetId) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Find dashboard index by ID
     *
     * @param string $dashboardId Dashboard ID
     * @return int|null Dashboard index or null
     */
    private function findDashboardIndexById(string $dashboardId): ?int
    {
        foreach ($this->dashboardsStorage as $index => $dashboard) {
            if ($dashboard['id'] === $dashboardId) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Unset all default dashboards
     */
    private function unsetDefaultDashboards(): void
    {
        foreach ($this->dashboardsStorage as &$dashboard) {
            $dashboard['is_default'] = false;
        }
    }

    /**
     * Get dashboard cache key
     *
     * @param string $dashboardId Dashboard ID
     * @return string Cache key
     */
    private function getDashboardCacheKey(string $dashboardId): string
    {
        return 'dashboard_' . $dashboardId;
    }

    /**
     * Get widget cache key
     *
     * @param string $widgetId Widget ID
     * @return string Cache key
     */
    private function getWidgetCacheKey(string $widgetId): string
    {
        return 'widget_' . $widgetId;
    }

    /**
     * Refresh widget cache
     *
     * @param array $widget Widget
     */
    private function refreshWidgetCache(array $widget): void
    {
        $cacheKey = $this->getWidgetCacheKey($widget['id'] ?? '');
        Cache::forget($cacheKey);
    }

    /**
     * Clear widget cache
     *
     * @param array $widget Widget
     */
    private function clearWidgetCache(array $widget): void
    {
        $cacheKey = $this->getWidgetCacheKey($widget['id'] ?? '');
        Cache::forget($cacheKey);
    }

    /**
     * Get dashboards from storage
     *
     * @return array Dashboards from storage
     */
    private function getDashboardsFromStorage(): array
    {
        if (empty($this->dashboardsStorage)) {
            $this->dashboardsStorage = Cache::get(self::DASHBOARD_CACHE_KEY, []);
        }

        return $this->dashboardsStorage;
    }

    /**
     * Update dashboards storage
     */
    private function updateDashboardsStorage(): void
    {
        Cache::put(self::DASHBOARD_CACHE_KEY, $this->dashboardsStorage, $this->dashboardConfig['cache_ttl'] ?? self::DASHBOARD_CACHE_TTL);
    }

    /**
     * Aggregate values using specified aggregation
     *
     * @param array $values Values to aggregate
     * @param string $aggregation Aggregation type
     * @return mixed Aggregated value
     */
    private function aggregateValues(array $values, string $aggregation): mixed
    {
        if (empty($values)) {
            return 0;
        }

        return match ($aggregation) {
            self::AGGREGATION_SUM => array_sum($values),
            self::AGGREGATION_AVG => array_sum($values) / count($values),
            self::AGGREGATION_MIN => min($values),
            self::AGGREGATION_MAX => max($values),
            self::AGGREGATION_COUNT => count($values),
            default => array_sum($values) / count($values),
        };
    }

    /**
     * Calculate statistics for values
     *
     * @param array $values Values
     * @return array Statistics
     */
    private function calculateStatistics(array $values): array
    {
        if (empty($values)) {
            return [
                'count' => 0,
                'sum' => 0,
                'avg' => 0,
                'min' => 0,
                'max' => 0,
            ];
        }

        $count = count($values);
        $sum = array_sum($values);

        return [
            'count' => $count,
            'sum' => $sum,
            'avg' => $sum / $count,
            'min' => min($values),
            'max' => max($values),
        ];
    }
}
