<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Matomo Track Event Request Validation
 */
class MatomoTrackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'event_data' => 'required|array',
            'event_data.event_type' => 'required|string|max:100',
            'event_data.module' => 'nullable|string|max:100',
            'event_data.engagement_score' => 'nullable|numeric|min:0|max:100',
            'event_data.user_id' => 'nullable|integer',
            'consent_token' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'event_data.required' => 'Event data is required',
            'event_data.event_type.required' => 'Event type is required',
            'consent_token.required' => 'Consent token is required',
        ];
    }
}