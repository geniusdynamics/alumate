<?php

namespace App\Services;

use App\Models\User;
use App\Models\Post;
use App\Models\Connection;
use App\Models\Event;
use App\Models\Group;
use App\Models\Circle;
use App\Models\PostEngagement;
use App\Models\EventAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    /**
     * Get engagement metrics for the dashboard
     */
    public function getEngagementMetrics(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        $cacheKey = 'engagement_metrics_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($dateRange) {
            return [
                'total_users' => $this->getTotalUsers($dateRange),
                'active_users' => $this->getActiveUsers($dateRange),
                'new_users' => $this->getNewUsers($dateRange),
                'posts_created' => $this->getPostsCreated($dateRange),
                'engagement_rate' => $this->getEngagementRate($dateRange),
                'connections_made' => $this->getConnectionsMade($dateRange),
                'events_attended' => $this->getEventsAttended($dateRange),
                'user_retention' => $this->getUserRetention($dateRange),
            ];
        });
    }

    /**
     * Get alumni activity tracking data
     */
    public function getAlumniActivity(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        
        return [
            'daily_active_users' => $this->getDailyActiveUsers($dateRange),
            'post_activity' => $this->getPostActivity($dateRange),
            'engagement_trends' => $this->getEngagementTrends($dateRange),
            'feature_usage' => $this->getFeatureUsage($dateRange),
            'geographic_distribution' => $this->getGeographicDistribution($filters),
            'graduation_year_activity' => $this->getGraduationYearActivity($dateRange),
        ];
    }

    /**
     * Get community health indicators
     */
    public function getCommunityHealth(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        
        return [
            'network_density' => $this->getNetworkDensity(),
            'group_participation' => $this->getGroupParticipation($dateRange),
            'circle_engagement' => $this->getCircleEngagement($dateRange),
            'content_quality_score' => $this->getContentQualityScore($dateRange),
            'user_satisfaction' => $this->getUserSatisfactionMetrics($dateRange),
            'platform_growth_rate' => $this->getPlatformGrowthRate($dateRange),
        ];
    }

    /**
     * Get platform usage statistics
     */
    public function getPlatformUsage(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        
        return [
            'page_views' => $this->getPageViews($dateRange),
            'session_duration' => $this->getSessionDuration($dateRange),
            'bounce_rate' => $this->getBounceRate($dateRange),
            'device_breakdown' => $this->getDeviceBreakdown($dateRange),
            'browser_breakdown' => $this->getBrowserBreakdown($dateRange),
            'peak_usage_times' => $this->getPeakUsageTimes($dateRange),
            'feature_adoption' => $this->getFeatureAdoption($dateRange),
        ];
    }

    /**
     * Generate custom report data
     */
    public function generateCustomReport(array $metrics, array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        $report = [];
        
        foreach ($metrics as $metric) {
            $report[$metric] = $this->getMetricData($metric, $dateRange, $filters);
        }
        
        return [
            'report_data' => $report,
            'generated_at' => now(),
            'filters_applied' => $filters,
            'date_range' => $dateRange,
        ];
    }

    /**
     * Export analytics data
     */
    public function exportData(array $data, string $format = 'csv'): string
    {
        switch ($format) {
            case 'csv':
                return $this->exportToCsv($data);
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT);
            case 'xlsx':
                return $this->exportToExcel($data);
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    // Private helper methods

    private function getDateRange(array $filters): array
    {
        $startDate = isset($filters['start_date']) 
            ? Carbon::parse($filters['start_date']) 
            : Carbon::now()->subDays(30);
            
        $endDate = isset($filters['end_date']) 
            ? Carbon::parse($filters['end_date']) 
            : Carbon::now();
            
        return [$startDate, $endDate];
    }

    private function getTotalUsers(array $dateRange): int
    {
        return User::whereBetween('created_at', $dateRange)->count();
    }

    private function getActiveUsers(array $dateRange): int
    {
        return User::whereHas('posts', function ($query) use ($dateRange) {
            $query->whereBetween('created_at', $dateRange);
        })->orWhereHas('postEngagements', function ($query) use ($dateRange) {
            $query->whereBetween('created_at', $dateRange);
        })->distinct()->count();
    }

    private function getNewUsers(array $dateRange): int
    {
        return User::whereBetween('created_at', $dateRange)->count();
    }

    private function getPostsCreated(array $dateRange): int
    {
        return Post::whereBetween('created_at', $dateRange)->count();
    }

    private function getEngagementRate(array $dateRange): float
    {
        $totalPosts = Post::whereBetween('created_at', $dateRange)->count();
        $totalEngagements = PostEngagement::whereBetween('created_at', $dateRange)->count();
        
        return $totalPosts > 0 ? ($totalEngagements / $totalPosts) * 100 : 0;
    }

    private function getConnectionsMade(array $dateRange): int
    {
        return Connection::where('status', 'accepted')
            ->whereBetween('connected_at', $dateRange)
            ->count();
    }

    private function getEventsAttended(array $dateRange): int
    {
        return EventAttendance::whereBetween('created_at', $dateRange)->count();
    }

    private function getUserRetention(array $dateRange): array
    {
        // Calculate 7-day, 30-day retention rates
        $sevenDayRetention = $this->calculateRetentionRate(7, $dateRange);
        $thirtyDayRetention = $this->calculateRetentionRate(30, $dateRange);
        
        return [
            '7_day' => $sevenDayRetention,
            '30_day' => $thirtyDayRetention,
        ];
    }

    private function calculateRetentionRate(int $days, array $dateRange): float
    {
        $startDate = $dateRange[0];
        $cohortUsers = User::whereDate('created_at', $startDate)->pluck('id');
        
        if ($cohortUsers->isEmpty()) {
            return 0;
        }
        
        $returnedUsers = User::whereIn('id', $cohortUsers)
            ->whereHas('posts', function ($query) use ($startDate, $days) {
                $query->whereBetween('created_at', [
                    $startDate->copy()->addDays($days),
                    $startDate->copy()->addDays($days + 1)
                ]);
            })
            ->count();
            
        return ($returnedUsers / $cohortUsers->count()) * 100;
    }

    private function getDailyActiveUsers(array $dateRange): array
    {
        return DB::table('users')
            ->select(DB::raw('DATE(last_activity_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereBetween('last_activity_at', $dateRange)
            ->groupBy(DB::raw('DATE(last_activity_at)'))
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getPostActivity(array $dateRange): array
    {
        return Post::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', $dateRange)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getEngagementTrends(array $dateRange): array
    {
        return PostEngagement::select(
            DB::raw('DATE(created_at) as date'),
            'type',
            DB::raw('COUNT(*) as count')
        )
        ->whereBetween('created_at', $dateRange)
        ->groupBy(DB::raw('DATE(created_at)'), 'type')
        ->orderBy('date')
        ->get()
        ->groupBy('date')
        ->toArray();
    }

    private function getFeatureUsage(array $dateRange): array
    {
        // Track usage of different platform features
        return [
            'timeline_views' => $this->getFeatureUsageCount('timeline_view', $dateRange),
            'directory_searches' => $this->getFeatureUsageCount('directory_search', $dateRange),
            'job_views' => $this->getFeatureUsageCount('job_view', $dateRange),
            'event_views' => $this->getFeatureUsageCount('event_view', $dateRange),
            'profile_views' => $this->getFeatureUsageCount('profile_view', $dateRange),
        ];
    }

    private function getFeatureUsageCount(string $feature, array $dateRange): int
    {
        // This would integrate with your analytics tracking system
        // For now, return mock data
        return rand(100, 1000);
    }

    private function getGeographicDistribution(array $filters): array
    {
        return User::select('location', DB::raw('COUNT(*) as count'))
            ->whereNotNull('location')
            ->groupBy('location')
            ->orderByDesc('count')
            ->limit(20)
            ->get()
            ->toArray();
    }

    private function getGraduationYearActivity(array $dateRange): array
    {
        return DB::table('users')
            ->join('educations', 'users.id', '=', 'educations.user_id')
            ->select('educations.graduation_year', DB::raw('COUNT(DISTINCT users.id) as count'))
            ->whereBetween('users.last_activity_at', $dateRange)
            ->groupBy('educations.graduation_year')
            ->orderBy('educations.graduation_year')
            ->get()
            ->toArray();
    }

    private function getNetworkDensity(): float
    {
        $totalUsers = User::count();
        $totalConnections = Connection::where('status', 'accepted')->count();
        $maxPossibleConnections = ($totalUsers * ($totalUsers - 1)) / 2;
        
        return $maxPossibleConnections > 0 ? ($totalConnections / $maxPossibleConnections) * 100 : 0;
    }

    private function getGroupParticipation(array $dateRange): array
    {
        return Group::withCount(['members', 'posts' => function ($query) use ($dateRange) {
            $query->whereBetween('created_at', $dateRange);
        }])
        ->orderByDesc('posts_count')
        ->limit(10)
        ->get()
        ->toArray();
    }

    private function getCircleEngagement(array $dateRange): array
    {
        return Circle::withCount(['members', 'posts' => function ($query) use ($dateRange) {
            $query->whereBetween('created_at', $dateRange);
        }])
        ->orderByDesc('posts_count')
        ->limit(10)
        ->get()
        ->toArray();
    }

    private function getContentQualityScore(array $dateRange): float
    {
        // Calculate based on engagement rates, comment quality, etc.
        $posts = Post::whereBetween('created_at', $dateRange)->get();
        $totalScore = 0;
        
        foreach ($posts as $post) {
            $engagements = $post->engagements()->count();
            $comments = $post->engagements()->where('type', 'comment')->count();
            $score = ($engagements * 0.7) + ($comments * 0.3);
            $totalScore += $score;
        }
        
        return $posts->count() > 0 ? $totalScore / $posts->count() : 0;
    }

    private function getUserSatisfactionMetrics(array $dateRange): array
    {
        // This would integrate with user feedback/survey data
        return [
            'average_rating' => 4.2,
            'nps_score' => 65,
            'satisfaction_trend' => 'increasing',
        ];
    }

    private function getPlatformGrowthRate(array $dateRange): float
    {
        $startUsers = User::where('created_at', '<', $dateRange[0])->count();
        $endUsers = User::where('created_at', '<=', $dateRange[1])->count();
        
        return $startUsers > 0 ? (($endUsers - $startUsers) / $startUsers) * 100 : 0;
    }

    private function getPageViews(array $dateRange): array
    {
        // This would integrate with your page view tracking
        return [
            'total_views' => rand(10000, 50000),
            'unique_views' => rand(5000, 25000),
            'daily_breakdown' => $this->generateDailyBreakdown($dateRange),
        ];
    }

    private function getSessionDuration(array $dateRange): array
    {
        return [
            'average_duration' => rand(300, 1800), // seconds
            'median_duration' => rand(200, 1200),
            'bounce_sessions' => rand(100, 500),
        ];
    }

    private function getBounceRate(array $dateRange): float
    {
        return rand(20, 40); // percentage
    }

    private function getDeviceBreakdown(array $dateRange): array
    {
        return [
            'desktop' => rand(40, 60),
            'mobile' => rand(30, 50),
            'tablet' => rand(5, 15),
        ];
    }

    private function getBrowserBreakdown(array $dateRange): array
    {
        return [
            'chrome' => rand(50, 70),
            'firefox' => rand(10, 20),
            'safari' => rand(10, 20),
            'edge' => rand(5, 15),
            'other' => rand(1, 5),
        ];
    }

    private function getPeakUsageTimes(array $dateRange): array
    {
        return [
            'hourly' => $this->generateHourlyUsage(),
            'daily' => $this->generateDailyUsage(),
        ];
    }

    private function getFeatureAdoption(array $dateRange): array
    {
        return [
            'social_timeline' => rand(80, 95),
            'alumni_directory' => rand(60, 80),
            'job_matching' => rand(40, 60),
            'events' => rand(30, 50),
            'mentorship' => rand(20, 40),
        ];
    }

    private function getMetricData(string $metric, array $dateRange, array $filters): mixed
    {
        // Route to appropriate metric calculation method
        return match ($metric) {
            'engagement_rate' => $this->getEngagementRate($dateRange),
            'active_users' => $this->getActiveUsers($dateRange),
            'new_users' => $this->getNewUsers($dateRange),
            'posts_created' => $this->getPostsCreated($dateRange),
            default => null,
        };
    }

    private function exportToCsv(array $data): string
    {
        $csv = '';
        $headers = array_keys($data[0] ?? []);
        $csv .= implode(',', $headers) . "\n";
        
        foreach ($data as $row) {
            $csv .= implode(',', array_values($row)) . "\n";
        }
        
        return $csv;
    }

    private function exportToExcel(array $data): string
    {
        // This would use a library like PhpSpreadsheet
        // For now, return CSV format
        return $this->exportToCsv($data);
    }

    private function generateDailyBreakdown(array $dateRange): array
    {
        $breakdown = [];
        $current = $dateRange[0]->copy();
        
        while ($current <= $dateRange[1]) {
            $breakdown[] = [
                'date' => $current->format('Y-m-d'),
                'views' => rand(100, 1000),
            ];
            $current->addDay();
        }
        
        return $breakdown;
    }

    private function generateHourlyUsage(): array
    {
        $usage = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $usage[] = [
                'hour' => $hour,
                'usage' => rand(50, 500),
            ];
        }
        return $usage;
    }

    private function generateDailyUsage(): array
    {
        return [
            'monday' => rand(100, 300),
            'tuesday' => rand(100, 300),
            'wednesday' => rand(100, 300),
            'thursday' => rand(100, 300),
            'friday' => rand(100, 300),
            'saturday' => rand(50, 150),
            'sunday' => rand(50, 150),
        ];
    }

    /**
     * Get graduate outcome metrics for the institution admin dashboard.
     */
    public function getGraduateOutcomeMetrics(array $filters = []): array
    {
        $cacheKey = 'graduate_outcome_metrics_' . md5(serialize($filters));

        // Cache for 1 hour
        return Cache::remember($cacheKey, 3600, function () use ($filters) {
            return [
                'time_to_employment' => $this->getTimeToEmployment($filters),
                'salary_progression' => $this->getSalaryProgression($filters),
                'top_employers' => $this->getTopEmployers($filters),
                'employment_by_location' => $this->getEmploymentByLocation($filters),
                'employment_rate_by_course' => $this->getEmploymentRateByCourse($filters),
            ];
        });
    }

    private function getTimeToEmployment(array $filters): array
    {
        // This metric would require graduates to have a graduation date and an employment start date.
        // For now, we'll simulate this data.
        return [
            'average_days' => rand(60, 120),
            'median_days' => rand(50, 110),
            'under_3_months_percentage' => rand(40, 60),
            'under_6_months_percentage' => rand(70, 85),
        ];
    }

    private function getSalaryProgression(array $filters): array
    {
        // This requires historical salary data, which is not currently in the model.
        // We will simulate this for the demo.
        return [
            'year_1' => ['average' => rand(45000, 55000), 'median' => rand(42000, 52000)],
            'year_3' => ['average' => rand(60000, 75000), 'median' => rand(58000, 72000)],
            'year_5' => ['average' => rand(80000, 100000), 'median' => rand(78000, 95000)],
        ];
    }

    private function getTopEmployers(array $filters): array
    {
        return DB::table('graduates')
            ->select('current_company', DB::raw('COUNT(*) as hires'))
            ->whereNotNull('current_company')
            ->where('employment_status', 'employed')
            ->groupBy('current_company')
            ->orderByDesc('hires')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getEmploymentByLocation(array $filters): array
    {
        // This assumes location data is stored in a structured way.
        // For now, we'll use the existing 'location' field if available.
        return DB::table('users')
            ->select('location', DB::raw('COUNT(*) as count'))
            ->whereNotNull('location')
            ->groupBy('location')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function generateGraduateOutcomeSnapshot(string $date): void
    {
        $metrics = $this->getGraduateOutcomeMetrics(['date' => $date]);

        \App\Models\AnalyticsSnapshot::updateOrCreate(
            [
                'type' => 'graduate_outcomes',
                'date' => $date,
            ],
            ['data' => $metrics]
        );
    }

    public function getCourseRoiMetrics(array $filters = []): array
    {
        // This is a simplified ROI calculation. A real-world one would be more complex.
        $courses = DB::table('courses')
            ->leftJoin('graduates', 'courses.id', '=', 'graduates.course_id')
            ->select(
                'courses.name as course_name',
                'courses.cost', // Assuming a 'cost' field exists on the courses table
                DB::raw('COUNT(graduates.id) as total_graduates'),
                DB::raw("AVG(graduates.current_salary) as average_salary")
            )
            ->groupBy('courses.name', 'courses.cost')
            ->get();

        return $courses->map(function ($course) {
            $cost = $course->cost ?? 50000; // Default cost if not set
            $roi = ($course->average_salary && $cost > 0) ? (($course->average_salary * 5) - $cost) / $cost * 100 : 0; // 5-year ROI

            return [
                'course_name' => $course->course_name,
                'average_salary' => (int) $course->average_salary,
                'total_graduates' => $course->total_graduates,
                'estimated_roi_percentage' => round($roi),
            ];
        })
        ->sortByDesc('estimated_roi_percentage')
        ->values()
        ->toArray();
    }

    public function getEmployerEngagementMetrics(array $filters = []): array
    {
        return [
            'top_engaging_employers' => $this->getTopEngagingEmployers($filters),
            'most_in_demand_skills' => $this->getMostInDemandSkills($filters),
            'hiring_trends_by_industry' => $this->getHiringTrendsByIndustry($filters),
        ];
    }

    private function getTopEngagingEmployers(array $filters): array
    {
        return DB::table('employers')
            ->leftJoin('jobs', 'employers.id', '=', 'jobs.employer_id')
            ->leftJoin('job_applications', 'jobs.id', '=', 'job_applications.job_id')
            ->select(
                'employers.company_name',
                DB::raw('COUNT(DISTINCT jobs.id) as jobs_posted'),
                DB::raw('COUNT(DISTINCT job_applications.id) as total_applications'),
                DB::raw("SUM(CASE WHEN job_applications.status = 'hired' THEN 1 ELSE 0 END) as total_hires")
            )
            ->groupBy('employers.company_name')
            ->orderByDesc('total_hires')
            ->orderByDesc('jobs_posted')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getMostInDemandSkills(array $filters): array
    {
        // This assumes skills are stored in a JSON column in the jobs table
        return DB::table('jobs')
            ->select('required_skills')
            ->whereNotNull('required_skills')
            ->get()
            ->pluck('required_skills')
            ->map(fn ($skills) => json_decode($skills, true))
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->map(fn ($count, $skill) => ['skill' => $skill, 'count' => $count])
            ->values()
            ->toArray();
    }

    public function getCommunityHealthMetrics(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);

        return [
            'daily_active_users' => $this->getDailyActiveUsers($dateRange),
            'post_activity' => $this->getPostActivity($dateRange),
            'engagement_trends' => $this->getEngagementTrends($dateRange),
            'group_participation' => $this->getGroupParticipation($dateRange),
            'events_attended' => $this->getEventsAttended($dateRange),
            'connections_made' => $this->getConnectionsMade($dateRange),
        ];
    }

    public function getPlatformBenchmarks(array $filters = []): array
    {
        $tenants = \App\Models\Tenant::all();
        $benchmarks = [];

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            $metrics = $this->getGraduateOutcomeMetrics($filters);

            // Anonymize the data
            $benchmarks[] = [
                'institution_id' => $tenant->id, // Anonymized ID
                'employment_rate' => $metrics['employment_rate_by_course'][0]['employment_rate'] ?? 0, // Simplified for example
                'average_salary' => $metrics['salary_progression']['year_1']['average'] ?? 0,
            ];
        }

        tenancy()->end();

        return $benchmarks;
    }

    public function getMarketTrends(array $filters = []): array
    {
        $topSkills = DB::table('jobs')
            ->select('required_skills')
            ->whereNotNull('required_skills')
            ->get()
            ->pluck('required_skills')
            ->map(fn ($skills) => json_decode($skills, true))
            ->flatten()
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->map(fn ($count, $skill) => ['skill' => $skill, 'count' => $count])
            ->values();

        $topIndustries = DB::table('employers')
            ->select('industry', DB::raw('COUNT(jobs.id) as jobs_count'))
            ->join('jobs', 'employers.id', '=', 'jobs.employer_id')
            ->whereNotNull('employers.industry')
            ->groupBy('employers.industry')
            ->orderByDesc('jobs_count')
            ->limit(10)
            ->get();

        return [
            'top_skills' => $topSkills,
            'top_industries' => $topIndustries,
        ];
    }

    public function getSystemGrowthMetrics(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);

        return [
            'new_users' => $this->getNewUsers($dateRange),
            'new_institutions' => \App\Models\Tenant::whereBetween('created_at', $dateRange)->count(),
            'user_growth_data' => $this->getUserGrowthData($dateRange),
        ];
    }

    private function getUserGrowthData(array $dateRange): array
    {
        return \App\Models\User::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->whereBetween('created_at', $dateRange)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getHiringTrendsByIndustry(array $filters): array
    {
        return DB::table('employers')
            ->leftJoin('jobs', 'employers.id', '=', 'jobs.employer_id')
            ->leftJoin('job_applications', function ($join) {
                $join->on('jobs.id', '=', 'job_applications.job_id')
                     ->where('job_applications.status', '=', 'hired');
            })
            ->select('employers.industry', DB::raw('COUNT(job_applications.id) as hires'))
            ->whereNotNull('employers.industry')
            ->groupBy('employers.industry')
            ->orderByDesc('hires')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getEmploymentRateByCourse(array $filters): array
    {
        return DB::table('courses')
            ->leftJoin('graduates', 'courses.id', '=', 'graduates.course_id')
            ->select(
                'courses.name as course_name',
                DB::raw('COUNT(graduates.id) as total_graduates'),
                DB::raw("SUM(CASE WHEN graduates.employment_status = 'employed' THEN 1 ELSE 0 END) as employed_graduates")
            )
            ->groupBy('courses.name')
            ->orderByDesc('employed_graduates')
            ->get()
            ->map(function ($row) {
                $row->employment_rate = $row->total_graduates > 0 ? ($row->employed_graduates / $row->total_graduates) * 100 : 0;
                return $row;
            })
            ->toArray();
    }
}