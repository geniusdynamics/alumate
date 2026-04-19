<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Cohort Trends Request
 *
 * Validates requests for retrieving trend analysis for a cohort.
 */
class CohortTrendsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'period' => [
                'sometimes',
                'string',
                'in:day,week,month',
            ],
            'periods' => [
                'sometimes',
                'integer',
                'min:1',
                'max:52',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'period.string' => 'Period must be a string.',
            'period.in' => 'Period must be one of: day, week, month.',
            'periods.integer' => 'Periods must be an integer.',
            'periods.min' => 'Periods must be at least 1.',
            'periods.max' => 'Periods cannot exceed 52.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('period')) {
            $this->merge([
                'period' => 'week',
            ]);
        }

        if (! $this->has('periods')) {
            $this->merge([
                'periods' => 12,
            ]);
        }
    }
}
