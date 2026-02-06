<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Update Event Request
 *
 * Validates requests for updating custom event definitions with proper
 * authorization checks and field-level validation.
 */
class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Only authenticated users can update custom events.
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
        $definitionId = $this->route('id') ?? null;

        return [
            'name' => [
                'sometimes',
                'string',
                'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/',
                'max:100',
                Rule::unique('custom_event_definitions', 'name')
                    ->where('tenant_id', $this->getCurrentTenantId())
                    ->ignore($definitionId),
            ],
            'parameters_json' => [
                'sometimes',
                'array',
            ],
            'parameters_json.*.name' => [
                'required_with:parameters_json',
                'string',
                'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/',
            ],
            'parameters_json.*.type' => [
                'required_with:parameters_json',
                'string',
                'in:string,number,boolean',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],
            'status' => [
                'sometimes',
                'string',
                'in:active,inactive,archived',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.string' => 'Event name must be a string.',
            'name.regex' => 'Event name must be alphanumeric with underscores, starting with letter or underscore.',
            'name.max' => 'Event name cannot exceed 100 characters.',
            'name.unique' => 'An event with this name already exists for this tenant.',
            'parameters_json.array' => 'Parameters must be an array.',
            'parameters_json.*.name.required_with' => 'Parameter name is required when parameters are provided.',
            'parameters_json.*.name.regex' => 'Parameter name must be alphanumeric with underscores, starting with letter or underscore.',
            'parameters_json.*.type.required_with' => 'Parameter type is required when parameters are provided.',
            'parameters_json.*.type.in' => 'Parameter type must be string, number, or boolean.',
            'description.string' => 'Description must be a string.',
            'description.max' => 'Description cannot exceed 500 characters.',
            'status.in' => 'Status must be active, inactive, or archived.',
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
