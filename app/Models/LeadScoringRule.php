<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadScoringRule extends Model
{
    protected $fillable = [
        'name',
        'description',
        'trigger_type',
        'conditions',
        'points',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Check if rule conditions are met for a lead
     */
    public function matches(Lead $lead, array $context = []): bool
    {
        if (! $this->is_active) {
            return false;
        }

        foreach ($this->conditions as $condition) {
            if (! $this->evaluateCondition($condition, $lead, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    private function evaluateCondition(array $condition, Lead $lead, array $context): bool
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];

        // Get the actual value from lead or context
        $actualValue = $this->getFieldValue($field, $lead, $context);

        return $this->compareValues($actualValue, $operator, $value);
    }

    /**
     * Get field value from lead or context
     */
    private function getFieldValue(string $field, Lead $lead, array $context)
    {
        // Check context first (for dynamic data like form submissions)
        if (isset($context[$field])) {
            return $context[$field];
        }

        // Check lead attributes
        if (isset($lead->$field)) {
            return $lead->$field;
        }

        // Check lead metadata
        if (isset($lead->form_data[$field])) {
            return $lead->form_data[$field];
        }

        if (isset($lead->behavioral_data[$field])) {
            return $lead->behavioral_data[$field];
        }

        return null;
    }

    /**
     * Compare values based on operator
     */
    private function compareValues($actual, string $operator, $expected): bool
    {
        switch ($operator) {
            case 'equals':
                return $actual == $expected;
            case 'not_equals':
                return $actual != $expected;
            case 'greater_than':
                return $actual > $expected;
            case 'less_than':
                return $actual < $expected;
            case 'greater_than_or_equal':
                return $actual >= $expected;
            case 'less_than_or_equal':
                return $actual <= $expected;
            case 'contains':
                return str_contains(strtolower($actual), strtolower($expected));
            case 'not_contains':
                return ! str_contains(strtolower($actual), strtolower($expected));
            case 'starts_with':
                return str_starts_with(strtolower($actual), strtolower($expected));
            case 'ends_with':
                return str_ends_with(strtolower($actual), strtolower($expected));
            case 'in':
                return in_array($actual, (array) $expected);
            case 'not_in':
                return ! in_array($actual, (array) $expected);
            default:
                return false;
        }
    }

    /**
     * Scope for active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for rules by trigger type
     */
    public function scopeByTrigger($query, string $triggerType)
    {
        return $query->where('trigger_type', $triggerType);
    }

    /**
     * Scope for rules ordered by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}
