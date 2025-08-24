<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramEffectiveness extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_name',
        'department',
        'graduation_year',
        'total_graduates',
        'employment_rate_6_months',
        'employment_rate_1_year',
        'employment_rate_2_years',
        'avg_starting_salary',
        'avg_salary_1_year',
        'avg_salary_2_years',
        'job_satisfaction_score',
        'alumni_engagement_score',
        'top_employers',
        'skills_gaps',
    ];

    protected $casts = [
        'employment_rate_6_months' => 'decimal:2',
        'employment_rate_1_year' => 'decimal:2',
        'employment_rate_2_years' => 'decimal:2',
        'avg_starting_salary' => 'decimal:2',
        'avg_salary_1_year' => 'decimal:2',
        'avg_salary_2_years' => 'decimal:2',
        'job_satisfaction_score' => 'decimal:2',
        'alumni_engagement_score' => 'decimal:2',
        'top_employers' => 'array',
        'skills_gaps' => 'array',
    ];

    // Scopes
    public function scopeByProgram($query, string $program)
    {
        return $query->where('program_name', $program);
    }

    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByGraduationYear($query, string $year)
    {
        return $query->where('graduation_year', $year);
    }

    public function scopeRecent($query, int $years = 5)
    {
        $cutoffYear = now()->year - $years;
        return $query->where('graduation_year', '>=', $cutoffYear);
    }

    // Accessors
    public function getEmploymentTrendAttribute()
    {
        $rates = [
            $this->employment_rate_6_months,
            $this->employment_rate_1_year,
            $this->employment_rate_2_years
        ];

        $trend = 'stable';
        if ($rates[2] > $rates[0] + 3) {
            $trend = 'improving';
        } elseif ($rates[2] < $rates[0] - 3) {
            $trend = 'declining';
        }

        return $trend;
    }

    public function getSalaryGrowthRateAttribute()
    {
        if (!$this->avg_starting_salary || !$this->avg_salary_2_years) {
            return null;
        }

        return round((($this->avg_salary_2_years - $this->avg_starting_salary) / $this->avg_starting_salary) * 100, 2);
    }

    public function getOverallEffectivenessScoreAttribute()
    {
        $score = 0;
        $factors = 0;

        // Employment rate (40% weight)
        if ($this->employment_rate_1_year !== null) {
            $score += ($this->employment_rate_1_year / 100) * 40;
            $factors++;
        }

        // Salary performance (30% weight)
        if ($this->avg_starting_salary !== null) {
            // Normalize salary score (assuming $50k as baseline)
            $salaryScore = min(100, ($this->avg_starting_salary / 50000) * 100);
            $score += ($salaryScore / 100) * 30;
            $factors++;
        }

        // Job satisfaction (20% weight)
        if ($this->job_satisfaction_score !== null) {
            $score += ($this->job_satisfaction_score / 5) * 20;
            $factors++;
        }

        // Alumni engagement (10% weight)
        if ($this->alumni_engagement_score !== null) {
            $score += ($this->alumni_engagement_score / 5) * 10;
            $factors++;
        }

        return $factors > 0 ? round($score / $factors * 100, 2) : 0;
    }

    public function getPerformanceGradeAttribute()
    {
        $score = $this->overall_effectiveness_score;

        return match (true) {
            $score >= 90 => 'A+',
            $score >= 85 => 'A',
            $score >= 80 => 'A-',
            $score >= 75 => 'B+',
            $score >= 70 => 'B',
            $score >= 65 => 'B-',
            $score >= 60 => 'C+',
            $score >= 55 => 'C',
            $score >= 50 => 'C-',
            default => 'D'
        };
    }
}