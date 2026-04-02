<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Cohort Retention Request
 *
 * Validates requests for retrieving retention metrics for a cohort.
 */
class CohortRetentionRequest extends FormRequest
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
            'days_after' => [
                'sometimes',
                'array',
            ],
            'days_after.*' => [
                'integer',
                'min:1',
                'max:365',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'days_after.array' => 'Days after must be an array.',
            'days_after.*.integer' => 'Each day value must be an integer.',
            'days_after.*.min' => 'Day value must be at least 1.',
            'days_after.*.max' => 'Day value cannot exceed 365.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('days_after') || empty($this->input('days_after'))) {
            $this->merge([
                'days_after' => [7, 30, 90],
            ]);
        }
    }
}
