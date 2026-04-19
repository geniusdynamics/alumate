<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Track Touch Request Validation
 *
 * Validates incoming attribution touch tracking requests.
 */
class TrackTouchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'sometimes|integer|exists:users,id',
            'session_id' => 'nullable|string|max:255',
            'event_type' => 'required|string|in:page_view,click,form_submit,purchase,signup,login',
            'source' => 'nullable|string|max:255',
            'medium' => 'nullable|string|max:255',
            'campaign' => 'nullable|string|max:255',
            'value' => 'required|numeric|min:0',
            'timestamp' => 'sometimes|date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'event_type.required' => 'Event type is required',
            'event_type.in' => 'Event type must be one of: page_view, click, form_submit, purchase, signup, login',
            'value.required' => 'Value is required',
            'value.numeric' => 'Value must be a number',
            'value.min' => 'Value must be greater than or equal to 0',
            'user_id.exists' => 'The specified user does not exist',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'user',
            'event_type' => 'event type',
            'session_id' => 'session ID',
            'source' => 'traffic source',
            'medium' => 'traffic medium',
            'campaign' => 'campaign name',
            'value' => 'touch value',
            'timestamp' => 'event timestamp',
        ];
    }
}
