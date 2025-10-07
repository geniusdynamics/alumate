<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Update Cohort Request
 *
 * Validates requests for updating existing cohorts.
 */
class UpdateCohortRequest extends FormRequest
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
            'name' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'criteria' => [
                'sometimes',
                'array',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.string' => 'Cohort name must be a string.',
            'name.max' => 'Cohort name cannot exceed 100 characters.',
            'criteria.array' => 'Cohort criteria must be an array.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateCohortCriteria($validator);
        });
    }

    /**
     * Validate the cohort criteria if provided.
     */
    private function validateCohortCriteria($validator): void
    {
        $criteria = $this->input('criteria');

        if ($criteria === null) {
            return;
        }

        if (empty($criteria)) {
            return;
        }

        $allowedKeys = ['grad_year', 'degree'];

        foreach ($criteria as $key => $value) {
            if (!in_array($key, $allowedKeys)) {
                $validator->errors()->add('criteria', "Invalid criteria key: {$key}. Allowed keys: " . implode(', ', $allowedKeys));
                continue;
            }

            if (empty($value)) {
                $validator->errors()->add('criteria', "Criteria value for {$key} cannot be empty.");
            }

            // Validate grad_year format
            if ($key === 'grad_year' && !is_numeric($value)) {
                $validator->errors()->add('criteria', "Graduation year must be numeric.");
            }

            // Validate degree format
            if ($key === 'degree' && !is_string($value)) {
                $validator->errors()->add('criteria', "Degree must be a string.");
            }
        }
    }
}