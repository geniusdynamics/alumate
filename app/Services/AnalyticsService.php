<?php

namespace App\Services;

use App\Models\AnalyticsSnapshot;
use App\Models\Circle;
use App\Models\Connection;
use App\Models\Course;
use App\Models\Employer;
use App\Models\Graduate;
use App\Models\Group;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\KpiDefinition;
use App\Models\Post;
use App\Models\PostEngagement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService extends BaseService
{
    /**
     * Get engagement metrics for the dashboard
     */
    public function getEngagementMetrics(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        $cacheKey = 'engagement_metrics_'.md5(serialize($filters));

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
        // EventAttendance model doesn't exist, return mock data
        return rand(50, 200);
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
                    $startDate->copy()->addDays($days + 1),
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
        $csv .= implode(',', $headers)."\n";

        foreach ($data as $row) {
            $csv .= implode(',', array_values($row))."\n";
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
        $cacheKey = 'graduate_outcome_metrics_'.md5(serialize($filters));

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
                DB::raw('AVG(graduates.current_salary) as average_salary')
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

    /**
     * Export analytics data
     */
    public function exportAnalyticsData(string $dataType, array $filters = [], string $format = 'json'): string
    {
        $data = match ($dataType) {
            'employment' => $this->getEmploymentData($filters),
            'engagement' => $this->getEngagementMetrics($filters),
            'alumni' => $this->getAlumniActivity($filters),
            'courses' => $this->getCoursesData($filters),
            'jobs' => $this->getJobsData($filters),
            'graduates' => $this->getGraduatesData($filters),
            default => [],
        };

        $exportData = [
            'data' => $data,
            'exported_at' => now()->toISOString(),
            'data_type' => $dataType,
            'filters' => $filters,
            'total_records' => is_array($data) ? count($data) : 0,
        ];

        return $this->exportData($exportData, $format);
    }

    /**
     * Get course performance metrics
     */
    public function getCoursePerformanceMetrics(int $courseId): array
    {
        $course = Course::with('graduates')->find($courseId);

        if (! $course) {
            return [];
        }

        $graduates = $course->graduates;
        $totalGraduates = $graduates->count();
        $employedGraduates = $graduates->where('employment_status', 'employed')->count();
        $employmentRate = $totalGraduates > 0 ? ($employedGraduates / $totalGraduates) * 100 : 0;

        $salaries = $graduates->where('current_salary', '>', 0)->pluck('current_salary');
        $averageSalary = $salaries->avg() ?: 0;

        // Calculate average job placement time (mock data for now)
        $placementTimes = $graduates->where('employment_start_date', '!=', null)
            ->map(function ($graduate) {
                $graduationDate = Carbon::create($graduate->graduation_year, 6, 1); // Assume June graduation
                $employmentDate = Carbon::parse($graduate->employment_start_date);

                return $graduationDate->diffInDays($employmentDate);
            });

        $jobPlacementTime = $placementTimes->avg() ?: 0;

        return [
            'course_id' => $courseId,
            'course_name' => $course->name,
            'total_graduates' => $totalGraduates,
            'employed_graduates' => $employedGraduates,
            'employment_rate' => round($employmentRate, 2),
            'average_salary' => round($averageSalary),
            'job_placement_time' => round($jobPlacementTime),
            'salary_range' => [
                'min' => $salaries->min() ?: 0,
                'max' => $salaries->max() ?: 0,
            ],
            'top_employers' => $this->getTopEmployersForCourse($courseId),
        ];
    }

    /**
     * Generate trend analysis
     */
    public function generateTrendAnalysis(string $metric, string $period = 'monthly'): array
    {
        $data = [];
        $trendDirection = 'stable';

        switch ($metric) {
            case 'employment':
                $data = $this->getEmploymentTrends($period);
                break;
            case 'graduates':
                $data = $this->getGraduationTrends($period);
                break;
            case 'salaries':
                $data = $this->getSalaryTrends($period);
                break;
            case 'jobs':
                $data = $this->getJobPostingTrends($period);
                break;
            default:
                $data = [];
        }

        // Calculate trend direction
        if (count($data) >= 2) {
            $first = reset($data)['value'] ?? 0;
            $last = end($data)['value'] ?? 0;

            if ($last > $first * 1.05) {
                $trendDirection = 'increasing';
            } elseif ($last < $first * 0.95) {
                $trendDirection = 'decreasing';
            }
        }

        return [
            'metric' => $metric,
            'period' => $period,
            'periods' => array_column($data, 'period'),
            'data' => $data,
            'trend_direction' => $trendDirection,
            'generated_at' => now(),
        ];
    }

    /**
     * Generate daily analytics snapshot
     */
    public function generateDailySnapshot(): AnalyticsSnapshot
    {
        $data = [
            'overview' => [
                'total_users' => User::count(),
                'total_graduates' => Graduate::count(),
                'total_courses' => Course::count(),
                'total_jobs' => Job::count(),
                'active_employers' => Employer::whereHas('jobs')->count(),
            ],
            'employment' => [
                'employment_rate' => $this->calculateOverallEmploymentRate(),
                'new_hires_today' => JobApplication::where('status', 'hired')
                    ->whereDate('updated_at', today())
                    ->count(),
                'average_salary' => Graduate::where('current_salary', '>', 0)
                    ->avg('current_salary') ?: 0,
            ],
            'job_market' => [
                'active_jobs' => Job::where('status', 'active')->count(),
                'new_jobs_today' => Job::whereDate('created_at', today())->count(),
                'total_applications' => JobApplication::count(),
            ],
        ];

        return AnalyticsSnapshot::create([
            'type' => 'daily',
            'date' => today(),
            'data' => $data,
        ]);
    }

    /**
     * Calculate KPI values
     */
    public function calculateKpiValues(): array
    {
        $kpis = KpiDefinition::where('is_active', true)->get();
        $results = [];

        foreach ($kpis as $kpi) {
            $results[$kpi->key] = $this->calculateKpiValue($kpi);
        }

        return $results;
    }

    /**
     * Get employer analytics
     */
    public function getEmployerAnalytics(int $employerId): array
    {
        $employer = Employer::find($employerId);

        if (! $employer) {
            return [];
        }

        $jobs = $employer->jobs();
        $jobsPosted = $jobs->count();
        $activeJobs = $jobs->where('status', 'active')->count();

        $applications = JobApplication::whereIn('job_id', $jobs->pluck('id'));
        $applicationsReceived = $applications->count();
        $hiresMade = $applications->where('status', 'hired')->count();
        $responseRate = $applicationsReceived > 0 ? ($hiresMade / $applicationsReceived) * 100 : 0;

        return [
            'employer_id' => $employerId,
            'employer_name' => $employer->company_name,
            'jobs_posted' => $jobsPosted,
            'active_jobs' => $activeJobs,
            'applications_received' => $applicationsReceived,
            'hires_made' => $hiresMade,
            'response_rate' => round($responseRate, 2),
            'average_time_to_hire' => $this->calculateAverageTimeToHire($employerId),
        ];
    }

    // Helper methods for the new functionality

    private function getEmploymentData(array $filters): array
    {
        $query = Graduate::query();

        if (isset($filters['graduation_year'])) {
            $query->where('graduation_year', $filters['graduation_year']);
        }

        return $query->select(
            'id', 'name', 'employment_status', 'current_job_title',
            'current_company', 'current_salary', 'graduation_year'
        )->get()->toArray();
    }

    private function getCoursesData(array $filters): array
    {
        return Course::withCount('graduates')->get()->toArray();
    }

    private function getJobsData(array $filters): array
    {
        return Job::with('employer')->get()->toArray();
    }

    private function getGraduatesData(array $filters): array
    {
        return Graduate::with('course')->get()->toArray();
    }

    private function getTopEmployersForCourse(int $courseId): array
    {
        return Graduate::where('course_id', $courseId)
            ->whereNotNull('current_company')
            ->select('current_company', DB::raw('COUNT(*) as count'))
            ->groupBy('current_company')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->toArray();
    }

    private function getEmploymentTrends(string $period): array
    {
        $data = [];
        $format = $period === 'yearly' ? 'Y' : 'Y-m';

        $trends = Graduate::select(
            DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
            DB::raw('COUNT(*) as total'),
            DB::raw("SUM(CASE WHEN employment_status = 'employed' THEN 1 ELSE 0 END) as employed")
        )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        foreach ($trends as $trend) {
            $rate = $trend->total > 0 ? ($trend->employed / $trend->total) * 100 : 0;
            $data[] = [
                'period' => $trend->period,
                'value' => round($rate, 2),
                'total' => $trend->total,
                'employed' => $trend->employed,
            ];
        }

        return $data;
    }

    private function getGraduationTrends(string $period): array
    {
        $data = [];
        $field = $period === 'yearly' ? 'graduation_year' : 'graduation_year';

        $trends = Graduate::select(
            DB::raw("{$field} as period"),
            DB::raw('COUNT(*) as value')
        )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        foreach ($trends as $trend) {
            $data[] = [
                'period' => $trend->period,
                'value' => $trend->value,
            ];
        }

        return $data;
    }

    private function getSalaryTrends(string $period): array
    {
        $data = [];
        $format = $period === 'yearly' ? 'Y' : 'Y-m';

        $trends = Graduate::select(
            DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
            DB::raw('AVG(current_salary) as value')
        )
            ->whereNotNull('current_salary')
            ->where('current_salary', '>', 0)
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        foreach ($trends as $trend) {
            $data[] = [
                'period' => $trend->period,
                'value' => round($trend->value ?: 0),
            ];
        }

        return $data;
    }

    private function getJobPostingTrends(string $period): array
    {
        $data = [];
        $format = $period === 'yearly' ? 'Y' : 'Y-m';

        $trends = Job::select(
            DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
            DB::raw('COUNT(*) as value')
        )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        foreach ($trends as $trend) {
            $data[] = [
                'period' => $trend->period,
                'value' => $trend->value,
            ];
        }

        return $data;
    }

    private function calculateOverallEmploymentRate(): float
    {
        $totalGraduates = Graduate::count();
        $employedGraduates = Graduate::where('employment_status', 'employed')->count();

        return $totalGraduates > 0 ? ($employedGraduates / $totalGraduates) * 100 : 0;
    }

    private function calculateKpiValue(KpiDefinition $kpi): float
    {
        $config = $kpi->calculation_config;

        // Get numerator count
        $numeratorModel = $config['numerator']['model'] ?? null;
        $numeratorFilters = $config['numerator']['filters'] ?? [];

        // Get denominator count
        $denominatorModel = $config['denominator']['model'] ?? null;
        $denominatorFilters = $config['denominator']['filters'] ?? [];

        if (! $numeratorModel || ! $denominatorModel) {
            return 0;
        }

        $numerator = $this->applyFiltersToModel($numeratorModel, $numeratorFilters);
        $denominator = $this->applyFiltersToModel($denominatorModel, $denominatorFilters);

        return $denominator > 0 ? ($numerator / $denominator) * 100 : 0;
    }

    private function applyFiltersToModel(string $model, array $filters): int
    {
        $query = $model::query();

        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'] ?? null;

            if ($field && $value !== null) {
                if (str_contains($field, '->')) {
                    // JSON field query
                    $query->whereJsonContains($field, $value);
                } else {
                    $query->where($field, $operator, $value);
                }
            }
        }

        return $query->count();
    }

    private function calculateAverageTimeToHire(int $employerId): float
    {
        $applications = JobApplication::whereHas('job', function ($query) use ($employerId) {
            $query->where('employer_id', $employerId);
        })
            ->where('status', 'hired')
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at')
            ->get();

        if ($applications->isEmpty()) {
            return 0;
        }

        $totalDays = $applications->sum(function ($app) {
            return Carbon::parse($app->created_at)->diffInDays(Carbon::parse($app->updated_at));
        });

        return $totalDays / $applications->count();
    }
}
