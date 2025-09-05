<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store A/B Test Request Validation
 */
class StoreAbTestRequest extends FormRequest
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
            'template_id' => 'required|exists:templates,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'variants' => 'required|array|min:2|max:10',
            'variants.*.id' => 'required|string|max:50',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.config' => 'nullable|array',
            'goal_metric' => 'nullable|string|in:conversion_rate,click_rate,time_on_page',
            'confidence_threshold' => 'nullable|numeric|min:0|max:1',
            'sample_size_per_variant' => 'nullable|integer|min:100|max:10000',
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
            'template_id.required' => 'Template ID is required',
            'template_id.exists' => 'Selected template does not exist',
            'name.required' => 'A/B test name is required',
            'variants.required' => 'At least 2 variants are required',
            'variants.min' => 'At least 2 variants are required',
            'variants.max' => 'Maximum 10 variants allowed',
            'goal_metric.in' => 'Invalid goal metric selected'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->has('goal_metric')) {
            $this->merge(['goal_metric' => 'conversion_rate']);
        }

        if (!$this->has('confidence_threshold')) {
            $this->merge(['confidence_threshold' => 0.95]);
        }

        if (!$this->has('sample_size_per_variant')) {
            $this->merge(['sample_size_per_variant' => 1000]);
        }
    }
}