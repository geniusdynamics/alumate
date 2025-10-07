<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Course;
use App\Models\LearningProgress;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LearningScoreJob;

/**
 * Learning Analytics Service
 *
 * Provides comprehensive analytics for learning activities including course interactions,
 * engagement scoring, certification verification, and learning insights generation.
 * Maintains tenant isolation and respects user consent for data tracking.
 */
class LearningAnalyticsService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const ENGAGEMENT_CACHE_KEY = 'learning_score_%s_%s';

    /**
     * Track course interaction event
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @param array $interactionData Interaction data (course_id, module_id, duration, score)
     * @return bool Success status
     */
    public function trackCourseInteraction(int $userId, int $courseId, array $interactionData): bool
    {
        try {
            // Check consent before tracking
            if (!$this->checkConsent($userId)) {
                Log::info('Learning analytics tracking skipped due to lack of consent', [
                    'user_id' => $userId,
                    'course_id' => $courseId
                ]);
                return false;
            }

            $tenantId = $this->getCurrentTenantId();

            // Create analytics event
            AnalyticsEvent::create([
                'tenant_id' => $tenantId,
                'event_type' => 'learning',
                'event_name' => 'course_interaction',
                'user_id' => $userId,
                'properties' => [
                    'course_id' => $courseId,
                    'module_id' => $interactionData['module_id'] ?? null,
                    'duration' => $interactionData['duration'] ?? 0,
                    'score' => $interactionData['score'] ?? null,
                    'interaction_type' => $interactionData['interaction_type'] ?? 'view'
                ],
                'occurred_at' => now(),
                'is_compliant' => true,
                'consent_given' => true,
                'analytics_version' => '1.0'
            ]);

            // Update learning progress
            $this->updateLearningProgress($userId, $courseId, $interactionData);

            // Clear engagement score cache
            $this->clearEngagementCache($userId, $courseId);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to track course interaction', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'interaction_data' => $interactionData,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Calculate engagement score for user-course combination
     *
     * Formula: (duration * 0.4 + interactions * 0.3 + completion * 0.3) normalized to 0-100
     * Duration: engagement_duration in minutes, normalized to 0-100 (max 10 hours = 600 min)
     * Interactions: interactions_count, normalized to 0-100 (max 100 interactions)
     * Completion: progress_percentage, already 0-100
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return float Engagement score (0-100)
     */
    public function calculateEngagementScore(int $userId, int $courseId): float
    {
        $cacheKey = sprintf(self::ENGAGEMENT_CACHE_KEY, $userId, $courseId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $courseId) {
            $tenantId = $this->getCurrentTenantId();

            // Get learning progress record
            $progress = LearningProgress::byTenant($tenantId)
                ->byUser($userId)
                ->byCourse($courseId)
                ->first();

            if (!$progress) {
                return 0.0;
            }

            // Calculate components using new formula
            $durationScore = min(($progress->engagement_duration ?? 0) / 600, 1) * 100; // Max 10 hours
            $interactionsScore = min(($progress->interactions_count ?? 0) / 100, 1) * 100; // Max 100 interactions
            $completionScore = $progress->progress_percentage ?? 0; // Already 0-100

            // Weighted formula: (duration*0.4 + interactions*0.3 + completion*0.3)
            $rawScore = ($durationScore * 0.4) + ($interactionsScore * 0.3) + ($completionScore * 0.3);

            return round(min($rawScore, 100.0), 2);
        });
    }

    /**
     * Verify certification eligibility
     *
     * @param int $userId User ID
     * @param array $criteria Certification criteria
     * @return array Eligibility result with score
     */
    public function verifyCertification(int $userId, array $criteria = []): array
    {
        $tenantId = $this->getCurrentTenantId();

        // Default criteria if not provided
        $criteria = array_merge([
            'min_score' => 80,
            'modules_completed' => 5
        ], $criteria);

        // Get user progress
        $progress = LearningProgress::byTenant($tenantId)
            ->byUser($userId)
            ->where('course_id', $criteria['course_id'] ?? null)
            ->first();

        if (!$progress) {
            return [
                'eligible' => false,
                'score' => 0,
                'reason' => 'No progress data found'
            ];
        }

        $eligible = $progress->total_score >= $criteria['min_score'] &&
                   $progress->modules_completed >= $criteria['modules_completed'];

        $result = [
            'eligible' => $eligible,
            'score' => $progress->total_score,
            'modules_completed' => $progress->modules_completed,
            'engagement_score' => $progress->engagement_score
        ];

        // Update prediction model if available
        if ($eligible && class_exists('\App\Services\Analytics\CareerPredictionService')) {
            try {
                app(\App\Services\Analytics\CareerPredictionService::class)
                    ->updateLearningImpact($userId, $progress->toArray());
            } catch (\Exception $e) {
                Log::warning('Failed to update career prediction model', [
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $result;
    }

    /**
     * Generate learning insights and trends
     *
     * @param array $filters Optional filters (date_range, course_id, etc.)
     * @return array Learning insights data
     */
    public function generateLearningInsights(array $filters = []): array
    {
        $tenantId = $this->getCurrentTenantId();

        $query = AnalyticsEvent::byTenant($tenantId)
            ->byEventType('learning')
            ->byDateRange(
                $filters['start_date'] ?? now()->subDays(30),
                $filters['end_date'] ?? now()
            );

        if (isset($filters['course_id'])) {
            $query->whereJsonContains('properties->course_id', $filters['course_id']);
        }

        $events = $query->get();

        if ($events->isEmpty()) {
            return [
                'total_interactions' => 0,
                'dropout_rate' => 0,
                'avg_completion_time' => 0,
                'engagement_trends' => [],
                'anomalies' => []
            ];
        }

        // Calculate insights
        $insights = [
            'total_interactions' => $events->count(),
            'unique_users' => $events->unique('user_id')->count(),
            'avg_duration_per_session' => $events->avg('properties.duration') ?? 0,
            'completion_rate' => $this->calculateCompletionRate($events),
            'dropout_rate' => $this->calculateDropoutRate($events),
            'avg_completion_time' => $this->calculateAvgCompletionTime($events),
            'engagement_trends' => $this->calculateEngagementTrends($events),
            'anomalies' => $this->detectAnomalies($events)
        ];

        // Integrate with SyncService if available
        if (isset($filters['sync']) && class_exists('\App\Services\Analytics\SyncService')) {
            $syncService = app(\App\Services\Analytics\SyncService::class);
            $insights = array_merge($insights, $syncService->unifyLearningData($insights));
        }

        return $insights;
    }

    /**
     * Generate certification insights for user
     *
     * @param int $userId User ID
     * @return array Certification insights with career impact
     */
    public function generateCertificationInsights(int $userId): array
    {
        $tenantId = $this->getCurrentTenantId();

        // Get user's learning progress and certifications
        $progressRecords = LearningProgress::byTenant($tenantId)
            ->byUser($userId)
            ->with('course')
            ->get();

        $certifications = [];
        $careerImpact = 0;

        foreach ($progressRecords as $progress) {
            if ($progress->certifications) {
                foreach ($progress->certifications as $cert) {
                    $certifications[] = [
                        'certification_id' => $cert['cert_id'],
                        'course_name' => $progress->course->name ?? 'Unknown Course',
                        'issued_at' => $cert['issued_at'],
                        'score' => $cert['score'] ?? 0,
                        'impact_score' => $this->calculateCertificationImpact($cert['cert_id'], $cert['score'] ?? 0)
                    ];
                    $careerImpact += $this->calculateCertificationImpact($cert['cert_id'], $cert['score'] ?? 0);
                }
            }
        }

        // Update CareerPredictionService if available
        if (class_exists('\App\Services\Analytics\CareerPredictionService')) {
            try {
                app(\App\Services\Analytics\CareerPredictionService::class)
                    ->updateLearningImpact($userId, [
                        'certifications_count' => count($certifications),
                        'total_career_impact' => $careerImpact,
                        'avg_certification_score' => count($certifications) > 0
                            ? array_sum(array_column($certifications, 'score')) / count($certifications)
                            : 0
                    ]);
            } catch (\Exception $e) {
                Log::warning('Failed to update career prediction model', [
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'certifications' => $certifications,
            'total_certifications' => count($certifications),
            'career_impact_score' => $careerImpact,
            'employability_boost' => min($careerImpact * 0.1, 10), // Max 10% boost
            'insights' => $this->generateCertificationInsightsText($certifications, $careerImpact)
        ];
    }

    /**
     * Calculate certification impact score
     */
    private function calculateCertificationImpact(string $certId, float $score): float
    {
        // Base impact by certification type + score modifier
        $baseImpact = match($certId) {
            'advanced', 'expert' => 15,
            'intermediate' => 10,
            'beginner', 'fundamental' => 5,
            default => 8
        };

        return $baseImpact * (0.5 + ($score / 200)); // Score ranges 0-100, so 0.5-1.0 multiplier
    }

    /**
     * Generate human-readable certification insights
     */
    private function generateCertificationInsightsText(array $certifications, float $careerImpact): array
    {
        $insights = [];

        if (empty($certifications)) {
            $insights[] = 'No certifications earned yet. Focus on completing courses to boost career prospects.';
            return $insights;
        }

        $insights[] = sprintf('Earned %d certification(s) with total career impact score of %.1f',
            count($certifications), $careerImpact);

        if ($careerImpact > 20) {
            $insights[] = 'Strong certification portfolio - excellent career advancement potential';
        } elseif ($careerImpact > 10) {
            $insights[] = 'Good certification foundation - continue building expertise';
        } else {
            $insights[] = 'Building certification credentials - focus on high-impact courses';
        }

        // Check for certification gaps or patterns
        $certTypes = array_column($certifications, 'certification_id');
        if (count(array_unique($certTypes)) < count($certifications)) {
            $insights[] = 'Multiple certifications in same area - consider diversifying skill set';
        }

        return $insights;
    }

    /**
     * Process batch learning score calculations
     *
     * @param array $userCoursePairs Array of [user_id, course_id] pairs
     * @return array Processing results
     */
    public function processBatchScores(array $userCoursePairs): array
    {
        $results = ['processed' => 0, 'errors' => 0];

        foreach ($userCoursePairs as $pair) {
            try {
                $score = $this->calculateEngagementScore($pair['user_id'], $pair['course_id']);

                // Update progress record
                LearningProgress::updateOrCreate(
                    [
                        'tenant_id' => $this->getCurrentTenantId(),
                        'user_id' => $pair['user_id'],
                        'course_id' => $pair['course_id']
                    ],
                    ['engagement_score' => $score]
                );

                $results['processed']++;
            } catch (\Exception $e) {
                Log::error('Failed to process learning score', [
                    'user_id' => $pair['user_id'],
                    'course_id' => $pair['course_id'],
                    'error' => $e->getMessage()
                ]);
                $results['errors']++;
            }
        }

        return $results;
    }

    /**
     * Check user consent for analytics tracking
     */
    private function checkConsent(int $userId): bool
    {
        return app(ConsentService::class)->checkConsent($userId, 'analytics');
    }

    /**
     * Update learning progress based on interaction
     */
    private function updateLearningProgress(int $userId, int $courseId, array $interactionData): void
    {
        $tenantId = $this->getCurrentTenantId();

        $progress = LearningProgress::firstOrNew([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'course_id' => $courseId
        ]);

        // Update based on interaction type
        switch ($interactionData['interaction_type'] ?? 'view') {
            case 'completion':
                $progress->modules_completed = ($progress->modules_completed ?? 0) + 1;
                break;
            case 'quiz':
                if (isset($interactionData['score'])) {
                    $progress->total_score = (($progress->total_score ?? 0) + $interactionData['score']) / 2;
                }
                break;
        }

        // Recalculate engagement score
        $progress->engagement_score = $this->calculateEngagementScore($userId, $courseId);
        $progress->save();
    }

    /**
     * Clear engagement score cache
     */
    private function clearEngagementCache(int $userId, int $courseId): void
    {
        $cacheKey = sprintf(self::ENGAGEMENT_CACHE_KEY, $userId, $courseId);
        Cache::forget($cacheKey);
    }

    /**
     * Calculate completion rate from events
     */
    private function calculateCompletionRate(Collection $events): float
    {
        $totalInteractions = $events->count();
        if ($totalInteractions === 0) return 0;

        $completions = $events->where('properties.interaction_type', 'completion')->count();
        return round(($completions / $totalInteractions) * 100, 2);
    }

    /**
     * Calculate dropout rate (simplified: users with < 3 interactions in last week)
     */
    private function calculateDropoutRate(Collection $events): float
    {
        $userInteractions = $events->groupBy('user_id');
        $totalUsers = $userInteractions->count();

        if ($totalUsers === 0) return 0;

        $dropoutUsers = $userInteractions->filter(function ($userEvents) {
            return $userEvents->where('occurred_at', '>=', now()->subWeek())->count() < 3;
        })->count();

        return round(($dropoutUsers / $totalUsers) * 100, 2);
    }

    /**
     * Calculate average completion time
     */
    private function calculateAvgCompletionTime(Collection $events): float
    {
        $completionEvents = $events->where('properties.interaction_type', 'completion');
        return $completionEvents->avg('properties.duration') ?? 0;
    }

    /**
     * Calculate engagement trends over time
     */
    private function calculateEngagementTrends(Collection $events): array
    {
        return $events->groupBy(function ($event) {
            return $event->occurred_at->format('Y-m-d');
        })->map(function ($dayEvents) {
            return [
                'date' => $dayEvents->first()->occurred_at->format('Y-m-d'),
                'interactions' => $dayEvents->count(),
                'avg_duration' => $dayEvents->avg('properties.duration') ?? 0
            ];
        })->values()->toArray();
    }

    /**
     * Detect anomalies using z-score (> 2 standard deviations)
     */
    private function detectAnomalies(Collection $events): array
    {
        $durations = $events->pluck('properties.duration')->filter()->values();
        if ($durations->isEmpty()) return [];

        $mean = $durations->avg();
        $stdDev = sqrt($durations->map(fn($d) => pow($d - $mean, 2))->avg());

        if ($stdDev == 0) return [];

        return $events->filter(function ($event) use ($mean, $stdDev) {
            $duration = $event->properties['duration'] ?? 0;
            $zScore = abs($duration - $mean) / $stdDev;
            return $zScore > 2;
        })->map(function ($event) {
            return [
                'user_id' => $event->user_id,
                'course_id' => $event->properties['course_id'] ?? null,
                'duration' => $event->properties['duration'] ?? 0,
                'occurred_at' => $event->occurred_at
            ];
        })->values()->toArray();
    }

    /**
     * Get current tenant ID
     */
    private function getCurrentTenantId(): string
    {
        return session('tenant_id', 'default');
    }
}