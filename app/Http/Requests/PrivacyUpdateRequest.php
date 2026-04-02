<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Privacy Update Request
 *
 * Validates requests for updating user privacy settings.
 */
class PrivacyUpdateRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'consent' => 'nullable|array',
            'consent.*' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'consent.array' => 'The consent field must be an array.',
            'consent.*.boolean' => 'Each consent value must be true or false.',
        ];
    }
}
