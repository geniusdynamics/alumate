<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request class for validating insights generation requests
 *
 * This request validates the parameters needed for generating analytics insights,
 * including period, metrics filter, and other options.
 */
class GenerateInsightsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => 'nullable|string|in:last_7_days,last_14_days,last_30_days,last_90_days,last_365_days,custom',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'metrics_filter' => 'nullable|array',
            'metrics_filter.*' => 'string',
            'queue' => 'nullable|boolean',
            'include_recommendations' => 'nullable|boolean',
            'include_anomalies' => 'nullable|boolean',
            'include_trends' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'period.in' => 'The selected period is invalid. Valid periods are: last_7_days, last_14_days, last_30_days, last_90_days, last_365_days, custom.',
            'end_date.after_or_equal' => 'The end date must be a date after or equal to the start date.',
        ];
    }
}
