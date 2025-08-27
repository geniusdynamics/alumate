<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareerTrend extends Model
{
    use HasFactory;

    const TREND_SALARY = 'salary';

    const TREND_INDUSTRY_SHIFT = 'industry_shift';

    const TREND_SKILL_DEMAND = 'skill_demand';

    const TREND_EMPLOYMENT_RATE = 'employment_rate';

    const TREND_JOB_SATISFACTION = 'job_satisfaction';

    const DIRECTION_INCREASING = 'increasing';

    const DIRECTION_DECREASING = 'decreasing';

    const DIRECTION_STABLE = 'stable';

    protected $fillable = [
        'trend_type',
        'category',
        'category_value',
        'period_start',
        'period_end',
        'trend_data',
        'growth_rate',
        'trend_direction',
        'analysis',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'trend_data' => 'array',
        'growth_rate' => 'decimal:2',
    ];

    // Scopes
    public function scopeByTrendType($query, string $type)
    {
        return $query->where('trend_type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeRecent($query, int $months = 12)
    {
        return $query->where('period_start', '>=', now()->subMonths($months));
    }

    public function scopeByDirection($query, string $direction)
    {
        return $query->where('trend_direction', $direction);
    }

    // Accessors
    public function getTrendStrengthAttribute()
    {
        if (! $this->growth_rate) {
            return 'weak';
        }

        $absRate = abs($this->growth_rate);

        return match (true) {
            $absRate >= 20 => 'very_strong',
            $absRate >= 10 => 'strong',
            $absRate >= 5 => 'moderate',
            $absRate >= 2 => 'weak',
            default => 'minimal'
        };
    }

    public function getTrendIconAttribute()
    {
        return match ($this->trend_direction) {
            self::DIRECTION_INCREASING => '📈',
            self::DIRECTION_DECREASING => '📉',
            self::DIRECTION_STABLE => '➡️',
            default => '❓'
        };
    }

    public function getFormattedGrowthRateAttribute()
    {
        if (! $this->growth_rate) {
            return 'N/A';
        }

        $sign = $this->growth_rate > 0 ? '+' : '';

        return $sign.$this->growth_rate.'%';
    }

    public function getPeriodLengthAttribute()
    {
        return $this->period_start->diffInMonths($this->period_end);
    }

    // Methods
    public function calculateTrendSignificance()
    {
        $dataPoints = count($this->trend_data ?? []);
        $periodLength = $this->period_length;
        $growthRate = abs($this->growth_rate ?? 0);

        // Simple significance calculation
        $significance = 0;

        if ($dataPoints >= 12) {
            $significance += 30;
        } // Sufficient data points
        if ($periodLength >= 12) {
            $significance += 30;
        } // Sufficient time period
        if ($growthRate >= 5) {
            $significance += 40;
        } // Meaningful change

        return min(100, $significance);
    }

    public function getDataPointsForChart()
    {
        if (! $this->trend_data) {
            return [];
        }

        // Format data for chart display
        $formatted = [];
        foreach ($this->trend_data as $date => $value) {
            $formatted[] = [
                'date' => $date,
                'value' => $value,
                'formatted_date' => \Carbon\Carbon::parse($date)->format('M Y'),
            ];
        }

        return $formatted;
    }

    // Static methods
    public static function getTrendTypes(): array
    {
        return [
            self::TREND_SALARY => 'Salary Trends',
            self::TREND_INDUSTRY_SHIFT => 'Industry Shifts',
            self::TREND_SKILL_DEMAND => 'Skill Demand',
            self::TREND_EMPLOYMENT_RATE => 'Employment Rate',
            self::TREND_JOB_SATISFACTION => 'Job Satisfaction',
        ];
    }

    public static function getTrendDirections(): array
    {
        return [
            self::DIRECTION_INCREASING => 'Increasing',
            self::DIRECTION_DECREASING => 'Decreasing',
            self::DIRECTION_STABLE => 'Stable',
        ];
    }
}
