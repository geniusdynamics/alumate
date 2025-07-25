<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PredictionModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'features',
        'model_config',
        'accuracy',
        'last_trained_at',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'model_config' => 'array',
        'accuracy' => 'decimal:4',
        'last_trained_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper methods
    public function needsRetraining()
    {
        if (!$this->last_trained_at) {
            return true;
        }

        $retrainingInterval = $this->model_config['retraining_interval'] ?? 30; // days
        
        return $this->last_trained_at->addDays($retrainingInterval)->isPast();
    }

    public function getAccuracyPercentage()
    {
        return $this->accuracy ? ($this->accuracy * 100) : 0;
    }

    public function getFormattedAccuracy()
    {
        return number_format($this->getAccuracyPercentage(), 1) . '%';
    }

    public function predict($subject, $features = null)
    {
        if (!$this->is_active) {
            throw new \Exception('Model is not active');
        }

        $features = $features ?? $this->extractFeatures($subject);
        
        // This is a simplified prediction implementation
        // In a real system, you would integrate with ML libraries or services
        $score = $this->calculatePredictionScore($features);
        
        return $this->predictions()->create([
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'prediction_score' => $score,
            'prediction_data' => $this->generatePredictionData($features, $score),
            'input_features' => $features,
            'prediction_date' => now()->toDateString(),
            'target_date' => $this->calculateTargetDate(),
        ]);
    }

    public function batchPredict($subjects)
    {
        $predictions = [];
        
        foreach ($subjects as $subject) {
            try {
                $predictions[] = $this->predict($subject);
            } catch (\Exception $e) {
                // Log error and continue with next subject
                \Log::error("Prediction failed for {$subject->id}: " . $e->getMessage());
            }
        }
        
        return collect($predictions);
    }

    public function train($trainingData = null)
    {
        if (!$trainingData) {
            $trainingData = $this->getTrainingData();
        }

        // This is a simplified training implementation
        // In a real system, you would implement actual ML training
        $accuracy = $this->performTraining($trainingData);
        
        $this->update([
            'accuracy' => $accuracy,
            'last_trained_at' => now(),
        ]);

        return $accuracy;
    }

    private function extractFeatures($subject)
    {
        $features = [];
        
        foreach ($this->features as $feature) {
            $features[$feature] = $this->getFeatureValue($subject, $feature);
        }
        
        return $features;
    }

    private function getFeatureValue($subject, $feature)
    {
        // Extract feature values based on the feature name and subject type
        return match($feature) {
            'graduation_year' => $subject->graduation_year ?? now()->year,
            'course_employment_rate' => $subject->course?->employment_rate ?? 0,
            'gpa' => $subject->gpa ?? 0,
            'skills_count' => count($subject->skills ?? []),
            'certifications_count' => count($subject->certifications ?? []),
            'profile_completion' => $subject->profile_completion ?? 0,
            'job_applications_count' => $subject->applications?->count() ?? 0,
            'interview_count' => $subject->applications?->where('status', 'interviewed')->count() ?? 0,
            default => 0,
        };
    }

    private function calculatePredictionScore($features)
    {
        // Simplified scoring algorithm
        // In a real system, this would use trained ML models
        
        $weights = $this->model_config['feature_weights'] ?? [];
        $score = 0;
        
        foreach ($features as $feature => $value) {
            $weight = $weights[$feature] ?? 1;
            $score += $value * $weight;
        }
        
        // Normalize to 0-1 range
        $maxScore = $this->model_config['max_score'] ?? 100;
        return min(1.0, max(0.0, $score / $maxScore));
    }

    private function generatePredictionData($features, $score)
    {
        return [
            'confidence' => $score,
            'factors' => $this->identifyKeyFactors($features),
            'recommendations' => $this->generateRecommendations($features, $score),
            'risk_factors' => $this->identifyRiskFactors($features),
        ];
    }

    private function identifyKeyFactors($features)
    {
        $weights = $this->model_config['feature_weights'] ?? [];
        $factors = [];
        
        foreach ($features as $feature => $value) {
            $weight = $weights[$feature] ?? 1;
            $impact = $value * $weight;
            
            $factors[] = [
                'feature' => $feature,
                'value' => $value,
                'impact' => $impact,
                'importance' => $weight,
            ];
        }
        
        // Sort by impact
        usort($factors, fn($a, $b) => $b['impact'] <=> $a['impact']);
        
        return array_slice($factors, 0, 5); // Top 5 factors
    }

    private function generateRecommendations($features, $score)
    {
        $recommendations = [];
        
        if ($score < 0.5) {
            $recommendations[] = 'Consider additional skill development';
            $recommendations[] = 'Complete profile information';
            $recommendations[] = 'Apply to more positions';
        }
        
        if (($features['profile_completion'] ?? 0) < 80) {
            $recommendations[] = 'Complete your profile to improve visibility';
        }
        
        if (($features['skills_count'] ?? 0) < 5) {
            $recommendations[] = 'Add more relevant skills to your profile';
        }
        
        return $recommendations;
    }

    private function identifyRiskFactors($features)
    {
        $risks = [];
        
        if (($features['job_applications_count'] ?? 0) > 50 && ($features['interview_count'] ?? 0) < 5) {
            $risks[] = 'Low interview conversion rate';
        }
        
        if (($features['course_employment_rate'] ?? 0) < 60) {
            $risks[] = 'Course has lower employment rate';
        }
        
        return $risks;
    }

    private function calculateTargetDate()
    {
        $daysAhead = $this->model_config['prediction_horizon'] ?? 90;
        return now()->addDays($daysAhead)->toDateString();
    }

    private function getTrainingData()
    {
        // Get historical data for training
        // This would depend on the model type
        return match($this->type) {
            'job_placement' => $this->getJobPlacementTrainingData(),
            'employment_success' => $this->getEmploymentSuccessTrainingData(),
            'course_demand' => $this->getCourseDemandTrainingData(),
            default => [],
        };
    }

    private function getJobPlacementTrainingData()
    {
        return Graduate::with(['course', 'applications'])
            ->whereNotNull('employment_status')
            ->get()
            ->map(function ($graduate) {
                return [
                    'features' => $this->extractFeatures($graduate),
                    'outcome' => $graduate->employment_status['status'] === 'employed' ? 1 : 0,
                ];
            })
            ->toArray();
    }

    private function getEmploymentSuccessTrainingData()
    {
        return JobApplication::with(['graduate', 'job'])
            ->whereIn('status', ['hired', 'rejected'])
            ->get()
            ->map(function ($application) {
                return [
                    'features' => $this->extractFeatures($application->graduate),
                    'outcome' => $application->status === 'hired' ? 1 : 0,
                ];
            })
            ->toArray();
    }

    private function getCourseDemandTrainingData()
    {
        return Course::withCount(['graduates', 'jobs'])
            ->get()
            ->map(function ($course) {
                return [
                    'features' => [
                        'graduates_count' => $course->graduates_count,
                        'employment_rate' => $course->employment_rate,
                        'industry_growth' => $course->industry_growth_rate ?? 0,
                    ],
                    'outcome' => $course->jobs_count,
                ];
            })
            ->toArray();
    }

    private function performTraining($trainingData)
    {
        // Simplified training simulation
        // In a real system, this would use actual ML algorithms
        
        if (empty($trainingData)) {
            return 0.5; // Default accuracy
        }
        
        // Simulate training accuracy based on data quality
        $dataQuality = min(1.0, count($trainingData) / 1000); // More data = better quality
        $baseAccuracy = 0.6;
        $maxImprovement = 0.3;
        
        return $baseAccuracy + ($dataQuality * $maxImprovement);
    }

    public static function getDefaultModels()
    {
        return [
            [
                'name' => 'Job Placement Predictor',
                'type' => 'job_placement',
                'description' => 'Predicts the likelihood of a graduate finding employment',
                'features' => [
                    'graduation_year',
                    'course_employment_rate',
                    'gpa',
                    'skills_count',
                    'certifications_count',
                    'profile_completion',
                ],
                'model_config' => [
                    'feature_weights' => [
                        'graduation_year' => 0.1,
                        'course_employment_rate' => 0.3,
                        'gpa' => 0.2,
                        'skills_count' => 0.15,
                        'certifications_count' => 0.1,
                        'profile_completion' => 0.15,
                    ],
                    'max_score' => 100,
                    'prediction_horizon' => 90,
                    'retraining_interval' => 30,
                ],
            ],
            [
                'name' => 'Application Success Predictor',
                'type' => 'employment_success',
                'description' => 'Predicts the likelihood of a job application being successful',
                'features' => [
                    'job_applications_count',
                    'interview_count',
                    'skills_count',
                    'profile_completion',
                    'course_employment_rate',
                ],
                'model_config' => [
                    'feature_weights' => [
                        'job_applications_count' => 0.1,
                        'interview_count' => 0.3,
                        'skills_count' => 0.2,
                        'profile_completion' => 0.2,
                        'course_employment_rate' => 0.2,
                    ],
                    'max_score' => 50,
                    'prediction_horizon' => 30,
                    'retraining_interval' => 14,
                ],
            ],
        ];
    }
}