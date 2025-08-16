<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobGraduateMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'graduate_id',
        'match_score',
        'match_factors',
        'compatibility_score',
        'compatibility_factors',
        'is_recommended',
        'is_viewed',
        'is_applied',
        'calculated_at',
    ];

    protected $casts = [
        'match_score' => 'decimal:2',
        'match_factors' => 'array',
        'compatibility_score' => 'decimal:2',
        'compatibility_factors' => 'array',
        'is_recommended' => 'boolean',
        'is_viewed' => 'boolean',
        'is_applied' => 'boolean',
        'calculated_at' => 'datetime',
    ];

    // Relationships
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function graduate()
    {
        return $this->belongsTo(Graduate::class);
    }

    // Scopes
    public function scopeHighQuality($query, $threshold = 80)
    {
        return $query->where('match_score', '>=', $threshold);
    }

    public function scopeRecommended($query)
    {
        return $query->where('is_recommended', true);
    }

    public function scopeViewed($query)
    {
        return $query->where('is_viewed', true);
    }

    public function scopeApplied($query)
    {
        return $query->where('is_applied', true);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('calculated_at', '>=', now()->subDays($days));
    }

    // Helper Methods
    public function getOverallScore()
    {
        if ($this->compatibility_score) {
            return round(($this->match_score * 0.7) + ($this->compatibility_score * 0.3), 2);
        }

        return $this->match_score;
    }

    public function getMatchQuality()
    {
        $score = $this->getOverallScore();

        if ($score >= 90) {
            return 'excellent';
        }
        if ($score >= 80) {
            return 'very_good';
        }
        if ($score >= 70) {
            return 'good';
        }
        if ($score >= 60) {
            return 'fair';
        }

        return 'poor';
    }

    public function getMatchSummary()
    {
        $factors = $this->match_factors ?? [];
        $summary = [];

        if (isset($factors['course_match']) && $factors['course_match']) {
            $summary[] = 'Perfect course match';
        } elseif (isset($factors['course_compatibility'])) {
            $summary[] = "Course compatibility: {$factors['course_compatibility']}%";
        }

        if (isset($factors['skills_match']['exact_matches'])) {
            $count = count($factors['skills_match']['exact_matches']);
            $summary[] = "{$count} exact skill matches";
        }

        if (isset($factors['gpa']) && $factors['gpa'] >= 3.5) {
            $summary[] = "High GPA ({$factors['gpa']})";
        }

        if (isset($factors['profile_completion']) && $factors['profile_completion'] >= 90) {
            $summary[] = 'Complete profile';
        }

        return $summary;
    }

    public function getMissingSkills()
    {
        $factors = $this->match_factors ?? [];

        if (isset($factors['skills_match']['missing_skills'])) {
            return $factors['skills_match']['missing_skills'];
        }

        return [];
    }

    public function getCompatibilityHighlights()
    {
        $factors = $this->compatibility_factors ?? [];
        $highlights = [];

        if (isset($factors['location_compatibility']) && $factors['location_compatibility'] > 70) {
            $highlights[] = 'Good location match';
        }

        if (isset($factors['salary_compatibility']) && $factors['salary_compatibility'] > 70) {
            $highlights[] = 'Salary expectations aligned';
        }

        if (isset($factors['active_job_seeker']) && $factors['active_job_seeker']) {
            $highlights[] = 'Actively seeking opportunities';
        }

        if (isset($factors['recent_activity']) && $factors['recent_activity']) {
            $highlights[] = 'Recently active profile';
        }

        return $highlights;
    }

    public function shouldRecommend()
    {
        return $this->getOverallScore() >= 70 && ! $this->is_recommended;
    }

    public function markAsRecommended()
    {
        $this->update(['is_recommended' => true]);

        return $this;
    }

    public function markAsViewed()
    {
        $this->update(['is_viewed' => true]);

        return $this;
    }

    public function markAsApplied()
    {
        $this->update(['is_applied' => true]);

        return $this;
    }
}
