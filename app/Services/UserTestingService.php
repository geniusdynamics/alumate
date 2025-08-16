<?php

namespace App\Services;

use App\Models\ABTest;
use App\Models\User;
use App\Models\UserFeedback;
use App\Models\UserTestingSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class UserTestingService
{
    /**
     * Create a new user testing session
     */
    public function createTestingSession(User $user, string $scenario, array $metadata = []): UserTestingSession
    {
        return UserTestingSession::create([
            'user_id' => $user->id,
            'scenario' => $scenario,
            'metadata' => $metadata,
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    /**
     * Record user feedback
     */
    public function recordFeedback(
        User $user,
        string $type,
        string $content,
        ?int $rating = null,
        array $metadata = []
    ): UserFeedback {
        return UserFeedback::create([
            'user_id' => $user->id,
            'type' => $type,
            'content' => $content,
            'rating' => $rating,
            'metadata' => $metadata,
            'status' => 'pending',
        ]);
    }

    /**
     * Get A/B test variant for user
     */
    public function getABTestVariant(User $user, string $testName): ?string
    {
        $cacheKey = "ab_test_{$testName}_{$user->id}";

        return Cache::remember($cacheKey, 3600, function () use ($user, $testName) {
            $test = ABTest::where('name', $testName)
                ->where('status', 'active')
                ->first();

            if (! $test) {
                return null;
            }

            // Check if user is already assigned to a variant
            $existingAssignment = $test->assignments()
                ->where('user_id', $user->id)
                ->first();

            if ($existingAssignment) {
                return $existingAssignment->variant;
            }

            // Assign user to variant based on distribution
            $variant = $this->assignUserToVariant($user, $test);

            $test->assignments()->create([
                'user_id' => $user->id,
                'variant' => $variant,
                'assigned_at' => now(),
            ]);

            return $variant;
        });
    }

    /**
     * Track A/B test conversion
     */
    public function trackConversion(User $user, string $testName, string $event, array $data = []): void
    {
        $test = ABTest::where('name', $testName)->first();

        if (! $test) {
            return;
        }

        $assignment = $test->assignments()
            ->where('user_id', $user->id)
            ->first();

        if (! $assignment) {
            return;
        }

        $test->conversions()->create([
            'user_id' => $user->id,
            'variant' => $assignment->variant,
            'event' => $event,
            'data' => $data,
            'converted_at' => now(),
        ]);
    }

    /**
     * Get user experience metrics
     */
    public function getUserExperienceMetrics(User $user, ?string $dateRange = null): array
    {
        $query = UserTestingSession::where('user_id', $user->id);

        if ($dateRange) {
            $query->where('created_at', '>=', now()->sub($dateRange));
        }

        $sessions = $query->get();

        return [
            'total_sessions' => $sessions->count(),
            'completed_sessions' => $sessions->where('status', 'completed')->count(),
            'average_duration' => $sessions->avg('duration_seconds'),
            'scenarios_tested' => $sessions->pluck('scenario')->unique()->count(),
            'feedback_count' => UserFeedback::where('user_id', $user->id)->count(),
            'average_rating' => UserFeedback::where('user_id', $user->id)
                ->whereNotNull('rating')
                ->avg('rating'),
        ];
    }

    /**
     * Get testing analytics for administrators
     */
    public function getTestingAnalytics(?string $dateRange = null): array
    {
        $query = UserTestingSession::query();

        if ($dateRange) {
            $query->where('created_at', '>=', now()->sub($dateRange));
        }

        $sessions = $query->get();
        $feedback = UserFeedback::query();

        if ($dateRange) {
            $feedback->where('created_at', '>=', now()->sub($dateRange));
        }

        $feedbackData = $feedback->get();

        return [
            'total_sessions' => $sessions->count(),
            'unique_users' => $sessions->pluck('user_id')->unique()->count(),
            'completion_rate' => $sessions->where('status', 'completed')->count() / max($sessions->count(), 1),
            'average_session_duration' => $sessions->avg('duration_seconds'),
            'feedback_summary' => [
                'total_feedback' => $feedbackData->count(),
                'average_rating' => $feedbackData->whereNotNull('rating')->avg('rating'),
                'feedback_by_type' => $feedbackData->groupBy('type')->map->count(),
                'recent_feedback' => $feedbackData->sortByDesc('created_at')->take(10),
            ],
            'popular_scenarios' => $sessions->groupBy('scenario')
                ->map->count()
                ->sortDesc()
                ->take(10),
        ];
    }

    /**
     * Create A/B test
     */
    public function createABTest(
        string $name,
        string $description,
        array $variants,
        array $distribution = []
    ): ABTest {
        // Default equal distribution if not provided
        if (empty($distribution)) {
            $variantCount = count($variants);
            $distribution = array_fill_keys($variants, 100 / $variantCount);
        }

        return ABTest::create([
            'name' => $name,
            'description' => $description,
            'variants' => $variants,
            'distribution' => $distribution,
            'status' => 'draft',
        ]);
    }

    /**
     * Generate user testing scenarios
     */
    public function getTestingScenarios(): Collection
    {
        return collect([
            [
                'name' => 'alumni_onboarding',
                'title' => 'Alumni Onboarding Flow',
                'description' => 'Test the complete onboarding experience for new alumni',
                'steps' => [
                    'Register new account',
                    'Complete profile setup',
                    'Connect with classmates',
                    'Join relevant groups',
                    'Create first post',
                ],
                'success_criteria' => [
                    'Profile completion rate > 80%',
                    'At least 3 connections made',
                    'Joined at least 1 group',
                ],
            ],
            [
                'name' => 'job_search_workflow',
                'title' => 'Job Search and Application',
                'description' => 'Test job discovery and application process',
                'steps' => [
                    'Browse job listings',
                    'Use search filters',
                    'View job details',
                    'Request introduction',
                    'Apply for position',
                ],
                'success_criteria' => [
                    'Found relevant jobs within 5 minutes',
                    'Successfully requested introduction',
                    'Completed application process',
                ],
            ],
            [
                'name' => 'mentorship_connection',
                'title' => 'Mentorship Matching',
                'description' => 'Test mentorship discovery and connection',
                'steps' => [
                    'Browse mentor profiles',
                    'Filter by industry/expertise',
                    'Send mentorship request',
                    'Schedule initial meeting',
                ],
                'success_criteria' => [
                    'Found suitable mentor within 10 minutes',
                    'Successfully sent request',
                    'Scheduled meeting',
                ],
            ],
            [
                'name' => 'event_participation',
                'title' => 'Event Discovery and RSVP',
                'description' => 'Test event browsing and registration',
                'steps' => [
                    'Browse upcoming events',
                    'Filter by location/type',
                    'View event details',
                    'RSVP to event',
                    'Add to calendar',
                ],
                'success_criteria' => [
                    'Found relevant event',
                    'Successfully registered',
                    'Calendar integration worked',
                ],
            ],
        ]);
    }

    /**
     * Assign user to A/B test variant
     */
    private function assignUserToVariant(User $user, ABTest $test): string
    {
        $hash = crc32($user->id.$test->name) % 100;
        $cumulative = 0;

        foreach ($test->distribution as $variant => $percentage) {
            $cumulative += $percentage;
            if ($hash < $cumulative) {
                return $variant;
            }
        }

        // Fallback to first variant
        return array_keys($test->variants)[0];
    }
}
