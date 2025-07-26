<?php

namespace App\Console\Commands;

use App\Models\PredictionModel;
use Illuminate\Console\Command;

class TrainPredictionModels extends Command
{
    protected $signature = 'analytics:train-models 
                            {--model= : Specific model ID to train}
                            {--type= : Specific model type to train}
                            {--force : Force retraining even if not needed}';

    protected $description = 'Train predictive analytics models with current data';

    public function handle()
    {
        $modelId = $this->option('model');
        $modelType = $this->option('type');
        $force = $this->option('force');

        $this->info("Training predictive analytics models...");

        try {
            if ($modelId) {
                $this->trainSpecificModel($modelId, $force);
            } elseif ($modelType) {
                $this->trainModelsByType($modelType, $force);
            } else {
                $this->trainAllModels($force);
            }

            $this->info("Model training completed successfully!");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to train models: " . $e->getMessage());
            return 1;
        }
    }

    private function trainSpecificModel($modelId, $force)
    {
        $model = PredictionModel::find($modelId);
        
        if (!$model) {
            $this->error("Model not found: {$modelId}");
            return;
        }

        if (!$force && !$model->needsRetraining()) {
            $this->warn("Model {$model->name} does not need retraining. Use --force to retrain anyway.");
            return;
        }

        $this->trainModel($model);
    }

    private function trainModelsByType($modelType, $force)
    {
        $models = PredictionModel::where('type', $modelType)->active()->get();
        
        if ($models->isEmpty()) {
            $this->warn("No active models found for type: {$modelType}");
            return;
        }

        foreach ($models as $model) {
            if (!$force && !$model->needsRetraining()) {
                $this->line("Skipping {$model->name} - does not need retraining");
                continue;
            }

            $this->trainModel($model);
        }
    }

