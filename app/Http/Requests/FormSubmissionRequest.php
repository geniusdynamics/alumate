<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Dynamic validation rules will be applied by FormBuilderService
        // This request just validates the basic structure and UTM parameters
        return [
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Extract UTM parameters from query string if not in request body
        $this->merge([
            'utm_source' => $this->utm_source ?? $this->query('utm_source'),
            'utm_medium' => $this->utm_medium ?? $this->query('utm_medium'),
            'utm_campaign' => $this->utm_campaign ?? $this->query('utm_campaign'),
        ]);
    }
}
