<?php

declare(strict_types=1);

namespace App\Http\Requests\Attribution;

use App\Services\Analytics\AttributionTrackingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Store Touchpoint Request Validation
 *
 * Validates incoming attribution touchpoint tracking requests.
 */
class StoreTouchpointRequest extends FormRequest
{
    /**
     * Valid event types for touchpoints
     */
    private const EVENT_TYPES = [
        'page_view',
        'click',
        'form_submit',
        'signup',
        'login',
        'purchase',
        'subscription',
        'download',
        'share',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow authenticated users or API requests with proper context
        return Auth::check() || Auth::guard('sanctum')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'sometimes|required|integer|exists:users,id',
            'source' => 'required|string|max:255',
            'session_id' => 'nullable|string|max:255',
            'event_type' => 'sometimes|string|in:' . implode(',', self::EVENT_TYPES),
            'medium' => 'nullable|string|max:255',
            'campaign' => 'nullable|string|max:255',
            'value' => 'sometimes|numeric|min:0',
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
            'user_id.required' => 'User ID is required when not authenticated',
            'user_id.exists' => 'The specified user does not exist',
            'source.required' => 'Source (channel) is required',
            'source.max' => 'Source cannot exceed 255 characters',
            'event_type.in' => 'Event type must be one of: ' . implode(', ', self::EVENT_TYPES),
            'value.numeric' => 'Value must be a number',
            'value.min' => 'Value cannot be negative',
            'timestamp.date' => 'Timestamp must be a valid date',
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
            'source' => 'marketing channel',
            'session_id' => 'session ID',
            'event_type' => 'event type',
            'medium' => 'traffic medium',
            'campaign' => 'campaign name',
            'value' => 'touchpoint value',
            'timestamp' => 'event timestamp',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default event type if not provided
        if (!$this->has('event_type')) {
            $this->merge(['event_type' => 'page_view']);
        }

        // Set default value if not provided
        if (!$this->has('value')) {
            $this->merge(['value' => 0]);
        }
    }
}
