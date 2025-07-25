<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Course;
use App\Models\Graduate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $employer = Auth::user()->employer;
        
        // Check and update expired jobs
        $this->checkExpiredJobs($employer);
        
        $query = Job::where('employer_id', $employer->id)
            ->with(['course', 'applications' => function($q) {
                $q->selectRaw('job_id, COUNT(*) as count, 
                    SUM(CASE WHEN status IN ("reviewed", "shortlisted", "interviewed", "hired") THEN 1 ELSE 0 END) as viewed_count,
                    SUM(CASE WHEN status IN ("shortlisted", "interviewed", "hired") THEN 1 ELSE 0 END) as shortlisted_count')
                  ->groupBy('job_id');
            }]);

        // Apply filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        if ($request->course_id) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->experience_level) {
            $query->where('experience_level', $request->experience_level);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $jobs = $query->orderBy('created_at', 'desc')->paginate(10);

        // Get comprehensive analytics data
        $analytics = [
            'total_jobs' => Job::where('employer_id', $employer->id)->count(),
            'active_jobs' => Job::where('employer_id', $employer->id)->where('status', 'active')->count(),
            'pending_jobs' => Job::where('employer_id', $employer->id)->where('status', 'pending_approval')->count(),
            'expired_jobs' => Job::where('employer_id', $employer->id)->where('status', 'expired')->count(),
            'filled_jobs' => Job::where('employer_id', $employer->id)->where('status', 'filled')->count(),
            'total_applications' => $employer->jobs()->withSum('applications', 'id')->get()->sum('applications_sum_id') ?? 0,
            'avg_applications_per_job' => $employer->jobs()->withAvg('applications', 'id')->get()->avg('applications_avg_id') ?? 0,
            'jobs_expiring_soon' => Job::where('employer_id', $employer->id)
                ->where('status', 'active')
                ->whereNotNull('application_deadline')
                ->whereBetween('application_deadline', [now(), now()->addDays(7)])
                ->count(),
            'top_performing_job' => Job::where('employer_id', $employer->id)
                ->orderBy('total_applications', 'desc')
                ->first(['id', 'title', 'total_applications']),
        ];

        return inertia('Jobs/Index', [
            'jobs' => $jobs,
            'analytics' => $analytics,
            'filters' => $request->only(['status', 'search', 'course_id', 'experience_level', 'date_from', 'date_to']),
            'courses' => Course::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        $courses = Course::active()->orderBy('name')->get(['id', 'name', 'skills_gained']);
        
        return inertia('Jobs/Create', [
            'courses' => $courses,
        ]);
    }

    public function store(Request $request)
    {
        $employer = Auth::user()->employer;
        
        if (!$employer) {
            return back()->with('error', 'You must be registered as an employer to post jobs.');
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'required_skills' => 'nullable|array',
            'required_skills.*' => 'string|max:100',
            'preferred_qualifications' => 'nullable|array',
            'preferred_qualifications.*' => 'string|max:255',
            'experience_level' => 'required|in:entry,junior,mid,senior,executive',
            'min_experience_years' => 'required|integer|min:0|max:50',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'salary_type' => 'required|in:hourly,monthly,annually',
            'job_type' => 'required|in:full_time,part_time,contract,internship,temporary',
            'work_arrangement' => 'required|in:on_site,remote,hybrid',
            'application_deadline' => 'nullable|date|after:today',
            'job_start_date' => 'nullable|date|after_or_equal:today',
            'job_end_date' => 'nullable|date|after:job_start_date',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:255',
            'benefits' => 'nullable|array',
            'benefits.*' => 'string|max:255',
            'company_culture' => 'nullable|string|max:1000',
        ]);

        // Determine if job requires approval
        $requiresApproval = $employer->verification_status !== 'verified';
        
        $data['employer_id'] = $employer->id;
        $data['status'] = $requiresApproval ? 'pending_approval' : 'active';
        $data['requires_approval'] = $requiresApproval;
        $data['employer_verified_required'] = true;

        $job = Job::create($data);

        // Send job to matching graduates if approved
        if (!$requiresApproval) {
            $job->sendToGraduates();
        }

        $message = $requiresApproval 
            ? 'Job posted successfully and is pending admin approval.'
            : 'Job posted successfully and is now active.';

        return redirect()->route('jobs.index')->with('success', $message);
    }

    public function show(Job $job)
    {
        $this->authorize('view', $job);
        
        $job->load(['course', 'employer.user', 'applications.graduate']);
        $job->incrementViewCount();

        // Get matching graduates for recommendations
        $matchingGraduates = $job->getMatchingGraduates(10);
        
        // Calculate match scores
        $graduateMatches = $matchingGraduates->map(function($graduate) use ($job) {
            $matchData = $job->calculateMatchScore($graduate);
            return [
                'graduate' => $graduate,
                'match_score' => $matchData['score'],
                'match_factors' => $matchData['factors'],
            ];
        })->sortByDesc('match_score');

        return inertia('Jobs/Show', [
            'job' => $job,
            'matching_graduates' => $graduateMatches,
            'application_stats' => $job->updateApplicationStats(),
        ]);
    }

    public function edit(Job $job)
    {
        $this->authorize('update', $job);
        
        $courses = Course::active()->orderBy('name')->get(['id', 'name', 'skills_gained']);
        
        return inertia('Jobs/Edit', [
            'job' => $job,
            'courses' => $courses,
        ]);
    }

    public function update(Request $request, Job $job)
    {
        $this->authorize('update', $job);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'required_skills' => 'nullable|array',
            'required_skills.*' => 'string|max:100',
            'preferred_qualifications' => 'nullable|array',
            'preferred_qualifications.*' => 'string|max:255',
            'experience_level' => 'required|in:entry,junior,mid,senior,executive',
            'min_experience_years' => 'required|integer|min:0|max:50',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'salary_type' => 'required|in:hourly,monthly,annually',
            'job_type' => 'required|in:full_time,part_time,contract,internship,temporary',
            'work_arrangement' => 'required|in:on_site,remote,hybrid',
            'application_deadline' => 'nullable|date|after:today',
            'job_start_date' => 'nullable|date|after_or_equal:today',
            'job_end_date' => 'nullable|date|after:job_start_date',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:255',
            'benefits' => 'nullable|array',
            'benefits.*' => 'string|max:255',
            'company_culture' => 'nullable|string|max:1000',
        ]);

        $job->update($data);

        return redirect()->route('jobs.index')->with('success', 'Job updated successfully.');
    }

    public function destroy(Job $job)
    {
        $this->authorize('delete', $job);
        $job->delete();

        return redirect()->route('jobs.index')->with('success', 'Job deleted successfully.');
    }

    // Job status management methods
    public function pause(Job $job)
    {
        $this->authorize('update', $job);
        $job->pause();
        
        return back()->with('success', 'Job paused successfully.');
    }

    public function resume(Job $job)
    {
        $this->authorize('update', $job);
        $job->resume();
        
        return back()->with('success', 'Job resumed successfully.');
    }

    public function markAsFilled(Job $job)
    {
        $this->authorize('update', $job);
        $job->markAsFilled();
        
        return back()->with('success', 'Job marked as filled.');
    }

    public function extend(Request $request, Job $job)
    {
        $this->authorize('update', $job);
        
        $request->validate([
            'application_deadline' => 'required|date|after:today',
        ]);

        $job->update([
            'application_deadline' => $request->application_deadline,
            'status' => 'active',
        ]);

        return back()->with('success', 'Job deadline extended successfully.');
    }

    // Analytics and recommendations
    public function analytics(Job $job)
    {
        $this->authorize('view', $job);
        
        $analytics = $job->getJobPerformanceMetrics();

        // Application trends (last 30 days)
        $applicationTrends = $job->applications()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // View trends (last 30 days) - would need a job_views table for this
        $viewTrends = collect(range(0, 29))->map(function($daysAgo) {
            $date = now()->subDays($daysAgo)->toDateString();
            return [
                'date' => $date,
                'views' => rand(0, 50), // Placeholder - implement proper view tracking
            ];
        })->reverse()->values();

        // Application status breakdown
        $applicationStatusBreakdown = $job->applications()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Graduate demographics
        $graduateDemographics = $job->applications()
            ->with('graduate.course')
            ->get()
            ->groupBy('graduate.course.name')
            ->map(function($applications) {
                return $applications->count();
            });

        // Skills match analysis
        $skillsAnalysis = [];
        if ($job->required_skills) {
            foreach ($job->required_skills as $skill) {
                $matchingApplicants = $job->applications()
                    ->whereHas('graduate', function($q) use ($skill) {
                        $q->whereJsonContains('skills', $skill);
                    })
                    ->count();
                
                $skillsAnalysis[$skill] = [
                    'total_applicants' => $matchingApplicants,
                    'match_rate' => $job->total_applications > 0 
                        ? round(($matchingApplicants / $job->total_applications) * 100, 1) 
                        : 0,
                ];
            }
        }

        // Comparison with similar jobs
        $similarJobsStats = Job::where('course_id', $job->course_id)
            ->where('experience_level', $job->experience_level)
            ->where('id', '!=', $job->id)
            ->where('status', 'active')
            ->selectRaw('
                AVG(total_applications) as avg_applications,
                AVG(view_count) as avg_views,
                AVG(CASE WHEN total_applications > 0 THEN (viewed_applications / total_applications) * 100 ELSE 0 END) as avg_view_rate
            ')
            ->first();

        return inertia('Jobs/Analytics', [
            'job' => $job,
            'analytics' => $analytics,
            'application_trends' => $applicationTrends,
            'view_trends' => $viewTrends,
            'application_status_breakdown' => $applicationStatusBreakdown,
            'graduate_demographics' => $graduateDemographics,
            'skills_analysis' => $skillsAnalysis,
            'similar_jobs_stats' => $similarJobsStats,
        ]);
    }

    public function recommend(Request $request, Job $job)
    {
        $this->authorize('view', $job);
        
        $request->validate([
            'graduate_ids' => 'required|array',
            'graduate_ids.*' => 'exists:graduates,id',
            'message' => 'nullable|string|max:500',
        ]);

        $graduates = Graduate::whereIn('id', $request->graduate_ids)->get();
        
        foreach ($graduates as $graduate) {
            // Send job recommendation notification
            $graduate->user->notifications()->create([
                'type' => 'job_recommendation',
                'data' => [
                    'job_id' => $job->id,
                    'job_title' => $job->title,
                    'company_name' => $job->employer->company_name,
                    'message' => $request->message,
                    'recommended_by' => Auth::user()->name,
                ],
                'read_at' => null,
            ]);
        }

        return back()->with('success', 'Job recommended to ' . count($graduates) . ' graduates.');
    }

    public function renew(Request $request, Job $job)
    {
        $this->authorize('update', $job);
        
        $request->validate([
            'application_deadline' => 'required|date|after:today',
        ]);

        $job->renewJob($request->application_deadline);

        return back()->with('success', 'Job renewed successfully and sent to matching graduates.');
    }

    public function duplicate(Job $job)
    {
        $this->authorize('create', Job::class);
        
        $newJob = $job->replicate();
        $newJob->status = 'draft';
        $newJob->total_applications = 0;
        $newJob->viewed_applications = 0;
        $newJob->shortlisted_applications = 0;
        $newJob->view_count = 0;
        $newJob->approved_at = null;
        $newJob->approved_by = null;
        $newJob->application_deadline = null;
        $newJob->save();

        return redirect()->route('jobs.edit', $newJob)->with('success', 'Job duplicated successfully. Please review and update the details.');
    }

    public function bulkAction(Request $request)
    {
        $this->authorize('viewAny', Job::class);
        
        $request->validate([
            'action' => 'required|in:pause,resume,delete,extend',
            'job_ids' => 'required|array',
            'job_ids.*' => 'exists:jobs,id',
            'extension_days' => 'required_if:action,extend|integer|min:1|max:90',
        ]);

        $employer = Auth::user()->employer;
        $jobs = Job::whereIn('id', $request->job_ids)
            ->where('employer_id', $employer->id)
            ->get();

        $count = 0;
        foreach ($jobs as $job) {
            switch ($request->action) {
                case 'pause':
                    $job->pause();
                    $count++;
                    break;
                case 'resume':
                    $job->resume();
                    $count++;
                    break;
                case 'delete':
                    $job->delete();
                    $count++;
                    break;
                case 'extend':
                    $newDeadline = $job->application_deadline 
                        ? $job->application_deadline->addDays($request->extension_days)
                        : now()->addDays($request->extension_days);
                    $job->renewJob($newDeadline);
                    $count++;
                    break;
            }
        }

        $actionText = [
            'pause' => 'paused',
            'resume' => 'resumed',
            'delete' => 'deleted',
            'extend' => 'extended',
        ];

        return back()->with('success', "Successfully {$actionText[$request->action]} {$count} jobs.");
    }

    private function checkExpiredJobs($employer)
    {
        $expiredJobs = Job::where('employer_id', $employer->id)
            ->where('status', 'active')
            ->whereNotNull('application_deadline')
            ->where('application_deadline', '<', now())
            ->get();

        foreach ($expiredJobs as $job) {
            $job->update(['status' => 'expired']);
        }

        return $expiredJobs->count();
    }

    public function autoRenew(Request $request, Job $job)
    {
        $this->authorize('update', $job);
        
        $request->validate([
            'auto_renew_days' => 'required|integer|min:7|max:90',
        ]);

        $newDeadline = now()->addDays($request->auto_renew_days);
        
        $job->update([
            'application_deadline' => $newDeadline,
            'status' => 'active',
        ]);

        // Send to matching graduates again
        $notifiedCount = $job->sendToGraduates();

        return back()->with('success', "Job auto-renewed for {$request->auto_renew_days} days and sent to {$notifiedCount} matching graduates.");
    }

    public function getJobInsights(Job $job)
    {
        $this->authorize('view', $job);
        
        $insights = [
            'performance_metrics' => $job->getJobPerformanceMetrics(),
            'application_trends' => $job->applications()
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'skills_demand' => $this->getSkillsDemandAnalysis($job),
            'competitor_analysis' => $this->getCompetitorAnalysis($job),
            'optimization_suggestions' => $this->getOptimizationSuggestions($job),
        ];

        return response()->json($insights);
    }

    private function getSkillsDemandAnalysis($job)
    {
        if (!$job->required_skills) {
            return [];
        }

        $analysis = [];
        foreach ($job->required_skills as $skill) {
            $demandCount = Job::where('status', 'active')
                ->where('course_id', $job->course_id)
                ->whereJsonContains('required_skills', $skill)
                ->count();
            
            $supplyCount = Graduate::where('course_id', $job->course_id)
                ->whereJsonContains('skills', $skill)
                ->count();

            $analysis[$skill] = [
                'demand' => $demandCount,
                'supply' => $supplyCount,
                'ratio' => $supplyCount > 0 ? round($demandCount / $supplyCount, 2) : 0,
            ];
        }

        return $analysis;
    }

    private function getCompetitorAnalysis($job)
    {
        $competitors = Job::where('course_id', $job->course_id)
            ->where('experience_level', $job->experience_level)
            ->where('id', '!=', $job->id)
            ->where('status', 'active')
            ->with('employer')
            ->get();

        return [
            'total_competitors' => $competitors->count(),
            'avg_salary_min' => $competitors->avg('salary_min'),
            'avg_salary_max' => $competitors->avg('salary_max'),
            'avg_applications' => $competitors->avg('total_applications'),
            'top_competitors' => $competitors->sortByDesc('total_applications')->take(3)->values(),
        ];
    }

    private function getOptimizationSuggestions($job)
    {
        $suggestions = [];
        
        // Low application rate suggestions
        if ($job->total_applications < 5 && $job->created_at->diffInDays(now()) > 7) {
            $suggestions[] = [
                'type' => 'low_applications',
                'title' => 'Low Application Rate',
                'description' => 'Consider revising job description, salary range, or requirements to attract more candidates.',
                'priority' => 'high',
            ];
        }

        // Salary competitiveness
        $avgSalary = Job::where('course_id', $job->course_id)
            ->where('experience_level', $job->experience_level)
            ->where('id', '!=', $job->id)
            ->avg('salary_max');
        
        if ($avgSalary && $job->salary_max && $job->salary_max < ($avgSalary * 0.8)) {
            $suggestions[] = [
                'type' => 'salary_competitiveness',
                'title' => 'Below Market Salary',
                'description' => 'Your salary range is below market average. Consider increasing to attract more candidates.',
                'priority' => 'medium',
            ];
        }

        // Skills mismatch
        $courseSkills = $job->course->skills_gained ?? [];
        $jobSkills = $job->required_skills ?? [];
        $mismatchedSkills = array_diff($jobSkills, $courseSkills);
        
        if (count($mismatchedSkills) > 0) {
            $suggestions[] = [
                'type' => 'skills_mismatch',
                'title' => 'Skills Mismatch',
                'description' => 'Some required skills may not align with the target course curriculum.',
                'priority' => 'low',
            ];
        }

        return $suggestions;
    }

    public function smartRecommendations(Job $job)
    {
        $this->authorize('view', $job);
        
        $graduates = $job->getMatchingGraduates(50);
        
        $recommendations = $graduates->map(function($graduate) use ($job) {
            $matchData = $job->calculateMatchScore($graduate);
            return [
                'graduate' => $graduate,
                'match_score' => $matchData['score'],
                'match_factors' => $matchData['factors'],
                'contact_preference' => $graduate->allow_employer_contact,
                'profile_completion' => $graduate->profile_completion_percentage,
            ];
        })->sortByDesc('match_score');

        return inertia('Jobs/SmartRecommendations', [
            'job' => $job,
            'recommendations' => $recommendations,
        ]);
    }
}
