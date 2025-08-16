<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\SavedSearch;
use App\Models\SearchAnalytics;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function index(Request $request)
    {
        $searchType = $request->get('type', 'jobs');
        $savedSearches = [];

        if (auth()->check()) {
            $savedSearches = $this->searchService->getUserSavedSearches(
                auth()->id(),
                $searchType
            );
        }

        return Inertia::render('Search/Index', [
            'searchType' => $searchType,
            'savedSearches' => $savedSearches,
            'courses' => Course::active()->get(['id', 'name', 'code']),
            'filters' => $this->getSearchFilters($searchType),
        ]);
    }

    public function searchJobs(Request $request)
    {
        $criteria = $request->validate([
            'keywords' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
            'job_type' => 'nullable|string|in:full_time,part_time,contract,internship',
            'experience_level' => 'nullable|string|in:entry,junior,mid,senior',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'work_arrangement' => 'nullable|string|in:onsite,remote,hybrid',
            'employer_verified' => 'nullable|boolean',
            'sort_by' => 'nullable|string|in:created_at,salary,deadline,applications,relevance',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:10|max:50',
        ]);

        $perPage = $request->get('per_page', 20);
        $results = $this->searchService->searchJobs($criteria, $perPage);

        // Track search analytics
        $this->trackSearch('jobs', $criteria, $results->total());

        return response()->json([
            'results' => $results,
            'criteria' => $criteria,
            'suggestions' => $this->searchService->getSearchSuggestions(
                $criteria['keywords'] ?? '',
                'jobs'
            ),
        ]);
    }

    public function searchGraduates(Request $request)
    {
        $this->authorize('search-graduates');

        $criteria = $request->validate([
            'keywords' => 'nullable|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
            'graduation_year' => 'nullable|array',
            'graduation_year.*' => 'integer|min:2000|max:'.(date('Y') + 5),
            'employment_status' => 'nullable|string|in:employed,unemployed,self_employed,student',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'min_gpa' => 'nullable|numeric|min:0|max:4',
            'max_gpa' => 'nullable|numeric|min:0|max:4',
            'location' => 'nullable|string|max:255',
            'profile_completion_min' => 'nullable|integer|min:0|max:100',
            'job_id' => 'nullable|exists:jobs,id',
            'sort_by' => 'nullable|string|in:profile_completion_percentage,graduation_year,gpa,name',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:10|max:50',
        ]);

        $perPage = $request->get('per_page', 20);
        $results = $this->searchService->searchGraduates($criteria, $perPage);

        // Track search analytics
        $this->trackSearch('graduates', $criteria, $results->total());

        return response()->json([
            'results' => $results,
            'criteria' => $criteria,
        ]);
    }

    public function searchCourses(Request $request)
    {
        $criteria = $request->validate([
            'keywords' => 'nullable|string|max:255',
            'level' => 'nullable|string|in:certificate,diploma,degree,postgraduate',
            'duration_min' => 'nullable|integer|min:1',
            'duration_max' => 'nullable|integer|min:1',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'min_employment_rate' => 'nullable|numeric|min:0|max:100',
            'featured_only' => 'nullable|boolean',
            'sort_by' => 'nullable|string|in:employment_rate,name,duration_months,total_graduated',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:10|max:50',
        ]);

        $perPage = $request->get('per_page', 20);
        $results = $this->searchService->searchCourses($criteria, $perPage);

        // Track search analytics
        $this->trackSearch('courses', $criteria, $results->total());

        return response()->json([
            'results' => $results,
            'criteria' => $criteria,
        ]);
    }

    public function getRecommendations(Request $request)
    {
        $type = $request->get('type', 'jobs');
        $limit = $request->get('limit', 10);

        if ($type === 'jobs') {
            $graduate = auth()->user()->graduate;
            if (! $graduate) {
                return response()->json(['recommendations' => []]);
            }

            $preferences = $request->validate([
                'location' => 'nullable|string|max:255',
                'salary_min' => 'nullable|numeric|min:0',
                'job_type' => 'nullable|string|in:full_time,part_time,contract,internship',
                'work_arrangement' => 'nullable|string|in:onsite,remote,hybrid',
            ]);

            $recommendations = $this->searchService->getAdvancedJobMatches($graduate, $preferences);

            return response()->json([
                'recommendations' => $recommendations->take($limit)->values(),
                'total' => $recommendations->count(),
            ]);
        }

        if ($type === 'candidates' && auth()->user()->hasRole('employer')) {
            $jobId = $request->get('job_id');
            if (! $jobId) {
                return response()->json(['error' => 'Job ID is required'], 400);
            }

            $job = auth()->user()->employer->jobs()->findOrFail($jobId);
            $recommendations = $this->searchService->getCandidateRecommendations($job, $limit);

            return response()->json([
                'recommendations' => $recommendations->values(),
                'total' => $recommendations->count(),
            ]);
        }

        return response()->json(['recommendations' => []]);
    }

    public function getSuggestions(Request $request)
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all');

        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $suggestions = $this->searchService->getSearchSuggestions($query, $type);

        return response()->json(['suggestions' => $suggestions]);
    }

    public function saveSearch(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'search_type' => 'required|string|in:jobs,graduates,courses',
            'search_criteria' => 'required|array',
            'alert_enabled' => 'boolean',
            'alert_frequency' => 'string|in:immediate,daily,weekly',
        ]);

        $savedSearch = $this->searchService->saveSearch(
            auth()->id(),
            $data['name'],
            $data['search_type'],
            $data['search_criteria'],
            $data['alert_enabled'] ?? false,
            $data['alert_frequency'] ?? 'weekly'
        );

        return response()->json([
            'message' => 'Search saved successfully',
            'saved_search' => $savedSearch,
        ]);
    }

    public function getSavedSearches(Request $request)
    {
        $type = $request->get('type');
        $savedSearches = $this->searchService->getUserSavedSearches(auth()->id(), $type);

        return response()->json(['saved_searches' => $savedSearches]);
    }

    public function updateSavedSearch(Request $request, SavedSearch $savedSearch)
    {
        $this->authorize('update', $savedSearch);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'search_criteria' => 'sometimes|array',
            'alert_enabled' => 'sometimes|boolean',
            'alert_frequency' => 'sometimes|string|in:immediate,daily,weekly',
            'is_active' => 'sometimes|boolean',
        ]);

        $savedSearch->update($data);

        return response()->json([
            'message' => 'Search updated successfully',
            'saved_search' => $savedSearch,
        ]);
    }

    public function deleteSavedSearch(SavedSearch $savedSearch)
    {
        $this->authorize('delete', $savedSearch);

        $savedSearch->delete();

        return response()->json(['message' => 'Search deleted successfully']);
    }

    public function executeSavedSearch(SavedSearch $savedSearch)
    {
        $this->authorize('view', $savedSearch);

        $results = $savedSearch->executeSearch();
        $savedSearch->updateResultsCount();

        return response()->json([
            'results' => $results,
            'saved_search' => $savedSearch,
        ]);
    }

    public function getSearchAnalytics(Request $request)
    {
        $this->authorize('view-analytics');

        $period = $request->get('period', '30'); // days
        $type = $request->get('type', 'all');

        $query = SearchAnalytics::where('searched_at', '>=', now()->subDays($period));

        if ($type !== 'all') {
            $query->where('search_type', $type);
        }

        $analytics = $query->get();

        $stats = [
            'total_searches' => $analytics->count(),
            'unique_users' => $analytics->whereNotNull('user_id')->unique('user_id')->count(),
            'searches_by_type' => $analytics->groupBy('search_type')->map->count(),
            'popular_keywords' => $this->getPopularKeywords($analytics),
            'search_trends' => $this->getSearchTrends($analytics),
            'avg_results_per_search' => $analytics->avg('results_count'),
        ];

        return response()->json(['analytics' => $stats]);
    }

    private function trackSearch($type, $criteria, $resultsCount)
    {
        if (auth()->check()) {
            SearchAnalytics::create([
                'user_id' => auth()->id(),
                'search_type' => $type,
                'search_criteria' => $criteria,
                'results_count' => $resultsCount,
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
                'searched_at' => now(),
            ]);
        }
    }

    private function getSearchFilters($type)
    {
        $baseFilters = [
            'keywords' => ['type' => 'text', 'label' => 'Keywords'],
            'location' => ['type' => 'text', 'label' => 'Location'],
        ];

        switch ($type) {
            case 'jobs':
                return array_merge($baseFilters, [
                    'course_id' => ['type' => 'select', 'label' => 'Course'],
                    'job_type' => [
                        'type' => 'select',
                        'label' => 'Job Type',
                        'options' => [
                            'full_time' => 'Full Time',
                            'part_time' => 'Part Time',
                            'contract' => 'Contract',
                            'internship' => 'Internship',
                        ],
                    ],
                    'experience_level' => [
                        'type' => 'select',
                        'label' => 'Experience Level',
                        'options' => [
                            'entry' => 'Entry Level',
                            'junior' => 'Junior',
                            'mid' => 'Mid Level',
                            'senior' => 'Senior',
                        ],
                    ],
                    'salary_min' => ['type' => 'number', 'label' => 'Minimum Salary'],
                    'salary_max' => ['type' => 'number', 'label' => 'Maximum Salary'],
                    'skills' => ['type' => 'tags', 'label' => 'Required Skills'],
                    'work_arrangement' => [
                        'type' => 'select',
                        'label' => 'Work Arrangement',
                        'options' => [
                            'onsite' => 'On-site',
                            'remote' => 'Remote',
                            'hybrid' => 'Hybrid',
                        ],
                    ],
                ]);

            case 'graduates':
                return array_merge($baseFilters, [
                    'course_id' => ['type' => 'select', 'label' => 'Course'],
                    'graduation_year' => ['type' => 'year_range', 'label' => 'Graduation Year'],
                    'employment_status' => [
                        'type' => 'select',
                        'label' => 'Employment Status',
                        'options' => [
                            'employed' => 'Employed',
                            'unemployed' => 'Unemployed',
                            'self_employed' => 'Self Employed',
                            'student' => 'Student',
                        ],
                    ],
                    'skills' => ['type' => 'tags', 'label' => 'Skills'],
                    'min_gpa' => ['type' => 'number', 'label' => 'Minimum GPA', 'step' => 0.1, 'max' => 4],
                    'max_gpa' => ['type' => 'number', 'label' => 'Maximum GPA', 'step' => 0.1, 'max' => 4],
                    'profile_completion_min' => ['type' => 'number', 'label' => 'Min Profile Completion %'],
                ]);

            case 'courses':
                return array_merge($baseFilters, [
                    'level' => [
                        'type' => 'select',
                        'label' => 'Level',
                        'options' => [
                            'certificate' => 'Certificate',
                            'diploma' => 'Diploma',
                            'degree' => 'Degree',
                            'postgraduate' => 'Postgraduate',
                        ],
                    ],
                    'duration_min' => ['type' => 'number', 'label' => 'Min Duration (months)'],
                    'duration_max' => ['type' => 'number', 'label' => 'Max Duration (months)'],
                    'skills' => ['type' => 'tags', 'label' => 'Skills Gained'],
                    'min_employment_rate' => ['type' => 'number', 'label' => 'Min Employment Rate %'],
                    'featured_only' => ['type' => 'checkbox', 'label' => 'Featured Courses Only'],
                ]);

            default:
                return $baseFilters;
        }
    }

    private function getPopularKeywords($analytics)
    {
        $keywords = [];

        foreach ($analytics as $search) {
            $criteria = $search->search_criteria;
            if (! empty($criteria['keywords'])) {
                $words = explode(' ', strtolower($criteria['keywords']));
                foreach ($words as $word) {
                    $word = trim($word);
                    if (strlen($word) > 2) {
                        $keywords[$word] = ($keywords[$word] ?? 0) + 1;
                    }
                }
            }
        }

        arsort($keywords);

        return array_slice($keywords, 0, 10, true);
    }

    private function getSearchTrends($analytics)
    {
        return $analytics->groupBy(function ($search) {
            return $search->searched_at->format('Y-m-d');
        })->map->count()->sortKeys();
    }
}
