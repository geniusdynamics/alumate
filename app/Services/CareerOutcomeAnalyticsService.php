<?php

namespace App\Services;

use App\Models\CareerPath;
use App\Models\CareerTrend;
use App\Models\DemographicOutcome;
use App\Models\IndustryPlacement;
use App\Models\ProgramEffectiveness;
use App\Models\SalaryProgression;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CareerOutcomeAnalyticsService
{
    /**
     * Generate comprehensive career outcome analytics
     */
    public function generateOutcomeAnalytics(array $filters = []): array
    {
        return [
            'overview' => $this->getOverviewMetrics($filters),
            'program_effectiveness' => $this->getProgramEffectiveness($filters),
            'salary_analysis' => $this->getSalaryAnalysis($filters),
            'industry_placement' => $this->getIndustryPlacement($filters),
            'demographic_outcomes' => $this->getDemographicOutcomes($filters),
            'career_paths' => $this->getCareerPathAnalysis($filters),
            'trends' => $this->getTrendAnalysis($filters),
        ];
    }

    /**
     * Get overview metrics
     */
    public function getOverviewMetrics(array $filters = []): array
    {
        $query = User::query()
            ->whereHas('educationHistories')
            ->with(['careerTimelines', 'educationHistories']);

        $this->applyFilters($query, $filters);

        $users = $query->get();
        $totalAlumni = $users->count();

        if ($totalAlumni === 0) {
            return $this->getEmptyOverviewMetrics();
        }

        // Calculate employment metrics
        $employed = $users->filter(function ($user) {
            return $user->careerTimelines()->where('is_current', true)->exists();
        });

        $employmentRate = ($employed->count() / $totalAlumni) * 100;

        // Calculate average salary
        $currentSalaries = SalaryProgression::whereIn('user_id', $users->pluck('id'))
            ->whereYear('effective_date', '>=', now()->year - 1)
            ->get();

        $avgSalary = $currentSalaries->avg('annualized_salary') ?? 0;

        // Calculate tracking rate
        $trackedUsers = $users->filter(function ($user) {
            return $user->careerTimelines()->exists() ||
                   SalaryProgression::where('user_id', $user->id)->exists();
        });

        $trackingRate = ($trackedUsers->count() / $totalAlumni) * 100;

        return [
            'total_alumni' => $totalAlumni,
            'employment_rate' => round($employmentRate, 2),
            'average_salary' => round($avgSalary, 2),
            'tracking_rate' => round($trackingRate, 2),
            'top_industries' => $this->getTopIndustries($users),
            'top_employers' => $this->getTopEmployers($users),
            'geographic_distribution' => $this->getGeographicDistribution($users),
        ];
    }

    /**
     * Get program effectiveness metrics
     */
    public function getProgramEffectiveness(array $filters = []): Collection
    {
        $query = ProgramEffectiveness::query();

        if (isset($filters['graduation_year'])) {
            $query->where('graduation_year', $filters['graduation_year']);
        }

        if (isset($filters['program'])) {
            $query->where('program_name', $filters['program']);
        }

        if (isset($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        return $query->orderBy('overall_effectiveness_score', 'desc')->get();
    }

    /**
     * Generate program effectiveness data
     */
    public function generateProgramEffectiveness(string $program, string $graduationYear): array
    {
        $graduates = User::whereHas('educationHistories', function ($query) use ($program, $graduationYear) {
            $query->where('degree', 'like', "%{$program}%")
                ->where('end_year', $graduationYear);
        })->with(['careerTimelines', 'salaryProgressions'])->get();

        if ($graduates->isEmpty()) {
            return [];
        }

        $totalGraduates = $graduates->count();

        // Calculate employment rates at different intervals
        $employmentRates = $this->calculateEmploymentRates($graduates, $graduationYear);

        // Calculate salary progression
        $salaryData = $this->calculateSalaryProgression($graduates, $graduationYear);

        // Get top employers
        $topEmployers = $this->getTopEmployersForGraduates($graduates);

        // Identify skills gaps (placeholder - would need job market data)
        $skillsGaps = $this->identifySkillsGaps($graduates);

        return [
            'program_name' => $program,
            'graduation_year' => $graduationYear,
            'total_graduates' => $totalGraduates,
            'employment_rate_6_months' => $employmentRates['6_months'],
            'employment_rate_1_year' => $employmentRates['1_year'],
            'employment_rate_2_years' => $employmentRates['2_years'],
            'avg_starting_salary' => $salaryData['starting'],
            'avg_salary_1_year' => $salaryData['1_year'],
            'avg_salary_2_years' => $salaryData['2_years'],
            'top_employers' => $topEmployers,
            'skills_gaps' => $skillsGaps,
        ];
    }

    /**
     * Get salary analysis
     */
    public function getSalaryAnalysis(array $filters = []): array
    {
        $query = SalaryProgression::query()->with('user');

        $this->applySalaryFilters($query, $filters);

        $salaryData = $query->get();

        if ($salaryData->isEmpty()) {
            return $this->getEmptySalaryAnalysis();
        }

        return [
            'overall_statistics' => $this->calculateSalaryStatistics($salaryData),
            'progression_by_years' => $this->getSalaryProgressionByYears($salaryData),
            'industry_comparison' => $this->getSalaryByIndustry($salaryData),
            'percentile_distribution' => $this->getSalaryPercentiles($salaryData),
            'growth_trends' => $this->getSalaryGrowthTrends($salaryData),
        ];
    }

    /**
     * Get industry placement analysis
     */
    public function getIndustryPlacement(array $filters = []): Collection
    {
        $query = IndustryPlacement::query();

        if (isset($filters['graduation_year'])) {
            $query->where('graduation_year', $filters['graduation_year']);
        }

        if (isset($filters['program'])) {
            $query->where('program', $filters['program']);
        }

        return $query->orderBy('placement_count', 'desc')->get();
    }

    /**
     * Generate industry placement data
     */
    public function generateIndustryPlacement(string $industry, string $graduationYear, string $program): array
    {
        $graduates = User::whereHas('educationHistories', function ($query) use ($program, $graduationYear) {
            $query->where('degree', 'like', "%{$program}%")
                ->where('end_year', $graduationYear);
        })->whereHas('careerTimelines', function ($query) use ($industry) {
            $query->where('industry', $industry);
        })->with(['careerTimelines', 'salaryProgressions'])->get();

        if ($graduates->isEmpty()) {
            return [];
        }

        $placementCount = $graduates->count();
        $currentPositions = $graduates->flatMap->careerTimelines->where('is_current', true);

        // Calculate salary statistics
        $startingSalaries = $this->getStartingSalariesForIndustry($graduates, $industry);
        $currentSalaries = $this->getCurrentSalariesForIndustry($graduates, $industry);

        // Calculate retention rate (simplified)
        $retentionRate = $this->calculateIndustryRetentionRate($graduates, $industry);

        // Get top companies
        $topCompanies = $currentPositions->groupBy('company')
            ->map->count()
            ->sortDesc()
            ->take(10)
            ->toArray();

        // Identify in-demand skills
        $skillsInDemand = $this->getInDemandSkills($graduates);

        return [
            'industry' => $industry,
            'graduation_year' => $graduationYear,
            'program' => $program,
            'placement_count' => $placementCount,
            'avg_starting_salary' => $startingSalaries->avg(),
            'avg_current_salary' => $currentSalaries->avg(),
            'retention_rate' => $retentionRate,
            'top_companies' => $topCompanies,
            'skills_in_demand' => $skillsInDemand,
        ];
    }

    /**
     * Get demographic outcomes analysis
     */
    public function getDemographicOutcomes(array $filters = []): Collection
    {
        $query = DemographicOutcome::query();

        if (isset($filters['demographic_type'])) {
            $query->where('demographic_type', $filters['demographic_type']);
        }

        if (isset($filters['graduation_year'])) {
            $query->where('graduation_year', $filters['graduation_year']);
        }

        return $query->orderBy('employment_rate', 'desc')->get();
    }

    /**
     * Get career path analysis
     */
    public function getCareerPathAnalysis(array $filters = []): array
    {
        $query = CareerPath::query()->with('user');

        $this->applyCareerPathFilters($query, $filters);

        $careerPaths = $query->get();

        if ($careerPaths->isEmpty()) {
            return $this->getEmptyCareerPathAnalysis();
        }

        return [
            'path_distribution' => $this->getPathTypeDistribution($careerPaths),
            'success_metrics' => $this->getCareerPathSuccessMetrics($careerPaths),
            'progression_patterns' => $this->getProgressionPatterns($careerPaths),
            'leadership_development' => $this->getLeadershipDevelopmentMetrics($careerPaths),
        ];
    }

    /**
     * Get trend analysis
     */
    public function getTrendAnalysis(array $filters = []): Collection
    {
        $query = CareerTrend::query();

        if (isset($filters['trend_type'])) {
            $query->where('trend_type', $filters['trend_type']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->orderBy('period_start', 'desc')->get();
    }

    /**
     * Generate career outcome snapshot
     */
    public function generateSnapshot(string $periodType, Carbon $periodStart, Carbon $periodEnd, array $filters = []): array
    {
        $graduates = $this->getGraduatesForPeriod($periodStart, $periodEnd, $filters);

        if ($graduates->isEmpty()) {
            return [];
        }

        $totalGraduates = $graduates->count();
        $trackedGraduates = $graduates->filter(function ($user) {
            return $user->careerTimelines()->exists();
        })->count();

        // Calculate metrics
        $employmentRate = $this->calculateEmploymentRateForPeriod($graduates, $periodEnd);
        $avgSalary = $this->calculateAverageSalaryForPeriod($graduates, $periodEnd);
        $jobSatisfaction = $this->calculateJobSatisfactionForPeriod($graduates);

        $metrics = [
            'employment_rate' => $employmentRate,
            'average_salary' => $avgSalary,
            'job_satisfaction' => $jobSatisfaction,
            'tracking_rate' => ($trackedGraduates / $totalGraduates) * 100,
        ];

        return [
            'period_type' => $periodType,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'graduation_year' => $filters['graduation_year'] ?? null,
            'program' => $filters['program'] ?? null,
            'department' => $filters['department'] ?? null,
            'demographic_group' => $filters['demographic_group'] ?? null,
            'metrics' => $metrics,
            'total_graduates' => $totalGraduates,
            'tracked_graduates' => $trackedGraduates,
        ];
    }

    // Helper methods

    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['graduation_year'])) {
            $query->whereHas('educationHistories', function ($q) use ($filters) {
                $q->where('end_year', $filters['graduation_year']);
            });
        }

        if (isset($filters['program'])) {
            $query->whereHas('educationHistories', function ($q) use ($filters) {
                $q->where('degree', 'like', "%{$filters['program']}%");
            });
        }

        if (isset($filters['industry'])) {
            $query->whereHas('careerTimelines', function ($q) use ($filters) {
                $q->where('industry', $filters['industry']);
            });
        }
    }

    private function applySalaryFilters($query, array $filters): void
    {
        if (isset($filters['industry'])) {
            $query->where('industry', $filters['industry']);
        }

        if (isset($filters['years_since_graduation'])) {
            $query->where('years_since_graduation', $filters['years_since_graduation']);
        }

        if (isset($filters['date_range'])) {
            $query->whereBetween('effective_date', $filters['date_range']);
        }
    }

    private function applyCareerPathFilters($query, array $filters): void
    {
        if (isset($filters['path_type'])) {
            $query->where('path_type', $filters['path_type']);
        }

        if (isset($filters['graduation_year'])) {
            $query->whereHas('user.educationHistories', function ($q) use ($filters) {
                $q->where('end_year', $filters['graduation_year']);
            });
        }
    }

    private function getEmptyOverviewMetrics(): array
    {
        return [
            'total_alumni' => 0,
            'employment_rate' => 0,
            'average_salary' => 0,
            'tracking_rate' => 0,
            'top_industries' => [],
            'top_employers' => [],
            'geographic_distribution' => [],
        ];
    }

    private function getEmptySalaryAnalysis(): array
    {
        return [
            'overall_statistics' => [],
            'progression_by_years' => [],
            'industry_comparison' => [],
            'percentile_distribution' => [],
            'growth_trends' => [],
        ];
    }

    private function getEmptyCareerPathAnalysis(): array
    {
        return [
            'path_distribution' => [],
            'success_metrics' => [],
            'progression_patterns' => [],
            'leadership_development' => [],
        ];
    }

    private function getTopIndustries(Collection $users): array
    {
        return $users->flatMap->careerTimelines
            ->where('is_current', true)
            ->groupBy('industry')
            ->map->count()
            ->sortDesc()
            ->take(10)
            ->toArray();
    }

    private function getTopEmployers(Collection $users): array
    {
        return $users->flatMap->careerTimelines
            ->where('is_current', true)
            ->groupBy('company')
            ->map->count()
            ->sortDesc()
            ->take(10)
            ->toArray();
    }

    private function getGeographicDistribution(Collection $users): array
    {
        return $users->flatMap->careerTimelines
            ->where('is_current', true)
            ->groupBy('location')
            ->map->count()
            ->sortDesc()
            ->take(10)
            ->toArray();
    }

    private function calculateEmploymentRates(Collection $graduates, string $graduationYear): array
    {
        $graduationDate = Carbon::createFromDate($graduationYear, 6, 1); // Assume June graduation

        return [
            '6_months' => $this->getEmploymentRateAtDate($graduates, $graduationDate->copy()->addMonths(6)),
            '1_year' => $this->getEmploymentRateAtDate($graduates, $graduationDate->copy()->addYear()),
            '2_years' => $this->getEmploymentRateAtDate($graduates, $graduationDate->copy()->addYears(2)),
        ];
    }

    private function getEmploymentRateAtDate(Collection $graduates, Carbon $date): float
    {
        $employed = $graduates->filter(function ($graduate) use ($date) {
            return $graduate->careerTimelines()
                ->where('start_date', '<=', $date)
                ->where(function ($query) use ($date) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', $date);
                })
                ->exists();
        });

        return $graduates->count() > 0 ? ($employed->count() / $graduates->count()) * 100 : 0;
    }

    private function calculateSalaryProgression(Collection $graduates, string $graduationYear): array
    {
        $graduationDate = Carbon::createFromDate($graduationYear, 6, 1);

        return [
            'starting' => $this->getAverageSalaryAtDate($graduates, $graduationDate->copy()->addMonths(6)),
            '1_year' => $this->getAverageSalaryAtDate($graduates, $graduationDate->copy()->addYear()),
            '2_years' => $this->getAverageSalaryAtDate($graduates, $graduationDate->copy()->addYears(2)),
        ];
    }

    private function getAverageSalaryAtDate(Collection $graduates, Carbon $date): float
    {
        $salaries = SalaryProgression::whereIn('user_id', $graduates->pluck('id'))
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->get()
            ->groupBy('user_id')
            ->map->first();

        return $salaries->avg('annualized_salary') ?? 0;
    }

    // Additional helper methods would be implemented here...
    // This is a comprehensive foundation for the career outcome analytics system
}
