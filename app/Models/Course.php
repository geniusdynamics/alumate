<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'description',
        'level',
        'duration_months',
        'cost',
        'study_mode',
        'required_skills',
        'skills_gained',
        'career_paths',
        'is_active',
        'is_featured',
        'total_enrolled',
        'total_graduated',
        'completion_rate',
        'employment_rate',
        'average_salary',
        'prerequisites',
        'learning_outcomes',
        'department',
    ];

    protected $casts = [
        'duration_months' => 'integer',
        'cost' => 'decimal:2',
        'required_skills' => 'array',
        'skills_gained' => 'array',
        'career_paths' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'total_enrolled' => 'integer',
        'total_graduated' => 'integer',
        'completion_rate' => 'decimal:2',
        'employment_rate' => 'decimal:2',
        'average_salary' => 'decimal:2',
        'prerequisites' => 'array',
        'learning_outcomes' => 'array',
    ];

    // Relationships
    public function institution()
    {
        return $this->belongsTo(Tenant::class, 'institution_id');
    }

    public function graduates()
    {
        return $this->hasMany(Graduate::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    // Accessors & Mutators
    protected function isPopular(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_enrolled > 50 && $this->employment_rate > 70,
        );
    }

    protected function hasHighEmploymentRate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->employment_rate >= 80,
        );
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeByInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeHighEmploymentRate($query, $minRate = 70)
    {
        return $query->where('employment_rate', '>=', $minRate);
    }

    public function scopeByDuration($query, $minMonths, $maxMonths = null)
    {
        $query->where('duration_months', '>=', $minMonths);

        if ($maxMonths) {
            $query->where('duration_months', '<=', $maxMonths);
        }

        return $query;
    }

    // Helper Methods
    public function updateStatistics()
    {
        $totalGraduates = $this->graduates()->count();
        $employedGraduates = $this->graduates()->employed()->count();

        $employmentRate = $totalGraduates > 0 ? ($employedGraduates / $totalGraduates) * 100 : 0;

        $averageSalary = $this->graduates()
            ->employed()
            ->whereNotNull('current_salary')
            ->avg('current_salary');

        $this->update([
            'total_graduated' => $totalGraduates,
            'employment_rate' => round($employmentRate, 2),
            'average_salary' => $averageSalary ? round($averageSalary, 2) : null,
        ]);

        return [
            'total_graduated' => $totalGraduates,
            'employment_rate' => $employmentRate,
            'average_salary' => $averageSalary,
        ];
    }

    public function getMatchingJobs($limit = 10)
    {
        return Job::where('course_id', $this->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getSkillsOverlap($jobSkills)
    {
        if (empty($this->skills_gained) || empty($jobSkills)) {
            return 0;
        }

        $courseSkills = array_map('strtolower', $this->skills_gained);
        $requiredSkills = array_map('strtolower', $jobSkills);

        $overlap = array_intersect($courseSkills, $requiredSkills);

        return (count($overlap) / count($requiredSkills)) * 100;
    }

    public function getRecentGraduates($limit = 20)
    {
        return $this->graduates()
            ->orderBy('graduation_year', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getEmploymentTrends($years = 5)
    {
        $trends = [];
        $currentYear = now()->year;

        for ($i = 0; $i < $years; $i++) {
            $year = $currentYear - $i;
            $totalGrads = $this->graduates()->byGraduationYear($year)->count();
            $employedGrads = $this->graduates()->byGraduationYear($year)->employed()->count();

            $trends[$year] = [
                'total' => $totalGrads,
                'employed' => $employedGrads,
                'rate' => $totalGrads > 0 ? round(($employedGrads / $totalGrads) * 100, 2) : 0,
            ];
        }

        return $trends;
    }
}
