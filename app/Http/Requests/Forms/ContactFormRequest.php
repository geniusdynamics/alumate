<?php

namespace App\Http\Requests\Forms;

class ContactFormRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(
            $this->getSpamProtectionRules(),
            [
                'name' => 'required|string|min:2|max:100|regex:/^[a-zA-Z\s\-\'\.]+$/',
                'organization' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9\s\-&,\.\/]+$/',
                'email' => 'required|email:rfc,dns|max:255',
                'phone' => ['nullable', new \App\Rules\PhoneNumber()],
                'contact_role' => 'required|in:alumni,prospective_student,current_student,institution_staff,employer,partner,media,vendor,researcher,consultant,other',
                'inquiry_category' => 'required|in:general,technical_support,account_issues,billing,sales,demo_request,partnership,events,career_services,alumni_directory,mentorship,fundraising,media,bug_report,feature_request,privacy,accessibility,integration,training,other',
                'priority_level' => 'required|in:low,medium,high,urgent',
                'preferred_contact_method' => 'nullable|in:email,phone,text,video_call,no_preference',
                'subject' => 'required|string|min:5|max:200|regex:/^[a-zA-Z0-9\s\-_:;,\.!?()&]+$/',
                'message' => 'required|string|min:20|max:5000',
                'attachments_needed' => 'boolean',
                'follow_up_consent' => 'required|accepted',
                
                // Additional fields for better categorization
                'affected_users' => 'nullable|integer|min:1|max:100000',
                'error_details' => 'nullable|string|max:2000',
                'steps_to_reproduce' => 'nullable|string|max:2000',
                'browser_info' => 'nullable|string|max:500',
                'device_info' => 'nullable|string|max:200',
                'screenshot_description' => 'nullable|string|max:500',
                'urgency_justification' => 'nullable|string|max:1000',
                'business_impact' => 'nullable|in:none,low,medium,high,critical',
                'deadline' => 'nullable|date|after:today|before:' . date('Y-m-d', strtotime('+1 year')),
                'budget_available' => 'nullable|in:none,<1k,1k-5k,5k-25k,25k-100k,>100k,tbd',
                'timeline_expectations' => 'nullable|in:immediate,same_day,within_week,within_month,flexible',
                'previous_ticket_number' => 'nullable|string|max:50|regex:/^[A-Z0-9\-]+$/',
                'related_feature' => 'nullable|string|max:100',
                'user_type' => 'nullable|in:admin,staff,alumni,student,employer,guest',
                'account_id' => 'nullable|string|max:100|regex:/^[A-Z0-9\-_]+$/',
                'subscription_type' => 'nullable|in:free,basic,premium,enterprise,trial',
                'integration_platform' => 'nullable|string|max:100',
                'api_usage' => 'boolean',
                'gdpr_request' => 'boolean',
                'data_export_needed' => 'boolean',
                'account_deletion_request' => 'boolean',
            ]
        );
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'name.regex' => 'The name should only contain letters, spaces, hyphens, apostrophes, and periods.',
            'organization.regex' => 'The organization name contains invalid characters.',
            'subject.min' => 'The subject must be at least 5 characters long.',
            'subject.max' => 'The subject cannot exceed 200 characters.',
            'subject.regex' => 'The subject contains invalid characters.',
            'message.min' => 'The message must be at least 20 characters long.',
            'message.max' => 'The message cannot exceed 5000 characters.',
            'follow_up_consent.accepted' => 'You must consent to follow-up communications.',
            'affected_users.min' => 'Number of affected users must be at least 1.',
            'affected_users.max' => 'Number of affected users cannot exceed 100,000.',
            'error_details.max' => 'Error details cannot exceed 2000 characters.',
            'steps_to_reproduce.max' => 'Steps to reproduce cannot exceed 2000 characters.',
            'urgency_justification.max' => 'Urgency justification cannot exceed 1000 characters.',
            'deadline.after' => 'The deadline must be in the future.',
            'deadline.before' => 'The deadline cannot be more than one year from now.',
            'previous_ticket_number.regex' => 'Please enter a valid ticket number format.',
            'account_id.regex' => 'Please enter a valid account ID format.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'contact_role' => 'your role',
            'inquiry_category' => 'inquiry category',
            'priority_level' => 'priority level',
            'preferred_contact_method' => 'preferred contact method',
            'attachments_needed' => 'attachments needed',
            'follow_up_consent' => 'follow-up consent',
            'affected_users' => 'number of affected users',
            'error_details' => 'error details',
            'steps_to_reproduce' => 'steps to reproduce',
            'browser_info' => 'browser information',
            'device_info' => 'device information',
            'screenshot_description' => 'screenshot description',
            'urgency_justification' => 'urgency justification',
            'business_impact' => 'business impact',
            'budget_available' => 'available budget',
            'timeline_expectations' => 'timeline expectations',
            'previous_ticket_number' => 'previous ticket number',
            'related_feature' => 'related feature',
            'user_type' => 'user type',
            'account_id' => 'account ID',
            'subscription_type' => 'subscription type',
            'integration_platform' => 'integration platform',
            'api_usage' => 'API usage',
            'gdpr_request' => 'GDPR request',
            'data_export_needed' => 'data export needed',
            'account_deletion_request' => 'account deletion request',
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validatePriorityConsistency($validator);
            $this->validateTechnicalFields($validator);
            $this->validateUrgencyFields($validator);
            $this->validatePrivacyRequests($validator);
        });
    }

    /**
     * Validate priority level consistency
     */
    private function validatePriorityConsistency($validator): void
    {
        $priority = $this->input('priority_level');
        $category = $this->input('inquiry_category');
        $urgencyJustification = $this->input('urgency_justification');
        
        // High/urgent priority should have justification
        if (in_array($priority, ['high', 'urgent']) && empty($urgencyJustification)) {
            $validator->errors()->add('urgency_justification', 'Please provide justification for high/urgent priority requests.');
        }
        
        // Certain categories should match priority levels
        if ($category === 'bug_report' && $priority === 'low') {
            $validator->errors()->add('priority_level', 'Bug reports typically require medium or higher priority.');
        }
        
        if ($category === 'general' && $priority === 'urgent') {
            $validator->errors()->add('priority_level', 'General inquiries are typically not urgent.');
        }
    }

    /**
     * Validate technical support fields
     */
    private function validateTechnicalFields($validator): void
    {
        $category = $this->input('inquiry_category');
        $errorDetails = $this->input('error_details');
        $stepsToReproduce = $this->input('steps_to_reproduce');
        
        // Technical support should have error details
        if (in_array($category, ['technical_support', 'bug_report', 'account_issues']) && empty($errorDetails)) {
            $validator->errors()->add('error_details', 'Please provide error details for technical issues.');
        }
        
        // Bug reports should have reproduction steps
        if ($category === 'bug_report' && empty($stepsToReproduce)) {
            $validator->errors()->add('steps_to_reproduce', 'Please provide steps to reproduce the bug.');
        }
    }

    /**
     * Validate urgency-related fields
     */
    private function validateUrgencyFields($validator): void
    {
        $priority = $this->input('priority_level');
        $businessImpact = $this->input('business_impact');
        $deadline = $this->input('deadline');
        $timelineExpectations = $this->input('timeline_expectations');
        
        // Urgent priority should have high business impact
        if ($priority === 'urgent' && !in_array($businessImpact, ['high', 'critical'])) {
            $validator->errors()->add('business_impact', 'Urgent requests should have high or critical business impact.');
        }
        
        // Immediate timeline with low priority is inconsistent
        if ($timelineExpectations === 'immediate' && $priority === 'low') {
            $validator->errors()->add('timeline_expectations', 'Immediate timeline expectations require higher priority.');
        }
        
        // Deadline within 24 hours should be urgent
        if ($deadline && strtotime($deadline) < strtotime('+1 day') && $priority !== 'urgent') {
            $validator->errors()->add('priority_level', 'Requests with tight deadlines should be marked as urgent.');
        }
    }

    /**
     * Validate privacy-related requests
     */
    private function validatePrivacyRequests($validator): void
    {
        $category = $this->input('inquiry_category');
        $gdprRequest = $this->input('gdpr_request');
        $dataExportNeeded = $this->input('data_export_needed');
        $accountDeletionRequest = $this->input('account_deletion_request');
        
        // Privacy category should have privacy-related flags
        if ($category === 'privacy' && !($gdprRequest || $dataExportNeeded || $accountDeletionRequest)) {
            $validator->errors()->add('inquiry_category', 'Privacy inquiries should specify the type of privacy request.');
        }
        
        // Account deletion should be high priority
        if ($accountDeletionRequest && !in_array($this->input('priority_level'), ['high', 'urgent'])) {
            $validator->errors()->add('priority_level', 'Account deletion requests should be high or urgent priority.');
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        
        // Auto-detect browser and device info if not provided
        if (!$this->input('browser_info')) {
            $this->merge(['browser_info' => $this->userAgent()]);
        }
        
        // Set default values for boolean fields
        $booleanFields = ['attachments_needed', 'api_usage', 'gdpr_request', 'data_export_needed', 'account_deletion_request'];
        foreach ($booleanFields as $field) {
            if (!$this->has($field)) {
                $this->merge([$field => false]);
            }
        }
    }
}
