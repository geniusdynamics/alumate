<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmailLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Email Analytics Service
 *
 * Service for tracking email engagement metrics including opens, clicks,
 * delivery statistics, and comprehensive engagement analytics.
 * Integrates with EmailLog model for persistent tracking.
 */
class EmailAnalyticsService extends BaseService
{
    /**
     * Cache configuration
     */
    protected const CACHE_PREFIX = 'email_analytics_';

    protected const CACHE_DURATION = 1800; // 30 minutes

    /**
     * Tracking pixel filename
     */
    protected const TRACKING_PIXEL = 'tracking_pixel.gif';

    public function __construct(TenantContextService $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    /**
     * Track email open event
     */
    public function trackOpen(string $trackingId, array $metadata = []): array
    {
        try {
            $emailLog = EmailLog::where('tracking_id', $trackingId)->first();

            if (! $emailLog) {
                Log::warning('Email open tracking failed - invalid tracking ID', [
                    'tracking_id' => $trackingId,
                ]);

                return [
                    'success' => false,
                    'error' => 'Invalid tracking ID',
                ];
            }

            // Prevent duplicate tracking
            if ($emailLog->isOpened()) {
                return [
                    'success' => true,
                    'duplicate' => true,
                    'email_log_id' => $emailLog->id,
                ];
            }

            $openMetadata = array_merge($metadata, [
                'ip_address' => $metadata['ip_address'] ?? request()->ip(),
                'user_agent' => $metadata['user_agent'] ?? request()->userAgent(),
                'opened_at' => now()->toIso8601String(),
            ]);

            $emailLog->recordOpen($openMetadata);
            $this->clearAnalyticsCache();

            Log::info('Email open tracked', [
                'email_log_id' => $emailLog->id,
                'tracking_id' => $trackingId,
                'recipient' => $emailLog->recipient_email,
            ]);

            return [
                'success' => true,
                'email_log_id' => $emailLog->id,
                'time_to_open' => $emailLog->getTimeToOpen(),
            ];
        } catch (\Exception $e) {
            Log::error('Email open tracking error', [
                'tracking_id' => $trackingId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Track email click event
     */
    public function trackClick(string $trackingId, string $url, array $metadata = []): array
    {
        try {
            $emailLog = EmailLog::where('tracking_id', $trackingId)->first();

            if (! $emailLog) {
                Log::warning('Email click tracking failed - invalid tracking ID', [
                    'tracking_id' => $trackingId,
                    'url' => $url,
                ]);

                return [
                    'success' => false,
                    'error' => 'Invalid tracking ID',
                ];
            }

            $clickMetadata = array_merge($metadata, [
                'url' => $url,
                'ip_address' => $metadata['ip_address'] ?? request()->ip(),
                'user_agent' => $metadata['user_agent'] ?? request()->userAgent(),
                'clicked_at' => now()->toIso8601String(),
            ]);

            // Track first click
            if (! $emailLog->isClicked()) {
                $emailLog->recordClick($clickMetadata);
            }

            // Store all clicks in metadata
            $clicks = $emailLog->metadata['clicks'] ?? [];
            $clicks[] = $clickMetadata;

            $emailLog->update([
                'metadata' => array_merge($emailLog->metadata ?? [], ['clicks' => $clicks]),
            ]);

            $this->clearAnalyticsCache();

            Log::info('Email click tracked', [
                'email_log_id' => $emailLog->id,
                'tracking_id' => $trackingId,
                'url' => $url,
            ]);

            return [
                'success' => true,
                'email_log_id' => $emailLog->id,
                'redirect_url' => $url,
                'time_to_click' => $emailLog->getTimeToClick(),
            ];
        } catch (\Exception $e) {
            Log::error('Email click tracking error', [
                'tracking_id' => $trackingId,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate tracking pixel URL for email opens
     */
    public function generateTrackingPixelUrl(string $trackingId): string
    {
        return route('email.track.open', ['trackingId' => $trackingId]);
    }

    /**
     * Generate tracking URL for link clicks
     */
    public function generateTrackingUrl(string $trackingId, string $destinationUrl): string
    {
        return route('email.track.click', [
            'trackingId' => $trackingId,
            'url' => urlencode($destinationUrl),
        ]);
    }

    /**
     * Get delivery statistics
     */
    public function getDeliveryStats(array $filters = []): array
    {
        $this->ensureTenantContext();

        $cacheKey = $this->getCacheKey('delivery_stats', $filters);

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filters) {
            $query = EmailLog::query();

            // Apply tenant filter
            $tenantId = $this->getCurrentTenantId();
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }

            // Apply date filters
            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }
            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }

            // Apply provider filter
            if (isset($filters['provider'])) {
                $query->byProvider($filters['provider']);
            }

            // Apply template filter
            if (isset($filters['template'])) {
                $query->where('template', $filters['template']);
            }

            $total = $query->count();
            $sent = (clone $query)->sent()->count();
            $delivered = (clone $query)->delivered()->count();
            $bounced = (clone $query)->bounced()->count();
            $failed = (clone $query)->failed()->count();
            $queued = (clone $query)->queued()->count();

            return [
                'total' => $total,
                'queued' => $queued,
                'sent' => $sent,
                'delivered' => $delivered,
                'bounced' => $bounced,
                'failed' => $failed,
                'delivery_rate' => $sent > 0 ? round(($delivered / $sent) * 100, 2) : 0,
                'bounce_rate' => $sent > 0 ? round(($bounced / $sent) * 100, 2) : 0,
                'failure_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
                'period' => [
                    'start' => $filters['start_date'] ?? null,
                    'end' => $filters['end_date'] ?? null,
                ],
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get engagement metrics (opens, clicks)
     */
    public function getEngagementMetrics(array $filters = []): array
    {
        $this->ensureTenantContext();

        $cacheKey = $this->getCacheKey('engagement_metrics', $filters);

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filters) {
            $query = EmailLog::query();

            // Apply tenant filter
            $tenantId = $this->getCurrentTenantId();
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }

            // Apply date filters
            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }
            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }

            // Apply provider filter
            if (isset($filters['provider'])) {
                $query->byProvider($filters['provider']);
            }

            // Apply template filter
            if (isset($filters['template'])) {
                $query->where('template', $filters['template']);
            }

            $delivered = (clone $query)->delivered()->count();
            $opened = (clone $query)->opened()->count();
            $clicked = (clone $query)->clicked()->count();
            $uniqueOpens = (clone $query)->opened()->distinct('recipient_email')->count('recipient_email');
            $uniqueClicks = (clone $query)->clicked()->distinct('recipient_email')->count('recipient_email');

            // Calculate rates
            $openRate = $delivered > 0 ? round(($opened / $delivered) * 100, 2) : 0;
            $clickRate = $delivered > 0 ? round(($clicked / $delivered) * 100, 2) : 0;
            $ctor = $opened > 0 ? round(($clicked / $opened) * 100, 2) : 0; // Click-to-open rate

            // Get trends by day
            $trends = $this->getEngagementTrends($query, $filters);

            // Get top performing templates
            $topTemplates = $this->getTopTemplates($filters);

            return [
                'summary' => [
                    'delivered' => $delivered,
                    'opened' => $opened,
                    'clicked' => $clicked,
                    'unique_opens' => $uniqueOpens,
                    'unique_clicks' => $uniqueClicks,
                    'open_rate' => $openRate,
                    'click_rate' => $clickRate,
                    'click_to_open_rate' => $ctor,
                ],
                'trends' => $trends,
                'top_templates' => $topTemplates,
                'period' => [
                    'start' => $filters['start_date'] ?? null,
                    'end' => $filters['end_date'] ?? null,
                ],
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get comprehensive analytics dashboard data
     */
    public function getDashboardData(int $days = 30): array
    {
        $this->ensureTenantContext();

        $endDate = now();
        $startDate = now()->subDays($days);

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        return [
            'delivery_stats' => $this->getDeliveryStats($filters),
            'engagement_metrics' => $this->getEngagementMetrics($filters),
            'provider_breakdown' => $this->getProviderBreakdown($filters),
            'hourly_distribution' => $this->getHourlyDistribution($filters),
            'top_performing_emails' => $this->getTopPerformingEmails($filters, 10),
            'comparison_to_previous_period' => $this->getPeriodComparison($days),
        ];
    }

    /**
     * Get real-time analytics
     */
    public function getRealTimeAnalytics(int $minutes = 5): array
    {
        $this->ensureTenantContext();

        $since = Carbon::now()->subMinutes($minutes);

        $query = EmailLog::query();

        $tenantId = $this->getCurrentTenantId();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $recentActivity = $query->where('updated_at', '>=', $since)
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        return [
            'period_minutes' => $minutes,
            'stats' => [
                'sent' => (clone $query)->where('sent_at', '>=', $since)->count(),
                'delivered' => (clone $query)->where('delivered_at', '>=', $since)->count(),
                'opened' => (clone $query)->where('opened_at', '>=', $since)->count(),
                'clicked' => (clone $query)->where('clicked_at', '>=', $since)->count(),
                'bounced' => (clone $query)->where('status', EmailLog::STATUS_BOUNCED)
                    ->where('updated_at', '>=', $since)
                    ->count(),
            ],
            'recent_activity' => $recentActivity->map(function ($log) {
                return [
                    'id' => $log->id,
                    'recipient' => $log->recipient_email,
                    'subject' => $log->subject,
                    'status' => $log->status,
                    'template' => $log->template,
                    'updated_at' => $log->updated_at->toIso8601String(),
                ];
            }),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate tracking pixel image data
     */
    public function getTrackingPixelData(): string
    {
        // 1x1 transparent GIF
        return base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    }

    /**
     * Get engagement trends by day
     */
    protected function getEngagementTrends($baseQuery, array $filters): array
    {
        $days = isset($filters['start_date']) && isset($filters['end_date'])
            ? Carbon::parse($filters['start_date'])->diffInDays(Carbon::parse($filters['end_date']))
            : 30;

        $days = min($days, 90); // Max 90 days

        $trends = [];
        $currentDate = now()->subDays($days);

        for ($i = 0; $i <= $days; $i++) {
            $date = $currentDate->copy()->addDays($i);
            $dateString = $date->format('Y-m-d');

            $dayQuery = (clone $baseQuery)->whereDate('created_at', $dateString);

            $trends[] = [
                'date' => $dateString,
                'sent' => (clone $dayQuery)->sent()->count(),
                'delivered' => (clone $dayQuery)->delivered()->count(),
                'opened' => (clone $dayQuery)->opened()->count(),
                'clicked' => (clone $dayQuery)->clicked()->count(),
            ];
        }

        return $trends;
    }

    /**
     * Get top performing templates
     */
    protected function getTopTemplates(array $filters, int $limit = 5): array
    {
        $query = EmailLog::query();

        $tenantId = $this->getCurrentTenantId();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        $templates = $query->whereNotNull('template')
            ->select(
                'template',
                DB::raw('COUNT(*) as total_sent'),
                DB::raw('COUNT(CASE WHEN opened_at IS NOT NULL THEN 1 END) as total_opened'),
                DB::raw('COUNT(CASE WHEN clicked_at IS NOT NULL THEN 1 END) as total_clicked')
            )
            ->groupBy('template')
            ->orderByDesc('total_sent')
            ->limit($limit)
            ->get();

        return $templates->map(function ($template) {
            $openRate = $template->total_sent > 0
                ? round(($template->total_opened / $template->total_sent) * 100, 2)
                : 0;
            $clickRate = $template->total_sent > 0
                ? round(($template->total_clicked / $template->total_sent) * 100, 2)
                : 0;

            return [
                'template' => $template->template,
                'sent' => $template->total_sent,
                'opened' => $template->total_opened,
                'clicked' => $template->total_clicked,
                'open_rate' => $openRate,
                'click_rate' => $clickRate,
            ];
        })->toArray();
    }

    /**
     * Get provider breakdown
     */
    protected function getProviderBreakdown(array $filters): array
    {
        $query = EmailLog::query();

        $tenantId = $this->getCurrentTenantId();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        $providers = $query->select(
            'provider',
            DB::raw('COUNT(*) as total'),
            DB::raw('COUNT(CASE WHEN status = \'delivered\' THEN 1 END) as delivered'),
            DB::raw('COUNT(CASE WHEN status = \'bounced\' THEN 1 END) as bounced'),
            DB::raw('COUNT(CASE WHEN status = \'failed\' THEN 1 END) as failed')
        )
            ->groupBy('provider')
            ->get();

        return $providers->map(function ($provider) {
            return [
                'provider' => $provider->provider,
                'total' => $provider->total,
                'delivered' => $provider->delivered,
                'bounced' => $provider->bounced,
                'failed' => $provider->failed,
                'success_rate' => $provider->total > 0
                    ? round(($provider->delivered / $provider->total) * 100, 2)
                    : 0,
            ];
        })->toArray();
    }

    /**
     * Get hourly distribution
     */
    protected function getHourlyDistribution(array $filters): array
    {
        $query = EmailLog::query();

        $tenantId = $this->getCurrentTenantId();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        $hourly = $query->select(
            DB::raw('EXTRACT(HOUR FROM created_at) as hour'),
            DB::raw('COUNT(*) as total'),
            DB::raw('COUNT(CASE WHEN opened_at IS NOT NULL THEN 1 END) as opened')
        )
            ->groupBy(DB::raw('EXTRACT(HOUR FROM created_at)'))
            ->orderBy('hour')
            ->get();

        // Fill in missing hours
        $distribution = [];
        for ($i = 0; $i < 24; $i++) {
            $hourData = $hourly->firstWhere('hour', $i);
            $distribution[] = [
                'hour' => $i,
                'total' => $hourData ? $hourData->total : 0,
                'opened' => $hourData ? $hourData->opened : 0,
            ];
        }

        return $distribution;
    }

    /**
     * Get top performing emails
     */
    protected function getTopPerformingEmails(array $filters, int $limit = 10): array
    {
        $query = EmailLog::query()
            ->whereNotNull('opened_at')
            ->with(['user:id,name,email']);

        $tenantId = $this->getCurrentTenantId();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return $query->orderByDesc('opened_at')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'subject' => $log->subject,
                    'recipient' => $log->recipient_email,
                    'template' => $log->template,
                    'sent_at' => $log->sent_at?->toIso8601String(),
                    'opened_at' => $log->opened_at?->toIso8601String(),
                    'clicked_at' => $log->clicked_at?->toIso8601String(),
                    'time_to_open' => $log->getTimeToOpen(),
                ];
            })
            ->toArray();
    }

    /**
     * Get comparison to previous period
     */
    protected function getPeriodComparison(int $days): array
    {
        $currentEnd = now();
        $currentStart = now()->subDays($days);
        $previousEnd = $currentStart->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subDays($days);

        $currentStats = $this->getDeliveryStats([
            'start_date' => $currentStart,
            'end_date' => $currentEnd,
        ]);

        $previousStats = $this->getDeliveryStats([
            'start_date' => $previousStart,
            'end_date' => $previousEnd,
        ]);

        return [
            'current_period' => [
                'sent' => $currentStats['sent'],
                'delivery_rate' => $currentStats['delivery_rate'],
            ],
            'previous_period' => [
                'sent' => $previousStats['sent'],
                'delivery_rate' => $previousStats['delivery_rate'],
            ],
            'change' => [
                'sent' => $this->calculateChange($currentStats['sent'], $previousStats['sent']),
                'delivery_rate' => $this->calculateChange($currentStats['delivery_rate'], $previousStats['delivery_rate']),
            ],
        ];
    }

    /**
     * Calculate percentage change
     */
    protected function calculateChange(float $current, float $previous): array
    {
        if ($previous == 0) {
            return [
                'value' => $current > 0 ? 100 : 0,
                'direction' => $current > 0 ? 'up' : 'neutral',
            ];
        }

        $change = (($current - $previous) / $previous) * 100;

        return [
            'value' => round(abs($change), 2),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
        ];
    }

    /**
     * Get cache key with tenant context
     */
    protected function getCacheKey(string $type, array $filters = []): string
    {
        $tenantId = $this->getCurrentTenantId() ?? 'global';
        $filterHash = md5(serialize($filters));

        return self::CACHE_PREFIX."{$type}_{$tenantId}_{$filterHash}";
    }

    /**
     * Clear analytics cache
     */
    protected function clearAnalyticsCache(): void
    {
        $tenantId = $this->getCurrentTenantId();

        if (! $tenantId) {
            return;
        }

        // Clear common cache keys
        $patterns = [
            self::CACHE_PREFIX."delivery_stats_{$tenantId}_*",
            self::CACHE_PREFIX."engagement_metrics_{$tenantId}_*",
        ];

        foreach ($patterns as $pattern) {
            // Note: This is a simplified approach. In production, you might want to
            // store cache keys in a set for efficient clearing
            Cache::flush();
        }
    }
}
