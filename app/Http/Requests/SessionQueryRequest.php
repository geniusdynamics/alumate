<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for session recording queries
 *
 * Validates filtering and pagination parameters for session recording queries
 * with support for date ranges, user segments, and device types.
 */
class SessionQueryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'date_range' => [
                'nullable',
                'array',
            ],
            'date_range.from' => [
                'nullable',
                'date',
                'before_or_equal:date_range.to',
                'after:2020-01-01', // Reasonable minimum date
            ],
            'date_range.to' => [
                'nullable',
                'date',
                'before_or_equal:tomorrow', // Allow up to tomorrow for timezone differences
                'after:date_range.from',
            ],
            'user_segment' => [
                'nullable',
                'string',
                'in:alumni,employer,student',
            ],
            'device_type' => [
                'nullable',
                'string',
                'in:desktop,mobile,tablet',
            ],
            'privacy_masked' => [
                'nullable',
                'boolean',
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
                'max:10000', // Prevent excessive pagination
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:1000', // Reasonable maximum per page
            ],
            'sort_by' => [
                'nullable',
                'string',
                'in:created_at,updated_at,duration_seconds,page_views,interactions_count',
            ],
            'sort_direction' => [
                'nullable',
                'string',
                'in:asc,desc',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'date_range.from.after' => 'Start date must be after January 1, 2020.',
            'date_range.from.before_or_equal' => 'Start date must be before or equal to end date.',
            'date_range.to.before_or_equal' => 'End date cannot be in the future.',
            'date_range.to.after' => 'End date must be after start date.',
            'user_segment.in' => 'User segment must be one of: alumni, employer, student.',
            'device_type.in' => 'Device type must be one of: desktop, mobile, tablet.',
            'page.min' => 'Page must be at least 1.',
            'page.max' => 'Page number is too high.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page cannot exceed 1000.',
            'sort_by.in' => 'Invalid sort field.',
            'sort_direction.in' => 'Sort direction must be asc or desc.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'date_range' => 'date range',
            'date_range.from' => 'start date',
            'date_range.to' => 'end date',
            'user_segment' => 'user segment',
            'device_type' => 'device type',
            'privacy_masked' => 'privacy masked filter',
            'page' => 'page number',
            'per_page' => 'items per page',
            'sort_by' => 'sort field',
            'sort_direction' => 'sort direction',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $defaults = [
            'page' => 1,
            'per_page' => 50,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];

        foreach ($defaults as $field => $default) {
            if (! $this->has($field)) {
                $this->merge([$field => $default]);
            }
        }

        // Normalize date range
        if ($this->has('date_range')) {
            $dateRange = $this->input('date_range', []);

            // Convert date strings to proper format if needed
            if (isset($dateRange['from'])) {
                $dateRange['from'] = $this->normalizeDate($dateRange['from']);
            }

            if (isset($dateRange['to'])) {
                $dateRange['to'] = $this->normalizeDate($dateRange['to']);
            }

            $this->merge(['date_range' => $dateRange]);
        }
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Validate date range logic
        $this->validateDateRangeLogic();

        // Validate pagination limits
        $this->validatePaginationLimits();
    }

    /**
     * Validate date range business logic
     */
    private function validateDateRangeLogic(): void
    {
        $dateRange = $this->input('date_range', []);

        if (! empty($dateRange['from']) && ! empty($dateRange['to'])) {
            $from = \Carbon\Carbon::parse($dateRange['from']);
            $to = \Carbon\Carbon::parse($dateRange['to']);

            // Prevent queries spanning more than 1 year for performance
            if ($from->diffInDays($to) > 365) {
                $this->addFailure('date_range', 'Date range cannot exceed 1 year.');
            }

            // Prevent queries for dates too far in the past
            if ($from->isBefore(\Carbon\Carbon::now()->subYears(2))) {
                $this->addFailure('date_range', 'Cannot query data older than 2 years.');
            }
        }
    }

    /**
     * Validate pagination limits based on filters
     */
    private function validatePaginationLimits(): void
    {
        $perPage = $this->input('per_page', 50);
        $page = $this->input('page', 1);

        // If no filters are applied, limit pagination to prevent large result sets
        $hasFilters = $this->has('date_range') ||
                     $this->has('user_segment') ||
                     $this->has('device_type') ||
                     $this->has('privacy_masked');

        if (! $hasFilters && $perPage > 100) {
            $this->addFailure('per_page', 'Per page limit is 100 when no filters are applied.');
        }

        // Prevent deep pagination
        if ($page > 1000) {
            $this->addFailure('page', 'Page number is too high. Consider using filters.');
        }
    }

    /**
     * Normalize date string to ensure consistent format
     */
    private function normalizeDate(string $date): string
    {
        try {
            return \Carbon\Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            // If parsing fails, return original (validation will catch it)
            return $date;
        }
    }

    /**
     * Add a validation failure
     */
    private function addFailure(string $key, string $message): void
    {
        $validator = \Illuminate\Support\Facades\Validator::make([], []);
        $validator->errors()->add($key, $message);
        throw new \Illuminate\Validation\ValidationException($validator);
    }

    /**
     * Get the validated data with defaults applied
     */
    public function validatedWithDefaults(): array
    {
        $validated = $this->validated();

        // Ensure defaults are included
        return array_merge([
            'page' => 1,
            'per_page' => 50,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ], $validated);
    }
}
