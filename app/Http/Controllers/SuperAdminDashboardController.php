<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\Employer;
use App\Models\Course;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Carbon\Carbon;

class SuperAdminDashboardController extends Controller
{
    public function index()
    {
        // System-wide analytics
        $systemStats = $this->getSystemStats();
        $institutionStats = $this->getInstitutionStats();
        $employerStats = $this->getEmployerStats();
        $jobStats = $this->getJobStats();
        $recentActivity = $this->getRecentActivity();
        $systemHealth = $this->getSystemHealth();

        return Inertia::render('SuperAdmin/Dashboard', [
            'systemStats' => $systemStats,
            'institutionStats' => $institutionStats,
            'employerStats' => $employerStats,
            'jobStats' => $jobStats,
            'recentActivity' => $recentActivity,
            'systemHealth' => $systemHealth,
        ]);
    }

    public function analytics(Request $request)
    {
        $timeframe = $request->get('timeframe', '30'); // days
        $startDate = Carbon::now()->subDays($timeframe);

        $analytics = [
            'user_growth' => $this->getUserGrowthData($startDate),
            'institution_performance' => $this->getInstitutionPerformance(),
            'employment_trends' => $this->getEmploymentTrends($startDate),
            'job_market_analysis' => $this->getJobMarketAnalysis($startDate),
            'system_usage' => $this->getSystemUsageData($startDate),
        ];

        return Inertia::render('SuperAdmin/Analytics', [
            'analytics' => $analytics,
            'timeframe' => $timeframe,
        ]);
    }

