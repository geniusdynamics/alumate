<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Graduate;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class JobListController extends Controller
{
    public function index(Request $request)
    {
        $query = Job::with(['employer.user', 'course'])
            ->where('status', 'active')
            ->notExpired();

        // Search filters
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%")
                    ->orWhereHas('employer', function ($eq) use ($request) {
                        $eq->where('company_name', 'like', "%{$request->search}%");
                    });
            });
        }

        if ($request->location) {
            $query->byLocation($request->location);
        }

        if ($request->course_id) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->experience_level) {
            $query->where('experience_level', $request->experience_level);
        }

        if ($request->job_type) {
            $query->where('job_type', $request->job_type);
        }

        if ($request->work_arrangement) {
            $query->where('work_arrangement', $request->work_arrangement);
        }

        if ($request->salary_min) {
            $query->where(function ($q) use ($request) {
                $q->where('salary_min', '>=', $request->salary_min)
                    ->orWhere('salary_max', '>=', $request->salary_min);
            });
        }

        if ($request->salary_max) {
            $query->where(function ($q) use ($request) {
                $q->where('salary_max', '<=', $request->salary_max)
                    ->orWhere('salary_min', '<=', $request->salary_max);
            });
        }

        // Skills matching
        if ($request->skills && is_array($request->skills)) {
            $query->where(function ($q) use ($request) {
                foreach ($request->skills as $skill) {
                    $q->orWhereJsonContains('required_skills', $skill)
                        ->orWhereJsonContains('preferred_qualifications', $skill);
                }
            });
        }

        // Benefits filter
        if ($request->benefits && is_array($request->benefits)) {
            $query->where(function ($q) use ($request) {
                foreach ($request->benefits as $benefit) {
                    $q->orWhereJsonContains('benefits', $benefit);
                }
            });
        }

        // Company size filter (if available in employer data)
        if ($request->company_size) {
            $query->whereHas('employer', function ($eq) use ($request) {
                $eq->where('company_size', $request->company_size);
            });
        }

        // Industry filter
        if ($request->industry) {
            $query->whereHas('employer', function ($eq) use ($request) {
                $eq->where('industry', $request->industry);
            });
        }

        // Posted within filter
        if ($request->posted_within) {
            $days = (int) $request->posted_within;
            $query->where('created_at', '>=', now()->subDays($days));
        }

        // Sorting
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';

        switch ($sortBy) {
            case 'salary':
                $query->orderByRaw('COALESCE(salary_max, salary_min) '.$sortOrder);
                break;
            case 'deadline':
                $query->orderBy('application_deadline', $sortOrder);
                break;
            case 'relevance':
                // If user is a graduate, sort by match score
                if (Auth::check() && Auth::user()->user_type === 'graduate') {
                    $graduate = Graduate::where('user_id', Auth::id())->first();
                    if ($graduate) {
                        // This would require a more complex query with calculated match scores
                        $query->orderBy('created_at', 'desc');
                    }
                } else {
                    $query->orderBy('created_at', 'desc');
                }
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }

        $jobs = $query->paginate(12);

        // Get filter options
        $courses = Course::active()->orderBy('name')->get(['id', 'name']);
        $locations = Job::where('status', 'active')
            ->distinct()
            ->pluck('location')
            ->filter()
            ->sort()
            ->values();

        // Get job recommendations for authenticated graduates
        $recommendations = [];
        if (Auth::check() && Auth::user()->user_type === 'graduate') {
            $graduate = Graduate::where('user_id', Auth::id())->first();
            if ($graduate) {
                $recommendations = $this->getJobRecommendations($graduate, 5);
            }
        }

        return Inertia::render('Jobs/PublicIndex', [
            'jobs' => $jobs,
            'courses' => $courses,
            'locations' => $locations,
            'recommendations' => $recommendations,
            'filters' => $request->only([
                'search', 'location', 'course_id', 'experience_level',
                'job_type', 'work_arrangement', 'salary_min', 'salary_max',
                'skills', 'sort_by', 'sort_order',
            ]),
            'filter_options' => [
                'experience_levels' => [
                    'entry' => 'Entry Level',
                    'junior' => 'Junior',
                    'mid' => 'Mid Level',
                    'senior' => 'Senior',
                    'executive' => 'Executive',
                ],
                'job_types' => [
                    'full_time' => 'Full Time',
                    'part_time' => 'Part Time',
                    'contract' => 'Contract',
                    'internship' => 'Internship',
                    'temporary' => 'Temporary',
                ],
                'work_arrangements' => [
                    'on_site' => 'On Site',
                    'remote' => 'Remote',
                    'hybrid' => 'Hybrid',
                ],
            ],
        ]);
    }

    public function show(Job $job)
    {
        if ($job->status !== 'active' || $job->is_expired) {
            abort(404);
        }

        $job->load(['employer.user', 'course']);
        $job->incrementViewCount();

        // Check if current user can apply
        $canApply = false;
        $hasApplied = false;

        if (Auth::check() && Auth::user()->user_type === 'graduate') {
            $graduate = Graduate::where('user_id', Auth::id())->first();
            if ($graduate) {
                $canApply = $job->canBeAppliedTo();
                $hasApplied = $job->applications()->where('graduate_id', $graduate->id)->exists();
            }
        }

        // Get similar jobs
        $similarJobs = Job::where('course_id', $job->course_id)
            ->where('id', '!=', $job->id)
            ->where('status', 'active')
            ->notExpired()
            ->with(['employer.user'])
            ->limit(4)
            ->get();

        return Inertia::render('Jobs/Show', [
            'job' => $job,
            'can_apply' => $canApply,
            'has_applied' => $hasApplied,
            'similar_jobs' => $similarJobs,
        ]);
    }

    private function getJobRecommendations($graduate, $limit = 5)
    {
        $jobs = Job::where('status', 'active')
            ->notExpired()
            ->with(['employer.user', 'course'])
            ->get();

        $recommendations = [];

        foreach ($jobs as $job) {
            $matchData = $job->calculateMatchScore($graduate);
            if ($matchData['score'] >= 50) { // Only recommend jobs with 50%+ match
                $recommendations[] = [
                    'job' => $job,
                    'match_score' => $matchData['score'],
                    'match_factors' => $matchData['factors'],
                ];
            }
        }

        // Sort by match score and limit
        usort($recommendations, function ($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Public jobs listing (accessible without authentication)
     */
    public function publicIndex(Request $request)
    {
        return $this->index($request);
    }

    /**
     * Public job details (accessible without authentication)
     */
    public function publicShow(Job $job)
    {
        if ($job->status !== 'active' || $job->is_expired) {
            abort(404);
        }

        $job->load(['employer.user', 'course']);
        $job->incrementViewCount();

        // Get similar jobs
        $similarJobs = Job::where('course_id', $job->course_id)
            ->where('id', '!=', $job->id)
            ->where('status', 'active')
            ->notExpired()
            ->with(['employer.user'])
            ->limit(4)
            ->get();

        return Inertia::render('Jobs/PublicShow', [
            'job' => $job,
            'can_apply' => false, // Not authenticated
            'has_applied' => false,
            'similar_jobs' => $similarJobs,
            'auth_required' => true, // Show login prompt for applications
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $query = $request->q;

        $jobs = Job::where('status', 'active')
            ->notExpired()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhereJsonContains('required_skills', $query)
                    ->orWhereHas('employer', function ($eq) use ($query) {
                        $eq->where('company_name', 'like', "%{$query}%");
                    })
                    ->orWhereHas('course', function ($cq) use ($query) {
                        $cq->where('name', 'like', "%{$query}%");
                    });
            })
            ->with(['employer.user', 'course'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'jobs' => $jobs,
            'query' => $query,
        ]);
    }
}
