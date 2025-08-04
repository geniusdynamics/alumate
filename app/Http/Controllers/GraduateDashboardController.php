<?php

namespace App\Http\Controllers;

use App\Models\Graduate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Course;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Stancl\Tenancy\Facades\Tenancy;

class GraduateDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get user's institution (tenant)
        if (!$user->institution_id) {
            return redirect()->route('graduates.create')
                ->with('error', 'Please select your institution first.');
        }

        $tenant = Tenant::find($user->institution_id);
        if (!$tenant) {
            return redirect()->route('graduates.create')
                ->with('error', 'Institution not found.');
        }

        // Switch to tenant context to access graduate data
        $graduate = null;
        $statistics = [];
        $recentActivities = [];
        $jobRecommendations = [];
        $classmateConnections = [];

        Tenancy::initialize($tenant);

        try {
            // Try to find existing graduate record
            $graduate = Graduate::where('user_id', $user->id)->first();

            if (!$graduate) {
                // Create graduate record if it doesn't exist
                $graduate = Graduate::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'student_id' => 'STU' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                    'graduation_year' => now()->year,
                    'employment_status' => 'unemployed',
                    'course_id' => null, // Will be set later when user selects a course
                ]);
            }

            // Get dashboard data
            $statistics = $this->getDashboardStatistics($graduate);
            $recentActivities = $this->getRecentActivities($graduate);
            $jobRecommendations = $this->getJobRecommendations($graduate);
            $classmateConnections = $this->getClassmateConnections($graduate);

        } finally {
            // Always end tenancy context
            Tenancy::end();
        }

        // Get dashboard statistics
        $statistics = $this->getDashboardStatistics($graduate);
        
        // Get recent activities
        $recentActivities = $this->getRecentActivities($graduate);
        
        // Get job recommendations
        $jobRecommendations = $this->getJobRecommendations($graduate);
        
        // Get classmate connections
        $classmateConnections = $this->getClassmateConnections($graduate);

        if (!$graduate) {
            return redirect()->route('graduates.create')
                ->with('error', 'Unable to access graduate profile.');
        }

        return Inertia::render('Dashboard/Graduate', [
            'graduate' => $graduate->load(['course']),
            'statistics' => $statistics,
            'recentActivities' => $recentActivities,
            'jobRecommendations' => $jobRecommendations,
            'classmateConnections' => $classmateConnections,
        ]);
    }

    public function profile()
    {
        $user = Auth::user();

        // TODO: Implement proper tenant-specific graduate access
        // For now, create a mock graduate profile
        $graduate = (object) [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'employment_status' => 'employed',
            'current_job_title' => 'Software Developer',
            'current_company' => 'Tech Corp',
            'graduation_year' => 2023,
            'profile_completion_percentage' => 85.5
        ];

        // Add mock relationships to the graduate object
        $graduate->user = (object) ['name' => $graduate->name, 'email' => $graduate->email];
        $graduate->course = (object) ['name' => 'Computer Science', 'id' => 1];

        return Inertia::render('Graduate/Profile', [
            'graduate' => $graduate,
            'profileCompletion' => $graduate->profile_completion_percentage ?? 85.5,
        ]);
    }

    public function jobBrowsing(Request $request)
    {
        $user = Auth::user();

        // TODO: Implement proper tenant-specific graduate access
        // For now, create a mock graduate profile
        $graduate = (object) [
            'id' => $user->id,
            'skills' => ['PHP', 'Laravel', 'Vue.js', 'JavaScript'],
            'course_id' => 1
        ];
        
        $query = Job::with(['employer', 'course', 'applications'])
            ->where('status', 'active')
            ->where('application_deadline', '>', now())
            ->orWhereNull('application_deadline');

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        if ($request->filled('job_type')) {
            $query->where('job_type', $request->job_type);
        }

        if ($request->filled('experience_level')) {
            $query->where('experience_level', $request->experience_level);
        }

        if ($request->filled('salary_min')) {
            $query->where('salary_max', '>=', $request->salary_min);
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        // Skills matching
        if ($graduate && $graduate->skills) {
            $query->orderByRaw('
                CASE 
                    WHEN JSON_OVERLAPS(required_skills, ?) THEN 1
                    WHEN JSON_OVERLAPS(preferred_skills, ?) THEN 2
                    ELSE 3
                END
            ', [json_encode($graduate->skills), json_encode($graduate->skills)]);
        }

        $jobs = $query->orderBy('created_at', 'desc')->paginate(12);
        
        $courses = Course::active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Graduate/JobBrowsing', [
            'jobs' => $jobs,
            'courses' => $courses,
            'graduate' => $graduate,
            'filters' => $request->only([
                'search', 'location', 'job_type', 'experience_level', 
                'salary_min', 'course_id'
            ]),
        ]);
    }

    public function applications(Request $request)
    {
        $user = Auth::user();

        // TODO: Implement proper tenant-specific graduate access
        // For now, create a mock graduate profile
        $graduate = (object) [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ];

        // Mock applications data
        $applications = collect([
            (object) [
                'id' => 1,
                'status' => 'pending',
                'created_at' => now()->subDays(2),
                'job' => (object) [
                    'title' => 'Software Developer',
                    'employer' => (object) ['company_name' => 'Tech Corp'],
                    'course' => (object) ['name' => 'Computer Science']
                ]
            ]
        ]);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->whereHas('job', function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhereHas('employer', function($empQuery) use ($request) {
                      $empQuery->where('company_name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(15);

        return Inertia::render('Graduate/Applications', [
            'applications' => $applications,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function classmates(Request $request)
    {
        $user = Auth::user();
        $graduate = $user->graduate;
        
        if (!$graduate) {
            return redirect()->route('graduates.create');
        }

        $query = Graduate::with(['user', 'course'])
            ->where('id', '!=', $graduate->id)
            ->where('course_id', $graduate->course_id)
            ->where('profile_visibility', 'public');

        // Apply filters
        if ($request->filled('search')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('graduation_year')) {
            $query->where('graduation_year', $request->graduation_year);
        }

        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->employment_status);
        }

        $classmates = $query->orderBy('graduation_year', 'desc')
            ->paginate(20);

        $graduationYears = Graduate::where('course_id', $graduate->course_id)
            ->distinct()
            ->orderBy('graduation_year', 'desc')
            ->pluck('graduation_year')
            ->filter();

        return Inertia::render('Graduate/Classmates', [
            'classmates' => $classmates,
            'graduationYears' => $graduationYears,
            'filters' => $request->only(['search', 'graduation_year', 'employment_status']),
        ]);
    }

    public function careerProgress()
    {
        $user = Auth::user();

        // TODO: Implement proper tenant-specific graduate access
        // For now, create a mock graduate profile
        $graduate = (object) [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'employment_status' => 'employed',
            'current_job_title' => 'Software Developer',
            'current_company' => 'Tech Corp',
            'graduation_year' => 2023,
            'profile_completion_percentage' => 85.5
        ];

        $careerHistory = $this->getCareerHistory($graduate);
        $skillsProgress = $this->getSkillsProgress($graduate);
        $achievements = $this->getAchievements($graduate);

        // Add mock relationships to the graduate object
        $graduate->user = (object) ['name' => $graduate->name, 'email' => $graduate->email];
        $graduate->course = (object) ['name' => 'Computer Science', 'id' => 1];

        return Inertia::render('Graduate/CareerProgress', [
            'graduate' => $graduate,
            'careerHistory' => $careerHistory,
            'skillsProgress' => $skillsProgress,
            'achievements' => $achievements,
        ]);
    }

    public function assistanceRequests(Request $request)
    {
        $user = Auth::user();
        $graduate = $user->graduate;
        
        if (!$graduate) {
            return redirect()->route('graduates.create');
        }

        // For now, return a placeholder implementation
        // In a real implementation, you would have an assistance requests system
        $requests = collect([]);

        return Inertia::render('Graduate/AssistanceRequests', [
            'requests' => [
                'data' => $requests,
                'links' => [],
                'total' => 0,
            ],
            'filters' => $request->only(['status', 'category']),
        ]);
    }

    public function submitAssistanceRequest(Request $request)
    {
        $request->validate([
            'category' => 'required|string|in:career,academic,technical,personal',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'priority' => 'required|string|in:low,medium,high,urgent',
        ]);

        // Placeholder for assistance request creation
        return back()->with('success', 'Assistance request submitted successfully!');
    }

    private function getDashboardStatistics($graduate)
    {
        return [
            'profile_completion' => $graduate->getProfileCompletionPercentage(),
            'total_applications' => JobApplication::where('graduate_id', $graduate->id)->count(),
            'pending_applications' => JobApplication::where('graduate_id', $graduate->id)
                ->where('status', 'pending')->count(),
            'interview_invitations' => JobApplication::where('graduate_id', $graduate->id)
                ->where('status', 'interviewed')->count(),
            'job_offers' => JobApplication::where('graduate_id', $graduate->id)
                ->where('status', 'hired')->count(),
            'employment_status' => $graduate->employment_status ?? 'unemployed',
            'course_completion_year' => $graduate->graduation_year,
            'skills_count' => count($graduate->skills ?? []),
        ];
    }

    private function getRecentActivities($graduate)
    {
        $recentApplications = JobApplication::where('graduate_id', $graduate->id)
            ->with(['job.employer'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentJobMatches = Job::where('status', 'active')
            ->where('created_at', '>=', now()->subDays(7))
            ->limit(5)
            ->get();

        return [
            'recent_applications' => $recentApplications,
            'recent_job_matches' => $recentJobMatches,
        ];
    }

    private function getJobRecommendations($graduate)
    {
        $query = Job::with(['employer', 'course'])
            ->where('status', 'active')
            ->where(function($q) {
                $q->where('application_deadline', '>', now())
                  ->orWhereNull('application_deadline');
            });

        // Match by course
        if ($graduate->course_id) {
            $query->where('course_id', $graduate->course_id);
        }

        // Match by skills
        if ($graduate->skills) {
            $query->where(function($q) use ($graduate) {
                $q->whereJsonOverlaps('required_skills', $graduate->skills)
                  ->orWhereJsonOverlaps('preferred_skills', $graduate->skills);
            });
        }

        // Exclude already applied jobs
        $appliedJobIds = JobApplication::where('graduate_id', $graduate->id)
            ->pluck('job_id');

        if ($appliedJobIds->isNotEmpty()) {
            $query->whereNotIn('id', $appliedJobIds);
        }

        return $query->orderBy('created_at', 'desc')->limit(6)->get();
    }

    private function getClassmateConnections($graduate)
    {
        return Graduate::where('course_id', $graduate->course_id)
            ->where('id', '!=', $graduate->id)
            ->where('profile_visibility', 'public')
            ->orderBy('graduation_year', 'desc')
            ->limit(8)
            ->get();
    }

    private function getCareerHistory($graduate)
    {
        // This would typically come from a career history table
        // For now, return employment status history
        $history = [];
        
        if ($graduate->employment_status && isset($graduate->employment_status['status'])) {
            $history[] = [
                'date' => $graduate->updated_at,
                'event' => 'Employment Status Updated',
                'details' => $graduate->employment_status,
            ];
        }

        return $history;
    }

    private function getSkillsProgress($graduate)
    {
        // This would typically track skill development over time
        // For now, return current skills
        return [
            'current_skills' => $graduate->skills ?? [],
            'skill_endorsements' => [], // Placeholder
            'certifications' => $graduate->certifications ?? [],
        ];
    }

    private function getAchievements($graduate)
    {
        $achievements = [];

        // Profile completion achievement
        $profileCompletion = $graduate->profile_completion_percentage ?? 85.5;
        if ($profileCompletion >= 100) {
            $achievements[] = [
                'title' => 'Profile Complete',
                'description' => 'Completed your graduate profile',
                'date' => now()->subDays(30),
                'type' => 'profile',
            ];
        }

        // Job application achievements - using mock data
        $applicationCount = 12; // Mock data
        if ($applicationCount >= 5) {
            $achievements[] = [
                'title' => 'Active Job Seeker',
                'description' => 'Applied to 5 or more jobs',
                'date' => now()->subDays(10),
                'type' => 'application',
            ];
        }

        return $achievements;
    }
}