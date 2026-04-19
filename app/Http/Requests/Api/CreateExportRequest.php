<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateExportRequest extends FormRequest
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
            'format' => 'required|string|in:json,xml,yaml,zip,html,pdf,markdown',
            'include_dependencies' => 'boolean',
            'include_assets' => 'boolean',
            'compress' => 'boolean',
            'encryption' => 'nullable|array',
            'encryption.enabled' => 'boolean',
            'encryption.algorithm' => 'nullable|string|in:AES-256,RSA-2048,RSA-4096',
            'metadata' => 'nullable|array',
            'metadata.name' => 'nullable|string|max:255',
            'metadata.description' => 'nullable|string|max:1000',
            'metadata.version' => 'nullable|string|max:50',
            'metadata.author' => 'nullable|string|max:255',
            'metadata.tags' => 'nullable|array',
            'metadata.tags.*' => 'string|max:50',
            'page_ids' => 'nullable|array',
            'page_ids.*' => 'string|uuid',
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
            'name.required' => 'Export name is required.',
            'name.max' => 'Export name cannot be longer than :max characters.',
            'format.required' => 'Export format is required.',
            'format.in' => 'Selected format is invalid.',
            'encryption.algorithm.in' => 'Selected encryption algorithm is invalid.',
            'metadata.name.max' => 'Metadata name cannot be longer than :max characters.',
            'metadata.description.max' => 'Metadata description cannot be longer than :max characters.',
            'metadata.version.max' => 'Metadata version cannot be longer than :max characters.',
            'metadata.author.max' => 'Metadata author cannot be longer than :max characters.',
            'metadata.tags.*.max' => 'Each tag cannot be longer than :max characters.',
            'page_ids.*.uuid' => 'Each page ID must be a valid UUID.',
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
            'name' => 'export name',
            'description' => 'export description',
            'format' => 'export format',
            'include_dependencies' => 'include dependencies',
            'include_assets' => 'include assets',
            'compress' => 'compression',
            'encryption.enabled' => 'encryption enabled',
            'encryption.algorithm' => 'encryption algorithm',
            'metadata.name' => 'metadata name',
            'metadata.description' => 'metadata description',
            'metadata.version' => 'metadata version',
            'metadata.author' => 'metadata author',
            'metadata.tags' => 'metadata tags',
            'page_ids' => 'page IDs',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (! $this->has('include_dependencies')) {
            $this->merge(['include_dependencies' => false]);
        }
        if (! $this->has('include_assets')) {
            $this->merge(['include_assets' => false]);
        }
        if (! $this->has('compress')) {
            $this->merge(['compress' => false]);
        }
        if ($this->has('encryption') && ! $this->input('encryption.enabled')) {
            $this->merge(['encryption' => null]);
        }
    }
}
