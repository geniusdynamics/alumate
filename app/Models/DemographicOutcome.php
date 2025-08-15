<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemographicOutcome extends Model
{
    use HasFactory;

    const TYPE_GENDER = 'gender';
    const TYPE_ETHNICITY = 'ethnicity';
    const TYPE_AGE_GROUP = 'age_group';
    const TYPE_SOCIOECONOMIC = 'socioeconomic';
    const TYPE_FIRST_GENERATION = 'first_generation';

    protected $fillable = [
        'demographic_type',
        'demographic_value',
        'graduation_year',
        'program',
        'employment_rate',
        'avg_salary',
        'leadership_rate',
        'entrepreneurship_rate',
        'industry_distribution',
        'challenges',
        'success_factors',
    ];

    protected $casts = [
        'employment_rate' => 'decimal:2',
        'avg_salary' => 'decimal:2',
        'leadership_rate' => 'decimal:2',
        'entrepreneurship_rate' => 'decimal:2',
        'industry_distribution' => 'array',
        'challenges' => 'array',
        'success_factors' => 'array',
    ];

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('demographic_type', $type);
    }

    public function scopeByValue($query, string $value)
    {
        return $query->where('demographic_value', $value);
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
    public function getEquityScoreAttribute()
    {
        // Calculate equity score based on comparison to overall averages
        // This would need baseline data to compare against
        $score = 100;

        // Placeholder logic - in real implementation, compare to institutional averages
        if ($this->employment_rate < 80) {
            $score -= 20;
        }
        if ($this->leadership_rate < 15) {
            $score -= 15;
        }
        if ($this->avg_salary < 50000) {
            $score -= 15;
        }

        return max(0, $score);
    }

    public function getOpportunityGapAttribute()
    {
        // Calculate opportunity gap compared to highest-performing demographic
        // This would require comparison data
        return [
            'employment_gap' => 0, // Placeholder
            'salary_gap' => 0,     // Placeholder
            'leadership_gap' => 0,  // Placeholder
        ];
    }

    public function getTopIndustriesAttribute()
    {
        if (!$this->industry_distribution) {
            return [];
        }

        // Sort industries by percentage and return top 5
        arsort($this->industry_distribution);
        return array_slice($this->industry_distribution, 0, 5, true);
    }

    // Static methods
    public static function getDemographicTypes(): array
    {
        return [
            self::TYPE_GENDER => 'Gender',
            self::TYPE_ETHNICITY => 'Ethnicity',
            self::TYPE_AGE_GROUP => 'Age Group',
            self::TYPE_SOCIOECONOMIC => 'Socioeconomic Status',
            self::TYPE_FIRST_GENERATION => 'First Generation College',
        ];
    }
}