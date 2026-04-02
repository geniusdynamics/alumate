<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Update Cohort Analysis Request
 *
 * Validates requests for updating existing cohorts.
 */
class UpdateCohortAnalysisRequest extends FormRequest
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
            'criteria.grad_year' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1900',
                'max:2100',
            ],
            'criteria.degree' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'criteria.major' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
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
            $this->validateCriteria($validator);
        });
    }

    /**
     * Validate the cohort criteria if provided.
     */
    private function validateCriteria($validator): void
    {
        $criteria = $this->input('criteria');

        if ($criteria === null) {
            return;
        }

        if (empty($criteria)) {
            $validator->errors()->add('criteria', 'Criteria cannot be empty when provided.');

            return;
        }

        $allowedKeys = ['grad_year', 'degree', 'major', 'acquisition_date', 'acquisition_source', 'metadata'];

        foreach ($criteria as $key => $value) {
            if (! in_array($key, $allowedKeys)) {
                $validator->errors()->add(
                    'criteria',
                    "Invalid criteria key: {$key}. Allowed keys: ".implode(', ', $allowedKeys)
                );
            }
        }
    }
}
