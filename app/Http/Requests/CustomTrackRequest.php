<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Custom Track Request
 *
 * Validates requests for tracking custom events with proper validation
 * and authorization checks.
 */
class CustomTrackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Only authenticated users can track custom events.
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
            'definition_id' => [
                'required',
                'integer',
                Rule::exists('custom_event_definitions', 'id')
                    ->where('tenant_id', $this->getCurrentTenantId())
                    ->where('status', 'active'),
            ],
            'data_json' => [
                'required',
                'array',
            ],
            'user_id' => [
                'required',
                'integer',
            ],
            'timestamp' => [
                'nullable',
                'date',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'definition_id.required' => 'Definition ID is required.',
            'definition_id.integer' => 'Definition ID must be an integer.',
            'definition_id.exists' => 'The specified event definition does not exist or is inactive.',
            'data_json.required' => 'Event data is required.',
            'data_json.array' => 'Event data must be an object.',
            'user_id.required' => 'User ID is required.',
            'user_id.integer' => 'User ID must be an integer.',
            'timestamp.date' => 'Timestamp must be a valid date.',
        ];
    }

    /**
     * Get the current tenant ID.
     */
    private function getCurrentTenantId(): ?int
    {
        return session('tenant_id') ? (int) session('tenant_id') : null;
    }
}