    private function trainAllModels($force)
    {
        $models = PredictionModel::active()->get();
        
        if ($models->isEmpty()) {
            $this->warn("No active prediction models found.");
            return;
        }

        $modelsToTrain = $models->filter(function ($model) use ($force) {
            return $force || $model->needsRetraining();
        });

        if ($modelsToTrain->isEmpty()) {
            $this->info("All models are up to date. Use --force to retrain anyway.");
            return;
        }

        $bar = $this->output->createProgressBar($modelsToTrain->count());
        $bar->start();

        $trained = 0;
        $failed = 0;

        foreach ($modelsToTrain as $model) {
            try {
                $this->trainModel($model, false);
                $trained++;
            } catch (\Exception $e) {
                $failed++;
                $this->error("Failed to train model {$model->name}: " . $e->getMessage());
                \Log::error("Model training failed", [
                    'model_id' => $model->id,
                    'model_name' => $model->name,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Training summary:");
        $this->line("  ✓ Trained: {$trained}");
        if ($failed > 0) {
            $this->line("  ✗ Failed: {$failed}");
        }
    }

    private function trainModel(PredictionModel $model, $showProgress = true)
    {
        if ($showProgress) {
            $this->info("Training model: {$model->name}");
        }

        // Get training data
        $trainingData = $this->getTrainingData($model);
        
        if (empty($trainingData)) {
            throw new \Exception("No training data available for model {$model->name}");
        }

        $minTrainingData = config('analytics.predictions.min_training_data', 100);
        if (count($trainingData) < $minTrainingData) {
            throw new \Exception("Insufficient training data. Need at least {$minTrainingData} records, got " . count($trainingData));
        }

        if ($showProgress) {
            $this->line("  Training data records: " . count($trainingData));
        }

        // Train the model
        $accuracy = $model->train($trainingData);

        if ($showProgress) {
            $this->info("  ✓ Training completed. Accuracy: " . number_format($accuracy * 100, 2) . "%");
        }

        // Generate some sample predictions to validate the model
        $this->validateModel($model, $showProgress);
    }

    private function getTrainingData(PredictionModel $model)
    {
        return match($model->type) {
            'job_placement' => $this->getJobPlacementTrainingData(),
            'employment_success' => $this->getEmploymentSuccessTrainingData(),
            'course_demand' => $this->getCourseDemandTrainingData(),
            default => [],
        };
    }

    private function getJobPlacementTrainingData()
    {
        return \App\Models\Graduate::with(['course', 'applications'])
            ->whereNotNull('employment_status')
            ->where('created_at', '>=', now()->subYear()) // Use recent data
            ->get()
            ->map(function ($graduate) {
                $features = $this->extractGraduateFeatures($graduate);
                $outcome = $graduate->employment_status['status'] === 'employed' ? 1 : 0;
                
                return [
                    'features' => $features,
                    'outcome' => $outcome,
                    'subject_id' => $graduate->id,
                ];
            })
            ->toArray();
    }

    private function getEmploymentSuccessTrainingData()
    {
        return \App\Models\JobApplication::with(['graduate', 'job'])
            ->whereIn('status', ['hired', 'rejected'])
            ->where('created_at', '>=', now()->subMonths(6))
            ->get()
            ->map(function ($application) {
                $features = $this->extractApplicationFeatures($application);
                $outcome = $application->status === 'hired' ? 1 : 0;
                
                return [
                    'features' => $features,
                    'outcome' => $outcome,
                    'subject_id' => $application->id,
                ];
            })
            ->toArray();
    }

    private function getCourseDemandTrainingData()
    {
        return \App\Models\Course::withCount(['graduates', 'jobs'])
            ->where('created_at', '>=', now()->subYear())
            ->get()
            ->map(function ($course) {
                $features = $this->extractCourseFeatures($course);
                $outcome = $course->jobs_count; // Number of job postings as demand indicator
                
                return [
                    'features' => $features,
                    'outcome' => $outcome,
                    'subject_id' => $course->id,
                ];
            })
            ->toArray();
    }

    private function extractGraduateFeatures($graduate)
    {
        return [
            'graduation_year' => $graduate->graduation_year ?? now()->year,
            'course_employment_rate' => $graduate->course?->employment_rate ?? 0,
            'gpa' => $graduate->gpa ?? 0,
            'skills_count' => count($graduate->skills ?? []),
            'certifications_count' => count($graduate->certifications ?? []),
            'profile_completion' => $graduate->profile_completion ?? 0,
            'location_job_market' => $this->getLocationJobMarketScore($graduate->location ?? ''),
        ];
    }

    private function extractApplicationFeatures($application)
    {
        $graduate = $application->graduate;
        
        return [
            'job_applications_count' => $graduate->applications->count(),
            'interview_count' => $graduate->applications->where('status', 'interviewed')->count(),
            'skills_match_score' => $this->calculateSkillsMatchScore($graduate, $application->job),
            'profile_completion' => $graduate->profile_completion ?? 0,
            'course_employment_rate' => $graduate->course?->employment_rate ?? 0,
            'application_timing' => $this->calculateApplicationTiming($application),
        ];
    }

    private function extractCourseFeatures($course)
    {
        return [
            'historical_enrollment' => $course->graduates_count,
            'employment_rate' => $course->employment_rate ?? 0,
            'job_postings_trend' => $this->getJobPostingsTrend($course),
            'industry_growth_rate' => $course->industry_growth_rate ?? 0,
            'salary_trends' => $this->getSalaryTrends($course),
            'skills_demand' => $this->getSkillsDemand($course),
        ];
    }

    private function getLocationJobMarketScore($location)
    {
        if (empty($location)) {
            return 0;
        }

        // Simple scoring based on job count in location
        $jobCount = \App\Models\Job::where('location', 'like', "%{$location}%")
            ->where('status', 'active')
            ->count();

        return min(100, $jobCount * 2); // Scale to 0-100
    }

    private function calculateSkillsMatchScore($graduate, $job)
    {
        $graduateSkills = collect($graduate->skills ?? []);
        $requiredSkills = collect($job->required_skills ?? []);
        
        if ($requiredSkills->isEmpty()) {
            return 50; // Neutral score if no requirements
        }

        $matchingSkills = $graduateSkills->intersect($requiredSkills);
        
        return ($matchingSkills->count() / $requiredSkills->count()) * 100;
    }

    private function calculateApplicationTiming($application)
    {
        $jobPostedAt = $application->job->created_at;
        $appliedAt = $application->created_at;
        
        $daysSincePosted = $jobPostedAt->diffInDays($appliedAt);
        
        // Earlier applications get higher scores
        return max(0, 100 - ($daysSincePosted * 5));
    }

    private function getJobPostingsTrend($course)
    {
        $currentMonth = \App\Models\Job::where('course_id', $course->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
            
        $lastMonth = \App\Models\Job::where('course_id', $course->id)
            ->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ])
            ->count();

        if ($lastMonth === 0) {
            return $currentMonth > 0 ? 100 : 0;
        }

        return (($currentMonth - $lastMonth) / $lastMonth) * 100;
    }

    private function getSalaryTrends($course)
    {
        $graduates = $course->graduates()
            ->whereJsonContains('employment_status->status', 'employed')
            ->whereNotNull('employment_status->salary_range')
            ->get();

        if ($graduates->isEmpty()) {
            return 0;
        }

        // Simple average of salary midpoints
        $salarySum = $graduates->sum(function ($graduate) {
            return $this->getSalaryMidpoint($graduate->employment_status['salary_range']);
        });

        return $salarySum / $graduates->count();
    }

    private function getSkillsDemand($course)
    {
        $courseSkills = collect($course->skills ?? []);
        
        if ($courseSkills->isEmpty()) {
            return 0;
        }

        $demandScore = 0;
        foreach ($courseSkills as $skill) {
            $jobsRequiringSkill = \App\Models\Job::whereJsonContains('required_skills', $skill)
                ->where('status', 'active')
                ->count();
            
            $demandScore += $jobsRequiringSkill;
        }

        return $demandScore / $courseSkills->count();
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

        return $ranges[$range] ?? 50000;
    }

    private function validateModel(PredictionModel $model, $showProgress = true)
    {
        try {
            // Get a sample of subjects to test predictions
            $subjects = $this->getSampleSubjects($model->type, 5);
            
            if ($subjects->isEmpty()) {
                if ($showProgress) {
                    $this->warn("  No subjects available for validation");
                }
                return;
            }

            $validPredictions = 0;
            foreach ($subjects as $subject) {
                try {
                    $prediction = $model->predict($subject);
                    if ($prediction && $prediction->prediction_score >= 0 && $prediction->prediction_score <= 1) {
                        $validPredictions++;
                    }
                } catch (\Exception $e) {
                    // Skip invalid predictions
                }
            }

            if ($showProgress) {
                $this->line("  Validation: {$validPredictions}/{$subjects->count()} predictions successful");
            }

            if ($validPredictions === 0) {
                throw new \Exception("Model validation failed - no valid predictions generated");
            }
        } catch (\Exception $e) {
            if ($showProgress) {
                $this->warn("  Validation warning: " . $e->getMessage());
            }
        }
    }

    private function getSampleSubjects($modelType, $limit = 5)
    {
        return match($modelType) {
            'job_placement' => \App\Models\Graduate::inRandomOrder()->limit($limit)->get(),
            'employment_success' => \App\Models\Graduate::has('applications')->inRandomOrder()->limit($limit)->get(),
            'course_demand' => \App\Models\Course::inRandomOrder()->limit($limit)->get(),
            default => collect(),
        };
    }
}