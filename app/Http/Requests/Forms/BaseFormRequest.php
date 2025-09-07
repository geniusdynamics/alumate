<?php

namespace App\Http\Requests\Forms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\RateLimiter;
use App\Rules\SpamProtection;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Apply rate limiting for form submissions
        $key = $this->getRateLimitKey();
        
        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts())) {
            $seconds = RateLimiter::availableIn($key);
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => "Too many form submissions. Please try again in {$seconds} seconds.",
                'errors' => ['rate_limit' => ['Rate limit exceeded']]
            ], 429));
        }
        
        RateLimiter::hit($key, $this->decayMinutes() * 60);
        
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    abstract public function rules(): array;

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'email' => 'The :attribute must be a valid email address.',
            'string' => 'The :attribute must be a string.',
            'integer' => 'The :attribute must be an integer.',
            'numeric' => 'The :attribute must be a number.',
            'boolean' => 'The :attribute must be true or false.',
            'array' => 'The :attribute must be an array.',
            'min' => 'The :attribute must be at least :min characters.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'between' => 'The :attribute must be between :min and :max characters.',
            'in' => 'The selected :attribute is invalid.',
            'unique' => 'The :attribute has already been taken.',
            'confirmed' => 'The :attribute confirmation does not match.',
            'accepted' => 'The :attribute must be accepted.',
            'date' => 'The :attribute is not a valid date.',
            'before' => 'The :attribute must be a date before :date.',
            'after' => 'The :attribute must be a date after :date.',
            'url' => 'The :attribute format is invalid.',
            'regex' => 'The :attribute format is invalid.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'email' => 'email address',
            'phone' => 'phone number',
            'date_of_birth' => 'date of birth',
            'graduation_year' => 'graduation year',
            'degree_level' => 'degree level',
            'current_job_title' => 'current job title',
            'current_company' => 'current company',
            'experience_level' => 'experience level',
            'newsletter_opt_in' => 'newsletter subscription',
            'privacy_consent' => 'privacy policy consent',
            'contact_name' => 'contact name',
            'contact_title' => 'contact title',
            'institution_name' => 'institution name',
            'institution_type' => 'institution type',
            'institution_size' => 'institution size',
            'decision_role' => 'decision making role',
            'alumni_count' => 'alumni count',
            'current_system' => 'current system',
            'budget_range' => 'budget range',
            'implementation_timeline' => 'implementation timeline',
            'primary_goals' => 'primary goals',
            'current_challenges' => 'current challenges',
            'demo_preferences' => 'demo preferences',
            'preferred_demo_time' => 'preferred demo time',
            'additional_attendees' => 'additional attendees',
            'inquiry_category' => 'inquiry category',
            'priority_level' => 'priority level',
            'preferred_contact_method' => 'preferred contact method',
            'follow_up_consent' => 'follow-up consent',
            'attachments_needed' => 'attachments needed',
            'newsletter_interests' => 'newsletter interests',
            'email_frequency' => 'email frequency',
            'attendee_name' => 'attendee name',
            'guest_count' => 'guest count',
            'dietary_restrictions' => 'dietary restrictions',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        
        // Log validation failures for monitoring
        logger()->warning('Form validation failed', [
            'form_type' => static::class,
            'errors' => array_keys($errors),
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
        ]);

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed. Please check your input and try again.',
            'errors' => $errors,
            'error_count' => count($errors),
        ], 422));
    }

    /**
     * Get the rate limiting key for this request.
     */
    protected function getRateLimitKey(): string
    {
        $identifier = $this->ip();
        
        // Use user ID if authenticated
        if (auth()->check()) {
            $identifier = 'user:' . auth()->id();
        }
        
        return 'form_submission:' . static::class . ':' . $identifier;
    }

    /**
     * Get the maximum number of attempts allowed.
     */
    protected function maxAttempts(): int
    {
        return 10; // 10 submissions per decay period
    }

    /**
     * Get the decay time in minutes.
     */
    protected function decayMinutes(): int
    {
        return 60; // 1 hour
    }

    /**
     * Get common spam protection rules.
     */
    protected function getSpamProtectionRules(): array
    {
        return [
            'honeypot' => 'nullable|max:0', // Honeypot field should be empty
            'submit_time' => ['nullable', 'integer', 'min:3'], // Minimum time to fill form
            'user_agent' => ['required', new SpamProtection()],
        ];
    }

    /**
     * Get common validation rules for personal information.
     */
    protected function getPersonalInfoRules(): array
    {
        return [
            'first_name' => 'required|string|min:2|max:50|regex:/^[a-zA-Z\s\-\'\.]+$/',
            'last_name' => 'required|string|min:2|max:50|regex:/^[a-zA-Z\s\-\'\.]+$/',
            'email' => 'required|email:rfc,dns|max:255',
            'phone' => ['nullable', new \App\Rules\PhoneNumber()],
        ];
    }

    /**
     * Get common validation rules for institutional information.
     */
    protected function getInstitutionalInfoRules(): array
    {
        return [
            'institution_name' => 'required|string|min:2|max:255',
            'institution_type' => 'required|in:public_university,private_university,community_college,liberal_arts,technical,graduate,professional,other',
            'institution_size' => 'required|in:<1000,1000-5000,5000-15000,15000-30000,>30000',
            'email' => ['required', 'email:rfc,dns', 'max:255', new \App\Rules\InstitutionalDomain()],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize and normalize input data
        $input = $this->all();
        
        // Trim whitespace from string fields
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $input[$key] = trim($value);
            }
        }
        
        // Normalize phone numbers
        if (isset($input['phone'])) {
            $input['phone'] = $this->normalizePhoneNumber($input['phone']);
        }
        
        // Normalize email addresses
        if (isset($input['email'])) {
            $input['email'] = strtolower(trim($input['email']));
        }
        
        // Add submission timestamp for spam protection
        $input['submit_time'] = $this->input('submit_time', 0);
        
        $this->merge($input);
    }

    /**
     * Normalize phone number format.
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except +
        $normalized = preg_replace('/[^\d+]/', '', $phone);
        
        // Ensure it starts with + for international format
        if (!str_starts_with($normalized, '+') && strlen($normalized) > 10) {
            $normalized = '+' . $normalized;
        }
        
        return $normalized;
    }

    /**
     * Get the validated data with additional processing.
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        
        // Remove spam protection fields from validated data
        unset($validated['honeypot'], $validated['submit_time'], $validated['user_agent']);
        
        return $validated;
    }
}
