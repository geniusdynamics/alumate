<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Update Learning Progress Request
 *
 * Validates requests for updating learning progress with manual overrides.
 */
class UpdateLearningProgressRequest extends FormRequest
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
            'course_id' => [
                'required',
                'uuid',
            ],
            'modules_completed' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'total_score' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'certified' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'course_id.required' => 'Course ID is required.',
            'course_id.uuid' => 'Course ID must be a valid UUID.',
            'modules_completed.integer' => 'Modules completed must be a valid integer.',
            'modules_completed.min' => 'Modules completed must be at least 0.',
            'total_score.numeric' => 'Total score must be a valid number.',
            'total_score.min' => 'Total score must be at least 0.',
            'total_score.max' => 'Total score must not exceed 100.',
            'certified.boolean' => 'Certified must be a boolean value.',
        ];
    }
}