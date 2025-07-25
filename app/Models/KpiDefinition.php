<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'description',
        'category',
        'calculation_method',
        'calculation_config',
        'target_type',
        'target_value',
        'warning_threshold',
        'is_active',
    ];

    protected $casts = [
        'calculation_config' => 'array',
        'target_value' => 'decimal:2',
        'warning_threshold' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function values(): HasMany
    {
        return $this->hasMany(KpiValue::class);
    }

    public function latestValue()
    {
        return $this->hasOne(KpiValue::class)->latest('measurement_date');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Helper methods
    public function getLatestValue()
    {
        return $this->latestValue?->value;
    }

    public function getValueForDate($date)
    {
        return $this->values()
            ->where('measurement_date', $date)
            ->first()?->value;
    }

    public function getTrendData($days = 30)
    {
        $startDate = now()->subDays($days);
        
        return $this->values()
            ->where('measurement_date', '>=', $startDate)
            ->orderBy('measurement_date')
            ->get();
    }

    public function isAboveTarget()
    {
        $latestValue = $this->getLatestValue();
        
        if ($latestValue === null || $this->target_value === null) {
            return null;
        }

        return match($this->target_type) {
            'minimum' => $latestValue >= $this->target_value,
            'maximum' => $latestValue <= $this->target_value,
            'range' => $this->isInTargetRange($latestValue),
            default => null,
        };
    }

    public function isInWarningZone()
    {
        $latestValue = $this->getLatestValue();
        
        if ($latestValue === null || $this->warning_threshold === null) {
            return false;
        }

        return match($this->target_type) {
            'minimum' => $latestValue < $this->warning_threshold,
            'maximum' => $latestValue > $this->warning_threshold,
            default => false,
        };
    }

    public function getStatus()
    {
        if ($this->isInWarningZone()) {
            return 'warning';
        }

        $aboveTarget = $this->isAboveTarget();
        
        if ($aboveTarget === null) {
            return 'unknown';
        }

        return $aboveTarget ? 'good' : 'poor';
    }

    public function getStatusColor()
    {
        return match($this->getStatus()) {
            'good' => 'green',
            'warning' => 'yellow',
            'poor' => 'red',
            default => 'gray',
        };
    }

    public function calculateValue($date = null)
    {
        $date = $date ?? now()->toDateString();
        $config = $this->calculation_config;

        return match($this->calculation_method) {
            'percentage' => $this->calculatePercentage($config, $date),
            'count' => $this->calculateCount($config, $date),
            'average' => $this->calculateAverage($config, $date),
            'ratio' => $this->calculateRatio($config, $date),
            'sum' => $this->calculateSum($config, $date),
            default => 0,
        };
    }

    private function calculatePercentage($config, $date)
    {
        $numerator = $this->executeQuery($config['numerator'], $date);
        $denominator = $this->executeQuery($config['denominator'], $date);

        if ($denominator == 0) {
            return 0;
        }

        return ($numerator / $denominator) * 100;
    }

    private function calculateCount($config, $date)
    {
        return $this->executeQuery($config['query'], $date);
    }

    private function calculateAverage($config, $date)
    {
        $values = $this->executeQuery($config['query'], $date, true);
        
        if (empty($values)) {
            return 0;
        }

        return array_sum($values) / count($values);
    }

    private function calculateRatio($config, $date)
    {
        $numerator = $this->executeQuery($config['numerator'], $date);
        $denominator = $this->executeQuery($config['denominator'], $date);

        if ($denominator == 0) {
            return 0;
        }

        return $numerator / $denominator;
    }

    private function calculateSum($config, $date)
    {
        $values = $this->executeQuery($config['query'], $date, true);
        
        return array_sum($values);
    }

    private function executeQuery($queryConfig, $date, $returnArray = false)
    {
        // This is a simplified implementation
        // In a real system, you would have a more sophisticated query builder
        
        $model = app($queryConfig['model']);
        $query = $model::query();

        // Apply date filters
        if (isset($queryConfig['date_field'])) {
            $query->whereDate($queryConfig['date_field'], '<=', $date);
        }

        // Apply additional filters
        if (isset($queryConfig['filters'])) {
            foreach ($queryConfig['filters'] as $filter) {
                $query->where($filter['field'], $filter['operator'], $filter['value']);
            }
        }

        // Apply aggregation
        if ($returnArray) {
            return $query->pluck($queryConfig['field'] ?? 'id')->toArray();
        }

        return $query->count();
    }

    private function isInTargetRange($value)
    {
        $config = $this->calculation_config['target_range'] ?? [];
        $min = $config['min'] ?? null;
        $max = $config['max'] ?? null;

        if ($min !== null && $value < $min) {
            return false;
        }

        if ($max !== null && $value > $max) {
            return false;
        }

        return true;
    }

    public static function getDefaultKpis()
    {
        return [
            [
                'name' => 'Employment Rate',
                'key' => 'employment_rate',
                'description' => 'Percentage of graduates who are currently employed',
                'category' => 'employment',
                'calculation_method' => 'percentage',
                'calculation_config' => [
                    'numerator' => [
                        'model' => 'App\\Models\\Graduate',
                        'filters' => [
                            ['field' => 'employment_status->status', 'operator' => '=', 'value' => 'employed']
                        ]
                    ],
                    'denominator' => [
                        'model' => 'App\\Models\\Graduate',
                        'filters' => []
                    ]
                ],
                'target_type' => 'minimum',
                'target_value' => 80.0,
                'warning_threshold' => 70.0,
            ],
            [
                'name' => 'Job Placement Rate',
                'key' => 'job_placement_rate',
                'description' => 'Percentage of job applications that result in employment',
                'category' => 'employment',
                'calculation_method' => 'percentage',
                'calculation_config' => [
                    'numerator' => [
                        'model' => 'App\\Models\\JobApplication',
                        'filters' => [
                            ['field' => 'status', 'operator' => '=', 'value' => 'hired']
                        ]
                    ],
                    'denominator' => [
                        'model' => 'App\\Models\\JobApplication',
                        'filters' => []
                    ]
                ],
                'target_type' => 'minimum',
                'target_value' => 25.0,
                'warning_threshold' => 15.0,
            ],
            [
                'name' => 'Average Time to Employment',
                'key' => 'avg_time_to_employment',
                'description' => 'Average number of days from graduation to first employment',
                'category' => 'employment',
                'calculation_method' => 'average',
                'calculation_config' => [
                    'query' => [
                        'model' => 'App\\Models\\Graduate',
                        'field' => 'days_to_employment',
                        'filters' => [
                            ['field' => 'employment_status->status', 'operator' => '=', 'value' => 'employed']
                        ]
                    ]
                ],
                'target_type' => 'maximum',
                'target_value' => 90.0,
                'warning_threshold' => 120.0,
            ],
        ];
    }
}