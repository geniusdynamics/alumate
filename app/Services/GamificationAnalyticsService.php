<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for tracking and analyzing gamification analytics
 *
 * Handles points accumulation, badge earning, leaderboard calculations,
 * and user engagement metrics within tenant-scoped contexts.
 */
class GamificationAnalyticsService extends BaseService
{
    /**
     * Track gamification event (points, badges, achievements)
     */
    public function trackGamificationEvent(
        string $userId,
        string $gamificationType,
        ?int $pointsEarned = null,
        ?string $badgeEarned = null,
        array $additionalData = []
    ): bool {
        try {
            $this->ensureTenantContext();
            $tenantId = $this->getCurrentTenantId();

            $eventData = [
                'tenant_id' => $tenantId,
                'event_type' => 'gamification',
                'event_name' => $gamificationType,
                'gamification_type' => $gamificationType,
                'user_id' => $userId,
                'points_earned' => $pointsEarned,
                'badge_earned' => $badgeEarned,
                'properties' => array_merge($additionalData, [
                    'tracked_at' => now()->toISOString(),
                ]),
                'occurred_at' => now(),
                'is_compliant' => true,
                'consent_given' => true,
            ];

            AnalyticsEvent::create($eventData);

            $this->logActivity(
                'gamification_event_tracked',
                "Tracked gamification event: {$gamificationType} for user {$userId}",
                [
                    'gamification_type' => $gamificationType,
                    'points_earned' => $pointsEarned,
                    'badge_earned' => $badgeEarned,
                ]
            );

            return true;
        } catch (\Exception $e) {
            $this->handleServiceError($e, 'track_gamification_event', [
                'user_id' => $userId,
                'gamification_type' => $gamificationType,
            ]);
            return false;
        }
    }

