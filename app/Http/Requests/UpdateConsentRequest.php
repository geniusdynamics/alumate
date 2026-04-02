<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Update Consent Request
 *
 * Validates requests for updating user consent preferences with granular categories.
 */
class UpdateConsentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Users can only update their own consent preferences.
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
            'preferences' => [
                'required',
                'array',
            ],
            'preferences.*' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'preferences.required' => 'Consent preferences are required.',
            'preferences.array' => 'Consent preferences must be an array.',
            'preferences.*.required' => 'Each consent category must have a boolean value.',
            'preferences.*.boolean' => 'Consent values must be true or false.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateConsentCategories($validator);
        });
    }

    /**
     * Validate that only valid consent categories are provided.
     */
    private function validateConsentCategories($validator): void
    {
        $preferences = $this->input('preferences', []);
        $validCategories = ['analytics', 'marketing', 'tracking', 'profiling'];

        foreach (array_keys($preferences) as $category) {
            if (! in_array($category, $validCategories)) {
                $validator->errors()->add('preferences', "Invalid consent category: {$category}. Valid categories are: ".implode(', ', $validCategories));
            }
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure preferences is properly structured
        $preferences = $this->input('preferences', []);

        if (is_array($preferences)) {
            $this->merge([
                'preferences' => $preferences,
            ]);
        }
    }
}
