<?php

declare(strict_types=1);

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
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'variants' => 'sometimes|required|array|min:2',
            'variants.*.name' => 'required|string|max:100',
            'variants.*.weight' => 'required|numeric|min:0|max:100',
            'status' => 'sometimes|required|in:active,inactive'
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
            'variants.*.name.required' => 'Variant name is required',
            'variants.*.weight.required' => 'Variant weight is required',
            'status.in' => 'Invalid status value'
        ];
    }
}