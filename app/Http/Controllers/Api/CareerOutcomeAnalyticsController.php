<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CareerOutcomeSnapshot;
use App\Models\CareerTrend;
use App\Models\DemographicOutcome;
use App\Models\IndustryPlacement;
use App\Models\ProgramEffectiveness;
use App\Services\CareerOutcomeAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CareerOutcomeAnalyticsController extends Controller
{
    public function __construct(
        private CareerOutcomeAnalyticsService $analyticsService
    ) {}

    /**
     * Get comprehensive career outcome analytics
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'graduation_year' => 'nullable|string',
            'program' => 'nullable|string',
            'department' => 'nullable|string',
            'industry' => 'nullable|string',
            'demographic_type' => 'nullable|string',
            'demographic_value' => 'nullable|string',
        ]);

        $analytics = $this->analyticsService->generateOutcomeAnalytics($filters);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Get overview metrics
     */
    public function overview(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'graduation_year' => 'nullable|string',
            'program' => 'nullable|string',
            'department' => 'nullable|string',
            'industry' => 'nullable|string',
        ]);

        $overview = $this->analyticsService->getOverviewMetrics($filters);

        return response()->json([
            'success' => true,
            'data' => $overview,
        ]);
    }

    /**
     * Get program effectiveness metrics
     */
    public function programEffectiveness(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'graduation_year' => 'nullable|string',
            'program' => 'nullable|string',
            'department' => 'nullable|string',
        ]);

        $effectiveness = $this->analyticsService->getProgramEffectiveness($filters);

        return response()->json([
            'success' => true,
            'data' => $effectiveness,
        ]);
    }

    /**
     * Generate program effectiveness data
     */
    public function generateProgramEffectiveness(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program' => 'required|string',
            'graduation_year' => 'required|string',
        ]);

        $data = $this->analyticsService->generateProgramEffectiveness(
            $validated['program'],
            $validated['graduation_year']
        );

        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'No data available for the specified program and graduation year.',
            ], 404);
        }

        // Store the generated data
        ProgramEffectiveness::updateOrCreate(
            [
                'program_name' => $data['program_name'],
                'graduation_year' => $data['graduation_year'],
            ],
            $data
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get salary analysis
     */
    public function salaryAnalysis(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'graduation_year' => 'nullable|string',
            'program' => 'nullable|string',
            'industry' => 'nullable|string',
            'years_since_graduation' => 'nullable|integer',
            'date_range' => 'nullable|array',
            'date_range.*' => 'date',
        ]);

        $analysis = $this->analyticsService->getSalaryAnalysis($filters);

        return response()->json([
            'success' => true,
            'data' => $analysis,
        ]);
    }

    /**
     * Get industry placement analysis
     */
    public function industryPlacement(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'graduation_year' => 'nullable|string',
            'program' => 'nullable|string',
            'industry' => 'nullable|string',
        ]);

        $placement = $this->analyticsService->getIndustryPlacement($filters);

        return response()->json([
            'success' => true,
            'data' => $placement,
        ]);
    }

    /**
     * Generate industry placement data
     */
    public function generateIndustryPlacement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'industry' => 'required|string',
            'graduation_year' => 'required|string',
            'program' => 'required|string',
        ]);

        $data = $this->analyticsService->generateIndustryPlacement(
            $validated['industry'],
            $validated['graduation_year'],
            $validated['program']
        );

        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'No data available for the specified criteria.',
            ], 404);
        }

        // Store the generated data
        IndustryPlacement::updateOrCreate(
            [
                'industry' => $data['industry'],
                'graduation_year' => $data['graduation_year'],
                'program' => $data['program'],
            ],
            $data
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get demographic outcomes
     */
    public function demographicOutcomes(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'demographic_type' => 'nullable|string',
            'demographic_value' => 'nullable|string',
            'graduation_year' => 'nullable|string',
            'program' => 'nullable|string',
        ]);

        $outcomes = $this->analyticsService->getDemographicOutcomes($filters);

        return response()->json([
            'success' => true,
            'data' => $outcomes,
        ]);
    }

    /**
     * Get career path analysis
     */
    public function careerPathAnalysis(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'path_type' => 'nullable|string',
            'graduation_year' => 'nullable|string',
            'program' => 'nullable|string',
        ]);

        $analysis = $this->analyticsService->getCareerPathAnalysis($filters);

        return response()->json([
            'success' => true,
            'data' => $analysis,
        ]);
    }

    /**
     * Get trend analysis
     */
    public function trendAnalysis(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'trend_type' => 'nullable|string',
            'category' => 'nullable|string',
            'category_value' => 'nullable|string',
            'period_months' => 'nullable|integer|min:1|max:60',
        ]);

        $trends = $this->analyticsService->getTrendAnalysis($filters);

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /**
     * Generate career outcome snapshot
     */
    public function generateSnapshot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_type' => 'required|in:monthly,quarterly,yearly',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'graduation_year' => 'nullable|string',
            'program' => 'nullable|string',
            'department' => 'nullable|string',
            'demographic_group' => 'nullable|string',
        ]);

        $periodStart = Carbon::parse($validated['period_start']);
        $periodEnd = Carbon::parse($validated['period_end']);

        $filters = array_filter([
            'graduation_year' => $validated['graduation_year'] ?? null,
            'program' => $validated['program'] ?? null,
            'department' => $validated['department'] ?? null,
            'demographic_group' => $validated['demographic_group'] ?? null,
        ]);

        $snapshotData = $this->analyticsService->generateSnapshot(
            $validated['period_type'],
            $periodStart,
            $periodEnd,
            $filters
        );

        if (empty($snapshotData)) {
            return response()->json([
                'success' => false,
                'message' => 'No data available for the specified period and filters.',
            ], 404);
        }

        // Store the snapshot
        $snapshot = CareerOutcomeSnapshot::create($snapshotData);

        return response()->json([
            'success' => true,
            'data' => $snapshot,
        ]);
    }

    /**
     * Get career outcome snapshots
     */
    public function snapshots(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'period_type' => 'nullable|in:monthly,quarterly,yearly',
            'graduation_year' => 'nullable|string',
            'program' => 'nullable|string',
            'department' => 'nullable|string',
            'demographic_group' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = CareerOutcomeSnapshot::query();

        if (isset($filters['period_type'])) {
            $query->byPeriod($filters['period_type']);
        }

        if (isset($filters['graduation_year'])) {
            $query->byGraduationYear($filters['graduation_year']);
        }

        if (isset($filters['program'])) {
            $query->byProgram($filters['program']);
        }

        if (isset($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        if (isset($filters['demographic_group'])) {
            $query->byDemographic($filters['demographic_group']);
        }

        $snapshots = $query->orderBy('period_start', 'desc')
            ->limit($filters['limit'] ?? 50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $snapshots,
        ]);
    }

    /**
     * Get available filter options
     */
    public function filterOptions(): JsonResponse
    {
        $options = [
            'graduation_years' => $this->getAvailableGraduationYears(),
            'programs' => $this->getAvailablePrograms(),
            'departments' => $this->getAvailableDepartments(),
            'industries' => $this->getAvailableIndustries(),
            'demographic_types' => DemographicOutcome::getDemographicTypes(),
            'career_path_types' => \App\Models\CareerPath::getPathTypes(),
            'trend_types' => CareerTrend::getTrendTypes(),
        ];

        return response()->json([
            'success' => true,
            'data' => $options,
        ]);
    }

    /**
     * Export career outcome data
     */
    public function export(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'format' => 'required|in:csv,xlsx,json',
            'data_type' => 'required|in:overview,program_effectiveness,salary_analysis,industry_placement,demographic_outcomes,career_paths,trends',
            'filters' => 'nullable|array',
        ]);

        // This would implement data export functionality
        // For now, return a placeholder response
        return response()->json([
            'success' => true,
            'message' => 'Export functionality would be implemented here',
            'download_url' => '/api/career-analytics/download/'.uniqid(),
        ]);
    }

    // Helper methods

    private function getAvailableGraduationYears(): array
    {
        return \App\Models\EducationHistory::distinct()
            ->pluck('end_year')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getAvailablePrograms(): array
    {
        return \App\Models\EducationHistory::distinct()
            ->pluck('degree')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getAvailableDepartments(): array
    {
        return ProgramEffectiveness::distinct()
            ->pluck('department')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getAvailableIndustries(): array
    {
        return \App\Models\CareerTimeline::distinct()
            ->pluck('industry')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }
}
