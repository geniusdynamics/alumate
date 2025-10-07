<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Track Recommendation Request
 *
 * Validates requests for tracking recommendation implementation and feedback.
 */
class TrackRecommendationRequest extends FormRequest
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
            'rec_id' => [
                'required',
                'string',
                'uuid',
            ],
            'status' => [
                'required',
                'string',
                'in:implemented,dismissed',
            ],
            'feedback' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'rec_id.required' => 'Recommendation ID is required.',
            'rec_id.uuid' => 'Recommendation ID must be a valid UUID.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be either implemented or dismissed.',
            'feedback.max' => 'Feedback cannot exceed 1000 characters.',
        ];
    }
}