<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateBackupRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:full,incremental,database,files',
            'include_data' => 'boolean',
            'include_files' => 'boolean',
            'include_config' => 'boolean',
            'compress' => 'boolean',
            'encryption' => 'nullable|array',
            'encryption.enabled' => 'boolean',
            'encryption.algorithm' => 'nullable|string|in:AES-256,RSA-2048,RSA-4096',
            'schedule' => 'nullable|array',
            'schedule.frequency' => 'nullable|string|in:daily,weekly,monthly',
            'schedule.time' => 'nullable|date_format:H:i',
            'retention_days' => 'nullable|integer|min:1|max:3650',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Backup name is required.',
            'name.max' => 'Backup name cannot be longer than :max characters.',
            'type.required' => 'Backup type is required.',
            'type.in' => 'Selected backup type is invalid.',
            'encryption.algorithm.in' => 'Selected encryption algorithm is invalid.',
            'schedule.frequency.in' => 'Selected schedule frequency is invalid.',
            'schedule.time.date_format' => 'Schedule time must be in H:i format.',
            'retention_days.min' => 'Retention days must be at least :min.',
            'retention_days.max' => 'Retention days cannot be more than :max.',
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
            'name' => 'backup name',
            'description' => 'backup description',
            'type' => 'backup type',
            'include_data' => 'include data',
            'include_files' => 'include files',
            'include_config' => 'include configuration',
            'compress' => 'compression',
            'encryption.enabled' => 'encryption enabled',
            'encryption.algorithm' => 'encryption algorithm',
            'schedule.frequency' => 'schedule frequency',
            'schedule.time' => 'schedule time',
            'retention_days' => 'retention days',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (! $this->has('include_data')) {
            $this->merge(['include_data' => true]);
        }
        if (! $this->has('include_files')) {
            $this->merge(['include_files' => true]);
        }
        if (! $this->has('include_config')) {
            $this->merge(['include_config' => true]);
        }
        if (! $this->has('compress')) {
            $this->merge(['compress' => true]);
        }
        if ($this->has('encryption') && ! $this->input('encryption.enabled')) {
            $this->merge(['encryption' => null]);
        }
    }
}
