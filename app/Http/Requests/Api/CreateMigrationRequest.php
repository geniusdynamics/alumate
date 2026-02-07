<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateMigrationRequest extends FormRequest
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
            'type' => 'required|string|in:data,schema,content,configuration',
            'source_version' => 'required|string|max:50',
            'target_version' => 'required|string|max:50',
            'migration_data' => 'nullable|array',
            'rollback_enabled' => 'boolean',
            'dry_run' => 'boolean',
            'schedule' => 'nullable|array',
            'schedule.execute_at' => 'nullable|date',
            'dependencies' => 'nullable|array',
            'dependencies.*' => 'string|max:255',
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
            'name.required' => 'Migration name is required.',
            'name.max' => 'Migration name cannot be longer than :max characters.',
            'type.required' => 'Migration type is required.',
            'type.in' => 'Selected migration type is invalid.',
            'source_version.required' => 'Source version is required.',
            'source_version.max' => 'Source version cannot be longer than :max characters.',
            'target_version.required' => 'Target version is required.',
            'target_version.max' => 'Target version cannot be longer than :max characters.',
            'schedule.execute_at.date' => 'Schedule execute date must be a valid date.',
            'dependencies.*.max' => 'Each dependency cannot be longer than :max characters.',
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
            'name' => 'migration name',
            'description' => 'migration description',
            'type' => 'migration type',
            'source_version' => 'source version',
            'target_version' => 'target version',
            'migration_data' => 'migration data',
            'rollback_enabled' => 'rollback enabled',
            'dry_run' => 'dry run',
            'schedule.execute_at' => 'schedule execute date',
            'dependencies' => 'dependencies',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (! $this->has('rollback_enabled')) {
            $this->merge(['rollback_enabled' => true]);
        }
        if (! $this->has('dry_run')) {
            $this->merge(['dry_run' => false]);
        }
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate version format
            if ($this->has('source_version') && ! preg_match('/^\d+\.\d+\.\d+$/', $this->source_version)) {
                $validator->errors()->add('source_version', 'Source version must be in semantic versioning format (e.g., 1.0.0)');
            }

            if ($this->has('target_version') && ! preg_match('/^\d+\.\d+\.\d+$/', $this->target_version)) {
                $validator->errors()->add('target_version', 'Target version must be in semantic versioning format (e.g., 1.0.0)');
            }

            // Validate that target version is higher than source version
            if ($this->has('source_version') && $this->has('target_version')) {
                if (version_compare($this->source_version, $this->target_version) >= 0) {
                    $validator->errors()->add('target_version', 'Target version must be higher than source version');
                }
            }
        });
    }
}
