<?php

namespace App\Console\Commands;

use App\Models\KpiDefinition;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateKpis extends Command
{
    protected $signature = 'analytics:calculate-kpis 
                            {--date= : Specific date to calculate KPIs for (YYYY-MM-DD)}
                            {--kpi= : Specific KPI key to calculate}
                            {--force : Force recalculation even if value exists}';

    protected $description = 'Calculate KPI values for tracking performance metrics';

    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    public function handle()
    {
        $date = $this->option('date') ?? now()->toDateString();
        $kpiKey = $this->option('kpi');
        $force = $this->option('force');

        $this->info("Calculating KPIs for date: {$date}");

        try {
            if ($kpiKey) {
                $this->calculateSpecificKpi($kpiKey, $date, $force);
            } else {
                $this->calculateAllKpis($date, $force);
            }

            $this->info('KPI calculations completed successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to calculate KPIs: '.$e->getMessage());

            return 1;
        }
    }

    private function calculateSpecificKpi($kpiKey, $date, $force)
    {
        $kpi = KpiDefinition::where('key', $kpiKey)->active()->first();

        if (! $kpi) {
            $this->error("KPI not found: {$kpiKey}");

            return;
        }

        // Check if value already exists
        if (! $force && $kpi->getValueForDate($date)) {
            $this->warn("KPI value already exists for {$kpiKey} on {$date}. Use --force to recalculate.");

            return;
        }

        $this->info("Calculating KPI: {$kpi->name}");

        $value = $kpi->calculateValue($date);

        \App\Models\KpiValue::updateOrCreate(
            ['kpi_definition_id' => $kpi->id, 'measurement_date' => $date],
            [
                'value' => $value,
                'breakdown' => $this->getKpiBreakdown($kpi, $date),
                'metadata' => ['calculated_at' => now()],
            ]
        );

        $this->info("✓ {$kpi->name}: {$value}");
    }

    private function calculateAllKpis($date, $force)
    {
        $kpis = KpiDefinition::active()->get();

        if ($kpis->isEmpty()) {
            $this->warn('No active KPIs found.');

            return;
        }

        $bar = $this->output->createProgressBar($kpis->count());
        $bar->start();

        $results = [];
        $errors = [];

        foreach ($kpis as $kpi) {
            try {
                // Check if value already exists
                if (! $force && $kpi->getValueForDate($date)) {
                    $bar->advance();

                    continue;
                }

                $value = $kpi->calculateValue($date);

                \App\Models\KpiValue::updateOrCreate(
                    ['kpi_definition_id' => $kpi->id, 'measurement_date' => $date],
                    [
                        'value' => $value,
                        'breakdown' => $this->getKpiBreakdown($kpi, $date),
                        'metadata' => ['calculated_at' => now()],
                    ]
                );

                $results[$kpi->key] = $value;
            } catch (\Exception $e) {
                $errors[$kpi->key] = $e->getMessage();
                \Log::error("Failed to calculate KPI {$kpi->key}: ".$e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Display results
        if (! empty($results)) {
            $this->info('Successfully calculated KPIs:');
            foreach ($results as $key => $value) {
                $this->line("  ✓ {$key}: {$value}");
            }
        }

        if (! empty($errors)) {
            $this->error('Failed to calculate KPIs:');
            foreach ($errors as $key => $error) {
                $this->line("  ✗ {$key}: {$error}");
            }
        }
    }

    private function getKpiBreakdown($kpi, $date)
    {
        // Generate detailed breakdown for KPI value
        return match ($kpi->key) {
            'employment_rate' => $this->getEmploymentRateBreakdown($date),
            'job_placement_rate' => $this->getJobPlacementRateBreakdown($date),
            'avg_time_to_employment' => $this->getTimeToEmploymentBreakdown($date),
            default => [],
        };
    }

    private function getEmploymentRateBreakdown($date)
    {
        return \App\Models\Graduate::whereDate('created_at', '<=', $date)
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
        return \App\Models\JobApplication::whereDate('created_at', '<=', $date)
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

    private function getTimeToEmploymentBreakdown($date)
    {
        $graduates = \App\Models\Graduate::whereDate('created_at', '<=', $date)
            ->where('employment_status', 'employed')
            ->whereNotNull('employment_start_date')
            ->with('course')
            ->get();

        return $graduates->groupBy('course.name')
            ->map(function ($courseGraduates) {
                $times = $courseGraduates->map(function ($graduate) {
                    // Approximate graduation date as end of graduation year
                    $graduationDate = Carbon::createFromDate($graduate->graduation_year, 12, 31);
                    $employmentDate = Carbon::parse($graduate->employment_start_date);

                    return $graduationDate->diffInDays($employmentDate);
                });

                return [
                    'count' => $times->count(),
                    'average_days' => $times->average(),
                    'min_days' => $times->min(),
                    'max_days' => $times->max(),
                ];
            });
    }
}
