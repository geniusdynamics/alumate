<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Prediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'prediction_model_id',
        'subject_type',
        'subject_id',
        'prediction_score',
        'prediction_data',
        'input_features',
        'prediction_date',
        'target_date',
    ];

    protected $casts = [
        'prediction_score' => 'decimal:4',
        'prediction_data' => 'array',
        'input_features' => 'array',
        'prediction_date' => 'date',
        'target_date' => 'date',
    ];

    // Relationships
    public function predictionModel(): BelongsTo
    {
        return $this->belongsTo(PredictionModel::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeForSubject($query, $subjectType, $subjectId)
    {
        return $query->where('subject_type', $subjectType)
                    ->where('subject_id', $subjectId);
    }

    public function scopeByScore($query, $minScore, $maxScore = null)
    {
        $query->where('prediction_score', '>=', $minScore);
        
        if ($maxScore !== null) {
            $query->where('prediction_score', '<=', $maxScore);
        }
        
        return $query;
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('prediction_date', '>=', now()->subDays($days));
    }

    // Helper methods
    public function getScorePercentage()
    {
        return $this->prediction_score * 100;
    }

    public function getFormattedScore()
    {
        return number_format($this->getScorePercentage(), 1) . '%';
    }

    public function getConfidenceLevel()
    {
        $score = $this->prediction_score;
        
        if ($score >= 0.8) {
            return 'high';
        } elseif ($score >= 0.6) {
            return 'medium';
        } elseif ($score >= 0.4) {
            return 'low';
        } else {
            return 'very_low';
        }
    }

    public function getConfidenceColor()
    {
        return match($this->getConfidenceLevel()) {
            'high' => 'green',
            'medium' => 'blue',
            'low' => 'yellow',
            'very_low' => 'red',
        };
    }

    public function getConfidenceLabel()
    {
        return match($this->getConfidenceLevel()) {
            'high' => 'High Confidence',
            'medium' => 'Medium Confidence',
            'low' => 'Low Confidence',
            'very_low' => 'Very Low Confidence',
        };
    }

    public function getPredictionData($key = null, $default = null)
    {
        if ($key === null) {
            return $this->prediction_data;
        }
        
        return data_get($this->prediction_data, $key, $default);
    }

    public function getRecommendations()
    {
        return $this->getPredictionData('recommendations', []);
    }

    public function getRiskFactors()
    {
        return $this->getPredictionData('risk_factors', []);
    }

    public function getKeyFactors()
    {
        return $this->getPredictionData('factors', []);
    }

    public function getTopFactor()
    {
        $factors = $this->getKeyFactors();
        return !empty($factors) ? $factors[0] : null;
    }

    public function isExpired()
    {
        return $this->target_date && $this->target_date->isPast();
    }

    public function getDaysUntilTarget()
    {
        if (!$this->target_date) {
            return null;
        }
        
        return now()->diffInDays($this->target_date, false);
    }

    public function getFeatureValue($feature, $default = null)
    {
        return data_get($this->input_features, $feature, $default);
    }

    public function hasFeature($feature)
    {
        return array_key_exists($feature, $this->input_features ?? []);
    }

    public function getAccuracyScore()
    {
        return $this->predictionModel->accuracy ?? 0;
    }

    public function getFormattedAccuracy()
    {
        return number_format($this->getAccuracyScore() * 100, 1) . '%';
    }

    public function shouldUpdate()
    {
        // Check if prediction should be updated based on age and model settings
        $maxAge = $this->predictionModel->model_config['prediction_refresh_days'] ?? 7;
        
        return $this->created_at->addDays($maxAge)->isPast();
    }

    public function getInterpretation()
    {
        $score = $this->prediction_score;
        $type = $this->predictionModel->type;
        
        return match($type) {
            'job_placement' => $this->getJobPlacementInterpretation($score),
            'employment_success' => $this->getEmploymentSuccessInterpretation($score),
            'course_demand' => $this->getCourseDemandInterpretation($score),
            default => 'Prediction score: ' . $this->getFormattedScore(),
        };
    }

    private function getJobPlacementInterpretation($score)
    {
        if ($score >= 0.8) {
            return 'Very likely to find employment within the predicted timeframe';
        } elseif ($score >= 0.6) {
            return 'Good chances of finding employment with some effort';
        } elseif ($score >= 0.4) {
            return 'May face challenges finding employment, consider additional support';
        } else {
            return 'Likely to face significant challenges, recommend intensive support';
        }
    }

    private function getEmploymentSuccessInterpretation($score)
    {
        if ($score >= 0.8) {
            return 'Very high likelihood of application success';
        } elseif ($score >= 0.6) {
            return 'Good chances of application success';
        } elseif ($score >= 0.4) {
            return 'Moderate chances, may need to improve application';
        } else {
            return 'Low chances, significant improvements needed';
        }
    }

    private function getCourseDemandInterpretation($score)
    {
        if ($score >= 0.8) {
            return 'Very high demand expected for this course';
        } elseif ($score >= 0.6) {
            return 'Good demand expected';
        } elseif ($score >= 0.4) {
            return 'Moderate demand expected';
        } else {
            return 'Low demand expected, consider course adjustments';
        }
    }
}