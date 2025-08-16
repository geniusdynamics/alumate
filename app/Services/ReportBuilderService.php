<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CustomReport;
use App\Models\Employer;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\ReportExecution;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ReportBuilderService
{
    public function executeReport(CustomReport $report, array $parameters = [])
    {
        $execution = ReportExecution::create([
            'custom_report_id' => $report->id,
            'user_id' => auth()->id(),
            'status' => 'pending',
            'parameters' => $parameters,
        ]);

        try {
            $execution->markAsStarted();

            $data = $this->generateReportData($report, $parameters);
            $filePath = $this->generateReportFile($report, $data, $parameters);

            $execution->markAsCompleted($data, $filePath);

            return $execution;
        } catch (\Exception $e) {
            $execution->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    public function generateReportData(CustomReport $report, array $parameters = [])
    {
        $filters = array_merge($report->filters, $parameters);

        return match ($report->type) {
            'employment' => $this->generateEmploymentReport($filters, $report->columns),
            'course_performance' => $this->generateCoursePerformanceReport($filters, $report->columns),
            'job_market' => $this->generateJobMarketReport($filters, $report->columns),
            'graduate_outcomes' => $this->generateGraduateOutcomesReport($filters, $report->columns),
            'employer_analytics' => $this->generateEmployerAnalyticsReport($filters, $report->columns),
            'institution_overview' => $this->generateInstitutionOverviewReport($filters, $report->columns),
            'custom_query' => $this->generateCustomQueryReport($filters, $report->columns),
            default => ['data' => [], 'summary' => []],
        };
    }

    public function generateReportFile(CustomReport $report, array $data, array $parameters = [])
    {
        $format = $parameters['format'] ?? 'csv';
        $filename = $this->generateFilename($report, $format);
        $filePath = "reports/{$filename}";

        $content = match ($format) {
            'csv' => $this->generateCsvContent($data),
            'excel' => $this->generateExcelContent($data),
            'pdf' => $this->generatePdfContent($report, $data),
            'json' => $this->generateJsonContent($data),
            default => $this->generateCsvContent($data),
        };

        Storage::put($filePath, $content);

        return $filePath;
    }

    public function getReportPreview(CustomReport $report, array $parameters = [], int $limit = 100)
    {
        $filters = array_merge($report->filters, $parameters);
        $data = $this->generateReportData($report, $filters);

        // Limit the data for preview
        if (isset($data['data']) && is_array($data['data'])) {
            $data['data'] = array_slice($data['data'], 0, $limit);
            $data['preview_info'] = [
                'showing' => min($limit, count($data['data'])),
                'total' => $data['total_records'] ?? count($data['data']),
                'is_preview' => true,
            ];
        }

        return $data;
    }

    public function validateReportFilters(CustomReport $report, array $filters)
    {
        $availableFilters = $report->getAvailableFilters($report->type);
        $errors = [];

        foreach ($filters as $key => $value) {
            if (! isset($availableFilters[$key])) {
                $errors[$key] = "Invalid filter: {$key}";

                continue;
            }

            $filterConfig = $availableFilters[$key];

            // Validate based on filter type
            switch ($filterConfig['type']) {
                case 'date_range':
                    if (! $this->isValidDateRange($value)) {
                        $errors[$key] = 'Invalid date range format';
                    }
                    break;

                case 'number':
                    if (! is_numeric($value)) {
                        $errors[$key] = 'Must be a number';
                    }
                    break;

                case 'select':
                    if (! $this->isValidSelectOption($value, $filterConfig['options'])) {
                        $errors[$key] = 'Invalid option selected';
                    }
                    break;
            }
        }

        return $errors;
    }

    public function getFilterOptions($filterType, $optionKey)
    {
        return match ($optionKey) {
            'courses' => Course::active()->orderBy('name')->pluck('name', 'id'),
            'graduation_years' => Graduate::distinct()->orderBy('graduation_year', 'desc')->pluck('graduation_year'),
            'salary_ranges' => $this->getSalaryRangeOptions(),
            'job_types' => Job::distinct()->whereNotNull('job_type')->pluck('job_type'),
            'employers' => Employer::verified()->orderBy('company_name')->pluck('company_name', 'id'),
            'departments' => Course::distinct()->whereNotNull('department')->pluck('department'),
            default => [],
        };
    }

    private function generateEmploymentReport(array $filters, array $columns)
    {
        $query = Graduate::with(['user', 'course']);

        // Apply filters
        $this->applyEmploymentFilters($query, $filters);

        $graduates = $query->get();
        $totalRecords = $graduates->count();

        $data = $graduates->map(function ($graduate) use ($columns) {
            return $this->mapGraduateToColumns($graduate, $columns);
        })->toArray();

        return [
            'data' => $data,
            'total_records' => $totalRecords,
            'summary' => $this->generateEmploymentSummary($graduates),
            'filters_applied' => $filters,
        ];
    }

    private function generateCoursePerformanceReport(array $filters, array $columns)
    {
        $query = Course::with(['graduates']);

        // Apply filters
        $this->applyCourseFilters($query, $filters);

        $courses = $query->get();
        $totalRecords = $courses->count();

        $data = $courses->map(function ($course) use ($columns) {
            return $this->mapCourseToColumns($course, $columns);
        })->toArray();

        return [
            'data' => $data,
            'total_records' => $totalRecords,
            'summary' => $this->generateCoursePerformanceSummary($courses),
            'filters_applied' => $filters,
        ];
    }

    private function generateJobMarketReport(array $filters, array $columns)
    {
        $query = Job::with(['employer', 'course', 'applications']);

        // Apply filters
        $this->applyJobFilters($query, $filters);

        $jobs = $query->get();
        $totalRecords = $jobs->count();

        $data = $jobs->map(function ($job) use ($columns) {
            return $this->mapJobToColumns($job, $columns);
        })->toArray();

        return [
            'data' => $data,
            'total_records' => $totalRecords,
            'summary' => $this->generateJobMarketSummary($jobs),
            'filters_applied' => $filters,
        ];
    }

    private function generateGraduateOutcomesReport(array $filters, array $columns)
    {
        $query = Graduate::with(['user', 'course', 'applications']);

        // Apply filters
        $this->applyEmploymentFilters($query, $filters);

        $graduates = $query->get();
        $totalRecords = $graduates->count();

        $data = $graduates->map(function ($graduate) use ($columns) {
            return $this->mapGraduateOutcomesToColumns($graduate, $columns);
        })->toArray();

        return [
            'data' => $data,
            'total_records' => $totalRecords,
            'summary' => $this->generateGraduateOutcomesSummary($graduates),
            'filters_applied' => $filters,
        ];
    }

    private function generateEmployerAnalyticsReport(array $filters, array $columns)
    {
        $query = Employer::with(['jobs', 'user']);

        // Apply filters
        $this->applyEmployerFilters($query, $filters);

        $employers = $query->get();
        $totalRecords = $employers->count();

        $data = $employers->map(function ($employer) use ($columns) {
            return $this->mapEmployerToColumns($employer, $columns);
        })->toArray();

        return [
            'data' => $data,
            'total_records' => $totalRecords,
            'summary' => $this->generateEmployerAnalyticsSummary($employers),
            'filters_applied' => $filters,
        ];
    }

    private function generateInstitutionOverviewReport(array $filters, array $columns)
    {
        // This would generate institution-wide overview data
        $data = [
            'graduates' => Graduate::count(),
            'courses' => Course::count(),
            'employment_rate' => $this->calculateOverallEmploymentRate(),
            'active_jobs' => Job::where('status', 'active')->count(),
            'verified_employers' => Employer::where('verification_status', 'verified')->count(),
        ];

        return [
            'data' => [$data],
            'total_records' => 1,
            'summary' => $data,
            'filters_applied' => $filters,
        ];
    }

    private function generateCustomQueryReport(array $filters, array $columns)
    {
        // This would allow for custom SQL queries with proper security measures
        // For now, return empty data
        return [
            'data' => [],
            'total_records' => 0,
            'summary' => [],
            'filters_applied' => $filters,
            'note' => 'Custom query reports require additional implementation',
        ];
    }

    // Filter application methods
    private function applyEmploymentFilters($query, array $filters)
    {
        if (! empty($filters['date_range'])) {
            $dateRange = $this->parseDateRange($filters['date_range']);
            $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }

        if (! empty($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        if (! empty($filters['employment_status'])) {
            $query->where('employment_status', $filters['employment_status']);
        }

        if (! empty($filters['graduation_year'])) {
            $query->where('graduation_year', $filters['graduation_year']);
        }

        if (! empty($filters['salary_range'])) {
            // Convert salary range to actual salary comparison
            switch ($filters['salary_range']) {
                case 'Under $30K':
                    $query->where('current_salary', '<', 30000);
                    break;
                case '$30K - $50K':
                    $query->whereBetween('current_salary', [30000, 49999]);
                    break;
                case '$50K - $75K':
                    $query->whereBetween('current_salary', [50000, 74999]);
                    break;
                case '$75K - $100K':
                    $query->whereBetween('current_salary', [75000, 99999]);
                    break;
                case 'Over $100K':
                    $query->where('current_salary', '>=', 100000);
                    break;
            }
        }
    }

    private function applyCourseFilters($query, array $filters)
    {
        if (! empty($filters['date_range'])) {
            $dateRange = $this->parseDateRange($filters['date_range']);
            $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }

        if (! empty($filters['course_id'])) {
            $query->where('id', $filters['course_id']);
        }

        if (! empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        if (! empty($filters['min_employment_rate'])) {
            $query->where('employment_rate', '>=', $filters['min_employment_rate']);
        }
    }

    private function applyJobFilters($query, array $filters)
    {
        if (! empty($filters['date_range'])) {
            $dateRange = $this->parseDateRange($filters['date_range']);
            $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }

        if (! empty($filters['location'])) {
            $query->where('location', 'like', '%'.$filters['location'].'%');
        }

        if (! empty($filters['job_type'])) {
            $query->where('job_type', $filters['job_type']);
        }

        if (! empty($filters['salary_min'])) {
            $query->where('salary_min', '>=', $filters['salary_min']);
        }

        if (! empty($filters['salary_max'])) {
            $query->where('salary_max', '<=', $filters['salary_max']);
        }

        if (! empty($filters['employer_id'])) {
            $query->where('employer_id', $filters['employer_id']);
        }
    }

    private function applyEmployerFilters($query, array $filters)
    {
        if (! empty($filters['verification_status'])) {
            $query->where('verification_status', $filters['verification_status']);
        }

        if (! empty($filters['industry'])) {
            $query->where('industry', $filters['industry']);
        }

        if (! empty($filters['company_size'])) {
            $query->where('company_size', $filters['company_size']);
        }
    }

    // Column mapping methods
    private function mapGraduateToColumns($graduate, array $columns)
    {
        $data = [];

        foreach ($columns as $column) {
            $data[$column] = match ($column) {
                'graduate_name' => $graduate->user->name,
                'course_name' => $graduate->course->name,
                'graduation_date' => $graduate->graduation_date,
                'employment_status' => $graduate->employment_status['status'] ?? 'unknown',
                'company_name' => $graduate->employment_status['company'] ?? null,
                'job_title' => $graduate->employment_status['job_title'] ?? null,
                'salary_range' => $graduate->employment_status['salary_range'] ?? null,
                'employment_date' => $graduate->employment_status['start_date'] ?? null,
                default => null,
            };
        }

        return $data;
    }

    private function mapCourseToColumns($course, array $columns)
    {
        $data = [];
        $employedCount = $course->graduates->where('employment_status.status', 'employed')->count();
        $totalGraduates = $course->graduates->count();

        foreach ($columns as $column) {
            $data[$column] = match ($column) {
                'course_name' => $course->name,
                'total_graduates' => $totalGraduates,
                'employed_count' => $employedCount,
                'employment_rate' => $totalGraduates > 0 ? ($employedCount / $totalGraduates) * 100 : 0,
                'average_salary' => $this->calculateAverageSalary($course->graduates),
                'top_employers' => $this->getTopEmployersForCourse($course->graduates),
                'skills_taught' => $course->skills ?? [],
                default => null,
            };
        }

        return $data;
    }

    private function mapJobToColumns($job, array $columns)
    {
        $data = [];

        foreach ($columns as $column) {
            $data[$column] = match ($column) {
                'job_title' => $job->title,
                'company_name' => $job->employer->company_name,
                'location' => $job->location,
                'salary_range' => $job->salary_min && $job->salary_max ?
                    "{$job->salary_min} - {$job->salary_max}" : null,
                'required_skills' => $job->required_skills ?? [],
                'application_count' => $job->applications->count(),
                'posted_date' => $job->created_at,
                'status' => $job->status,
                default => null,
            };
        }

        return $data;
    }

    private function mapGraduateOutcomesToColumns($graduate, array $columns)
    {
        $data = [];

        foreach ($columns as $column) {
            $data[$column] = match ($column) {
                'graduate_name' => $graduate->user->name,
                'course_name' => $graduate->course->name,
                'graduation_year' => $graduate->graduation_year,
                'current_status' => $graduate->employment_status['status'] ?? 'unknown',
                'career_progression' => $this->getCareerProgression($graduate),
                'skills_acquired' => $graduate->skills ?? [],
                'certifications' => $graduate->certifications ?? [],
                default => null,
            };
        }

        return $data;
    }

    private function mapEmployerToColumns($employer, array $columns)
    {
        $data = [];

        foreach ($columns as $column) {
            $data[$column] = match ($column) {
                'company_name' => $employer->company_name,
                'industry' => $employer->industry,
                'company_size' => $employer->company_size,
                'verification_status' => $employer->verification_status,
                'total_jobs_posted' => $employer->jobs->count(),
                'active_jobs' => $employer->jobs->where('status', 'active')->count(),
                'total_hires' => $employer->total_hires,
                'registration_date' => $employer->created_at,
                default => null,
            };
        }

        return $data;
    }

    // Content generation methods
    private function generateCsvContent(array $data)
    {
        if (empty($data['data'])) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        // Write headers
        $headers = array_keys($data['data'][0]);
        fputcsv($output, $headers);

        // Write data rows
        foreach ($data['data'] as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    private function generateJsonContent(array $data)
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    private function generateExcelContent(array $data)
    {
        // This would use a library like PhpSpreadsheet
        // For now, return CSV content
        return $this->generateCsvContent($data);
    }

    private function generatePdfContent(CustomReport $report, array $data)
    {
        // This would use a library like TCPDF or DOMPDF
        // For now, return a simple text representation
        $content = "Report: {$report->name}\n";
        $content .= 'Generated: '.now()->format('Y-m-d H:i:s')."\n\n";

        if (! empty($data['summary'])) {
            $content .= "Summary:\n";
            foreach ($data['summary'] as $key => $value) {
                $content .= "- {$key}: {$value}\n";
            }
            $content .= "\n";
        }

        $content .= 'Total Records: '.($data['total_records'] ?? 0)."\n";

        return $content;
    }

    // Helper methods
    private function generateFilename(CustomReport $report, string $format)
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $report->name);

        return "{$safeName}_{$timestamp}.{$format}";
    }

    private function isValidDateRange($value)
    {
        if (! is_array($value) || ! isset($value['start']) || ! isset($value['end'])) {
            return false;
        }

        try {
            Carbon::parse($value['start']);
            Carbon::parse($value['end']);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function isValidSelectOption($value, $options)
    {
        if (is_string($options)) {
            // Dynamic options - would need to validate against actual data
            return true;
        }

        return in_array($value, $options);
    }

    private function parseDateRange($dateRange)
    {
        return [
            'start' => Carbon::parse($dateRange['start']),
            'end' => Carbon::parse($dateRange['end']),
        ];
    }

    private function getSalaryRangeOptions()
    {
        return [
            'below_20k' => 'Below $20,000',
            '20k_30k' => '$20,000 - $30,000',
            '30k_40k' => '$30,000 - $40,000',
            '40k_50k' => '$40,000 - $50,000',
            '50k_75k' => '$50,000 - $75,000',
            '75k_100k' => '$75,000 - $100,000',
            'above_100k' => 'Above $100,000',
        ];
    }

    private function calculateOverallEmploymentRate()
    {
        $total = Graduate::count();
        if ($total === 0) {
            return 0;
        }

        $employed = Graduate::where('employment_status', 'employed')->count();

        return ($employed / $total) * 100;
    }

    private function calculateAverageSalary($graduates)
    {
        $salaries = $graduates->filter(function ($graduate) {
            return isset($graduate->employment_status['salary_range']);
        });

        if ($salaries->isEmpty()) {
            return null;
        }

        // Convert salary ranges to midpoint values
        $salaryValues = $salaries->map(function ($graduate) {
            return $this->getSalaryMidpoint($graduate->employment_status['salary_range']);
        })->filter();

        return $salaryValues->isEmpty() ? null : $salaryValues->average();
    }

    private function getSalaryMidpoint($range)
    {
        $ranges = [
            'below_20k' => 15000,
            '20k_30k' => 25000,
            '30k_40k' => 35000,
            '40k_50k' => 45000,
            '50k_75k' => 62500,
            '75k_100k' => 87500,
            'above_100k' => 125000,
        ];

        return $ranges[$range] ?? null;
    }

    private function getTopEmployersForCourse($graduates)
    {
        return $graduates->filter(function ($graduate) {
            return isset($graduate->employment_status['company']);
        })
            ->groupBy('employment_status.company')
            ->map->count()
            ->sortDesc()
            ->take(5)
            ->keys()
            ->toArray();
    }

    private function getCareerProgression($graduate)
    {
        // This would track career progression over time
        // For now, return current employment info
        return [
            'current_position' => $graduate->employment_status['job_title'] ?? null,
            'current_company' => $graduate->employment_status['company'] ?? null,
            'employment_date' => $graduate->employment_status['start_date'] ?? null,
        ];
    }

    private function generateEmploymentSummary($graduates)
    {
        $total = $graduates->count();
        $employed = $graduates->where('employment_status.status', 'employed')->count();

        return [
            'total_graduates' => $total,
            'employed_count' => $employed,
            'employment_rate' => $total > 0 ? ($employed / $total) * 100 : 0,
            'unemployment_rate' => $total > 0 ? (($total - $employed) / $total) * 100 : 0,
        ];
    }

    private function generateCoursePerformanceSummary($courses)
    {
        $totalCourses = $courses->count();
        $avgEmploymentRate = $courses->avg(function ($course) {
            $total = $course->graduates->count();
            $employed = $course->graduates->where('employment_status.status', 'employed')->count();

            return $total > 0 ? ($employed / $total) * 100 : 0;
        });

        return [
            'total_courses' => $totalCourses,
            'average_employment_rate' => $avgEmploymentRate,
            'total_graduates' => $courses->sum(fn ($course) => $course->graduates->count()),
        ];
    }

    private function generateJobMarketSummary($jobs)
    {
        return [
            'total_jobs' => $jobs->count(),
            'active_jobs' => $jobs->where('status', 'active')->count(),
            'filled_jobs' => $jobs->where('status', 'filled')->count(),
            'total_applications' => $jobs->sum(fn ($job) => $job->applications->count()),
        ];
    }

    private function generateGraduateOutcomesSummary($graduates)
    {
        return $this->generateEmploymentSummary($graduates);
    }

    private function generateEmployerAnalyticsSummary($employers)
    {
        return [
            'total_employers' => $employers->count(),
            'verified_employers' => $employers->where('verification_status', 'verified')->count(),
            'total_jobs_posted' => $employers->sum(fn ($employer) => $employer->jobs->count()),
            'total_hires' => $employers->sum('total_hires'),
        ];
    }
}
