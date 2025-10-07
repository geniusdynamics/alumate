<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Compare Cohorts Request
 *
 * Validates requests for comparing multiple cohorts with statistical analysis.
 */
class CompareCohortsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Implement proper role-based authorization
        // For now, allow authenticated users
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cohort_ids' => [
                'required',
                'array',
                'min:2',
                'max:5',
            ],
            'cohort_ids.*' => [
                'required',
                'string',
                'exists:cohorts,id',
            ],
            'metrics' => [
                'nullable',
                'array',
            ],
            'metrics.*' => [
                'string',
                'in:retention,engagement,conversion',
            ],
            'time_range' => [
                'nullable',
                'array',
            ],
            'time_range.days' => [
                'nullable',
                'integer',
                'min:1',
                'max:365',
            ],
            'time_range.start_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'time_range.end_date' => [
                'nullable',
                'date',
                'after_or_equal:time_range.start_date',
                'before_or_equal:today',
            ],
            'include_statistical_significance' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'cohort_ids.required' => 'At least two cohort IDs are required for comparison.',
            'cohort_ids.min' => 'At least two cohorts must be selected for comparison.',
            'cohort_ids.max' => 'Maximum of 5 cohorts can be compared at once.',
            'cohort_ids.*.exists' => 'One or more selected cohorts do not exist.',
            'metrics.*.in' => 'Invalid metric selected. Valid options are: retention, engagement, conversion.',
            'time_range.days.min' => 'Time range must be at least 1 day.',
            'time_range.days.max' => 'Time range cannot exceed 365 days.',
            'time_range.start_date.before_or_equal' => 'Start date cannot be in the future.',
            'time_range.end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'time_range.end_date.before_or_equal' => 'End date cannot be in the future.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateTimeRange($validator);
            $this->validateCohortAccess($validator);
        });
    }

    /**
     * Validate time range configuration.
     */
    private function validateTimeRange($validator): void
    {
        $timeRange = $this->input('time_range', []);

        if (empty($timeRange)) {
            return;
        }

        $hasDays = isset($timeRange['days']);
        $hasStartDate = isset($timeRange['start_date']);
        $hasEndDate = isset($timeRange['end_date']);

        // If any time range parameter is provided, ensure consistency
        if ($hasDays && ($hasStartDate || $hasEndDate)) {
            $validator->errors()->add('time_range', 'Cannot specify both days and date range. Choose either days or start_date/end_date.');
        }

        if (($hasStartDate && !$hasEndDate) || (!$hasStartDate && $hasEndDate)) {
            $validator->errors()->add('time_range', 'Both start_date and end_date must be provided together.');
        }
    }

    /**
     * Validate that user has access to all requested cohorts.
     */
    private function validateCohortAccess($validator): void
    {
        $cohortIds = $this->input('cohort_ids', []);

        if (empty($cohortIds)) {
            return;
        }

        // TODO: Implement tenant-based cohort access validation
        // For now, assume all cohorts are accessible
        // This should check that all cohorts belong to the current tenant
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default metrics if not provided
        if (!$this->has('metrics') || empty($this->input('metrics'))) {
            $this->merge([
                'metrics' => ['retention', 'engagement'],
            ]);
        }

        // Set default statistical significance flag
        if (!$this->has('include_statistical_significance')) {
            $this->merge([
                'include_statistical_significance' => true,
            ]);
        }
    }

    /**
     * Get the validated data with defaults applied.
     */
    public function validatedWithDefaults(): array
    {
        $validated = $this->validated();

        // Apply default time range if not specified
        if (!isset($validated['time_range']) || empty($validated['time_range'])) {
            $validated['time_range'] = [
                'days' => 30, // Default to 30 days
            ];
        }

        return $validated;
    }
}
