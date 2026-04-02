<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OnboardingStepRequest extends FormRequest
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
        $step = $this->route('step');

        return match ($step) {
            'institution' => $this->institutionRules(),
            'branding' => $this->brandingRules(),
            'admins' => $this->adminsRules(),
            'payment' => $this->paymentRules(),
            'imports' => $this->importsRules(),
            default => [],
        };
    }

    /**
     * Rules for institution step.
     */
    protected function institutionRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Rules for branding step.
     */
    protected function brandingRules(): array
    {
        return [
            'primary_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'secondary_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'accent_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'font_family' => ['nullable', Rule::in(['Inter', 'Roboto', 'Open Sans', 'Lato', 'Poppins'])],
            'custom_css' => 'nullable|string|max:5000',
        ];
    }

    /**
     * Rules for admins step.
     */
    protected function adminsRules(): array
    {
        return [
            'admins' => 'required|array|min:1',
            'admins.*.name' => 'required|string|max:255',
            'admins.*.email' => 'required|email|max:255',
            'admins.*.role' => ['required', Rule::in(['institution-admin', 'content-manager', 'readonly-admin'])],
            'admins.*.is_primary' => 'boolean',
        ];
    }

    /**
     * Rules for payment step.
     */
    protected function paymentRules(): array
    {
        return [
            'plan_id' => ['required', Rule::in(['basic', 'professional', 'enterprise'])],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'payment_method_id' => 'nullable|string',
            'trial_ends_at' => 'nullable|date|after:today',
        ];
    }

    /**
     * Rules for imports step.
     */
    protected function importsRules(): array
    {
        return [
            'skip_imports' => 'boolean',
            'courses_file' => 'nullable|file|mimes:csv,txt|max:10240',
            'alumni_file' => 'nullable|file|mimes:csv,txt|max:10240',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Institution name is required.',
            'contact_email.required' => 'Contact email is required.',
            'contact_email.email' => 'Please enter a valid email address.',
            'primary_color.required' => 'Primary brand color is required.',
            'primary_color.regex' => 'Please enter a valid hex color code (e.g., #000000).',
            'secondary_color.required' => 'Secondary brand color is required.',
            'secondary_color.regex' => 'Please enter a valid hex color code (e.g., #ffffff).',
            'admins.required' => 'At least one admin user is required.',
            'admins.*.name.required' => 'Admin name is required.',
            'admins.*.email.required' => 'Admin email is required.',
            'admins.*.email.email' => 'Please enter a valid email address for all admins.',
            'plan_id.required' => 'Please select a subscription plan.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'admins.*.name' => 'admin name',
            'admins.*.email' => 'admin email',
            'admins.*.role' => 'admin role',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values if not provided
        if ($this->has('admins') && is_array($this->admins)) {
            $admins = $this->admins;

            // Ensure at least one admin is marked as primary
            $hasPrimary = false;
            foreach ($admins as $admin) {
                if (! empty($admin['is_primary'])) {
                    $hasPrimary = true;
                    break;
                }
            }

            if (! $hasPrimary && ! empty($admins[0])) {
                $admins[0]['is_primary'] = true;
                $this->merge(['admins' => $admins]);
            }
        }
    }
}
