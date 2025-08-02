<?php

namespace App\Console\Commands;

use App\Models\PredictionModel;
use App\Models\Graduate;
use App\Models\JobApplication;
use App\Models\Course;
use Illuminate\Console\Command;

class GeneratePredictions extends Command
{
    protected $signature = 'analytics:generate-predictions 
                            {--model= : Specific model type to generate predictions for}
                            {--retrain : Retrain models before generating predictions}
                            {--limit=100 : Maximum number of predictions to generate per model}';

    protected $description = 'Generate predictive analytics for job placement and employment success';

    public function handle()
    {
        $modelType = $this->option('model');
        $retrain = $this->option('retrain');
        $limit = (int) $this->option('limit');

        $this->info("Generating predictive analytics...");

        try {
            $models = PredictionModel::active()
                ->when($modelType, fn($query) => $query->where('type', $modelType))
                ->get();

            if ($models->isEmpty()) {
                $this->warn("No active prediction models found.");
                return 0;
            }

            $totalPredictions = 0;

            foreach ($models as $model) {
                $this->line("Processing model: {$model->name}");
                
                // Retrain model if requested or needed
                if ($retrain || $model->needsRetraining()) {
                    $this->info("  → Retraining model...");
                    $accuracy = $model->train();
                    $this->info("  → Model retrained with accuracy: " . number_format($accuracy * 100, 2) . '%');
                }

                // Generate predictions
                $predictions = $this->generateModelPredictions($model, $limit);
                $totalPredictions += $predictions;
                
                $this->info("  → Generated {$predictions} predictions");
            }

            $this->info("Generated {$totalPredictions} total predictions across " . $models->count() . " models");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to generate predictions: " . $e->getMessage());
            return 1;
        }
    }

    private function generateModelPredictions(PredictionModel $model, $limit)
    {
        $subjects = $this->getSubjectsForModel($model, $limit);
        
        if ($subjects->isEmpty()) {
            $this->warn("  → No subjects found for model: {$model->name}");
            return 0;
        }

        $bar = $this->output->createProgressBar($subjects->count());
        $bar->start();

        $generated = 0;
        $errors = 0;

        foreach ($subjects as $subject) {
            try {
                // Check if recent prediction already exists
                $existingPrediction = $model->predictions()
                    ->forSubject(get_class($subject), $subject->id)
                    ->where('prediction_date', '>=', now()->subDays(7))
                    ->first();

                if ($existingPrediction && !$existingPrediction->shouldUpdate()) {
                    $bar->advance();
                    continue;
                }

                $model->predict($subject);
                $generated++;
            } catch (\Exception $e) {
                $errors++;
                \Log::error("Failed to generate prediction for {$model->type} model: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($errors > 0) {
            $this->warn("  → {$errors} predictions failed (check logs for details)");
        }

        return $generated;
    }

    private function getSubjectsForModel(PredictionModel $model, $limit)
    {
        return match($model->type) {
            'job_placement' => $this->getJobPlacementSubjects($limit),
            'employment_success' => $this->getEmploymentSuccessSubjects($limit),
            'course_demand' => $this->getCourseDemandSubjects($limit),
            default => collect(),
        };
    }

    private function getJobPlacementSubjects($limit)
    {
        // Get recent graduates who are actively seeking employment
        return Graduate::with(['course', 'user'])
            ->where('employment_status', 'unemployed') // Combining seeking and unemployed as unemployed
            ->orderBy('graduation_date', 'desc')
            ->limit($limit)
            ->get();
    }

    private function getEmploymentSuccessSubjects($limit)
    {
        // Get recent job applications that are still pending
        return JobApplication::with(['graduate', 'job'])
            ->whereIn('status', ['applied', 'reviewed', 'interviewed'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($application) => $application->graduate)
            ->unique('id')
            ->values();
    }

    private function getCourseDemandSubjects($limit)
    {
        // Get all active courses for demand prediction
        return Course::active()
            ->withCount(['graduates', 'jobs'])
            ->orderBy('graduates_count', 'desc')
            ->limit($limit)
            ->get();
    }
}