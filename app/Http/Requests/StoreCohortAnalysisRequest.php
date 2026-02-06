<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Store Cohort Analysis Request
 *
 * Validates requests for creating new cohorts with comprehensive criteria.
 */
class StoreCohortAnalysisRequest extends FormRequest
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
                'min:1',
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
            'criteria.acquisition_date' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'criteria.acquisition_source' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
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
            'criteria.min' => 'Cohort criteria must have at least one filter.',
            'criteria.grad_year.integer' => 'Graduation year must be a valid year.',
            'criteria.grad_year.min' => 'Graduation year must be after 1900.',
            'criteria.grad_year.max' => 'Graduation year must be before 2100.',
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
     * Validate the cohort criteria.
     */
    private function validateCriteria($validator): void
    {
        $criteria = $this->input('criteria', []);

        if (empty($criteria)) {
            return;
        }

        $allowedKeys = ['grad_year', 'degree', 'major', 'acquisition_date', 'acquisition_source', 'metadata'];

        foreach ($criteria as $key => $value) {
            if (!in_array($key, $allowedKeys)) {
                $validator->errors()->add(
                    'criteria',
                    "Invalid criteria key: {$key}. Allowed keys: " . implode(', ', $allowedKeys)
                );
            }
        }
    }
}
