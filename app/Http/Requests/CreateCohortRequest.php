<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Create Cohort Request
 *
 * Validates requests for creating new cohorts with criteria-based filtering.
 */
class CreateCohortRequest extends FormRequest
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
                'required',
                'string',
                'max:100',
            ],
            'criteria' => [
                'required',
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
            'name.required' => 'Cohort name is required.',
            'name.string' => 'Cohort name must be a string.',
            'name.max' => 'Cohort name cannot exceed 100 characters.',
            'criteria.required' => 'Cohort criteria is required.',
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
     * Validate the cohort criteria.
     */
    private function validateCohortCriteria($validator): void
    {
        $criteria = $this->input('criteria', []);

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
