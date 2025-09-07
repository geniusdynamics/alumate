<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareerOutcomeSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_type',
        'period_start',
        'period_end',
        'graduation_year',
        'program',
        'department',
        'demographic_group',
        'metrics',
        'total_graduates',
        'tracked_graduates',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'metrics' => 'array',
    ];

    // Scopes
    public function scopeByPeriod($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    public function scopeByGraduationYear($query, string $year)
    {
        return $query->where('graduation_year', $year);
    }

    public function scopeByProgram($query, string $program)
    {
        return $query->where('program', $program);
    }

    public function scopeByDemographic($query, string $demographic)
    {
        return $query->where('demographic_group', $demographic);
    }

    // Accessors
    public function getEmploymentRateAttribute()
    {
        return $this->metrics['employment_rate'] ?? 0;
    }

    public function getAverageSalaryAttribute()
    {
        return $this->metrics['average_salary'] ?? 0;
    }

    public function getTrackingRateAttribute()
    {
        return $this->total_graduates > 0
            ? round(($this->tracked_graduates / $this->total_graduates) * 100, 2)
            : 0;
    }
}
