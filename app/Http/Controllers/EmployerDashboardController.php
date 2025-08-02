<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Graduate;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EmployerDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // TODO: Implement proper employer profile access
        // For now, create a mock employer profile
        $employer = (object) [
            'id' => $user->id,
            'company_name' => 'Tech Corp',
            'industry' => 'Technology',
            'company_size' => '50-100',
            'website' => 'https://techcorp.com',
            'verification_status' => 'verified'
        ];

        // Get dashboard statistics
        $statistics = $this->getDashboardStatistics($employer);
        
        // Get recent activities
        $recentActivities = $this->getRecentActivities($employer);
        
        // Get job performance metrics
        $jobMetrics = $this->getJobMetrics($employer);
        
        // Get hiring analytics
        $hiringAnalytics = $this->getHiringAnalytics($employer);

        return Inertia::render('Dashboard/Employer', [
            'employer' => $employer->load(['user']),
            'statistics' => $statistics,
            'recentActivities' => $recentActivities,
            'jobMetrics' => $jobMetrics,
            'hiringAnalytics' => $hiringAnalytics,
        ]);
    }

    public function jobManagement(Request $request)
    {
        $employer = Auth::user()->employer;
        
        $query = Job::where('employer_id', $employer->id)
            ->with(['course', 'applications']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $jobs = $query->orderBy('created_at', 'desc')->paginate(15);
        
        $courses = Course::active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Employer/JobManagement', [
            'jobs' => $jobs,
            'courses' => $courses,
            'filters' => $request->only(['status', 'search', 'course_id']),
        ]);
    }

    public function applicationManagement(Request $request)
    {
        $employer = Auth::user()->employer;
        
        $query = JobApplication::whereHas('job', function($q) use ($employer) {
            $q->where('employer_id', $employer->id);
        })->with(['job', 'graduate.user', 'graduate.course']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        if ($request->filled('search')) {
            $query->whereHas('graduate', function($q) use ($request) {
                $q->whereHas('user', function($userQuery) use ($request) {
                    $userQuery->where('name', 'like', '%' . $request->search . '%')
                             ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            });
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(15);
        
        $jobs = Job::where('employer_id', $employer->id)
            ->orderBy('title')
            ->get(['id', 'title']);

        return Inertia::render('Employer/ApplicationManagement', [
            'applications' => $applications,
            'jobs' => $jobs,
            'filters' => $request->only(['status', 'job_id', 'search']),
        ]);
    }

    public function graduateSearch(Request $request)
    {
        $employer = Auth::user()->employer;
        
        if (!$employer->can_search_graduates) {
            return back()->with('error', 'Your account does not have permission to search graduates.');
        }

        $query = Graduate::with(['user', 'course'])
            ->where('profile_visibility', 'public')
            ->orWhere('allow_employer_contact', true);

        // Apply advanced filters
        if ($request->filled('search')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('graduation_year')) {
            $query->where('graduation_year', $request->graduation_year);
        }

        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->employment_status);
        }

        if ($request->filled('skills')) {
            $skills = explode(',', $request->skills);
            $query->where(function($q) use ($skills) {
                foreach ($skills as $skill) {
                    $q->orWhereJsonContains('skills', trim($skill));
                }
            });
        }

        if ($request->filled('location')) {
            $query->where('personal_information->address', 'like', '%' . $request->location . '%');
        }

        $graduates = $query->orderBy('graduation_year', 'desc')
            ->paginate(20);
        
        $courses = Course::active()->orderBy('name')->get(['id', 'name']);
        $graduationYears = Graduate::distinct()
            ->orderBy('graduation_year', 'desc')
            ->pluck('graduation_year')
            ->filter();

        return Inertia::render('Employer/GraduateSearch', [
            'graduates' => $graduates,
            'courses' => $courses,
            'graduationYears' => $graduationYears,
            'filters' => $request->only([
                'search', 'course_id', 'graduation_year', 
                'employment_status', 'skills', 'location'
            ]),
        ]);
    }

    public function companyProfile()
    {
        $employer = Auth::user()->employer;
        
        return Inertia::render('Employer/CompanyProfile', [
            'employer' => $employer,
        ]);
    }

    public function analytics()
    {
        $employer = Auth::user()->employer;
        
        $analytics = [
            'overview' => $this->getAnalyticsOverview($employer),
            'job_performance' => $this->getJobPerformanceAnalytics($employer),
            'application_trends' => $this->getApplicationTrends($employer),
            'hiring_metrics' => $this->getHiringMetrics($employer),
            'candidate_insights' => $this->getCandidateInsights($employer),
        ];

        return Inertia::render('Employer/Analytics', [
            'analytics' => $analytics,
        ]);
    }

    private function getDashboardStatistics($employer)
    {
        // Update job stats to ensure we have current data
        $employer->updateJobStats();
        $employer->refresh();

        return [
            'total_jobs_posted' => $employer->total_jobs_posted ?? 0,
            'active_jobs' => $employer->active_jobs_count ?? 0,
            'total_applications' => JobApplication::whereHas('job', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })->count(),
            'pending_applications' => JobApplication::whereHas('job', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })->where('status', 'pending')->count(),
            'shortlisted_candidates' => JobApplication::whereHas('job', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })->where('status', 'shortlisted')->count(),
            'total_hires' => $employer->total_hires ?? 0,
            'profile_completion' => $employer->getProfileCompletionPercentage(),
            'remaining_job_posts' => $employer->getRemainingJobPosts(),
            'subscription_plan' => $employer->subscription_plan,
            'verification_status' => $employer->verification_status,
        ];
    }

    private function getRecentActivities($employer)
    {
        $recentApplications = JobApplication::whereHas('job', function($q) use ($employer) {
            $q->where('employer_id', $employer->id);
        })
        ->with(['job', 'graduate.user'])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

        $recentJobs = Job::where('employer_id', $employer->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'status', 'created_at']);

        return [
            'recent_applications' => $recentApplications,
            'recent_jobs' => $recentJobs,
        ];
    }

    private function getJobMetrics($employer)
    {
        $jobs = Job::where('employer_id', $employer->id)
            ->withCount('applications')
            ->get();

        return [
            'avg_applications_per_job' => $jobs->avg('applications_count') ?? 0,
            'most_popular_job' => $jobs->sortByDesc('applications_count')->first(),
            'jobs_expiring_soon' => Job::where('employer_id', $employer->id)
                ->where('status', 'active')
                ->whereNotNull('application_deadline')
                ->whereBetween('application_deadline', [now(), now()->addDays(7)])
                ->count(),
        ];
    }

    private function getHiringAnalytics($employer)
    {
        $totalApplications = JobApplication::whereHas('job', function($q) use ($employer) {
            $q->where('employer_id', $employer->id);
        })->count();

        $hiredCount = JobApplication::whereHas('job', function($q) use ($employer) {
            $q->where('employer_id', $employer->id);
        })->where('status', 'hired')->count();

        $averageTimeToHire = JobApplication::whereHas('job', function($q) use ($employer) {
            $q->where('employer_id', $employer->id);
        })
        ->where('status', 'hired')
        ->whereNotNull('hired_at')
        ->selectRaw('AVG(DATEDIFF(hired_at, created_at)) as avg_days')
        ->value('avg_days');

        return [
            'hire_rate' => $totalApplications > 0 ? ($hiredCount / $totalApplications) * 100 : 0,
            'average_time_to_hire' => round($averageTimeToHire ?? 0, 1),
            'total_hires' => $hiredCount,
        ];
    }

    private function getAnalyticsOverview($employer)
    {
        $last30Days = now()->subDays(30);
        
        return [
            'jobs_posted_last_30_days' => Job::where('employer_id', $employer->id)
                ->where('created_at', '>=', $last30Days)
                ->count(),
            'applications_last_30_days' => JobApplication::whereHas('job', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })->where('created_at', '>=', $last30Days)->count(),
            'hires_last_30_days' => JobApplication::whereHas('job', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })->where('status', 'hired')
              ->where('hired_at', '>=', $last30Days)
              ->count(),
        ];
    }

    private function getJobPerformanceAnalytics($employer)
    {
        return Job::where('employer_id', $employer->id)
            ->withCount('applications')
            ->orderBy('applications_count', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'status', 'created_at']);
    }

    private function getApplicationTrends($employer)
    {
        return JobApplication::whereHas('job', function($q) use ($employer) {
            $q->where('employer_id', $employer->id);
        })
        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
        ->where('created_at', '>=', now()->subDays(30))
        ->groupBy('date')
        ->orderBy('date')
        ->get();
    }

    private function getHiringMetrics($employer)
    {
        $applications = JobApplication::whereHas('job', function($q) use ($employer) {
            $q->where('employer_id', $employer->id);
        });

        return [
            'total_applications' => $applications->count(),
            'pending' => $applications->where('status', 'pending')->count(),
            'reviewed' => $applications->where('status', 'reviewed')->count(),
            'shortlisted' => $applications->where('status', 'shortlisted')->count(),
            'interviewed' => $applications->where('status', 'interviewed')->count(),
            'hired' => $applications->where('status', 'hired')->count(),
            'rejected' => $applications->where('status', 'rejected')->count(),
        ];
    }

    private function getCandidateInsights($employer)
    {
        $applications = JobApplication::whereHas('job', function($q) use ($employer) {
            $q->where('employer_id', $employer->id);
        })->with(['graduate.course']);

        return [
            'top_courses' => $applications->get()
                ->groupBy('graduate.course.name')
                ->map->count()
                ->sortDesc()
                ->take(5),
            'graduation_years' => $applications->get()
                ->groupBy('graduate.graduation_year')
                ->map->count()
                ->sortDesc()
                ->take(5),
        ];
    }

    public function communications(Request $request)
    {
        $employer = Auth::user()->employer;
        
        // For now, return a placeholder implementation
        // In a real implementation, you would have a conversations/messages system
        $conversations = collect([]);
        $candidates = Graduate::with(['user', 'course'])
            ->whereHas('applications.job', function($q) use ($employer) {
                $q->where('employer_id', $employer->id);
            })
            ->get();

        return Inertia::render('Employer/Communications', [
            'conversations' => [
                'data' => $conversations,
                'links' => [],
                'total' => 0,
            ],
            'candidates' => $candidates,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function startConversation(Request $request)
    {
        // Placeholder for starting a new conversation
        return back()->with('success', 'Conversation started successfully!');
    }

    public function sendMessage(Request $request, $conversationId)
    {
        // Placeholder for sending a message
        return back()->with('success', 'Message sent successfully!');
    }

    public function markAsRead($conversationId)
    {
        // Placeholder for marking conversation as read
        return back();
    }

    public function archiveConversation($conversationId)
    {
        // Placeholder for archiving conversation
        return back()->with('success', 'Conversation archived successfully!');
    }

    public function blockCandidate($conversationId)
    {
        // Placeholder for blocking candidate
        return back()->with('success', 'Candidate blocked successfully!');
    }
}