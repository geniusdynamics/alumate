<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Update Insight Request
 *
 * Validates requests for updating insight feedback and effectiveness.
 */
class UpdateInsightRequest extends FormRequest
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
            'feedback' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'effectiveness_score' => [
                'nullable',
                'numeric',
                'min:0',
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
            'feedback.max' => 'Feedback cannot exceed 1000 characters.',
            'effectiveness_score.numeric' => 'Effectiveness score must be a number.',
            'effectiveness_score.min' => 'Effectiveness score must be at least 0.',
            'effectiveness_score.max' => 'Effectiveness score cannot exceed 100.',
        ];
    }
}