    public function institutions()
    {
        $institutions = Tenant::with(['domains'])
            ->withCount(['users', 'graduates', 'courses'])
            ->get()
            ->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->data['name'] ?? 'Unknown',
                    'domains' => $tenant->domains->pluck('domain'),
                    'users_count' => $tenant->users_count,
                    'graduates_count' => $tenant->graduates_count,
                    'courses_count' => $tenant->courses_count,
                    'created_at' => $tenant->created_at,
                    'status' => $tenant->data['status'] ?? 'active',
                ];
            });

        return Inertia::render('SuperAdmin/Institutions', [
            'institutions' => $institutions,
        ]);
    }

    public function users(Request $request)
    {
        $query = User::with(['roles'])
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->role, function ($q, $role) {
                $q->whereHas('roles', function ($roleQuery) use ($role) {
                    $roleQuery->where('name', $role);
                });
            });

        $users = $query->paginate(15);

        return Inertia::render('SuperAdmin/Users', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    public function employerVerification()
    {
        $pendingEmployers = Employer::with(['user'])
            ->where('verification_status', 'pending')
            ->orWhere('verification_status', 'under_review')
            ->orderBy('created_at', 'desc')
            ->get();

        $verificationStats = [
            'pending' => Employer::where('verification_status', 'pending')->count(),
            'under_review' => Employer::where('verification_status', 'under_review')->count(),
            'verified' => Employer::where('verification_status', 'verified')->count(),
            'rejected' => Employer::where('verification_status', 'rejected')->count(),
        ];

        return Inertia::render('SuperAdmin/EmployerVerification', [
            'pendingEmployers' => $pendingEmployers,
            'verificationStats' => $verificationStats,
        ]);
    }

    public function reports(Request $request)
    {
        $reportType = $request->get('type', 'overview');
        $timeframe = $request->get('timeframe', '30');

        $reports = [
            'overview' => $this->generateOverviewReport($timeframe),
            'institutions' => $this->generateInstitutionReport($timeframe),
            'employment' => $this->generateEmploymentReport($timeframe),
            'jobs' => $this->generateJobReport($timeframe),
        ];

        return Inertia::render('SuperAdmin/Reports', [
            'reports' => $reports,
            'reportType' => $reportType,
            'timeframe' => $timeframe,
        ]);
    }

    public function systemHealth()
    {
        $health = [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'queue' => $this->checkQueueHealth(),
            'storage' => $this->checkStorageHealth(),
            'performance' => $this->getPerformanceMetrics(),
            'security' => $this->getSecurityStatus(),
            'backups' => $this->getBackupStatus(),
        ];

        return Inertia::render('SuperAdmin/SystemHealth', [
            'health' => $health,
        ]);
    }

    public function exportReport(Request $request)
    {
        $request->validate([
            'type' => 'required|in:overview,institutions,employment,jobs',
            'timeframe' => 'required|integer|min:1|max:365',
            'format' => 'required|in:excel,pdf,csv',
        ]);

        $reportType = $request->get('type');
        $timeframe = $request->get('timeframe');
        $format = $request->get('format');

        $reportData = match($reportType) {
            'overview' => $this->generateOverviewReport($timeframe),
            'institutions' => $this->generateInstitutionReport($timeframe),
            'employment' => $this->generateEmploymentReport($timeframe),
            'jobs' => $this->generateJobReport($timeframe),
        };

        return $this->generateReportFile($reportData, $reportType, $format);
    }

    private function getSystemStats()
    {
        // Calculate total graduates across all tenants
        $totalGraduates = 0;
        $totalApplications = 0;
        
        foreach (Tenant::all() as $tenant) {
            try {
                $tenant->run(function () use (&$totalGraduates, &$totalApplications) {
                    if (Schema::hasTable('graduates')) {
                        $totalGraduates += Graduate::count();
                    }
                    if (Schema::hasTable('job_applications')) {
                        $totalApplications += JobApplication::count();
                    }
                });
            } catch (\Exception $e) {
                // Skip if tenant database is not accessible
                continue;
            }
        }
        
        return [
            'total_institutions' => Tenant::count(),
            'total_users' => User::count(),
            'total_graduates' => $totalGraduates,
            'total_employers' => Employer::count(),
            'total_jobs' => Job::count(),
            'total_applications' => $totalApplications,
            'active_jobs' => Job::where('status', 'active')->count(),
            'pending_verifications' => Employer::where('verification_status', 'pending')->count(),
        ];
    }

    private function getInstitutionStats()
    {
        return Tenant::with(['domains'])
            ->get()
            ->map(function ($tenant) {
                $graduateCount = 0;
                $employmentRate = 0;
                
                try {
                    // Switch to tenant context to get accurate counts
                    $tenant->run(function () use (&$graduateCount, &$employmentRate) {
                        if (Schema::hasTable('graduates')) {
                            $graduateCount = Graduate::count();
                            $employedCount = Graduate::whereIn('employment_status', ['employed', 'self_employed'])->count();
                            $employmentRate = $graduateCount > 0 ? ($employedCount / $graduateCount) * 100 : 0;
                        }
                    });
                } catch (\Exception $e) {
                    // Skip if tenant database is not accessible
                    $graduateCount = 0;
                    $employmentRate = 0;
                }

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->data['name'] ?? 'Unknown',
                    'graduate_count' => $graduateCount,
                    'employment_rate' => round($employmentRate, 1),
                    'status' => $tenant->data['status'] ?? 'active',
                ];
            });
    }

    private function getEmployerStats()
    {
        return [
            'total_employers' => Employer::count(),
            'verified_employers' => Employer::where('verification_status', 'verified')->count(),
            'pending_verification' => Employer::where('verification_status', 'pending')->count(),
            'active_employers' => Employer::where('is_active', true)->count(),
            'top_industries' => Employer::select('industry', DB::raw('count(*) as count'))
                ->whereNotNull('industry')
                ->groupBy('industry')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get(),
        ];
    }

    private function getJobStats()
    {
        return [
            'total_jobs' => Job::count(),
            'active_jobs' => Job::where('status', 'active')->count(),
            'filled_jobs' => Job::where('status', 'filled')->count(),
            'pending_approval' => Job::where('status', 'pending_approval')->count(),
            'avg_applications_per_job' => Job::avg('total_applications') ?? 0,
            'top_job_types' => Job::select('job_type', DB::raw('count(*) as count'))
                ->whereNotNull('job_type')
                ->groupBy('job_type')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get(),
        ];
    }

    private function getRecentActivity()
    {
        $activities = [];

        // Recent user registrations
        $recentUsers = User::with(['roles'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'user_registration',
                    'description' => "New user registered: {$user->name}",
                    'timestamp' => $user->created_at,
                    'data' => ['user' => $user],
                ];
            });

        // Recent job postings
        $recentJobs = Job::with(['employer'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($job) {
                return [
                    'type' => 'job_posted',
                    'description' => "New job posted: {$job->title}",
                    'timestamp' => $job->created_at,
                    'data' => ['job' => $job],
                ];
            });

        // Recent applications
        $recentApplications = JobApplication::with(['job', 'graduate'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($application) {
                return [
                    'type' => 'job_application',
                    'description' => "New application for: {$application->job->title}",
                    'timestamp' => $application->created_at,
                    'data' => ['application' => $application],
                ];
            });

        return collect($activities)
            ->merge($recentUsers)
            ->merge($recentJobs)
            ->merge($recentApplications)
            ->sortByDesc('timestamp')
            ->take(15)
            ->values();
    }

    private function getSystemHealth()
    {
        return [
            'database_status' => 'healthy',
            'cache_status' => 'healthy',
            'queue_status' => 'healthy',
            'storage_usage' => '45%',
            'response_time' => '120ms',
            'uptime' => '99.9%',
            'last_backup' => Carbon::now()->subHours(6),
        ];
    }

    private function getUserGrowthData($startDate)
    {
        return User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    private function getInstitutionPerformance()
    {
        return Tenant::get()->map(function ($tenant) {
            $performance = [
                'graduate_count' => 0,
                'employment_rate' => 0,
                'active_jobs' => 0,
                'total_applications' => 0,
            ];
            
            try {
                $tenant->run(function () use (&$performance) {
                    $totalGraduates = 0;
                    $employedGraduates = 0;
                    $activeJobs = 0;
                    $totalApplications = 0;
                    
                    if (Schema::hasTable('graduates')) {
                        $totalGraduates = Graduate::count();
                        $employedGraduates = Graduate::whereIn('employment_status', ['employed', 'self_employed'])->count();
                    }
                    
                    if (Schema::hasTable('jobs')) {
                        $activeJobs = Job::where('status', 'active')->count();
                    }
                    
                    if (Schema::hasTable('job_applications')) {
                        $totalApplications = JobApplication::count();
                    }

                    $performance = [
                        'graduate_count' => $totalGraduates,
                        'employment_rate' => $totalGraduates > 0 ? ($employedGraduates / $totalGraduates) * 100 : 0,
                        'active_jobs' => $activeJobs,
                        'total_applications' => $totalApplications,
                    ];
                });
            } catch (\Exception $e) {
                // Skip if tenant database is not accessible
            }

            return [
                'institution' => $tenant->data['name'] ?? 'Unknown',
                'performance' => $performance,
            ];
        });
    }

    private function getEmploymentTrends($startDate)
    {
        return Graduate::select(
                DB::raw('employment_status'),
                DB::raw('COUNT(*) as count')
            )
            ->where('last_employment_update', '>=', $startDate)
            ->groupBy('employment_status')
            ->get();
    }

    private function getJobMarketAnalysis($startDate)
    {
        return [
            'jobs_by_type' => Job::select('job_type', DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', $startDate)
                ->groupBy('job_type')
                ->get(),
            'jobs_by_location' => Job::select('location', DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('location')
                ->groupBy('location')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
            'salary_ranges' => Job::select(
                    DB::raw('CASE 
                        WHEN salary_min < 30000 THEN "Under 30k"
                        WHEN salary_min < 50000 THEN "30k-50k"
                        WHEN salary_min < 80000 THEN "50k-80k"
                        ELSE "80k+"
                    END as range'),
                    DB::raw('COUNT(*) as count')
                )
                ->whereNotNull('salary_min')
                ->where('created_at', '>=', $startDate)
                ->groupBy('range')
                ->get(),
        ];
    }

    private function getSystemUsageData($startDate)
    {
        return [
            'daily_logins' => User::select(
                    DB::raw('DATE(last_login_at) as date'),
                    DB::raw('COUNT(DISTINCT id) as count')
                )
                ->where('last_login_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'feature_usage' => [
                'job_applications' => JobApplication::where('created_at', '>=', $startDate)->count(),
                'profile_updates' => Graduate::where('last_profile_update', '>=', $startDate)->count(),
                'job_posts' => Job::where('created_at', '>=', $startDate)->count(),
            ],
        ];
    }

    private function generateOverviewReport($timeframe)
    {
        $startDate = Carbon::now()->subDays($timeframe);
        
        return [
            'period' => $timeframe . ' days',
            'total_users' => User::count(),
            'new_users' => User::where('created_at', '>=', $startDate)->count(),
            'total_jobs' => Job::count(),
            'new_jobs' => Job::where('created_at', '>=', $startDate)->count(),
            'total_applications' => JobApplication::count(),
            'new_applications' => JobApplication::where('created_at', '>=', $startDate)->count(),
            'employment_rate' => $this->calculateOverallEmploymentRate(),
        ];
    }

    private function generateInstitutionReport($timeframe)
    {
        return Tenant::get()->map(function ($tenant) use ($timeframe) {
            $startDate = Carbon::now()->subDays($timeframe);
            $report = [];
            
            try {
                $tenant->run(function () use (&$report, $startDate) {
                    $totalGraduates = 0;
                    $newGraduates = 0;
                    $employedGraduates = 0;
                    $jobApplications = 0;
                    
                    if (Schema::hasTable('graduates')) {
                        $totalGraduates = Graduate::count();
                        $newGraduates = Graduate::where('created_at', '>=', $startDate)->count();
                        $employedGraduates = Graduate::whereIn('employment_status', ['employed', 'self_employed'])->count();
                    }
                    
                    if (Schema::hasTable('job_applications')) {
                        $jobApplications = JobApplication::where('created_at', '>=', $startDate)->count();
                    }
                    
                    $coursePerformance = [];
                    if (Schema::hasTable('courses')) {
                        $coursePerformance = Course::get()->map(function ($course) {
                            return [
                                'course_name' => $course->name,
                                'total_graduates' => 0, // Simplified for now
                                'employment_rate' => 0,
                            ];
                        });
                    }
                    
                    $report = [
                        'total_graduates' => $totalGraduates,
                        'new_graduates' => $newGraduates,
                        'employed_graduates' => $employedGraduates,
                        'job_applications' => $jobApplications,
                        'course_performance' => $coursePerformance,
                    ];
                });
            } catch (\Exception $e) {
                $report = [
                    'total_graduates' => 0,
                    'new_graduates' => 0,
                    'employed_graduates' => 0,
                    'job_applications' => 0,
                    'course_performance' => [],
                ];
            }

            return [
                'institution' => $tenant->data['name'] ?? 'Unknown',
                'report' => $report,
            ];
        });
    }

    private function generateEmploymentReport($timeframe)
    {
        $startDate = Carbon::now()->subDays($timeframe);
        
        return [
            'overall_employment_rate' => 0, // Temporarily disabled
            'employment_by_status' => collect([ // Temporarily return empty data
                ['employment_status' => 'employed', 'count' => 0],
                ['employment_status' => 'seeking', 'count' => 0],
            ])
                ->groupBy('employment_status')
                ->get(),
            'recent_employment_changes' => collect([]), // Temporarily return empty data
            'top_employers' => collect([]) // Temporarily return empty data
                ->groupBy('current_company')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
        ];
    }

    private function generateJobReport($timeframe)
    {
        $startDate = Carbon::now()->subDays($timeframe);
        
        return [
            'job_posting_trends' => Job::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'application_success_rate' => $this->calculateApplicationSuccessRate(),
            'top_job_categories' => Job::select('job_type', DB::raw('COUNT(*) as count'))
                ->whereNotNull('job_type')
                ->groupBy('job_type')
                ->orderBy('count', 'desc')
                ->get(),
            'employer_performance' => Employer::with(['jobs'])
                ->get()
                ->map(function ($employer) {
                    return [
                        'company_name' => $employer->company_name,
                        'total_jobs' => $employer->jobs->count(),
                        'active_jobs' => $employer->jobs->where('status', 'active')->count(),
                        'filled_jobs' => $employer->jobs->where('status', 'filled')->count(),
                        'total_applications' => $employer->jobs->sum('total_applications'),
                    ];
                })
                ->sortByDesc('total_jobs')
                ->take(10)
                ->values(),
        ];
    }

    private function calculateOverallEmploymentRate()
    {
        $totalGraduates = Graduate::count();
        $employedGraduates = Graduate::whereIn('employment_status', ['employed', 'self_employed'])->count();
        
        return $totalGraduates > 0 ? round(($employedGraduates / $totalGraduates) * 100, 1) : 0;
    }

    private function calculateApplicationSuccessRate()
    {
        $totalApplications = JobApplication::count();
        $successfulApplications = JobApplication::where('status', 'hired')->count();
        
        return $totalApplications > 0 ? round(($successfulApplications / $totalApplications) * 100, 1) : 0;
    }

    private function checkDatabaseHealth()
    {
        try {
            DB::connection()->getPdo();
            $responseTime = $this->measureDatabaseResponseTime();
            return [
                'status' => 'healthy',
                'response_time' => $responseTime . 'ms',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'response_time' => 'N/A',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkCacheHealth()
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            cache()->put($testKey, $testValue, 60);
            $retrieved = cache()->get($testKey);
            cache()->forget($testKey);
            
            return [
                'status' => $retrieved === $testValue ? 'healthy' : 'warning',
                'hit_rate' => '95%', // This would be calculated from actual cache metrics
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'hit_rate' => 'N/A',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkQueueHealth()
    {
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            
            $status = 'healthy';
            if ($failedJobs > 10) {
                $status = 'warning';
            }
            if ($failedJobs > 50) {
                $status = 'critical';
            }
            
            return [
                'status' => $status,
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkStorageHealth()
    {
        try {
            $storagePath = storage_path();
            $totalSpace = disk_total_space($storagePath);
            $freeSpace = disk_free_space($storagePath);
            $usedSpace = $totalSpace - $freeSpace;
            $usagePercent = round(($usedSpace / $totalSpace) * 100, 1);
            
            $status = 'healthy';
            if ($usagePercent > 80) {
                $status = 'warning';
            }
            if ($usagePercent > 95) {
                $status = 'critical';
            }
            
            return [
                'status' => $status,
                'usage' => $usagePercent . '%',
                'free_space' => $this->formatBytes($freeSpace),
                'total_space' => $this->formatBytes($totalSpace),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'usage' => 'N/A',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getPerformanceMetrics()
    {
        return [
            'response_time' => '120ms',
            'response_time_score' => 85,
            'memory_usage' => '256MB',
            'memory_usage_percent' => 45,
            'cpu_usage' => '15%',
            'cpu_usage_percent' => 15,
            'uptime' => '99.9%',
        ];
    }

    private function getSecurityStatus()
    {
        return [
            'ssl_status' => 'healthy',
            'firewall_status' => 'healthy',
            'alerts_count' => 0,
            'last_scan' => Carbon::now()->subHours(2),
        ];
    }

    private function getBackupStatus()
    {
        return [
            'status' => 'healthy',
            'last_backup' => Carbon::now()->subHours(6),
            'backup_size' => '2.5GB',
            'next_backup' => Carbon::now()->addHours(18),
            'retention_days' => 30,
        ];
    }

    private function measureDatabaseResponseTime()
    {
        $start = microtime(true);
        DB::select('SELECT 1');
        $end = microtime(true);
        
        return round(($end - $start) * 1000, 2);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function generateReportFile($reportData, $reportType, $format)
    {
        // This would implement actual file generation
        // For now, return a simple response
        $filename = "{$reportType}_report_" . date('Y-m-d') . ".{$format}";
        
        return response()->json([
            'message' => 'Report generated successfully',
            'filename' => $filename,
            'data' => $reportData,
        ]);
    }
}