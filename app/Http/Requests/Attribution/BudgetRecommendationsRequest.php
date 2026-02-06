<?php

declare(strict_types=1);

namespace App\Http\Requests\Attribution;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Budget Recommendations Request Validation
 *
 * Validates requests for generating budget allocation recommendations.
 */
class BudgetRecommendationsRequest extends FormRequest
{
    /**
     * Maximum total budget allowed
     */
    private const MAX_BUDGET = 100000000; // 100 million

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() || Auth::guard('sanctum')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'total_budget' => 'nullable|numeric|min:0|max:' . self::MAX_BUDGET,
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.date' => 'Start date must be a valid date',
            'start_date.before_or_equal' => 'Start date must be before or equal to end date',
            'end_date.date' => 'End date must be a valid date',
            'end_date.after_or_equal' => 'End date must be after or equal to start date',
            'total_budget.numeric' => 'Total budget must be a number',
            'total_budget.min' => 'Total budget cannot be negative',
            'total_budget.max' => 'Total budget cannot exceed ' . number_format(self::MAX_BUDGET),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'start_date' => 'start date',
            'end_date' => 'end date',
            'total_budget' => 'total budget',
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
     * Validate that date range is consistent.
     */
    private function validateDateRange($validator): void
    {
        $hasStartDate = $this->has('start_date');
        $hasEndDate = $this->has('end_date');

        if ($hasStartDate && !$hasEndDate) {
            $validator->errors()->add('end_date', 'Both start_date and end_date must be provided together.');
        }

        if (!$hasStartDate && $hasEndDate) {
            $validator->errors()->add('start_date', 'Both start_date and end_date must be provided together.');
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default date range if not provided (last 90 days)
        if (!$this->has('start_date')) {
            $this->merge(['start_date' => now()->subDays(90)->toDateString()]);
        }

        if (!$this->has('end_date')) {
            $this->merge(['end_date' => now()->toDateString()]);
        }

        // Ensure total_budget is a float if provided as string
        if ($this->has('total_budget') && is_string($this->input('total_budget'))) {
            $this->merge(['total_budget' => (float) $this->input('total_budget')]);
        }
    }
}
