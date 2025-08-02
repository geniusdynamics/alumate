<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\Tenant;
use App\Models\ImportHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Stancl\Tenancy\Facades\Tenancy;
use Carbon\Carbon;

class InstitutionAdminDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get basic statistics
        $stats = $this->getBasicStats();
        
        // Get recent activities
        $recentActivities = $this->getRecentActivities();
        
        // Get employment statistics
        $employmentStats = $this->getEmploymentStats();
        
        // Get course performance
        $coursePerformance = $this->getCoursePerformance();

        return Inertia::render('InstitutionAdmin/Dashboard', [
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'employmentStats' => $employmentStats,
            'coursePerformance' => $coursePerformance,
        ]);
    }

    public function analytics()
    {
        // Get comprehensive analytics data
        $analytics = [
            'graduatesByYear' => $this->getGraduatesByYear(),
            'employmentRates' => $this->getEmploymentRates(),
            'salaryRanges' => $this->getSalaryRanges(),
            'topEmployers' => $this->getTopEmployers(),
            'courseOutcomes' => $this->getCourseOutcomes(),
            'jobApplicationTrends' => $this->getJobApplicationTrends(),
            'graduateProgression' => $this->getGraduateProgression(),
        ];

        return Inertia::render('InstitutionAdmin/Analytics', [
            'analytics' => $analytics,
        ]);
    }

    public function reports(Request $request)
    {
        $reportType = $request->get('type', 'employment');
        $dateRange = $request->get('date_range', '1_year');
        
        $reports = [
            'employment' => $this->getEmploymentReport($dateRange),
            'course_performance' => $this->getCoursePerformanceReport($dateRange),
            'graduate_outcomes' => $this->getGraduateOutcomesReport($dateRange),
            'job_placement' => $this->getJobPlacementReport($dateRange),
        ];

        return Inertia::render('InstitutionAdmin/Reports', [
            'reports' => $reports,
            'currentReport' => $reportType,
            'dateRange' => $dateRange,
        ]);
    }

    public function staffManagement()
    {
        $staff = User::where('user_type', 'institution-admin')
            ->orWhere('user_type', 'tutor')
            ->with(['roles'])
            ->paginate(20);

        $roles = \Spatie\Permission\Models\Role::where('name', '!=', 'super-admin')
            ->where('name', '!=', 'graduate')
            ->where('name', '!=', 'employer')
            ->get();

        return Inertia::render('InstitutionAdmin/StaffManagement', [
            'staff' => $staff,
            'roles' => $roles,
        ]);
    }

    public function importExportCenter()
    {
        $importHistory = ImportHistory::with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $stats = [
            'total_imports' => ImportHistory::count(),
            'successful_imports' => ImportHistory::where('status', 'completed')->count(),
            'failed_imports' => ImportHistory::where('status', 'failed')->count(),
            'pending_imports' => ImportHistory::where('status', 'processing')->count(),
        ];

        return Inertia::render('InstitutionAdmin/ImportExportCenter', [
            'importHistory' => $importHistory,
            'stats' => $stats,
        ]);
    }

    private function getBasicStats()
    {
        $user = auth()->user();
        $institutionId = $user->institution_id;

        return [
            'total_graduates' => 0, // TODO: Implement tenant-specific graduate counting
            'employed_graduates' => 0, // TODO: Implement tenant-specific employed graduate counting
            'total_courses' => Course::where('institution_id', $institutionId)->count(),
            'active_jobs' => 0, // TODO: Jobs are not directly linked to institutions
            'pending_applications' => 0, // TODO: Applications are not directly linked to institutions
            'staff_members' => User::where('institution_id', $institutionId)
                ->whereHas('roles', function($query) {
                    $query->whereIn('name', ['institution-admin', 'tutor']);
                })->count(),
        ];
    }

    private function getRecentActivities()
    {
        $user = Auth::user();
        $activities = collect();

        if (!$user->institution_id) {
            return $activities;
        }

        $tenant = Tenant::find($user->institution_id);
        if (!$tenant) {
            return $activities;
        }

        Tenancy::initialize($tenant);

        try {
            // Recent graduate registrations
            $recentGraduates = Graduate::with(['course'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($graduate) {
                    $courseName = $graduate->course ? $graduate->course->name : 'Unknown Course';
                    return [
                        'type' => 'graduate_registered',
                        'message' => "New graduate {$graduate->name} registered for {$courseName}",
                        'timestamp' => $graduate->created_at,
                        'icon' => 'user-plus',
                    ];
                });
        } finally {
            Tenancy::end();
        }

        // Get recent job applications from central database
        $recentApplications = JobApplication::with(['graduate', 'job'])
            ->whereHas('graduate', function($q) use ($user) {
                $q->where('tenant_id', $user->institution_id);
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($application) {
                return [
                    'type' => 'job_application',
                    'message' => "{$application->graduate->name} applied for {$application->job->title}",
                    'timestamp' => $application->created_at,
                    'icon' => 'briefcase',
                ];
            });

        return $activities->merge($recentGraduates)
            ->merge($recentApplications)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values();
    }

    private function getEmploymentStats()
    {
        $user = Auth::user();

        if (!$user->institution_id) {
            return ['total' => 0, 'employed' => 0, 'unemployed' => 0, 'employment_rate' => 0];
        }

        $tenant = Tenant::find($user->institution_id);
        if (!$tenant) {
            return ['total' => 0, 'employed' => 0, 'unemployed' => 0, 'employment_rate' => 0];
        }

        Tenancy::initialize($tenant);

        try {
            $total = Graduate::count();
            $employed = Graduate::where('employment_status', 'employed')->count();
            $unemployed = Graduate::where('employment_status', 'unemployed')->count();
            $seeking = Graduate::where('employment_status', 'unemployed')->count(); // Assuming seeking is same as unemployed for now
        } finally {
            Tenancy::end();
        }

        return [
            'total' => $total,
            'employed' => $employed,
            'unemployed' => $unemployed,
            'seeking' => $seeking,
            'employment_rate' => $total > 0 ? round(($employed / $total) * 100, 1) : 0,
        ];
    }

    private function getCoursePerformance()
    {
        return Course::withCount([
            'graduates',
            'graduates as employed_count' => function ($query) {
                $query->where('employment_status', 'employed');
            }
        ])
        ->get()
        ->map(function ($course) {
            $totalGraduates = $course->graduates_count;
            $employedCount = $course->employed_count;
            
            return [
                'id' => $course->id,
                'name' => $course->name,
                'total_graduates' => $totalGraduates,
                'employed_graduates' => $employedCount,
                'employment_rate' => $totalGraduates > 0 ? round(($employedCount / $totalGraduates) * 100, 1) : 0,
            ];
        });
    }

    private function getGraduatesByYear()
    {
        return Graduate::selectRaw('graduation_year as year, COUNT(*) as count')
            ->whereNotNull('graduation_year')
            ->groupBy('graduation_year')
            ->orderBy('graduation_year')
            ->get();
    }

    private function getEmploymentRates()
    {
        return Course::withCount([
            'graduates',
            'graduates as employed_count' => function ($query) {
                $query->where('employment_status', 'employed');
            }
        ])
        ->get()
        ->map(function ($course) {
            return [
                'course' => $course->name,
                'total' => $course->graduates_count,
                'employed' => $course->employed_count,
                'rate' => $course->graduates_count > 0 ? 
                    round(($course->employed_count / $course->graduates_count) * 100, 1) : 0,
            ];
        });
    }

    private function getSalaryRanges()
    {
        return Graduate::where('employment_status', 'employed')
            ->whereNotNull('current_salary')
            ->get()
            ->groupBy(function ($graduate) {
                $salary = $graduate->current_salary;
                if ($salary < 30000) return 'Under $30K';
                if ($salary < 50000) return '$30K - $50K';
                if ($salary < 75000) return '$50K - $75K';
                if ($salary < 100000) return '$75K - $100K';
                return 'Over $100K';
            })
            ->map(function ($graduates, $range) {
                return [
                    'range' => $range,
                    'count' => $graduates->count(),
                ];
            })
            ->values();
    }

    private function getTopEmployers()
    {
        return Graduate::where('employment_status', 'employed')
            ->whereNotNull('current_company')
            ->get()
            ->groupBy('current_company')
            ->map(function ($graduates, $company) {
                return [
                    'company' => $company,
                    'count' => $graduates->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values();
    }

    private function getCourseOutcomes()
    {
        return Course::with(['graduates' => function ($query) {
            $query->select('course_id', 'employment_status');
        }])
        ->get()
        ->map(function ($course) {
            $graduates = $course->graduates;
            $total = $graduates->count();
            
            return [
                'course' => $course->name,
                'total_graduates' => $total,
                'employed' => $graduates->where('employment_status.status', 'employed')->count(),
                'unemployed' => $graduates->where('employment_status.status', 'unemployed')->count(),
                'seeking' => $graduates->where('employment_status.status', 'seeking')->count(),
                'average_salary' => $this->calculateAverageSalary($graduates),
            ];
        });
    }

    private function getJobApplicationTrends()
    {
        return JobApplication::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    private function getGraduateProgression()
    {
        return Graduate::with(['course'])
            ->where('employment_status', 'employed')
            ->get()
            ->groupBy('course.name')
            ->map(function ($graduates, $courseName) {
                return [
                    'course' => $courseName,
                    'progression' => $graduates->map(function ($graduate) {
                        return [
                            'name' => $graduate->name, // Graduate has name directly, not through user
                            'company' => $graduate->current_company ?? 'Unknown',
                            'position' => $graduate->current_job_title ?? 'Unknown',
                            'start_date' => $graduate->employment_start_date ?? null,
                        ];
                    }),
                ];
            });
    }

    private function calculateAverageSalary($graduates)
    {
        $salaries = $graduates->filter(function ($graduate) {
            return isset($graduate->employment_status['salary_range']);
        });

        if ($salaries->isEmpty()) {
            return null;
        }

        // Convert salary ranges to midpoint values for calculation
        $salaryValues = $salaries->map(function ($graduate) {
            $range = $graduate->employment_status['salary_range'];
            return $this->getSalaryMidpoint($range);
        })->filter();

        return $salaryValues->isEmpty() ? null : round($salaryValues->average());
    }

    private function getSalaryMidpoint($range)
    {
        $ranges = [
            'below_20k' => 15000,
            '20k_30k' => 25000,
            '30k_40k' => 35000,
            '40k_50k' => 45000,
            '50k_75k' => 62500,
            '75k_100k' => 87500,
            'above_100k' => 125000,
        ];

        return $ranges[$range] ?? null;
    }

    private function getEmploymentReport($dateRange)
    {
        $startDate = $this->getStartDate($dateRange);
        
        return [
            'title' => 'Employment Report',
            'period' => $dateRange,
            'data' => Graduate::where('created_at', '>=', $startDate)
                ->with(['course'])
                ->get()
                ->groupBy('employment_status.status')
                ->map(function ($graduates, $status) {
                    return [
                        'status' => $status,
                        'count' => $graduates->count(),
                        'percentage' => round(($graduates->count() / Graduate::count()) * 100, 1),
                    ];
                }),
        ];
    }

    private function getCoursePerformanceReport($dateRange)
    {
        $startDate = $this->getStartDate($dateRange);
        
        return [
            'title' => 'Course Performance Report',
            'period' => $dateRange,
            'data' => Course::withCount([
                'graduates' => function ($query) use ($startDate) {
                    $query->where('created_at', '>=', $startDate);
                },
                'graduates as employed_count' => function ($query) use ($startDate) {
                    $query->where('created_at', '>=', $startDate)
                        ->where('employment_status', 'employed');
                }
            ])
            ->get()
            ->map(function ($course) {
                return [
                    'course' => $course->name,
                    'graduates' => $course->graduates_count,
                    'employed' => $course->employed_count,
                    'employment_rate' => $course->graduates_count > 0 ? 
                        round(($course->employed_count / $course->graduates_count) * 100, 1) : 0,
                ];
            }),
        ];
    }

    private function getGraduateOutcomesReport($dateRange)
    {
        $startDate = $this->getStartDate($dateRange);
        
        return [
            'title' => 'Graduate Outcomes Report',
            'period' => $dateRange,
            'data' => Graduate::where('created_at', '>=', $startDate)
                ->with(['course', 'user'])
                ->get()
                ->map(function ($graduate) {
                    return [
                        'name' => $graduate->user->name,
                        'course' => $graduate->course->name,
                        'graduation_date' => $graduate->graduation_date,
                        'employment_status' => $graduate->employment_status['status'] ?? 'unknown',
                        'company' => $graduate->employment_status['company'] ?? null,
                        'position' => $graduate->employment_status['job_title'] ?? null,
                    ];
                }),
        ];
    }

    private function getJobPlacementReport($dateRange)
    {
        $startDate = $this->getStartDate($dateRange);
        
        return [
            'title' => 'Job Placement Report',
            'period' => $dateRange,
            'data' => JobApplication::where('created_at', '>=', $startDate)
                ->where('status', 'hired')
                ->with(['graduate.user', 'graduate.course', 'job'])
                ->get()
                ->map(function ($application) {
                    return [
                        'graduate' => $application->graduate->user->name,
                        'course' => $application->graduate->course->name,
                        'job_title' => $application->job->title,
                        'company' => $application->job->employer->company_name ?? 'Unknown',
                        'hired_date' => $application->updated_at,
                    ];
                }),
        ];
    }

    public function exportReport(Request $request)
    {
        $reportType = $request->get('type', 'employment');
        $dateRange = $request->get('date_range', '1_year');
        
        $report = match ($reportType) {
            'employment' => $this->getEmploymentReport($dateRange),
            'course_performance' => $this->getCoursePerformanceReport($dateRange),
            'graduate_outcomes' => $this->getGraduateOutcomesReport($dateRange),
            'job_placement' => $this->getJobPlacementReport($dateRange),
            default => $this->getEmploymentReport($dateRange),
        };

        $filename = "institution_report_{$reportType}_{$dateRange}_" . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($report, $reportType) {
            $file = fopen('php://output', 'w');
            
            // Write headers based on report type
            switch ($reportType) {
                case 'employment':
                    fputcsv($file, ['Status', 'Count', 'Percentage']);
                    foreach ($report['data'] as $item) {
                        fputcsv($file, [$item['status'], $item['count'], $item['percentage'] . '%']);
                    }
                    break;
                    
                case 'course_performance':
                    fputcsv($file, ['Course', 'Graduates', 'Employed', 'Employment Rate']);
                    foreach ($report['data'] as $item) {
                        fputcsv($file, [$item['course'], $item['graduates'], $item['employed'], $item['employment_rate'] . '%']);
                    }
                    break;
                    
                case 'graduate_outcomes':
                    fputcsv($file, ['Name', 'Course', 'Graduation Date', 'Employment Status', 'Company', 'Position']);
                    foreach ($report['data'] as $item) {
                        fputcsv($file, [
                            $item['name'], 
                            $item['course'], 
                            $item['graduation_date'], 
                            $item['employment_status'], 
                            $item['company'], 
                            $item['position']
                        ]);
                    }
                    break;
                    
                case 'job_placement':
                    fputcsv($file, ['Graduate', 'Course', 'Job Title', 'Company', 'Hired Date']);
                    foreach ($report['data'] as $item) {
                        fputcsv($file, [
                            $item['graduate'], 
                            $item['course'], 
                            $item['job_title'], 
                            $item['company'], 
                            $item['hired_date']
                        ]);
                    }
                    break;
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getStartDate($dateRange)
    {
        return match ($dateRange) {
            '1_month' => Carbon::now()->subMonth(),
            '3_months' => Carbon::now()->subMonths(3),
            '6_months' => Carbon::now()->subMonths(6),
            '1_year' => Carbon::now()->subYear(),
            '2_years' => Carbon::now()->subYears(2),
            default => Carbon::now()->subYear(),
        };
    }
}