    /**
     * Get user points summary
     */
    public function getUserPoints(string $userId): array
    {
        try {
            $this->ensureTenantContext();
            $tenantId = $this->getCurrentTenantId();

            $pointsData = AnalyticsEvent::byTenant($tenantId)
                ->where('user_id', $userId)
                ->where('event_type', 'gamification')
                ->whereNotNull('points_earned')
                ->selectRaw('
                    SUM(points_earned) as total_points,
                    COUNT(*) as total_events,
                    MAX(occurred_at) as last_earned_at
                ')
                ->first();

            return [
                'total_points' => (int) ($pointsData->total_points ?? 0),
                'total_events' => (int) ($pointsData->total_events ?? 0),
                'last_earned_at' => $pointsData->last_earned_at?->toISOString(),
            ];
        } catch (\Exception $e) {
            $this->handleServiceError($e, 'get_user_points', ['user_id' => $userId]);
            return ['total_points' => 0, 'total_events' => 0, 'last_earned_at' => null];
        }
    }

    /**
     * Get user badges earned
     */
    public function getUserBadges(string $userId): Collection
    {
        try {
            $this->ensureTenantContext();
            $tenantId = $this->getCurrentTenantId();

            return AnalyticsEvent::byTenant($tenantId)
                ->where('user_id', $userId)
                ->where('event_type', 'gamification')
                ->whereNotNull('badge_earned')
                ->orderBy('occurred_at', 'desc')
                ->get(['badge_earned', 'occurred_at', 'properties'])
                ->map(function ($event) {
                    return [
                        'badge' => $event->badge_earned,
                        'earned_at' => $event->occurred_at->toISOString(),
                        'details' => $event->properties,
                    ];
                });
        } catch (\Exception $e) {
            $this->handleServiceError($e, 'get_user_badges', ['user_id' => $userId]);
            return collect();
        }
    }

    /**
     * Get leaderboard by points
     */
    public function getLeaderboard(int $limit = 10): Collection
    {
        try {
            $this->ensureTenantContext();
            $tenantId = $this->getCurrentTenantId();

            return AnalyticsEvent::byTenant($tenantId)
                ->where('event_type', 'gamification')
                ->whereNotNull('points_earned')
                ->selectRaw('
                    user_id,
                    SUM(points_earned) as total_points,
                    COUNT(*) as total_events,
                    MAX(occurred_at) as last_activity
                ')
                ->groupBy('user_id')
                ->orderBy('total_points', 'desc')
                ->orderBy('last_activity', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($record, $index) {
                    return [
                        'rank' => $index + 1,
                        'user_id' => $record->user_id,
                        'total_points' => (int) $record->total_points,
                        'total_events' => (int) $record->total_events,
                        'last_activity' => $record->last_activity?->toISOString(),
                    ];
                });
        } catch (\Exception $e) {
            $this->handleServiceError($e, 'get_leaderboard');
            return collect();
        }
    }

    /**
     * Get gamification metrics summary
     */
    public function getGamificationMetrics(array $dateRange = []): array
    {
        try {
            $this->ensureTenantContext();
            $tenantId = $this->getCurrentTenantId();

            $query = AnalyticsEvent::byTenant($tenantId)
                ->where('event_type', 'gamification');

            if (!empty($dateRange)) {
                $query->byDateRange($dateRange[0], $dateRange[1]);
            }

            $metrics = $query->selectRaw('
                COUNT(*) as total_events,
                COUNT(DISTINCT user_id) as unique_users,
                SUM(CASE WHEN points_earned IS NOT NULL THEN points_earned ELSE 0 END) as total_points_awarded,
                COUNT(DISTINCT CASE WHEN badge_earned IS NOT NULL THEN CONCAT(user_id, badge_earned) END) as total_badges_earned,
                AVG(CASE WHEN points_earned IS NOT NULL THEN points_earned ELSE NULL END) as avg_points_per_event
            ')->first();

            return [
                'total_events' => (int) $metrics->total_events,
                'unique_users' => (int) $metrics->unique_users,
                'total_points_awarded' => (int) $metrics->total_points_awarded,
                'total_badges_earned' => (int) $metrics->total_badges_earned,
                'avg_points_per_event' => round((float) $metrics->avg_points_per_event, 2),
                'date_range' => $dateRange,
            ];
        } catch (\Exception $e) {
            $this->handleServiceError($e, 'get_gamification_metrics');
            return [
                'total_events' => 0,
                'unique_users' => 0,
                'total_points_awarded' => 0,
                'total_badges_earned' => 0,
                'avg_points_per_event' => 0.0,
                'date_range' => $dateRange,
            ];
        }
    }

    /**
     * Get gamification activity over time
     */
    public function getActivityTimeline(string $period = 'daily', int $days = 30): Collection
    {
        try {
            $this->ensureTenantContext();
            $tenantId = $this->getCurrentTenantId();

            $dateFormat = match ($period) {
                'hourly' => '%Y-%m-%d %H:00:00',
                'weekly' => '%Y-%u',
                default => '%Y-%m-%d',
            };

            return AnalyticsEvent::byTenant($tenantId)
                ->where('event_type', 'gamification')
                ->where('occurred_at', '>=', now()->subDays($days))
                ->selectRaw("
                    DATE_FORMAT(occurred_at, '{$dateFormat}') as period,
                    COUNT(*) as total_events,
                    COUNT(DISTINCT user_id) as unique_users,
                    SUM(CASE WHEN points_earned IS NOT NULL THEN points_earned ELSE 0 END) as points_awarded
                ")
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(function ($record) {
                    return [
                        'period' => $record->period,
                        'total_events' => (int) $record->total_events,
                        'unique_users' => (int) $record->unique_users,
                        'points_awarded' => (int) $record->points_awarded,
                    ];
                });
        } catch (\Exception $e) {
            $this->handleServiceError($e, 'get_activity_timeline');
            return collect();
        }
    }

    /**
     * Get user engagement score based on gamification activity
     */
    public function getUserEngagementScore(string $userId): float
    {
        try {
            $this->ensureTenantContext();
            $tenantId = $this->getCurrentTenantId();

            // Calculate engagement based on recent activity (last 30 days)
            $recentActivity = AnalyticsEvent::byTenant($tenantId)
                ->where('user_id', $userId)
                ->where('event_type', 'gamification')
                ->where('occurred_at', '>=', now()->subDays(30))
                ->selectRaw('
                    COUNT(*) as event_count,
                    SUM(CASE WHEN points_earned IS NOT NULL THEN points_earned ELSE 0 END) as points_earned,
                    COUNT(DISTINCT DATE(occurred_at)) as active_days
                ')
                ->first();

            if (!$recentActivity || $recentActivity->event_count == 0) {
                return 0.0;
            }

            // Simple engagement score calculation
            // Factors: frequency, points earned, consistency
            $frequencyScore = min($recentActivity->event_count / 10, 1.0); // Max at 10 events
            $pointsScore = min($recentActivity->points_earned / 100, 1.0); // Max at 100 points
            $consistencyScore = $recentActivity->active_days / 30; // Days active out of 30

            return round(($frequencyScore * 0.4 + $pointsScore * 0.4 + $consistencyScore * 0.2) * 100, 2);
        } catch (\Exception $e) {
            $this->handleServiceError($e, 'get_user_engagement_score', ['user_id' => $userId]);
            return 0.0;
        }
    }
}