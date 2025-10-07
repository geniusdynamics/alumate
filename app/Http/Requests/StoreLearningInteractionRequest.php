<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Store Learning Interaction Request
 *
 * Validates requests for tracking learning interactions.
 */
class StoreLearningInteractionRequest extends FormRequest
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
            'module_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'duration' => [
                'required',
                'integer',
                'min:0',
            ],
            'score' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'interaction_type' => [
                'nullable',
                'string',
                'in:view,completion,quiz,assessment',
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
            'module_id.integer' => 'Module ID must be a valid integer.',
            'module_id.min' => 'Module ID must be at least 1.',
            'duration.required' => 'Duration is required.',
            'duration.integer' => 'Duration must be a valid integer.',
            'duration.min' => 'Duration must be at least 0.',
            'score.numeric' => 'Score must be a valid number.',
            'score.min' => 'Score must be at least 0.',
            'score.max' => 'Score must not exceed 100.',
            'interaction_type.in' => 'Interaction type must be one of: view, completion, quiz, assessment.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default interaction type if not provided
        if (!$this->has('interaction_type') || !$this->interaction_type) {
            $this->merge([
                'interaction_type' => 'view',
            ]);
        }
    }
}