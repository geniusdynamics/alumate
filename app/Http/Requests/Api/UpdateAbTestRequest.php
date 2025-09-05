<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update A/B Test Request Validation
 */
class UpdateAbTestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add authorization logic as needed
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'variants' => 'sometimes|required|array|min:2|max:10',
            'variants.*.id' => 'required|string|max:50',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.config' => 'nullable|array',
            'goal_metric' => 'sometimes|required|string|in:conversion_rate,click_rate,time_on_page',
            'confidence_threshold' => 'sometimes|required|numeric|min:0|max:1',
            'sample_size_per_variant' => 'sometimes|required|integer|min:100|max:10000',
            'traffic_distribution' => 'nullable|array',
            'traffic_distribution.*' => 'numeric|min:0|max:100'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A/B test name is required',
            'variants.required' => 'At least 2 variants are required',
            'variants.min' => 'At least 2 variants are required',
            'variants.max' => 'Maximum 10 variants allowed',
            'goal_metric.in' => 'Invalid goal metric selected'
        ];
    }
}