<?php

namespace App\Services;

use App\Models\TemplateAnalyticsEvent;
use App\Models\Template;
use App\Models\LandingPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Template Analytics Service
 *
 * Core business logic for template analytics tracking, reporting, and conversion analysis.
 * Handles event tracking, session management, and comprehensive analytics reporting.
 */
class TemplateAnalyticsService
{
    /**
     * Cache keys and durations
     */
    private const CACHE_PREFIX = 'template_analytics_';
    private const CACHE_DURATION = 300; // 5 minutes
    private const REPORT_CACHE_DURATION = 1800; // 30 minutes

    /**
     * Track analytics event for template or landing page
     *
     * @param array $eventData Event data including type, user info, and metadata
     * @return TemplateAnalyticsEvent|bool
     */
    public function trackEvent(array $eventData): TemplateAnalyticsEvent|bool
    {
        try {
            // Validate required data
            $this->validateEventData($eventData);

            // Enhance event data with derived information
            $enhancedData = $this->enhanceEventData($eventData);

            // Add GDPR compliance data
            $enhancedData = $this->addGdprComplianceData($enhancedData);

            $event = TemplateAnalyticsEvent::create($enhancedData);

            // Update related model statistics
            $this->updateRelatedModelStats($event);

            // Broadcast real-time event
            $this->broadcastRealTimeEvent($event);

            return $event;

        } catch (\Exception $e) {
            Log::error('Analytics event tracking failed', [
                'event_data' => $eventData,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Track multiple events in batch
     *
     * @param array $events Array of event data
     * @return array Results of event tracking
     */
    public function trackEvents(array $events): array
    {
        $results = [];

        foreach ($events as $eventData) {
            try {
                $result = $this->trackEvent($eventData);
                $results[] = [
                    'event_type' => $eventData['event_type'] ?? 'unknown',
                    'success' => $result !== false,
                    'event_id' => $result ? $result->id : null,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'event_type' => $eventData['event_type'] ?? 'unknown',
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get analytics statistics for a template
     *
     * @param int $templateId
     * @param array $options Optional filters (date_range, event_types, etc.)
     * @return array
     */
    public function getTemplateAnalytics(int $templateId, array $options = []): array
    {
        $cacheKey = self::CACHE_PREFIX . 'template_' . $templateId . '_' . md5(serialize($options));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($templateId, $options) {
            $query = TemplateAnalyticsEvent::forTemplate($templateId);

            // Apply filters
            $query = $this->applyAnalyticsFilters($query, $options);

            $basicStats = $this->generateBasicStats($query);
            $conversionStats = $this->generateConversionStats($query);
            $engagementStats = $this->generateEngagementStats($query);
            $deviceStats = $this->generateDeviceStats($query);

            return array_merge($basicStats, $conversionStats, $engagementStats, $deviceStats);
        });
    }

    /**
     * Get analytics statistics for a landing page
     *
     * @param int $landingPageId
     * @param array $options Optional filters
     * @return array
     */
    public function getLandingPageAnalytics(int $landingPageId, array $options = []): array
    {
        $cacheKey = self::CACHE_PREFIX . 'landing_page_' . $landingPageId . '_' . md5(serialize($options));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($landingPageId, $options) {
            $query = TemplateAnalyticsEvent::forLandingPage($landingPageId);

            // Apply filters
            $query = $this->applyAnalyticsFilters($query, $options);

            $basicStats = $this->generateBasicStats($query);
            $conversionStats = $this->generateConversionStats($query);
            $engagementStats = $this->generateEngagementStats($query);
            $deviceStats = $this->generateDeviceStats($query);

            return array_merge($basicStats, $conversionStats, $engagementStats, $deviceStats);
        });
    }

    /**
     * Get comprehensive analytics report
     *
     * @param array $options Report options (date_range, templates, metrics, etc.)
     * @return array
     */
    public function getAnalyticsReport(array $options = []): array
    {
        $cacheKey = self::CACHE_PREFIX . 'report_' . md5(serialize($options));

        return Cache::remember($cacheKey, self::REPORT_CACHE_DURATION, function () use ($options) {
            $reports = [];

            if (isset($options['templates']) && is_array($options['templates'])) {
                foreach ($options['templates'] as $templateId) {
                    $reports['templates'][$templateId] = $this->getTemplateAnalytics($templateId, $options);
                }
            }

            if (isset($options['landing_pages']) && is_array($options['landing_pages'])) {
                foreach ($options['landing_pages'] as $landingPageId) {
                    $reports['landing_pages'][$landingPageId] = $this->getLandingPageAnalytics($landingPageId, $options);
                }
            }

            return [
                'summary' => $this->generateSummaryReport($reports),
                'detailed' => $reports,
                'generated_at' => now()->toISOString(),
                'options' => $options,
            ];
        });
    }

    /**
     * Generate tracking code for a landing page or template
     *
     * @param int $templateId
     * @param int|null $landingPageId
     * @return string JavaScript tracking code
     */
    public function generateTrackingCode(int $templateId, ?int $landingPageId = null): string
    {
        $template = Template::findOrFail($templateId);

        $trackingParams = json_encode([
            'template_id' => $templateId,
            'tenant_id' => $template->tenant_id,
            'landing_page_id' => $landingPageId,
            'api_endpoint' => route('api.analytics.events.track'),
            'session_id' => '{{SESSION_ID}}', // To be replaced by frontend
        ]);

        return "
            <!-- Template Analytics Tracking -->
            <script>
                (function() {
                    var _etConfig = {$trackingParams};
                    var _et_sessionId = localStorage.getItem('et_session_id');

                    if (!_et_sessionId) {
                        _et_sessionId = Date.now().toString(36) + Math.random().toString(36).substr(2);
                        localStorage.setItem('et_session_id', _et_sessionId);
                    }

                    _etConfig.session_id = _et_sessionId;

                    function trackEvent(eventType, eventData) {
                        fetch(_etConfig.api_endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content') || '',
                            },
                            body: JSON.stringify({
                                event_type: eventType,
                                template_id: _etConfig.template_id,
                                landing_page_id: _etConfig.landing_page_id,
                                event_data: eventData,
                                session_id: _etConfig.session_id,
                                referrer_url: document.referrer,
                                user_agent: navigator.userAgent,
                                timestamp: new Date().toISOString(),
                                user_identifier: getUniqueUserId(),
                                device_info: getDeviceInfo(),
                            })
                        });
                    }

                    function getUniqueUserId() {
                        var userId = localStorage.getItem('et_user_id');
                        if (!userId) {
                            userId = Date.now().toString(36) + Math.random().toString(36).substr(2);
                            localStorage.setItem('et_user_id', userId);
                        }
                        return userId;
                    }

                    function getDeviceInfo() {
                        return {
                            user_agent: navigator.userAgent,
                            viewport_width: window.innerWidth,
                            viewport_height: window.innerHeight,
                            device_pixel_ratio: window.devicePixelRatio,
                            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                        };
                    }

                    // Auto-track page view
                    trackEvent('page_view', {
                        page_title: document.title,
                        page_url: window.location.href,
                        load_time: performance.now(),
                    });

                    // Track scroll depth
                    var maxScroll = 0;
                    var scrollTracked = false;
                    window.addEventListener('scroll', function() {
                        var scrollPercent = Math.round((window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100);
                        if (scrollPercent > maxScroll) {
                            maxScroll = scrollPercent;
                        }

                        if (scrollPercent >= 75 && !scrollTracked) {
                            trackEvent('scroll', {
                                depth_percent: scrollPercent,
                                scroll_position: window.scrollY,
                            });
                            scrollTracked = true;
                        }
                    });

                    // Track time on page
                    var startTime = Date.now();
                    window.addEventListener('beforeunload', function() {
                        var duration = Math.round((Date.now() - startTime) / 1000);
                        trackEvent('time_on_page', {
                            duration_seconds: duration,
                        });
                    });

                    // Expose tracking function globally
                    window._etTrack = trackEvent;

                    // Track form submissions
                    document.addEventListener('submit', function(e) {
                        var formData = new FormData(e.target);
                        var formFields = {};
                        for (var [key, value] of formData.entries()) {
                            formFields[key] = value.length > 0 ? '[FILLED]' : '[EMPTY]';
                        }

                        trackEvent('form_submit', {
                            form_id: e.target.id || 'unknown',
                            form_action: e.target.action,
                            form_fields_count: Object.keys(formFields).length,
                        });
                    });

                    // Track outgoing clicks
                    document.addEventListener('click', function(e) {
                        var target = e.target.closest('a');
                        if (target && target.href) {
                            var url = new URL(target.href);
                            if (url.host !== window.location.host) {
                                trackEvent('link_click', {
                                    href: target.href,
                                    link_text: target.textContent?.trim() || '',
                                    link_position: getElementPosition(target),
                                });
                            }
                        }
                    });

                    // Track CTA button clicks
                    document.querySelectorAll('[data-cta], .cta-button, .btn-primary').forEach(function(el) {
                        el.addEventListener('click', function() {
                            trackEvent('cta_click', {
                                cta_text: el.textContent?.trim() || '',
                                cta_position: getElementPosition(el),
                                cta_type: el.dataset.cta || 'general',
                            });
                        });
                    });
                })();

                function getElementPosition(element) {
                    var rect = element.getBoundingClientRect();
                    return {
                        x: Math.round(rect.left),
                        y: Math.round(rect.top),
                        width: Math.round(rect.width),
                        height: Math.round(rect.height),
                    };
                }
            </script>
        ";
    }

    /**
     * Validate event data before creating event
     *
     * @param array $eventData
     * @throws \InvalidArgumentException
     */
    private function validateEventData(array $eventData): void
    {
        if (empty($eventData['event_type']) ||
            !in_array($eventData['event_type'], TemplateAnalyticsEvent::EVENT_TYPES)) {
            throw new \InvalidArgumentException('Invalid event type provided');
        }

        if (empty($eventData['template_id'])) {
            throw new \InvalidArgumentException('Template ID is required');
        }
    }

    /**
     * Enhance event data with derived information
     *
     * @param array $eventData
     * @return array
     */
    private function enhanceEventData(array $eventData): array
    {
        // Get current tenant
        $tenant = tenant();
        if (!$tenant) {
            throw new \RuntimeException('No tenant context available');
        }

        // Set tenant ID
        $eventData['tenant_id'] = $tenant->id;

        // Generate session ID if not provided
        if (empty($eventData['session_id'])) {
            $eventData['session_id'] = $this->generateSessionId();
        }

        // Parse device info from user agent
        if (!empty($eventData['user_agent'])) {
            $eventData['device_info'] = $this->parseDeviceInfo($eventData['user_agent']);
        }

        // Set timestamp if not provided
        if (empty($eventData['timestamp'])) {
            $eventData['timestamp'] = now();
        } elseif (is_string($eventData['timestamp'])) {
            $eventData['timestamp'] = Carbon::parse($eventData['timestamp']);
        }

        // Determine IP address if not provided
        if (empty($eventData['ip_address'])) {
            $eventData['ip_address'] = request()->ip();
        }

        return $eventData;
    }

    /**
     * Add GDPR compliance data to event
     *
     * @param array $eventData
     * @return array
     */
    private function addGdprComplianceData(array $eventData): array
    {
        // Set GDPR compliance defaults
        $eventData['is_compliant'] = $eventData['is_compliant'] ?? true;
        $eventData['consent_given'] = $eventData['consent_given'] ?? true;
        $eventData['analytics_version'] = $eventData['analytics_version'] ?? 'v1.0';

        // Set data retention period (7 years for analytics data)
        if (empty($eventData['data_retention_until'])) {
            $eventData['data_retention_until'] = now()->addYears(7);
        } elseif (is_string($eventData['data_retention_until'])) {
            $eventData['data_retention_until'] = Carbon::parse($eventData['data_retention_until']);
        }

        return $eventData;
    }

    /**
     * Update related model statistics after event creation
     *
     * @param TemplateAnalyticsEvent $event
     */
    private function updateRelatedModelStats(TemplateAnalyticsEvent $event): void
    {
        try {
            if ($event->template_id) {
                // Update template stats
                if ($event->isConversion()) {
                    $this->incrementConversionCount($event->template_id, $event->conversion_value);
                }

                if ($event->event_type === 'page_view') {
                    $this->incrementUsageCount($event->template_id);
                }
            }

            if ($event->landing_page_id) {
                // Update landing page stats
                if ($event->isConversion()) {
                    $this->incrementLandingPageConversion($event->landing_page_id, $event->conversion_value);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update related model stats', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate basic analytics statistics
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return array
     */
    private function generateBasicStats($query): array
    {
        return [
            'total_events' => $query->count(),
            'page_views' => $query->byEventType('page_view')->count(),
            'unique_sessions' => $query->distinct('session_id')->count('session_id'),
            'unique_users' => $query->distinct('user_identifier')->count('user_identifier'),
            'events_today' => $query->today()->count(),
            'events_last_week' => $query->dateRange(now()->subWeek(), now())->count(),
            'events_last_month' => $query->dateRange(now()->subMonth(), now())->count(),
        ];
    }

    /**
     * Generate conversion-related statistics
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return array
     */
    private function generateConversionStats($query): array
    {
        $conversionCount = $query->conversions()->count();
        $totalConversionValue = $query->conversions()->sum('conversion_value');
        $conversionRate = $this->calculateConversionRate($query);

        return [
            'conversion_count' => $conversionCount,
            'total_conversion_value' => $totalConversionValue,
            'conversion_rate' => $conversionRate,
            'conversion_funnel' => $this->getConversionFunnel($query),
        ];
    }

    /**
     * Generate engagement statistics
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return array
     */
    private function generateEngagementStats($query): array
    {
        return [
            'click_events' => $query->byEventType('click')->count(),
            'form_submissions' => $query->byEventType('form_submit')->count(),
            'cta_clicks' => $query->byEventType('cta_click')->count(),
            'average_scroll_depth' => $this->calculateAverageScrollDepth($query),
            'average_time_on_page' => $this->calculateAverageTimeOnPage($query),
            'exit_rate' => $this->calculateExitRate($query),
        ];
    }

    /**
     * Generate device and browser statistics
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return array
     */
    private function generateDeviceStats($query): array
    {
        $events = $query->withUserAgent()->get();

        $deviceStats = [];
        $browserStats = [];

        foreach ($events as $event) {
            $parsed = $event->parseUserAgent();

            $deviceType = $parsed['type'] ?? 'unknown';
            $browser = $parsed['browser'] ?? 'unknown';

            $deviceStats[$deviceType] = ($deviceStats[$deviceType] ?? 0) + 1;
            $browserStats[$browser] = ($browserStats[$browser] ?? 0) + 1;
        }

        return [
            'device_breakdown' => $deviceStats,
            'browser_breakdown' => $browserStats,
        ];
    }

    /**
     * Generate summary report from detailed reports
     *
     * @param array $reports
     * @return array
     */
    private function generateSummaryReport(array $reports): array
    {
        $summary = [
            'total_templates' => count($reports['templates'] ?? []),
            'total_landing_pages' => count($reports['landing_pages'] ?? []),
            'total_events' => 0,
            'total_conversions' => 0,
            'total_conversion_value' => 0,
            'average_conversion_rate' => 0,
        ];

        // Aggregate template stats
        if (!empty($reports['templates'])) {
            foreach ($reports['templates'] as $template) {
                $summary['total_events'] += $template['total_events'] ?? 0;
                $summary['total_conversions'] += $template['conversion_count'] ?? 0;
                $summary['total_conversion_value'] += $template['total_conversion_value'] ?? 0;
            }

            if ($summary['total_templates'] > 0) {
                $conversionRates = array_column($reports['templates'], 'conversion_rate');
                $summary['average_conversion_rate'] = round(array_sum($conversionRates) / count($conversionRates), 2);
            }
        }

        return $summary;
    }

    /**
     * Apply filters to analytics query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyAnalyticsFilters($query, array $options)
    {
        // Date range filter
        if (isset($options['date_from']) && isset($options['date_to'])) {
            $query->dateRange($options['date_from'], $options['date_to']);
        }

        // Event type filter
        if (isset($options['event_types']) && is_array($options['event_types'])) {
            $query->whereIn('event_type', $options['event_types']);
        }

        // Session filter
        if (isset($options['session_id'])) {
            $query->bySession($options['session_id']);
        }

        // User filter
        if (isset($options['user_identifier'])) {
            $query->where('user_identifier', $options['user_identifier']);
        }

        return $query;
    }

    /**
     * Increment conversion count for template
     *
     * @param int $templateId
     * @param float $value
     */
    private function incrementConversionCount(int $templateId, float $value): void
    {
        $template = Template::findOrFail($templateId);
        $currentMetrics = $template->performance_metrics ?? [];
        $currentMetrics['conversion_count'] = ($currentMetrics['conversion_count'] ?? 0) + 1;
        $currentMetrics['total_conversion_value'] = ($currentMetrics['total_conversion_value'] ?? 0) + $value;
        $template->update(['performance_metrics' => $currentMetrics]);
    }

    /**
     * Increment usage count for template (already handled by template, just clear cache)
     *
     * @param int $templateId
     */
    private function incrementUsageCount(int $templateId): void
    {
        Cache::forget('templates_*' . $templateId . '*');
    }

    /**
     * Increment conversion for landing page
     *
     * @param int $landingPageId
     * @param float $value
     */
    private function incrementLandingPageConversion(int $landingPageId, float $value): void
    {
        $landingPage = LandingPage::findOrFail($landingPageId);
        $landingPage->increment('conversion_count');
        $landingPage->increment('total_conversion_value', $value);
    }

    /**
     * Generate a unique session ID
     *
     * @return string
     */
    private function generateSessionId(): string
    {
        return Str::random(32);
    }

    /**
     * Parse device information from user agent
     *
     * @param string $userAgent
     * @return array
     */
    private function parseDeviceInfo(string $userAgent): array
    {
        // Basic device detection (can be enhanced with a proper library)
        $userAgentLower = strtolower($userAgent);

        $isMobile = strpos($userAgentLower, 'mobile') !== false ||
                   strpos($userAgentLower, 'android') !== false ||
                   strpos($userAgentLower, 'iphone') !== false;

        $isTablet = strpos($userAgentLower, 'tablet') !== false ||
                   strpos($userAgentLower, 'ipad') !== false;

        $deviceType = $isTablet ? 'tablet' : ($isMobile ? 'mobile' : 'desktop');

        return [
            'device_type' => $deviceType,
            'is_mobile' => $isMobile,
            'is_tablet' => $isTablet,
            'is_desktop' => !$isMobile && !$isTablet,
        ];
    }

    /**
     * Calculate conversion rate
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return float
     */
    private function calculateConversionRate($query): float
    {
        $totalEvents = $query->count();
        $conversionCount = $query->conversions()->count();

        if ($totalEvents === 0) {
            return 0.0;
        }

        return round(($conversionCount / $totalEvents) * 100, 2);
    }

    /**
     * Calculate average scroll depth
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return int
     */
    private function calculateAverageScrollDepth($query): int
    {
        $scrollEvents = $query->byEventType('scroll')->get();
        $totalDepth = 0;
        $count = 0;

        foreach ($scrollEvents as $event) {
            $depth = $event->getScrollDepth();
            if ($depth > 0) {
                $totalDepth += $depth;
                $count++;
            }
        }

        return $count > 0 ? round($totalDepth / $count) : 0;
    }

    /**
     * Calculate average time on page
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return int
     */
    private function calculateAverageTimeOnPage($query): int
    {
        $timeEvents = $query->byEventType('time_on_page')->get();
        $totalTime = 0;
        $count = 0;

        foreach ($timeEvents as $event) {
            $duration = $event->getTimeOnPage();
            if ($duration > 0) {
                $totalTime += $duration;
                $count++;
            }
        }

        return $count > 0 ? round($totalTime / $count) : 0;
    }

    /**
     * Calculate exit rate
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return float
     */
    private function calculateExitRate($query): float
    {
        $totalPageViews = $query->byEventType('page_view')->count();
        $totalExits = $query->byEventType('exit')->count();

        if ($totalPageViews === 0) {
            return 0.0;
        }

        return round(($totalExits / $totalPageViews) * 100, 2);
    }

    /**
     * Get conversion funnel data
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return array
     */
    private function getConversionFunnel($query): array
    {
        $events = $query->bySession(null) // Get all sessions
                        ->orderBy('timestamp')
                        ->get()
                        ->groupBy('session_id');

        $steps = [
            'page_view' => 0,
            'click' => 0,
            'form_submit' => 0,
            'conversion' => 0,
        ];

        foreach ($events as $sessionEvents) {
            $sessionSteps = [];

            foreach ($sessionEvents as $event) {
                if (array_key_exists($event->event_type, $steps)) {
                    $sessionSteps[$event->event_type] = true;
                }
            }

            // Only count if they had a page view
            if (!empty($sessionSteps['page_view'])) {
                foreach ($steps as $step => $count) {
                    if (isset($sessionSteps[$step])) {
                        $steps[$step]++;
                    }
                }
            }
        }

        $sessionCount = $events->count();
        $funnelRates = [];

        foreach ($steps as $step => $count) {
            $rate = $sessionCount > 0 ? round(($count / $sessionCount) * 100, 2) : 0.0;
            $funnelRates[$step] = $rate;
        }

        return [
            'sessions' => $sessionCount,
            'steps' => $steps,
            'rates' => $funnelRates,
        ];
    }

    /**
     * Clear analytics cache for specific template
     *
     * @param int $templateId
     */
    public function clearTemplateCache(int $templateId): void
    {
        Cache::forget(self::CACHE_PREFIX . 'template_' . $templateId . '_*');
        Cache::forget(self::CACHE_PREFIX . 'report_*');
    }

    /**
     * Clear all analytics cache
     */
    public function clearAllCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . '*');
    }

    /**
     * Broadcast real-time analytics event
     *
     * @param TemplateAnalyticsEvent $event
     * @return void
     */
    public function broadcastRealTimeEvent(TemplateAnalyticsEvent $event): void
    {
        try {
            // Update real-time metrics cache
            $this->updateRealTimeMetrics($event);

            // Log real-time event for monitoring
            Log::info('Real-time analytics event processed', [
                'event_id' => $event->id,
                'template_id' => $event->template_id,
                'event_type' => $event->event_type,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to process real-time analytics event', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get real-time analytics metrics
     *
     * @param int $templateId
     * @return array
     */
    public function getRealTimeMetrics(int $templateId): array
    {
        $cacheKey = self::CACHE_PREFIX . 'realtime_' . $templateId;

        return Cache::remember($cacheKey, 60, function () use ($templateId) {
            $now = now();
            $lastHour = $now->copy()->subHour();

            $events = TemplateAnalyticsEvent::forTemplate($templateId)
                ->where('timestamp', '>=', $lastHour)
                ->get();

            return [
                'template_id' => $templateId,
                'time_range' => 'last_hour',
                'total_events' => $events->count(),
                'page_views' => $events->where('event_type', 'page_view')->count(),
                'conversions' => $events->where('event_type', 'conversion')->count(),
                'unique_users' => $events->pluck('user_identifier')->unique()->count(),
                'events_per_minute' => round($events->count() / 60, 2),
                'last_updated' => $now->toISOString(),
            ];
        });
    }

    /**
     * Update real-time metrics cache
     *
     * @param TemplateAnalyticsEvent $event
     * @return void
     */
    private function updateRealTimeMetrics(TemplateAnalyticsEvent $event): void
    {
        $cacheKey = self::CACHE_PREFIX . 'realtime_' . $event->template_id;
        Cache::forget($cacheKey); // Force refresh on next access
    }

    /**
     * Track performance metrics for analytics operations
     *
     * @param string $operation
     * @param callable $callback
     * @return mixed
     */
    public function trackPerformance(string $operation, callable $callback)
    {
        $startTime = microtime(true);

        try {
            $result = $callback();

            $duration = microtime(true) - $startTime;

            // Log performance metrics
            Log::info('Analytics operation performance', [
                'operation' => $operation,
                'duration_ms' => round($duration * 1000, 2),
                'memory_usage' => memory_get_peak_usage(true),
            ]);

            // Store performance metrics
            $this->storePerformanceMetric($operation, $duration);

            return $result;

        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;

            Log::error('Analytics operation failed', [
                'operation' => $operation,
                'duration_ms' => round($duration * 1000, 2),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Store performance metric
     *
     * @param string $operation
     * @param float $duration
     * @return void
     */
    private function storePerformanceMetric(string $operation, float $duration): void
    {
        $metrics = Cache::get('analytics_performance_metrics', []);

        $key = $operation . '_' . now()->format('Y-m-d-H');

        if (!isset($metrics[$key])) {
            $metrics[$key] = [
                'operation' => $operation,
                'count' => 0,
                'total_duration' => 0,
                'avg_duration' => 0,
                'max_duration' => 0,
                'min_duration' => $duration,
            ];
        }

        $metrics[$key]['count']++;
        $metrics[$key]['total_duration'] += $duration;
        $metrics[$key]['avg_duration'] = $metrics[$key]['total_duration'] / $metrics[$key]['count'];
        $metrics[$key]['max_duration'] = max($metrics[$key]['max_duration'], $duration);
        $metrics[$key]['min_duration'] = min($metrics[$key]['min_duration'], $duration);

        Cache::put('analytics_performance_metrics', $metrics, 3600); // Cache for 1 hour
    }

    /**
     * Get performance metrics report
     *
     * @return array
     */
    public function getPerformanceMetricsReport(): array
    {
        $metrics = Cache::get('analytics_performance_metrics', []);

        return [
            'report_generated_at' => now()->toISOString(),
            'metrics' => $metrics,
            'summary' => $this->generatePerformanceSummary($metrics),
        ];
    }

    /**
     * Generate performance summary
     *
     * @param array $metrics
     * @return array
     */
    private function generatePerformanceSummary(array $metrics): array
    {
        $summary = [
            'total_operations' => 0,
            'average_response_time' => 0,
            'slowest_operation' => null,
            'fastest_operation' => null,
        ];

        $totalDuration = 0;
        $totalCount = 0;

        foreach ($metrics as $metric) {
            $summary['total_operations'] += $metric['count'];
            $totalDuration += $metric['total_duration'];
            $totalCount += $metric['count'];

            if (!$summary['slowest_operation'] ||
                $metric['max_duration'] > $metrics[$summary['slowest_operation']]['max_duration']) {
                $summary['slowest_operation'] = $metric['operation'];
            }

            if (!$summary['fastest_operation'] ||
                $metric['min_duration'] < $metrics[$summary['fastest_operation']]['min_duration']) {
                $summary['fastest_operation'] = $metric['operation'];
            }
        }

        if ($totalCount > 0) {
            $summary['average_response_time'] = round(($totalDuration / $totalCount) * 1000, 2);
        }

        return $summary;
    }

    /**
     * Anonymize old analytics data for GDPR compliance
     *
     * @param int $daysOld Number of days after which to anonymize data
     * @return int Number of records anonymized
     */
    public function anonymizeOldData(int $daysOld = 90): int
    {
        $cutoffDate = now()->subDays($daysOld);

        $events = TemplateAnalyticsEvent::where('created_at', '<', $cutoffDate)
            ->where('is_compliant', true)
            ->get();

        $anonymized = 0;
        foreach ($events as $event) {
            $event->anonymize();
            $anonymized++;
        }

        Log::info('Analytics data anonymization completed', [
            'anonymized_count' => $anonymized,
            'cutoff_date' => $cutoffDate,
        ]);

        return $anonymized;
    }

    /**
     * Delete expired analytics data based on retention policy
     *
     * @return int Number of records deleted
     */
    public function deleteExpiredData(): int
    {
        $expiredEvents = TemplateAnalyticsEvent::where('data_retention_until', '<', now())
            ->delete();

        Log::info('Expired analytics data deletion completed', [
            'deleted_count' => $expiredEvents,
        ]);

        return $expiredEvents;
    }

    /**
     * Get GDPR compliance statistics
     *
     * @return array
     */
    public function getGdprComplianceStats(): array
    {
        return [
            'total_events' => TemplateAnalyticsEvent::count(),
            'compliant_events' => TemplateAnalyticsEvent::where('is_compliant', true)->count(),
            'consent_given_events' => TemplateAnalyticsEvent::where('consent_given', true)->count(),
            'expiring_soon' => TemplateAnalyticsEvent::where('data_retention_until', '<', now()->addDays(30))
                ->where('data_retention_until', '>', now())
                ->count(),
            'already_expired' => TemplateAnalyticsEvent::where('data_retention_until', '<', now())->count(),
        ];
    }

    /**
     * Export user data for GDPR data portability
     *
     * @param string $userIdentifier
     * @return array
     */
    public function exportUserData(string $userIdentifier): array
    {
        $events = TemplateAnalyticsEvent::where('user_identifier', $userIdentifier)
            ->where('is_compliant', true)
            ->where('consent_given', true)
            ->orderBy('created_at')
            ->get();

        return [
            'user_identifier' => $userIdentifier,
            'export_timestamp' => now()->toISOString(),
            'data_controller' => config('app.name'),
            'analytics_events' => $events->map(function ($event) {
                return [
                    'event_type' => $event->event_type,
                    'timestamp' => $event->timestamp,
                    'template_id' => $event->template_id,
                    'landing_page_id' => $event->landing_page_id,
                    'session_id' => $event->session_id,
                    'event_data' => $event->event_data,
                    'device_info' => $event->device_info,
                    'geo_location' => $event->geo_location,
                    'conversion_value' => $event->conversion_value,
                ];
            }),
            'gdpr_rights' => [
                'right_to_access' => true,
                'right_to_rectification' => true,
                'right_to_erasure' => true,
                'right_to_restriction' => true,
                'right_to_portability' => true,
                'right_to_object' => true,
            ],
        ];
    }

    /**
     * Delete user data for GDPR right to erasure
     *
     * @param string $userIdentifier
     * @return int Number of records deleted
     */
    public function deleteUserData(string $userIdentifier): int
    {
        $deleted = TemplateAnalyticsEvent::where('user_identifier', $userIdentifier)->delete();

        Log::info('User analytics data deletion completed', [
            'user_identifier' => $userIdentifier,
            'deleted_count' => $deleted,
        ]);

        return $deleted;
    }
}