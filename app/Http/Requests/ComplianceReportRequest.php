<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Compliance Report Request
 *
 * Validates requests for generating compliance audit reports.
 */
class ComplianceReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Users can only request reports for their own data.
     */
    public function authorize(): bool
    {
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
            'date_range' => [
                'nullable',
                'array',
            ],
            'date_range.start' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'date_range.end' => [
                'nullable',
                'date',
                'after_or_equal:date_range.start',
                'before_or_equal:today',
            ],
            'include_deleted' => [
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
            'date_range.array' => 'Date range must be an array.',
            'date_range.start.date' => 'Start date must be a valid date.',
            'date_range.start.before_or_equal' => 'Start date cannot be in the future.',
            'date_range.end.date' => 'End date must be a valid date.',
            'date_range.end.after_or_equal' => 'End date must be after or equal to start date.',
            'date_range.end.before_or_equal' => 'End date cannot be in the future.',
            'include_deleted.boolean' => 'Include deleted flag must be true or false.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateDateRange($validator);
        });
    }

    /**
     * Validate the date range for reasonable bounds.
     */
    private function validateDateRange($validator): void
    {
        $dateRange = $this->input('date_range', []);

        if (!empty($dateRange) && isset($dateRange['start']) && isset($dateRange['end'])) {
            $startDate = strtotime($dateRange['start']);
            $endDate = strtotime($dateRange['end']);

            if ($startDate && $endDate) {
                // Check if date range is not too large (max 2 years)
                $maxRangeDays = 365 * 2;
                $rangeDays = ($endDate - $startDate) / (60 * 60 * 24);

                if ($rangeDays > $maxRangeDays) {
                    $validator->errors()->add('date_range', 'Date range cannot exceed 2 years.');
                }

                // Check if date range is not too far in the past (max 5 years ago)
                $fiveYearsAgo = strtotime('-5 years');
                if ($startDate < $fiveYearsAgo) {
                    $validator->errors()->add('date_range', 'Start date cannot be more than 5 years ago.');
                }
            }
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure date_range is properly structured
        $dateRange = $this->input('date_range', []);

        if (is_array($dateRange)) {
            $this->merge([
                'date_range' => $dateRange,
            ]);
        }

        // Set default for include_deleted
        if (!$this->has('include_deleted')) {
            $this->merge([
                'include_deleted' => false,
            ]);
        }
    }
}