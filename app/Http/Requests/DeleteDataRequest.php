<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Delete Data Request
 *
 * Validates requests for data deletion with GDPR/CCPA compliance requirements.
 */
class DeleteDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Users can only delete their own data.
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
            'categories' => [
                'nullable',
                'array',
            ],
            'categories.*' => [
                'string',
                'in:analytics,marketing,tracking,profiling,all',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
            'confirmation_token' => [
                'required',
                'string',
                'regex:/^[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}$/',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'categories.array' => 'Categories must be an array.',
            'categories.*.in' => 'Invalid category specified. Valid categories are: analytics, marketing, tracking, profiling, all.',
            'reason.max' => 'Reason cannot exceed 500 characters.',
            'confirmation_token.required' => 'Confirmation token is required for data deletion.',
            'confirmation_token.regex' => 'Confirmation token must be a valid UUID format.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateConfirmationToken($validator);
        });
    }

    /**
     * Validate the confirmation token for data deletion.
     */
    private function validateConfirmationToken($validator): void
    {
        $token = $this->input('confirmation_token');

        if ($token) {
            // In a real implementation, this would verify against a stored token
            // For now, we'll accept any valid UUID format as specified in the regex
            // The actual verification would happen in the controller/service layer
            if (!preg_match('/^[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}$/', $token)) {
                $validator->errors()->add('confirmation_token', 'Invalid confirmation token format.');
            }
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure categories is properly structured
        $categories = $this->input('categories', []);

        if (is_array($categories)) {
            $this->merge([
                'categories' => $categories,
            ]);
        }

        // Normalize reason
        $reason = $this->input('reason');
        if (is_string($reason)) {
            $this->merge([
                'reason' => trim($reason),
            ]);
        }
    }
}