<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Funnel Analysis Request
 *
 * Validates requests for funnel analysis with proper validation
 * of event sequences and date ranges.
 */
class FunnelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Only authenticated users can perform funnel analysis.
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
            'event_sequence' => [
                'required',
                'array',
                'min:2',
            ],
            'event_sequence.*' => [
                'required',
                'integer',
                Rule::exists('custom_event_definitions', 'id')
                    ->where('tenant_id', $this->getCurrentTenantId())
                    ->where('status', 'active'),
            ],
            'start_date' => [
                'nullable',
                'date',
                'before_or_equal:end_date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'user_id' => [
                'nullable',
                'integer',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'event_sequence.required' => 'Event sequence is required.',
            'event_sequence.array' => 'Event sequence must be an array.',
            'event_sequence.min' => 'Funnel requires at least 2 events.',
            'event_sequence.*.required' => 'Each event in the sequence is required.',
            'event_sequence.*.integer' => 'Event IDs must be integers.',
            'event_sequence.*.exists' => 'One or more event definitions do not exist or are inactive.',
            'start_date.date' => 'Start date must be a valid date.',
            'start_date.before_or_equal' => 'Start date must be before or equal to end date.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'user_id.integer' => 'User ID must be an integer.',
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
