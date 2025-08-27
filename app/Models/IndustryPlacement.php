<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndustryPlacement extends Model
{
    use HasFactory;

    protected $fillable = [
        'industry',
        'sub_industry',
        'graduation_year',
        'program',
        'placement_count',
        'avg_starting_salary',
        'avg_current_salary',
        'retention_rate',
        'top_companies',
        'skills_in_demand',
    ];

    protected $casts = [
        'avg_starting_salary' => 'decimal:2',
        'avg_current_salary' => 'decimal:2',
        'retention_rate' => 'decimal:2',
        'top_companies' => 'array',
        'skills_in_demand' => 'array',
    ];

    // Scopes
    public function scopeByIndustry($query, string $industry)
    {
        return $query->where('industry', $industry);
    }

    public function scopeByGraduationYear($query, string $year)
    {
        return $query->where('graduation_year', $year);
    }

    public function scopeByProgram($query, string $program)
    {
        return $query->where('program', $program);
    }

    // Accessors
    public function getSalaryGrowthAttribute()
    {
        if (! $this->avg_starting_salary || ! $this->avg_current_salary) {
            return null;
        }

        return round((($this->avg_current_salary - $this->avg_starting_salary) / $this->avg_starting_salary) * 100, 2);
    }

    public function getFormattedRetentionRateAttribute()
    {
        return $this->retention_rate ? $this->retention_rate.'%' : 'N/A';
    }
}
