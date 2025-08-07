<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Graduate;
use App\Models\Job;
use App\Traits\Exportable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CourseController extends Controller
{
    use Exportable;

    public function index(Request $request)
    {
        $user = Auth::user();

        // Initialize tenant context if user has institution_id
        if ($user->institution_id) {
            $tenant = \App\Models\Tenant::find($user->institution_id);
            if ($tenant) {
                tenancy()->initialize($tenant);
            }
        }

        // Start with courses query and handle potential database errors gracefully
        try {
            $query = Course::with(['graduates']);
            
            // If user has institution_id, filter by institution or show all for super-admin
            if ($user->institution_id && !$user->hasRole('super-admin')) {
                $query->where('institution_id', $user->institution_id);
            }
        } catch (\Exception $e) {
            // Log the error and return empty result with error message
            \Log::error('Course index query failed: ' . $e->getMessage());
            
            return Inertia::render('Courses/Index', [
                'courses' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15),
                'levels' => ['certificate', 'diploma', 'advanced_diploma', 'degree', 'other'],
                'studyModes' => ['full_time', 'part_time', 'online', 'hybrid'],
                'departments' => [],
                'filters' => [],
                'error' => 'Unable to load courses. Please contact your administrator.'
            ]);
        }

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('department', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('level')) {
            if (is_array($request->level)) {
                $query->whereIn('level', $request->level);
            } else {
                $query->where('level', $request->level);
            }
        }

        if ($request->filled('study_mode')) {
            if (is_array($request->study_mode)) {
                $query->whereIn('study_mode', $request->study_mode);
            } else {
                $query->where('study_mode', $request->study_mode);
            }
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === 'true');
        }

        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->is_featured === 'true');
        }

        if ($request->filled('employment_rate_min')) {
            $query->where('employment_rate', '>=', $request->employment_rate_min);
        }

        if ($request->filled('duration_min')) {
            $query->where('duration_months', '>=', $request->duration_min);
        }

        if ($request->filled('duration_max')) {
            $query->where('duration_months', '<=', $request->duration_max);
        }

        if ($request->filled('skills')) {
            $skills = is_array($request->skills) ? $request->skills : explode(',', $request->skills);
            foreach ($skills as $skill) {
                $query->where(function($q) use ($skill) {
                    $q->whereJsonContains('skills_gained', trim($skill))
                      ->orWhereJsonContains('required_skills', trim($skill));
                });
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSorts = ['name', 'code', 'level', 'duration_months', 'employment_rate', 'total_graduated', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $courses = $query->paginate(15)->withQueryString();

        // Get filter options
        $levels = ['certificate', 'diploma', 'advanced_diploma', 'degree', 'other'];
        $studyModes = ['full_time', 'part_time', 'online', 'hybrid'];
        $departments = Course::distinct()->pluck('department')->filter()->sort()->values();

        return Inertia::render('Courses/Index', [
            'courses' => $courses,
            'levels' => $levels,
            'studyModes' => $studyModes,
            'departments' => $departments,
            'filters' => $request->only([
                'search', 'level', 'study_mode', 'department', 'is_active', 'is_featured',
                'employment_rate_min', 'duration_min', 'duration_max', 'skills',
                'sort_by', 'sort_order'
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Courses/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:courses',
            'description' => 'nullable|string',
            'level' => 'required|in:certificate,diploma,advanced_diploma,degree,other',
            'duration_months' => 'required|integer|min:1|max:120',
            'study_mode' => 'required|in:full_time,part_time,online,hybrid',
            'department' => 'nullable|string|max:255',
            'required_skills' => 'nullable|array',
            'skills_gained' => 'nullable|array',
            'career_paths' => 'nullable|array',
            'prerequisites' => 'nullable|array',
            'learning_outcomes' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $course = Course::create($data);

        return redirect()->route('courses.index')
            ->with('success', 'Course created successfully!');
    }

    public function show(Course $course)
    {
        $course->load(['graduates.applications.job']);
        
        // Get course analytics
        $analytics = [
            'employment_trends' => $course->getEmploymentTrends(),
            'recent_graduates' => $course->getRecentGraduates(10),
            'matching_jobs' => $course->getMatchingJobs(10),
            'statistics' => $course->updateStatistics(),
        ];

        return Inertia::render('Courses/Show', [
            'course' => $course,
            'analytics' => $analytics,
        ]);
    }

    public function edit(Course $course)
    {
        return Inertia::render('Courses/Edit', [
            'course' => $course,
        ]);
    }

    public function update(Request $request, Course $course)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:courses,code,' . $course->id,
            'description' => 'nullable|string',
            'level' => 'required|in:certificate,diploma,advanced_diploma,degree,other',
            'duration_months' => 'required|integer|min:1|max:120',
            'study_mode' => 'required|in:full_time,part_time,online,hybrid',
            'department' => 'nullable|string|max:255',
            'required_skills' => 'nullable|array',
            'skills_gained' => 'nullable|array',
            'career_paths' => 'nullable|array',
            'prerequisites' => 'nullable|array',
            'learning_outcomes' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $course->update($data);

        return redirect()->route('courses.index')
            ->with('success', 'Course updated successfully!');
    }

    public function destroy(Course $course)
    {
        // Check if course has graduates
        if ($course->graduates()->count() > 0) {
            return back()->withErrors([
                'course' => 'Cannot delete course with existing graduates. Please transfer graduates to another course first.'
            ]);
        }

        $course->delete();

        return redirect()->route('courses.index')
            ->with('success', 'Course deleted successfully!');
    }

    public function analytics(Course $course)
    {
        $analytics = [
            'employment_trends' => $course->getEmploymentTrends(10),
            'graduate_statistics' => [
                'total_graduates' => $course->graduates()->count(),
                'employed_graduates' => $course->graduates()->employed()->count(),
                'unemployed_graduates' => $course->graduates()->unemployed()->count(),
                'job_search_active' => $course->graduates()->jobSearchActive()->count(),
            ],
            'salary_statistics' => [
                'average_salary' => $course->graduates()->employed()->avg('current_salary'),
                'median_salary' => $course->graduates()->employed()->orderBy('current_salary')->skip(intval($course->graduates()->employed()->count() / 2))->first()?->current_salary,
                'salary_ranges' => $this->getSalaryRanges($course),
            ],
            'job_matching' => [
                'total_jobs' => Job::where('course_id', $course->id)->count(),
                'active_jobs' => Job::where('course_id', $course->id)->where('status', 'active')->count(),
                'recent_jobs' => $course->getMatchingJobs(5),
            ],
            'skills_analysis' => $this->getSkillsAnalysis($course),
        ];

        return Inertia::render('Courses/Analytics', [
            'course' => $course,
            'analytics' => $analytics,
        ]);
    }

    public function updateStatistics(Course $course)
    {
        $statistics = $course->updateStatistics();
        
        return response()->json([
            'message' => 'Course statistics updated successfully',
            'statistics' => $statistics,
        ]);
    }

    public function export(Request $request)
    {
        $query = Course::query();

        // Apply same filters as index method
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('level')) {
            if (is_array($request->level)) {
                $query->whereIn('level', $request->level);
            } else {
                $query->where('level', $request->level);
            }
        }

        // Apply other filters...
        if ($request->filled('study_mode')) {
            if (is_array($request->study_mode)) {
                $query->whereIn('study_mode', $request->study_mode);
            } else {
                $query->where('study_mode', $request->study_mode);
            }
        }

        // Define export columns
        $columns = [
            'id', 'name', 'code', 'description', 'level', 'duration_months',
            'study_mode', 'department', 'total_enrolled', 'total_graduated',
            'completion_rate', 'employment_rate', 'average_salary',
            'is_active', 'is_featured', 'created_at', 'updated_at'
        ];

        $format = $request->get('format', 'csv');
        $filename = 'courses_export_' . date('Y-m-d_H-i-s');

        if ($format === 'json') {
            return $this->exportToJson($query, $filename . '.json');
        }

        return $this->exportToCsv($query, $columns, $filename . '.csv');
    }

    private function getSalaryRanges(Course $course)
    {
        $salaries = $course->graduates()->employed()->whereNotNull('current_salary')->pluck('current_salary');
        
        if ($salaries->isEmpty()) {
            return [];
        }

        $ranges = [
            '0-30k' => 0,
            '30k-50k' => 0,
            '50k-70k' => 0,
            '70k-100k' => 0,
            '100k+' => 0,
        ];

        foreach ($salaries as $salary) {
            if ($salary < 30000) {
                $ranges['0-30k']++;
            } elseif ($salary < 50000) {
                $ranges['30k-50k']++;
            } elseif ($salary < 70000) {
                $ranges['50k-70k']++;
            } elseif ($salary < 100000) {
                $ranges['70k-100k']++;
            } else {
                $ranges['100k+']++;
            }
        }

        return $ranges;
    }

    private function getSkillsAnalysis(Course $course)
    {
        $skillsGained = collect($course->skills_gained ?? []);
        $graduateSkills = $course->graduates()->pluck('skills')->flatten();
        
        $skillsComparison = [];
        
        foreach ($skillsGained as $skill) {
            $skillsComparison[$skill] = [
                'taught' => true,
                'graduates_with_skill' => $graduateSkills->filter(function($graduateSkill) use ($skill) {
                    return stripos($graduateSkill, $skill) !== false;
                })->count(),
            ];
        }

        return $skillsComparison;
    }
}
