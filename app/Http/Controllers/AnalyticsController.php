<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsSnapshot;
use App\Models\CustomReport;
use App\Models\ReportExecution;
use App\Models\KpiDefinition;
use App\Models\KpiValue;
use App\Models\PredictionModel;
use App\Models\Prediction;
use App\Models\Course;
use App\Models\Graduate;
use App\Services\AnalyticsService;
use App\Services\ReportBuilderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    protected $analyticsService;
    protected $reportBuilderService;

    public function __construct(AnalyticsService $analyticsService, ReportBuilderService $reportBuilderService)
    {
        $this->analyticsService = $analyticsService;
        $this->reportBuilderService = $reportBuilderService;
    }

    public function dashboard(Request $request)
    {
        $timeframe = $request->get('timeframe', '30_days');
        
        $data = $this->analyticsService->getAnalyticsDashboard($timeframe);
        
        return Inertia::render('Analytics/Dashboard', [
            'analytics' => $data,
            'timeframe' => $timeframe,
            'availableTimeframes' => [
                '7_days' => '7 Days',
                '30_days' => '30 Days',
                '90_days' => '90 Days',
                '1_year' => '1 Year',
            ],
        ]);
    }

    public function employment(Request $request)
    {
        $filters = $request->only(['course_id', 'graduation_year', 'employment_status']);
        
        $data = $this->analyticsService->getEmploymentAnalytics($filters);
        
        return Inertia::render('Analytics/Employment', [
            'analytics' => $data,
            'filters' => $filters,
            'courses' => Course::orderBy('name')->get(['id', 'name']),
            'graduationYears' => Graduate::distinct()->orderBy('graduation_year', 'desc')->pluck('graduation_year'),
        ]);
    }

    public function courses(Request $request)
    {
        $courseId = $request->get('course_id');
        
        $data = $this->analyticsService->getCourseAnalytics($courseId);
        
        return Inertia::render('Analytics/Courses', [
            'analytics' => $data,
            'selectedCourse' => $courseId,
            'courses' => Course::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function jobMarket(Request $request)
    {
        $filters = $request->only(['location', 'job_type', 'salary_range']);
        
        $data = $this->analyticsService->getJobMarketAnalytics($filters);
        
        return Inertia::render('Analytics/JobMarket', [
            'analytics' => $data,
            'filters' => $filters,
        ]);
    }

    public function kpis(Request $request)
    {
        $category = $request->get('category');
        
        $kpis = KpiDefinition::active()
            ->when($category, fn($query) => $query->byCategory($category))
            ->with(['latestValue'])
            ->get();
        
        return Inertia::render('Analytics/Kpis', [
            'kpis' => $kpis->map(function ($kpi) {
                return [
                    'id' => $kpi->id,
                    'name' => $kpi->name,
                    'key' => $kpi->key,
                    'description' => $kpi->description,
                    'category' => $kpi->category,
                    'current_value' => $kpi->getLatestValue(),
                    'formatted_value' => $kpi->latestValue?->getFormattedValue(),
                    'target_value' => $kpi->target_value,
                    'status' => $kpi->getStatus(),
                    'status_color' => $kpi->getStatusColor(),
                    'trend_data' => $kpi->getTrendData(30),
                ];
            }),
            'categories' => KpiDefinition::distinct()->pluck('category'),
            'selectedCategory' => $category,
        ]);
    }

    public function predictions(Request $request)
    {
        $type = $request->get('type');
        
        $models = PredictionModel::active()
            ->when($type, fn($query) => $query->byType($type))
            ->with(['predictions' => function ($query) {
                $query->recent(30)->orderBy('prediction_score', 'desc');
            }])
            ->get();
        
        return Inertia::render('Analytics/Predictions', [
            'models' => $models->map(function ($model) {
                return [
                    'id' => $model->id,
                    'name' => $model->name,
                    'type' => $model->type,
                    'description' => $model->description,
                    'accuracy' => $model->getFormattedAccuracy(),
                    'last_trained' => $model->last_trained_at?->format('Y-m-d H:i:s'),
                    'predictions' => $model->predictions->map(function ($prediction) {
                        return [
                            'id' => $prediction->id,
                            'score' => $prediction->getFormattedScore(),
                            'confidence' => $prediction->getConfidenceLevel(),
                            'subject_type' => $prediction->subject_type,
                            'subject_id' => $prediction->subject_id,
                            'prediction_date' => $prediction->prediction_date,
                            'target_date' => $prediction->target_date,
                        ];
                    }),
                ];
            }),
            'types' => PredictionModel::distinct()->pluck('type'),
            'selectedType' => $type,
        ]);
    }

    public function reports(Request $request)
    {
        $reports = CustomReport::where('user_id', Auth::id())
            ->orWhere('is_public', true)
            ->with(['latestExecution'])
            ->orderBy('updated_at', 'desc')
            ->get();
        
        return Inertia::render('Analytics/Reports', [
            'reports' => $reports->map(function ($report) {
                return [
                    'id' => $report->id,
                    'name' => $report->name,
                    'description' => $report->description,
                    'type' => $report->type,
                    'is_scheduled' => $report->is_scheduled,
                    'schedule_frequency' => $report->schedule_frequency,
                    'is_public' => $report->is_public,
                    'last_execution' => $report->latestExecution ? [
                        'status' => $report->latestExecution->status,
                        'completed_at' => $report->latestExecution->completed_at?->format('Y-m-d H:i:s'),
                    ] : null,
                    'updated_at' => $report->updated_at->format('Y-m-d H:i:s'),
                ];
            }),
            'reportTypes' => (new CustomReport())->getAvailableTypes(),
        ]);
    }

    public function createReport(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string',
            'filters' => 'required|array',
            'columns' => 'required|array',
            'chart_config' => 'nullable|array',
            'is_scheduled' => 'boolean',
            'schedule_frequency' => 'nullable|string',
            'schedule_config' => 'nullable|array',
            'is_public' => 'boolean',
        ]);

        $report = CustomReport::create([
            'user_id' => Auth::id(),
            ...$validated,
        ]);

        return redirect()->route('analytics.reports')
            ->with('success', 'Report created successfully.');
    }

    public function executeReport(Request $request, CustomReport $report)
    {
        if (!$report->canBeExecutedBy(Auth::user())) {
            abort(403, 'Unauthorized to execute this report.');
        }

        $parameters = $request->only(['format', 'filters']);
        
        try {
            $execution = $this->reportBuilderService->executeReport($report, $parameters);
            
            return response()->json([
                'success' => true,
                'execution_id' => $execution->id,
                'message' => 'Report execution started.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to execute report: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reportPreview(Request $request, CustomReport $report)
    {
        if (!$report->canBeExecutedBy(Auth::user())) {
            abort(403, 'Unauthorized to preview this report.');
        }

        $parameters = $request->only(['filters']);
        
        try {
            $preview = $this->reportBuilderService->getReportPreview($report, $parameters);
            
            return response()->json([
                'success' => true,
                'preview' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate preview: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function exportData(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:employment,courses,job_market,kpis',
            'format' => 'required|string|in:csv,excel,json,pdf',
            'filters' => 'nullable|array',
        ]);

        try {
            $data = $this->analyticsService->exportAnalyticsData(
                $validated['type'],
                $validated['filters'] ?? [],
                $validated['format']
            );
            
            $filename = "analytics_{$validated['type']}_" . now()->format('Y-m-d_H-i-s') . ".{$validated['format']}";
            
            return response()->streamDownload(
                function () use ($data) {
                    echo $data;
                },
                $filename,
                ['Content-Type' => $this->getContentType($validated['format'])]
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generateSnapshots(Request $request)
    {
        $type = $request->get('type', 'daily');
        $date = $request->get('date');
        
        try {
            $snapshot = match($type) {
                'daily' => $this->analyticsService->generateDailySnapshot($date),
                'weekly' => $this->analyticsService->generateWeeklySnapshot($date),
                'monthly' => $this->analyticsService->generateMonthlySnapshot($date),
                default => throw new \InvalidArgumentException('Invalid snapshot type'),
            };
            
            return response()->json([
                'success' => true,
                'snapshot' => $snapshot,
                'message' => 'Snapshot generated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate snapshot: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function calculateKpis(Request $request)
    {
        $date = $request->get('date');
        
        try {
            $results = $this->analyticsService->calculateKpiValues($date);
            
            return response()->json([
                'success' => true,
                'kpis' => $results,
                'message' => 'KPIs calculated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate KPIs: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generatePredictions(Request $request)
    {
        try {
            $results = $this->analyticsService->generatePredictiveAnalytics();
            
            return response()->json([
                'success' => true,
                'predictions' => $results,
                'message' => 'Predictions generated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate predictions: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getContentType($format)
    {
        return match($format) {
            'csv' => 'text/csv',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'json' => 'application/json',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }
}