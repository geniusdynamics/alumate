<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SubmitVerificationRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $currentYear = now()->year;

        return [
            'institution_id' => [
                'required',
                'integer',
                'exists:institutions,id',
            ],
            'graduation_year' => [
                'required',
                'integer',
                'min:1900',
                'max:'.($currentYear + 1),
            ],
            'student_id' => [
                'nullable',
                'string',
                'max:50',
            ],
            'degree' => [
                'nullable',
                'string',
                'max:100',
            ],
            'major' => [
                'nullable',
                'string',
                'max:100',
            ],
            'supporting_documents' => [
                'nullable',
                'array',
            ],
            'supporting_documents.*.path' => [
                'required_with:supporting_documents',
                'string',
            ],
            'supporting_documents.*.type' => [
                'required_with:supporting_documents',
                'string',
                Rule::in(['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']),
            ],
            'supporting_documents.*.original_name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'institution_id.required' => 'Please select an institution.',
            'institution_id.exists' => 'The selected institution is invalid.',
            'graduation_year.required' => 'Graduation year is required.',
            'graduation_year.integer' => 'Graduation year must be a valid year.',
            'graduation_year.min' => 'Graduation year must be 1900 or later.',
            'graduation_year.max' => 'Graduation year cannot be more than 1 year in the future.',
            'student_id.max' => 'Student ID cannot exceed 50 characters.',
            'degree.max' => 'Degree cannot exceed 100 characters.',
            'major.max' => 'Major cannot exceed 100 characters.',
            'supporting_documents.*.type.in' => 'Invalid document type. Allowed types: PDF, JPG, PNG, DOC, DOCX.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'institution_id' => 'institution',
            'graduation_year' => 'graduation year',
            'student_id' => 'student ID',
            'degree' => 'degree',
            'major' => 'major',
            'supporting_documents' => 'supporting documents',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim string fields
        if ($this->has('student_id')) {
            $this->merge(['student_id' => trim($this->student_id)]);
        }
        if ($this->has('degree')) {
            $this->merge(['degree' => trim($this->degree)]);
        }
        if ($this->has('major')) {
            $this->merge(['major' => trim($this->major)]);
        }

        // Normalize graduation year
        if ($this->has('graduation_year')) {
            $this->merge(['graduation_year' => (int) $this->graduation_year]);
        }
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Additional validation can be performed here
        // For example, checking if graduation year makes sense with degree type
    }
}
