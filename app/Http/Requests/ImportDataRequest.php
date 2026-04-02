<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportDataRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'type' => 'nullable|string|in:courses,alumni',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to import.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'The file must be a CSV or text file.',
            'file.max' => 'The file size must not exceed 10MB.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('file')) {
                $file = $this->file('file');

                // Check if file is readable
                if (! $file->isReadable()) {
                    $validator->errors()->add('file', 'The uploaded file cannot be read.');
                }

                // Additional MIME type validation
                $validMimeTypes = ['text/csv', 'text/plain', 'application/csv', 'text/comma-separated-values'];
                $mimeType = $file->getMimeType();

                if (! in_array($mimeType, $validMimeTypes) && ! str_starts_with($mimeType, 'text/')) {
                    $validator->errors()->add('file', 'The file must be a valid CSV file.');
                }
            }
        });
    }
}
