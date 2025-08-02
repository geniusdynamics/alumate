<?php

namespace App\Services;

use App\Models\AnalyticsSnapshot;
use App\Models\KpiDefinition;
use App\Models\KpiValue;
use App\Models\PredictionModel;
use App\Models\Graduate;
use App\Models\Course;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Employer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    public function generateDailySnapshot($date = null)
    {
        $date = $date ?? now()->toDateString();
        
        $data = [
            'overview' => $this->getOverviewMetrics($date),
            'employment' => $this->getEmploymentMetrics($date),
            'courses' => $this->getCourseMetrics($date),
            'jobs' => $this->getJobMetrics($date),
            'applications' => $this->getApplicationMetrics($date),
            'employers' => $this->getEmployerMetrics($date),
        ];

        return AnalyticsSnapshot::updateOrCreate(
            ['type' => 'daily', 'snapshot_date' => $date],
            ['data' => $data, 'metadata' => ['generated_at' => now()]]
        );
    }

    public function generateWeeklySnapshot($date = null)
    {
        $date = $date ?? now()->startOfWeek()->toDateString();
        $endDate = Carbon::parse($date)->endOfWeek();
        
        $data = [
            'period' => ['start' => $date, 'end' => $endDate->toDateString()],
            'trends' => $this->getWeeklyTrends($date, $endDate),
            'performance' => $this->getWeeklyPerformance($date, $endDate),
            'comparisons' => $this->getWeeklyComparisons($date, $endDate),
        ];

        return AnalyticsSnapshot::updateOrCreate(
            ['type' => 'weekly', 'snapshot_date' => $date],
            ['data' => $data, 'metadata' => ['generated_at' => now()]]
        );
    }

    public function generateMonthlySnapshot($date = null)
    {
        $date = $date ?? now()->startOfMonth()->toDateString();
        $endDate = Carbon::parse($date)->endOfMonth();
        
        $data = [
            'period' => ['start' => $date, 'end' => $endDate->toDateString()],
            'summary' => $this->getMonthlySummary($date, $endDate),
            'achievements' => $this->getMonthlyAchievements($date, $endDate),
            'forecasts' => $this->getMonthlyForecasts($date, $endDate),
        ];

        return AnalyticsSnapshot::updateOrCreate(
            ['type' => 'monthly', 'snapshot_date' => $date],
            ['data' => $data, 'metadata' => ['generated_at' => now()]]
        );
    }

    public function calculateKpiValues($date = null)
    {
        $date = $date ?? now()->toDateString();
        
        $kpis = KpiDefinition::active()->get();
        $results = [];
        
        foreach ($kpis as $kpi) {
            try {
                $value = $kpi->calculateValue($date);
                
                KpiValue::updateOrCreate(
                    ['kpi_definition_id' => $kpi->id, 'measurement_date' => $date],
                    [
                        'value' => $value,
                        'breakdown' => $this->getKpiBreakdown($kpi, $date),
                        'metadata' => ['calculated_at' => now()],
                    ]
                );
                
                $results[$kpi->key] = $value;
            } catch (\Exception $e) {
                \Log::error("Failed to calculate KPI {$kpi->key}: " . $e->getMessage());
            }
        }
        
        return $results;
    }

    public function getAnalyticsDashboard($timeframe = '30_days')
    {
        $cacheKey = "analytics_dashboard_{$timeframe}";
        
        return Cache::remember($cacheKey, 300, function () use ($timeframe) {
            $period = $this->parsePeriod($timeframe);
            
            return [
                'overview' => $this->getDashboardOverview($period),
                'kpis' => $this->getDashboardKpis($period),
                'trends' => $this->getDashboardTrends($period),
                'predictions' => $this->getDashboardPredictions(),
                'alerts' => $this->getDashboardAlerts(),
                'charts' => $this->getDashboardCharts($period),
            ];
        });
    }

    public function getEmploymentAnalytics($filters = [])
    {
        $query = Graduate::with(['course', 'user']);
        
        // Apply filters
        if (!empty($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }
        
        if (!empty($filters['graduation_year'])) {
            $query->where('graduation_year', $filters['graduation_year']);
        }
        
        if (!empty($filters['employment_status'])) {
            $query->where('employment_status', $filters['employment_status']);
        }
        
        $graduates = $query->get();
        
        return [
            'summary' => $this->getEmploymentSummary($graduates),
            'by_course' => $this->getEmploymentByCourse($graduates),
            'by_year' => $this->getEmploymentByYear($graduates),
            'salary_analysis' => $this->getSalaryAnalysis($graduates),
            'time_to_employment' => $this->getTimeToEmploymentAnalysis($graduates),
            'top_employers' => $this->getTopEmployers($graduates),
            'skills_demand' => $this->getSkillsDemandAnalysis($graduates),
        ];
    }

    public function getCourseAnalytics($courseId = null)
    {
        $query = Course::with(['graduates']);
        
        if ($courseId) {
            $query->where('id', $courseId);
        }
        
        $courses = $query->get();
        
        return [
            'performance' => $this->getCoursePerformanceAnalysis($courses),
            'outcomes' => $this->getCourseOutcomeAnalysis($courses),
            'job_matching' => $this->getCourseJobMatchingAnalysis($courses),
            'skills_alignment' => $this->getCourseSkillsAlignment($courses),
            'industry_trends' => $this->getCourseIndustryTrends($courses),
        ];
    }

    public function getJobMarketAnalytics($filters = [])
    {
        $query = Job::with(['employer', 'course', 'applications']);
        
        // Apply filters
        if (!empty($filters['location'])) {
            $query->where('location', 'like', '%' . $filters['location'] . '%');
        }
        
        if (!empty($filters['job_type'])) {
            $query->where('job_type', $filters['job_type']);
        }
        
        if (!empty($filters['salary_range'])) {
            $query->whereBetween('salary_min', $filters['salary_range']);
        }
        
        $jobs = $query->get();
        
        return [
            'market_overview' => $this->getJobMarketOverview($jobs),
            'demand_analysis' => $this->getJobDemandAnalysis($jobs),
            'salary_trends' => $this->getJobSalaryTrends($jobs),
            'location_analysis' => $this->getJobLocationAnalysis($jobs),
            'skills_demand' => $this->getJobSkillsDemand($jobs),
            'employer_analysis' => $this->getJobEmployerAnalysis($jobs),
            'application_success' => $this->getApplicationSuccessAnalysis($jobs),
        ];
    }

    public function generatePredictiveAnalytics()
    {
        $models = PredictionModel::active()->get();
        $results = [];
        
        foreach ($models as $model) {
            try {
                if ($model->needsRetraining()) {
                    $model->train();
                }
                
                $predictions = $this->generateModelPredictions($model);
                $results[$model->type] = $predictions;
            } catch (\Exception $e) {
                \Log::error("Failed to generate predictions for model {$model->id}: " . $e->getMessage());
            }
        }
        
        return $results;
    }

    public function exportAnalyticsData($type, $filters = [], $format = 'csv')
    {
        $data = match($type) {
            'employment' => $this->getEmploymentAnalytics($filters),
            'courses' => $this->getCourseAnalytics(),
            'job_market' => $this->getJobMarketAnalytics($filters),
            'kpis' => $this->getKpiExportData($filters),
            default => [],
        };
        
        return $this->formatExportData($data, $format);
    }

    // Private helper methods
    private function getOverviewMetrics($date)
    {
        return [
            'total_graduates' => Graduate::whereDate('created_at', '<=', $date)->count(),
            'employed_graduates' => Graduate::where('employment_status', 'employed')
                ->whereDate('created_at', '<=', $date)->count(),
            'active_jobs' => Job::where('status', 'active')
                ->whereDate('created_at', '<=', $date)->count(),
            'total_applications' => JobApplication::whereDate('created_at', '<=', $date)->count(),
            'verified_employers' => Employer::where('verification_status', 'verified')
                ->whereDate('created_at', '<=', $date)->count(),
        ];
    }

    private function getEmploymentMetrics($date)
    {
        $graduates = Graduate::whereDate('created_at', '<=', $date)->get();
        $total = $graduates->count();
        
        if ($total === 0) {
            return ['employment_rate' => 0, 'by_status' => []];
        }
        
        $byStatus = $graduates->groupBy('employment_status.status')
            ->map->count();
        
        return [
            'employment_rate' => ($byStatus['employed'] ?? 0) / $total * 100,
            'by_status' => $byStatus,
            'avg_time_to_employment' => $this->calculateAverageTimeToEmployment($graduates),
        ];
    }

    private function getCourseMetrics($date)
    {
        return Course::withCount([
            'graduates' => function ($query) use ($date) {
                $query->whereDate('created_at', '<=', $date);
            },
            'graduates as employed_count' => function ($query) use ($date) {
                $query->whereDate('created_at', '<=', $date)
                    ->where('employment_status', 'employed');
            }
        ])
        ->get()
        ->map(function ($course) {
            return [
                'id' => $course->id,
                'name' => $course->name,
                'graduates_count' => $course->graduates_count,
                'employment_rate' => $course->graduates_count > 0 ? 
                    ($course->employed_count / $course->graduates_count) * 100 : 0,
            ];
        });
    }

    private function getJobMetrics($date)
    {
        return [
            'total_jobs' => Job::whereDate('created_at', '<=', $date)->count(),
            'active_jobs' => Job::where('status', 'active')
                ->whereDate('created_at', '<=', $date)->count(),
            'filled_jobs' => Job::where('status', 'filled')
                ->whereDate('created_at', '<=', $date)->count(),
            'avg_applications_per_job' => Job::whereDate('created_at', '<=', $date)
                ->withCount('applications')
                ->avg('applications_count') ?? 0,
        ];
    }

    private function getApplicationMetrics($date)
    {
        $applications = JobApplication::whereDate('created_at', '<=', $date);
        
        return [
            'total_applications' => $applications->count(),
            'by_status' => $applications->get()->groupBy('status')->map->count(),
            'success_rate' => $this->calculateApplicationSuccessRate($applications->get()),
        ];
    }

    private function getEmployerMetrics($date)
    {
        return [
            'total_employers' => Employer::whereDate('created_at', '<=', $date)->count(),
            'verified_employers' => Employer::where('verification_status', 'verified')
                ->whereDate('created_at', '<=', $date)->count(),
            'pending_verification' => Employer::where('verification_status', 'pending')
                ->whereDate('created_at', '<=', $date)->count(),
        ];
    }

    private function getKpiBreakdown($kpi, $date)
    {
        // Generate detailed breakdown for KPI value
        return match($kpi->key) {
            'employment_rate' => $this->getEmploymentRateBreakdown($date),
            'job_placement_rate' => $this->getJobPlacementRateBreakdown($date),
            default => [],
        };
    }

    private function getEmploymentRateBreakdown($date)
    {
        return Graduate::whereDate('created_at', '<=', $date)
            ->with('course')
            ->get()
            ->groupBy('course.name')
            ->map(function ($graduates) {
                $total = $graduates->count();
                $employed = $graduates->where('employment_status.status', 'employed')->count();
                
                return [
                    'total' => $total,
                    'employed' => $employed,
                    'rate' => $total > 0 ? ($employed / $total) * 100 : 0,
                ];
            });
    }

    private function getJobPlacementRateBreakdown($date)
    {
        return JobApplication::whereDate('created_at', '<=', $date)
            ->with('job.course')
            ->get()
            ->groupBy('job.course.name')
            ->map(function ($applications) {
                $total = $applications->count();
                $hired = $applications->where('status', 'hired')->count();
                
                return [
                    'total' => $total,
                    'hired' => $hired,
                    'rate' => $total > 0 ? ($hired / $total) * 100 : 0,
                ];
            });
    }

    private function parsePeriod($timeframe)
    {
        return match($timeframe) {
            '7_days' => ['start' => now()->subDays(7), 'end' => now()],
            '30_days' => ['start' => now()->subDays(30), 'end' => now()],
            '90_days' => ['start' => now()->subDays(90), 'end' => now()],
            '1_year' => ['start' => now()->subYear(), 'end' => now()],
            default => ['start' => now()->subDays(30), 'end' => now()],
        };
    }

    private function getDashboardOverview($period)
    {
        return [
            'graduates' => Graduate::whereBetween('created_at', [$period['start'], $period['end']])->count(),
            'jobs' => Job::whereBetween('created_at', [$period['start'], $period['end']])->count(),
            'applications' => JobApplication::whereBetween('created_at', [$period['start'], $period['end']])->count(),
            'employment_rate' => $this->calculateEmploymentRate($period),
        ];
    }

    private function getDashboardKpis($period)
    {
        return KpiDefinition::active()
            ->with(['latestValue'])
            ->get()
            ->map(function ($kpi) {
                return [
                    'key' => $kpi->key,
                    'name' => $kpi->name,
                    'value' => $kpi->getLatestValue(),
                    'formatted_value' => $kpi->latestValue?->getFormattedValue(),
                    'status' => $kpi->getStatus(),
                    'trend' => $kpi->latestValue?->getTrendDirection(),
                ];
            });
    }

    private function getDashboardTrends($period)
    {
        return [
            'employment' => $this->getEmploymentTrend($period),
            'applications' => $this->getApplicationTrend($period),
            'job_postings' => $this->getJobPostingTrend($period),
        ];
    }

    private function getDashboardPredictions()
    {
        return PredictionModel::active()
            ->with(['predictions' => function ($query) {
                $query->recent(7)->orderBy('prediction_score', 'desc')->limit(5);
            }])
            ->get()
            ->map(function ($model) {
                return [
                    'type' => $model->type,
                    'name' => $model->name,
                    'accuracy' => $model->getFormattedAccuracy(),
                    'recent_predictions' => $model->predictions->map(function ($prediction) {
                        return [
                            'score' => $prediction->getFormattedScore(),
                            'confidence' => $prediction->getConfidenceLevel(),
                            'subject' => $prediction->subject_type,
                        ];
                    }),
                ];
            });
    }

    private function getDashboardAlerts()
    {
        // Get recent alerts or generate new ones based on KPI thresholds
        $alerts = [];
        
        $kpis = KpiDefinition::active()->with('latestValue')->get();
        
        foreach ($kpis as $kpi) {
            if ($kpi->isInWarningZone()) {
                $alerts[] = [
                    'type' => 'kpi_warning',
                    'title' => "KPI Warning: {$kpi->name}",
                    'message' => "Current value is below warning threshold",
                    'severity' => 'warning',
                    'data' => [
                        'kpi' => $kpi->key,
                        'current_value' => $kpi->getLatestValue(),
                        'threshold' => $kpi->warning_threshold,
                    ],
                ];
            }
        }
        
        return $alerts;
    }

    private function getDashboardCharts($period)
    {
        return [
            'employment_trend' => $this->getEmploymentTrendChart($period),
            'course_performance' => $this->getCoursePerformanceChart(),
            'job_market_activity' => $this->getJobMarketActivityChart($period),
            'application_funnel' => $this->getApplicationFunnelChart($period),
        ];
    }

    // Additional helper methods would continue here...
    // For brevity, I'll include a few key ones

    private function calculateEmploymentRate($period)
    {
        $graduates = Graduate::whereBetween('created_at', [$period['start'], $period['end']])->get();
        $total = $graduates->count();
        
        if ($total === 0) {
            return 0;
        }
        
        $employed = $graduates->where('employment_status.status', 'employed')->count();
        
        return ($employed / $total) * 100;
    }

    private function calculateAverageTimeToEmployment($graduates)
    {
        $employedGraduates = $graduates->filter(function ($graduate) {
            return $graduate->employment_status['status'] === 'employed' &&
                   isset($graduate->employment_status['start_date']);
        });
        
        if ($employedGraduates->isEmpty()) {
            return 0;
        }
        
        $totalDays = $employedGraduates->sum(function ($graduate) {
            $graduationDate = Carbon::parse($graduate->graduation_date);
            $employmentDate = Carbon::parse($graduate->employment_status['start_date']);
            
            return $graduationDate->diffInDays($employmentDate);
        });
        
        return $totalDays / $employedGraduates->count();
    }

    private function calculateApplicationSuccessRate($applications)
    {
        $total = $applications->count();
        
        if ($total === 0) {
            return 0;
        }
        
        $successful = $applications->where('status', 'hired')->count();
        
        return ($successful / $total) * 100;
    }

    private function formatExportData($data, $format)
    {
        return match($format) {
            'csv' => $this->formatAsCsv($data),
            'excel' => $this->formatAsExcel($data),
            'json' => $this->formatAsJson($data),
            'pdf' => $this->formatAsPdf($data),
            default => $data,
        };
    }

    private function formatAsCsv($data)
    {
        // Implementation for CSV formatting
        return $data;
    }

    private function formatAsExcel($data)
    {
        // Implementation for Excel formatting
        return $data;
    }

    private function formatAsJson($data)
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    private function formatAsPdf($data)
    {
        // Implementation for PDF formatting
        return $data;
    }
}