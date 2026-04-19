<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Define Event Request
 *
 * Validates requests for defining new custom events with JSON schema validation
 * and proper authorization checks.
 */
class DefineEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Only authenticated users can define custom events.
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
            'name' => [
                'required',
                'string',
                'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/',
                'max:100',
                Rule::unique('custom_event_definitions', 'name')
                    ->where('tenant_id', $this->getCurrentTenantId()),
            ],
            'parameters_json' => [
                'required',
                'array',
            ],
            'parameters_json.*.name' => [
                'required',
                'string',
                'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/',
            ],
            'parameters_json.*.type' => [
                'required',
                'string',
                'in:string,number,boolean',
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Event name is required.',
            'name.regex' => 'Event name must be alphanumeric with underscores, starting with letter or underscore.',
            'name.max' => 'Event name cannot exceed 100 characters.',
            'name.unique' => 'An event with this name already exists for this tenant.',
            'parameters_json.required' => 'Parameters are required.',
            'parameters_json.*.name.required' => 'Parameter name is required.',
            'parameters_json.*.name.regex' => 'Parameter name must be alphanumeric with underscores, starting with letter or underscore.',
            'parameters_json.*.type.required' => 'Parameter type is required.',
            'parameters_json.*.type.in' => 'Parameter type must be string, number, or boolean.',
            'description.max' => 'Description cannot exceed 500 characters.',
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
