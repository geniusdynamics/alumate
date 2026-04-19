<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Course;
use App\Models\LearningProgress;
use App\Models\User;
use App\Services\TenantContextService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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

    private const PROGRESS_CACHE_KEY = 'learning_progress_%s_%s';

    private const METRICS_CACHE_KEY = 'learning_metrics_%s_%s_%s';

    public function __construct(
        private TenantContextService $tenantContextService
    ) {}

    /**
     * Track learning progress for a user in a course
     *
     * @param  int  $userId  User ID
     * @param  int  $courseId  Course ID
     * @param  array  $progress  Progress data
     */
    public function trackLearningProgress(int $userId, int $courseId, array $progress): LearningProgress
    {
        $tenantId = $this->getCurrentTenantId();

        // Check consent before tracking
        if (! $this->checkConsent($userId)) {
            Log::info('Learning analytics tracking skipped due to lack of consent', [
                'user_id' => $userId,
                'course_id' => $courseId,
            ]);
            throw new \Exception('User has not consented to analytics tracking');
        }

        $progressData = array_merge([
            'progress_percentage' => 0,
            'modules_completed' => 0,
            'engagement_duration' => 0,
            'interactions_count' => 0,
            'total_score' => 0,
        ], $progress);

        $learningProgress = LearningProgress::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'course_id' => $courseId,
            ],
            [
                'progress_percentage' => $progressData['progress_percentage'],
                'modules_completed' => $progressData['modules_completed'],
                'engagement_duration' => $progressData['engagement_duration'],
                'interactions_count' => $progressData['interactions_count'],
                'total_score' => $progressData['total_score'],
                'engagement_score' => $this->calculateEngagementScore($userId, $courseId),
            ]
        );

        // Clear relevant caches
        $this->clearProgressCache($userId, $courseId);

        // Log the progress update
        $this->logProgressEvent($userId, $courseId, $progressData);

        return $learningProgress;
    }

    /**
     * Get learning progress for a user in a course
     *
     * @param  int  $userId  User ID
     * @param  int  $courseId  Course ID
     */
    public function getLearningProgress(int $userId, int $courseId): ?array
    {
        $cacheKey = sprintf(self::PROGRESS_CACHE_KEY, $userId, $courseId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $courseId) {
            $progress = LearningProgress::byTenant($this->getCurrentTenantId())
                ->byUser($userId)
                ->byCourse($courseId)
                ->with('course')
                ->first();

            if (! $progress) {
                return null;
            }

            return [
                'user_id' => $progress->user_id,
                'course_id' => $progress->course_id,
                'course_name' => $progress->course->title ?? 'Unknown Course',
                'progress_percentage' => $progress->progress_percentage,
                'modules_completed' => $progress->modules_completed,
                'total_modules' => $progress->course->modules_count ?? 10,
                'engagement_duration' => $progress->engagement_duration,
                'interactions_count' => $progress->interactions_count,
                'total_score' => $progress->total_score,
                'engagement_score' => $progress->engagement_score,
                'certified' => $progress->certified,
                'started_at' => $progress->created_at,
                'last_activity' => $progress->updated_at,
            ];
        });
    }

    /**
     * Analyze learning outcomes for a user
     *
     * @param  int  $userId  User ID
     * @return array Learning outcomes analysis
     */
    public function analyzeLearningOutcomes(int $userId): array
    {
        $tenantId = $this->getCurrentTenantId();
        $cacheKey = "learning_outcomes_{$tenantId}_{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $tenantId) {
            $progressRecords = LearningProgress::byTenant($tenantId)
                ->byUser($userId)
                ->with('course')
                ->get();

            if ($progressRecords->isEmpty()) {
                return [
                    'user_id' => $userId,
                    'total_courses' => 0,
                    'completed_courses' => 0,
                    'in_progress_courses' => 0,
                    'average_score' => 0,
                    'average_engagement' => 0,
                    'certifications_earned' => 0,
                    'total_learning_time' => 0,
                    'strengths' => [],
                    'areas_for_improvement' => [],
                    'overall_performance' => 'insufficient_data',
                ];
            }

            // Calculate metrics
            $completed = $progressRecords->where('progress_percentage', 100);
            $inProgress = $progressRecords->where('progress_percentage', '<', 100);
            $avgScore = $progressRecords->avg('total_score') ?? 0;
            $avgEngagement = $progressRecords->avg('engagement_score') ?? 0;
            $totalTime = $progressRecords->sum('engagement_duration');
            $certifications = $progressRecords->where('certified', true)->count();

            // Analyze strengths and weaknesses
            $analysis = $this->analyzePerformanceAreas($progressRecords);

            // Determine overall performance
            $performance = $this->determineOverallPerformance($avgScore, $avgEngagement, $completed->count(), $progressRecords->count());

            return [
                'user_id' => $userId,
                'total_courses' => $progressRecords->count(),
                'completed_courses' => $completed->count(),
                'in_progress_courses' => $inProgress->count(),
                'completion_rate' => round(($completed->count() / $progressRecords->count()) * 100, 2),
                'average_score' => round($avgScore, 2),
                'average_engagement' => round($avgEngagement, 2),
                'certifications_earned' => $certifications,
                'total_learning_time_minutes' => $totalTime,
                'total_learning_time_hours' => round($totalTime / 60, 2),
                'strengths' => $analysis['strengths'],
                'areas_for_improvement' => $analysis['weaknesses'],
                'overall_performance' => $performance,
                'courses' => $progressRecords->map(function ($progress) {
                    return [
                        'course_id' => $progress->course_id,
                        'course_name' => $progress->course->title ?? 'Unknown',
                        'progress' => $progress->progress_percentage,
                        'score' => $progress->total_score,
                        'engagement' => $progress->engagement_score,
                        'status' => $progress->progress_percentage >= 100 ? 'completed' : 'in_progress',
                    ];
                })->toArray(),
            ];
        });
    }

    /**
     * Get learning metrics for a user within a date range
     *
     * @param  int  $userId  User ID
     * @param  array  $dateRange  Date range ['start' => Carbon, 'end' => Carbon]
     * @return array Learning metrics
     */
    public function getLearningMetrics(int $userId, array $dateRange): array
    {
        $tenantId = $this->getCurrentTenantId();
        $cacheKey = sprintf(
            self::METRICS_CACHE_KEY,
            $tenantId,
            $userId,
            md5(serialize($dateRange))
        );

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $dateRange, $tenantId) {
            $startDate = $dateRange['start'] ?? now()->subDays(30);
            $endDate = $dateRange['end'] ?? now();

            // Get learning events in date range
            $events = AnalyticsEvent::byTenant($tenantId)
                ->byUser($userId)
                ->byEventType('learning')
                ->whereBetween('occurred_at', [$startDate, $endDate])
                ->get();

            // Get progress records
            $progressRecords = LearningProgress::byTenant($tenantId)
                ->byUser($userId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            return [
                'user_id' => $userId,
                'period' => [
                    'start' => $startDate->toIso8601String(),
                    'end' => $endDate->toIso8601String(),
                ],
                'events_summary' => [
                    'total_events' => $events->count(),
                    'unique_event_types' => $events->pluck('event_name')->unique()->count(),
                    'events_by_type' => $events->groupBy('event_name')->map(fn ($g) => $count = $g->count())->toArray(),
                ],
                'time_metrics' => [
                    'total_learning_time_minutes' => $events->sum('properties.duration') ?? 0,
                    'average_session_duration' => $events->avg('properties.duration') ?? 0,
                    'total_sessions' => $events->groupBy(function ($e) {
                        return $e->occurred_at->format('Y-m-d');
                    })->count(),
                ],
                'progress_metrics' => [
                    'courses_started' => $progressRecords->count(),
                    'modules_completed' => $progressRecords->sum('modules_completed') ?? 0,
                    'average_progress_change' => $progressRecords->avg('progress_percentage') ?? 0,
                ],
                'engagement_metrics' => [
                    'total_interactions' => $progressRecords->sum('interactions_count') ?? 0,
                    'average_engagement_score' => $progressRecords->avg('engagement_score') ?? 0,
                    'peak_learning_days' => $this->getPeakLearningDays($events),
                ],
                'trends' => $this->calculateLearningTrends($events, $dateRange),
            ];
        });
    }

    /**
     * Compare learning performance across multiple users
     *
     * @param  array  $userIds  Array of user IDs
     * @return array Comparison results
     */
    public function compareLearningPerformance(array $userIds): array
    {
        $tenantId = $this->getCurrentTenantId();
        $cacheKey = 'learning_comparison_'.md5(serialize($userIds))."_{$tenantId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userIds) {
            $results = [];
            $rankings = [];

            foreach ($userIds as $userId) {
                $outcomes = $this->analyzeLearningOutcomes($userId);

                $results[$userId] = [
                    'user_id' => $userId,
                    'total_courses' => $outcomes['total_courses'],
                    'completed_courses' => $outcomes['completed_courses'],
                    'completion_rate' => $outcomes['completion_rate'],
                    'average_score' => $outcomes['average_score'],
                    'average_engagement' => $outcomes['average_engagement'],
                    'total_learning_time' => $outcomes['total_learning_time_hours'],
                    'certifications_earned' => $outcomes['certifications_earned'],
                ];

                // Calculate composite score for ranking
                $rankings[$userId] = $this->calculateCompositeScore($outcomes);
            }

            // Sort by composite score
            arsort($rankings);

            $rankedResults = [];
            $rank = 1;
            foreach ($rankings as $userId => $score) {
                $result = $results[$userId];
                $result['rank'] = $rank;
                $result['composite_score'] = round($score, 2);
                $rankedResults[] = $result;
                $rank++;
            }

            return [
                'compared_users' => count($userIds),
                'rankings' => $rankedResults,
                'statistics' => [
                    'average_completion_rate' => round(array_sum(array_column($results, 'completion_rate')) / count($results), 2),
                    'average_score' => round(array_sum(array_column($results, 'average_score')) / count($results), 2),
                    'average_engagement' => round(array_sum(array_column($results, 'average_engagement')) / count($results), 2),
                    'top_performer' => array_keys($rankings)[0] ?? null,
                    'most_improved' => $this->identifyMostImproved($results),
                ],
            ];
        });
    }

    /**
     * Predict learning completion for a user in a course
     *
     * @param  int  $userId  User ID
     * @param  int  $courseId  Course ID
     * @return array Prediction results
     */
    public function predictLearningCompletion(int $userId, int $courseId): array
    {
        $tenantId = $this->getCurrentTenantId();
        $cacheKey = "completion_prediction_{$tenantId}_{$userId}_{$courseId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $courseId, $tenantId) {
            $progress = LearningProgress::byTenant($tenantId)
                ->byUser($userId)
                ->byCourse($courseId)
                ->first();

            $course = Course::byTenant($tenantId)->find($courseId);
            $totalModules = $course->modules_count ?? 10;
            $completedModules = $progress->modules_completed ?? 0;
            $remainingModules = $totalModules - $completedModules;

            if ($remainingModules <= 0) {
                return [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'status' => 'completed',
                    'completion_percentage' => 100,
                    'predicted_completion_date' => now()->toIso8601String(),
                    'days_remaining' => 0,
                    'confidence' => 1.0,
                    'factors' => [
                        'progress_rate' => 100,
                        'engagement_level' => $progress->engagement_score ?? 0,
                        'historical_completion_rate' => 100,
                    ],
                ];
            }

            // Calculate historical completion rate for similar users
            $similarProgress = LearningProgress::byTenant($tenantId)
                ->byCourse($courseId)
                ->where('progress_percentage', '>', 0)
                ->where('progress_percentage', '<', 100)
                ->get();

            $avgProgressRate = $similarProgress->avg('progress_percentage') ?? 0;
            $avgTimePerModule = $this->calculateAvgTimePerModule($similarProgress);

            // Predict completion based on current pace
            $currentProgressRate = $progress->progress_percentage > 0
                ? ($progress->progress_percentage / max(1, $this->getDaysSinceStart($progress)))
                : $avgProgressRate;

            $daysToComplete = $currentProgressRate > 0
                ? ((100 - $progress->progress_percentage) / $currentProgressRate)
                : ($remainingModules * $avgTimePerModule);

            $predictedCompletionDate = now()->addDays((int) round($daysToComplete));

            // Calculate confidence based on consistency
            $confidence = $this->calculatePredictionConfidence($progress, $similarProgress);

            return [
                'user_id' => $userId,
                'course_id' => $courseId,
                'status' => 'in_progress',
                'current_progress' => $progress->progress_percentage ?? 0,
                'completed_modules' => $completedModules,
                'total_modules' => $totalModules,
                'predicted_completion_date' => $predictedCompletionDate->toIso8601String(),
                'days_remaining' => (int) round($daysToComplete),
                'confidence' => round($confidence, 2),
                'factors' => [
                    'current_progress_rate' => round($currentProgressRate, 2),
                    'avg_progress_rate' => round($avgProgressRate, 2),
                    'engagement_level' => $progress->engagement_score ?? 0,
                    'historical_completion_rate' => round($similarProgress->where('progress_percentage', 100)->count() / max(1, $similarProgress->count()) * 100, 2),
                ],
                'recommendations' => $this->generateCompletionRecommendations($progress, $daysToComplete),
            ];
        });
    }

    /**
     * Get learning recommendations for a user
     *
     * @param  int  $userId  User ID
     * @return array Learning recommendations
     */
    public function getLearningRecommendations(int $userId): array
    {
        $tenantId = $this->getCurrentTenantId();
        $cacheKey = "learning_recommendations_{$tenantId}_{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $tenantId) {
            $outcomes = $this->analyzeLearningOutcomes($userId);
            $recommendations = [];

            // Based on performance analysis
            foreach ($outcomes['areas_for_improvement'] as $area) {
                $recommendations[] = [
                    'type' => 'improvement',
                    'category' => $area['category'],
                    'priority' => $area['severity'],
                    'title' => "Improve {$area['category']}",
                    'description' => $area['description'],
                    'action' => $area['recommendation'],
                ];
            }

            // Check for incomplete courses
            $incompleteCourses = LearningProgress::byTenant($tenantId)
                ->byUser($userId)
                ->where('progress_percentage', '<', 100)
                ->where('progress_percentage', '>', 0)
                ->get();

            foreach ($incompleteCourses as $courseProgress) {
                $prediction = $this->predictLearningCompletion($userId, $courseProgress->course_id);
                $courseTitle = $courseProgress->course->title ?? 'Course';

                if ($prediction['days_remaining'] > 14) {
                    $recommendations[] = [
                        'type' => 'completion',
                        'course_id' => $courseProgress->course_id,
                        'priority' => 'medium',
                        'title' => "Complete {$courseTitle}",
                        'description' => "You're {$courseProgress->progress_percentage}% complete. Keep going!",
                        'estimated_time' => $prediction['days_remaining'].' days',
                    ];
                }
            }

            // Recommend new courses based on strengths
            if (! empty($outcomes['strengths'])) {
                $recommendedCourses = $this->recommendCoursesBasedOnStrengths(
                    $userId,
                    array_column($outcomes['strengths'], 'category')
                );

                foreach ($recommendedCourses as $course) {
                    $recommendations[] = [
                        'type' => 'course_recommendation',
                        'course_id' => $course['id'],
                        'priority' => 'low',
                        'title' => "Explore {$course['title']}",
                        'description' => $course['description'],
                        'match_reason' => "Based on your strength in {$course['matched_skill']}",
                    ];
                }
            }

            // Sort by priority
            $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
            usort($recommendations, function ($a, $b) use ($priorityOrder) {
                return ($priorityOrder[$a['priority']] ?? 3) - ($priorityOrder[$b['priority']] ?? 3);
            });

            return [
                'user_id' => $userId,
                'total_recommendations' => count($recommendations),
                'recommendations' => $recommendations,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Track learning activity for a user
     *
     * @param  int  $userId  User ID
     * @param  array  $activity  Activity data
     */
    public function trackLearningActivity(int $userId, array $activity): AnalyticsEvent
    {
        $tenantId = $this->getCurrentTenantId();

        // Check consent
        if (! $this->checkConsent($userId)) {
            Log::info('Learning activity tracking skipped due to lack of consent', [
                'user_id' => $userId,
            ]);
            throw new \Exception('User has not consented to analytics tracking');
        }

        $event = AnalyticsEvent::create([
            'tenant_id' => $tenantId,
            'event_type' => 'learning',
            'event_name' => $activity['event_name'] ?? 'activity',
            'user_id' => $userId,
            'properties' => array_merge([
                'activity_type' => $activity['activity_type'] ?? 'general',
                'duration' => $activity['duration'] ?? 0,
                'module_id' => $activity['module_id'] ?? null,
                'resource_type' => $activity['resource_type'] ?? null,
                'resource_id' => $activity['resource_id'] ?? null,
            ], $activity['properties'] ?? []),
            'occurred_at' => $activity['occurred_at'] ?? now(),
            'is_compliant' => true,
            'consent_given' => true,
            'analytics_version' => '1.0',
        ]);

        // Update progress if applicable
        if (isset($activity['course_id']) && isset($activity['progress_update'])) {
            $this->trackLearningProgress(
                $userId,
                $activity['course_id'],
                $activity['progress_update']
            );
        }

        return $event;
    }

    /**
     * Calculate engagement score for user-course combination
     *
     * Formula: (duration * 0.4 + interactions * 0.3 + completion * 0.3) normalized to 0-100
     *
     * @param  int  $userId  User ID
     * @param  int  $courseId  Course ID
     * @return float Engagement score (0-100)
     */
    public function calculateEngagementScore(int $userId, int $courseId): float
    {
        $cacheKey = sprintf(self::ENGAGEMENT_CACHE_KEY, $userId, $courseId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $courseId) {
            $progress = LearningProgress::byTenant($this->getCurrentTenantId())
                ->byUser($userId)
                ->byCourse($courseId)
                ->first();

            if (! $progress) {
                return 0.0;
            }

            // Calculate components
            $durationScore = min(($progress->engagement_duration ?? 0) / 600, 1) * 100; // Max 10 hours
            $interactionsScore = min(($progress->interactions_count ?? 0) / 100, 1) * 100; // Max 100 interactions
            $completionScore = $progress->progress_percentage ?? 0; // Already 0-100

            // Weighted formula
            $rawScore = ($durationScore * 0.4) + ($interactionsScore * 0.3) + ($completionScore * 0.3);

            return round(min($rawScore, 100.0), 2);
        });
    }

    /**
     * Verify certification eligibility
     *
     * @param  int  $userId  User ID
     * @param  array  $criteria  Certification criteria
     * @return array Eligibility result with score
     */
    public function verifyCertification(int $userId, array $criteria = []): array
    {
        $tenantId = $this->getCurrentTenantId();

        $criteria = array_merge([
            'min_score' => 80,
            'modules_completed' => 5,
            'min_engagement' => 50,
        ], $criteria);

        $progress = LearningProgress::byTenant($tenantId)
            ->byUser($userId)
            ->where('course_id', $criteria['course_id'] ?? null)
            ->first();

        if (! $progress) {
            return [
                'eligible' => false,
                'score' => 0,
                'reason' => 'No progress data found',
            ];
        }

        $eligible = ($progress->total_score ?? 0) >= $criteria['min_score'] &&
                   ($progress->modules_completed ?? 0) >= $criteria['modules_completed'] &&
                   ($progress->engagement_score ?? 0) >= $criteria['min_engagement'];

        return [
            'eligible' => $eligible,
            'score' => $progress->total_score ?? 0,
            'modules_completed' => $progress->modules_completed ?? 0,
            'engagement_score' => $progress->engagement_score ?? 0,
            'criteria_met' => [
                'min_score' => ($progress->total_score ?? 0) >= $criteria['min_score'],
                'modules_completed' => ($progress->modules_completed ?? 0) >= $criteria['modules_completed'],
                'min_engagement' => ($progress->engagement_score ?? 0) >= $criteria['min_engagement'],
            ],
        ];
    }

    /**
     * Generate learning insights and trends
     *
     * @param  array  $filters  Optional filters
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
                'anomalies' => [],
            ];
        }

        return [
            'total_interactions' => $events->count(),
            'unique_users' => $events->unique('user_id')->count(),
            'avg_duration_per_session' => $events->avg('properties.duration') ?? 0,
            'completion_rate' => $this->calculateCompletionRate($events),
            'dropout_rate' => $this->calculateDropoutRate($events),
            'avg_completion_time' => $this->calculateAvgCompletionTime($events),
            'engagement_trends' => $this->calculateEngagementTrends($events),
            'anomalies' => $this->detectAnomalies($events),
        ];
    }

    /**
     * Process batch learning score calculations
     *
     * @param  array  $userCoursePairs  Array of [user_id, course_id] pairs
     * @return array Processing results
     */
    public function processBatchScores(array $userCoursePairs): array
    {
        $results = ['processed' => 0, 'errors' => 0];

        foreach ($userCoursePairs as $pair) {
            try {
                $score = $this->calculateEngagementScore($pair['user_id'], $pair['course_id']);

                LearningProgress::updateOrCreate(
                    [
                        'tenant_id' => $this->getCurrentTenantId(),
                        'user_id' => $pair['user_id'],
                        'course_id' => $pair['course_id'],
                    ],
                    ['engagement_score' => $score]
                );

                $results['processed']++;
            } catch (\Exception $e) {
                Log::error('Failed to process learning score', [
                    'user_id' => $pair['user_id'],
                    'course_id' => $pair['course_id'],
                    'error' => $e->getMessage(),
                ]);
                $results['errors']++;
            }
        }

        return $results;
    }

    // ============ Private Helper Methods ============

    /**
     * Check user consent for analytics tracking
     */
    private function checkConsent(int $userId): bool
    {
        return app(ConsentService::class)->hasConsent($userId, 'analytics');
    }

    /**
     * Get current tenant ID
     */
    private function getCurrentTenantId(): string
    {
        return $this->tenantContextService->getCurrentTenantId() ?? 'default';
    }

    /**
     * Clear progress cache
     */
    private function clearProgressCache(int $userId, int $courseId): void
    {
        $tenantId = $this->getCurrentTenantId();
        Cache::forget(sprintf(self::PROGRESS_CACHE_KEY, $userId, $courseId));
        Cache::forget("learning_outcomes_{$tenantId}_{$userId}");
        Cache::forget(sprintf(self::ENGAGEMENT_CACHE_KEY, $userId, $courseId));
    }

    /**
     * Log progress event
     */
    private function logProgressEvent(int $userId, int $courseId, array $progressData): void
    {
        try {
            AnalyticsEvent::create([
                'tenant_id' => $this->getCurrentTenantId(),
                'event_type' => 'learning',
                'event_name' => 'progress_update',
                'user_id' => $userId,
                'properties' => [
                    'course_id' => $courseId,
                    'progress_percentage' => $progressData['progress_percentage'],
                    'modules_completed' => $progressData['modules_completed'],
                ],
                'occurred_at' => now(),
                'is_compliant' => true,
                'consent_given' => true,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log progress event', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Analyze performance areas for strengths and weaknesses
     */
    private function analyzePerformanceAreas(Collection $progressRecords): array
    {
        $strengths = [];
        $weaknesses = [];

        $avgScore = $progressRecords->avg('total_score') ?? 0;
        $avgEngagement = $progressRecords->avg('engagement_score') ?? 0;
        $completionRate = $progressRecords->where('progress_percentage', 100)->count() / max(1, $progressRecords->count());

        if ($avgScore >= 80) {
            $strengths[] = [
                'category' => 'Academic Performance',
                'description' => 'Consistently high scores across courses',
                'score' => round($avgScore, 2),
            ];
        } elseif ($avgScore < 60) {
            $weaknesses[] = [
                'category' => 'Academic Performance',
                'description' => 'Below average scores, may need additional support',
                'severity' => $avgScore < 40 ? 'high' : 'medium',
                'recommendation' => 'Consider reviewing course materials and seeking tutoring',
            ];
        }

        if ($avgEngagement >= 70) {
            $strengths[] = [
                'category' => 'Engagement',
                'description' => 'Highly engaged with learning materials',
                'score' => round($avgEngagement, 2),
            ];
        } elseif ($avgEngagement < 40) {
            $weaknesses[] = [
                'category' => 'Engagement',
                'description' => 'Low engagement with learning materials',
                'severity' => 'medium',
                'recommendation' => 'Try setting smaller goals and tracking progress daily',
            ];
        }

        if ($completionRate >= 0.8) {
            $strengths[] = [
                'category' => 'Persistence',
                'description' => 'High course completion rate',
                'score' => round($completionRate * 100, 2),
            ];
        } elseif ($completionRate < 0.5) {
            $weaknesses[] = [
                'category' => 'Persistence',
                'description' => 'Difficulty completing courses',
                'severity' => 'high',
                'recommendation' => 'Focus on finishing one course at a time before starting new ones',
            ];
        }

        return ['strengths' => $strengths, 'weaknesses' => $weaknesses];
    }

    /**
     * Determine overall performance rating
     */
    private function determineOverallPerformance(float $avgScore, float $avgEngagement, int $completed, int $total): string
    {
        if ($total === 0) {
            return 'insufficient_data';
        }

        $completionRate = $completed / $total;
        $compositeScore = ($avgScore * 0.4) + ($avgEngagement * 0.3) + ($completionRate * 100 * 0.3);

        if ($compositeScore >= 80) {
            return 'excellent';
        } elseif ($compositeScore >= 65) {
            return 'good';
        } elseif ($compositeScore >= 50) {
            return 'average';
        } elseif ($compositeScore >= 35) {
            return 'below_average';
        } else {
            return 'needs_improvement';
        }
    }

    /**
     * Calculate composite score for ranking
     */
    private function calculateCompositeScore(array $outcomes): float
    {
        $score = 0;
        $score += ($outcomes['completion_rate'] ?? 0) * 0.3;
        $score += ($outcomes['average_score'] ?? 0) * 0.35;
        $score += ($outcomes['average_engagement'] ?? 0) * 0.25;
        $score += ($outcomes['certifications_earned'] ?? 0) * 10;

        return $score;
    }

    /**
     * Identify most improved user
     */
    private function identifyMostImproved(array $results): ?int
    {
        // This would need historical data to calculate improvement
        // For now, return null
        return null;
    }

    /**
     * Get peak learning days
     */
    private function getPeakLearningDays(Collection $events): array
    {
        $dayCounts = $events->groupBy(fn ($e) => $e->occurred_at->format('l'))
            ->map(fn ($g) => $g->count())
            ->sortDesc()
            ->take(3)
            ->toArray();

        return $dayCounts;
    }

    /**
     * Calculate learning trends
     */
    private function calculateLearningTrends(Collection $events, array $dateRange): array
    {
        $grouped = $events->groupBy(fn ($e) => $e->occurred_at->format('Y-m-d'));

        $trend = [];
        foreach ($grouped as $date => $dayEvents) {
            $trend[] = [
                'date' => $date,
                'events' => $dayEvents->count(),
                'avg_duration' => $dayEvents->avg('properties.duration') ?? 0,
            ];
        }

        return [
            'daily_breakdown' => $trend,
            'trend_direction' => $this->calculateTrendDirection($trend),
        ];
    }

    /**
     * Calculate trend direction
     */
    private function calculateTrendDirection(array $trend): string
    {
        if (count($trend) < 2) {
            return 'insufficient_data';
        }

        $recentAvg = array_sum(array_column(array_slice($trend, -3), 'events')) / min(3, count($trend));
        $olderAvg = array_sum(array_column(array_slice($trend, 0, -3), 'events')) / max(1, count($trend) - 3);

        if ($recentAvg > $olderAvg * 1.1) {
            return 'improving';
        } elseif ($recentAvg < $olderAvg * 0.9) {
            return 'declining';
        } else {
            return 'stable';
        }
    }

    /**
     * Calculate average time per module
     */
    private function calculateAvgTimePerModule(Collection $progressRecords): float
    {
        $totalModules = $progressRecords->sum('modules_completed') ?? 0;
        $totalTime = $progressRecords->sum('engagement_duration') ?? 0;

        if ($totalModules === 0) {
            return 60; // Default 60 minutes per module
        }

        return $totalTime / $totalModules;
    }

    /**
     * Get days since progress started
     */
    private function getDaysSinceStart(?LearningProgress $progress): int
    {
        if (! $progress) {
            return 1;
        }

        return max(1, (int) $progress->created_at->diffInDays(now()));
    }

    /**
     * Calculate prediction confidence
     */
    private function calculatePredictionConfidence(?LearningProgress $progress, Collection $similarProgress): float
    {
        // Base confidence on consistency of similar users
        $variance = $this->calculateVariance(
            $similarProgress->pluck('progress_percentage')->filter()->toArray()
        );

        // Lower variance = higher confidence
        $baseConfidence = max(0.5, min(0.95, 1 - ($variance / 100)));

        // Adjust based on current user's consistency
        if ($progress && $progress->engagement_score >= 70) {
            $baseConfidence += 0.1;
        }

        return min(1.0, $baseConfidence);
    }

    /**
     * Calculate variance
     */
    private function calculateVariance(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn ($x) => pow($x - $mean, 2), $values)) / count($values);

        return sqrt($variance);
    }

    /**
     * Generate completion recommendations
     */
    private function generateCompletionRecommendations(?LearningProgress $progress, float $daysToComplete): array
    {
        $recommendations = [];

        if ($daysToComplete > 30) {
            $recommendations[] = 'Consider increasing your weekly study time to finish faster';
        }

        if (! $progress || ($progress->engagement_score ?? 0) < 50) {
            $recommendations[] = 'Try breaking your study sessions into smaller, focused 25-minute blocks';
        }

        $recommendations[] = 'Set milestone checkpoints to track your progress weekly';

        return $recommendations;
    }

    /**
     * Recommend courses based on strengths
     */
    private function recommendCoursesBasedOnStrengths(int $userId, array $strengthCategories): array
    {
        // This would typically query available courses and match with strengths
        // For now, return empty array
        return [];
    }

    /**
     * Calculate completion rate from events
     */
    private function calculateCompletionRate(Collection $events): float
    {
        $totalInteractions = $events->count();
        if ($totalInteractions === 0) {
            return 0;
        }

        $completions = $events->where('properties.interaction_type', 'completion')->count();

        return round(($completions / $totalInteractions) * 100, 2);
    }

    /**
     * Calculate dropout rate
     */
    private function calculateDropoutRate(Collection $events): float
    {
        $userInteractions = $events->groupBy('user_id');
        $totalUsers = $userInteractions->count();

        if ($totalUsers === 0) {
            return 0;
        }

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
        return $events->groupBy(fn ($event) => $event->occurred_at->format('Y-m-d'))
            ->map(fn ($dayEvents) => [
                'date' => $dayEvents->first()->occurred_at->format('Y-m-d'),
                'interactions' => $dayEvents->count(),
                'avg_duration' => $dayEvents->avg('properties.duration') ?? 0,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Detect anomalies using z-score
     */
    private function detectAnomalies(Collection $events): array
    {
        $durations = $events->pluck('properties.duration')->filter()->values();
        if ($durations->isEmpty()) {
            return [];
        }

        $mean = $durations->avg();
        $stdDev = sqrt($durations->map(fn ($d) => pow($d - $mean, 2))->avg());

        if ($stdDev == 0) {
            return [];
        }

        return $events->filter(fn ($event) => abs((($event->properties['duration'] ?? 0) - $mean) / $stdDev) > 2)
            ->map(fn ($event) => [
                'user_id' => $event->user_id,
                'course_id' => $event->properties['course_id'] ?? null,
                'duration' => $event->properties['duration'] ?? 0,
                'occurred_at' => $event->occurred_at,
            ])
            ->values()
            ->toArray();
    }
}
