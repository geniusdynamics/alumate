<?php

namespace App\Http\Requests\Forms;

class InstitutionDemoRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(
            $this->getInstitutionalInfoRules(),
            $this->getSpamProtectionRules(),
            [
                'contact_name' => 'required|string|min:2|max:100|regex:/^[a-zA-Z\s\-\'\.]+$/',
                'contact_title' => 'required|string|min:2|max:100',
                'phone' => ['required', new \App\Rules\PhoneNumber()],
                'department' => 'required|in:alumni_relations,advancement,marketing,student_affairs,it,administration,career_services,enrollment,communications,development,other',
                'decision_role' => 'required|in:decision_maker,influencer,evaluator,end_user,researcher',
                'alumni_count' => 'required|in:<1000,1000-5000,5000-15000,15000-50000,50000-100000,>100000',
                'current_system' => 'nullable|in:none,manual,spreadsheet,crm,specialized_alumni,custom,salesforce,hubspot,blackbaud,other',
                'budget_range' => 'nullable|in:<10k,10k-25k,25k-50k,50k-100k,100k-250k,>250k,tbd',
                'implementation_timeline' => 'required|in:immediate,1-3months,3-6months,6-12months,>12months,exploring',
                'primary_goals' => 'required|array|min:1|max:8',
                'primary_goals.*' => 'in:alumni_engagement,fundraising,event_management,career_services,networking,data_management,communication,analytics,mentorship,volunteer_coordination',
                'current_challenges' => 'nullable|string|max:2000',
                'demo_preferences' => 'nullable|string|max:1000',
                'preferred_demo_time' => 'nullable|in:morning,afternoon,evening,flexible',
                'additional_attendees' => 'nullable|integer|min:0|max:25',
                'attendee_roles' => 'nullable|array|max:10',
                'attendee_roles.*' => 'string|max:100',
                'specific_features' => 'nullable|array|max:15',
                'specific_features.*' => 'in:alumni_directory,event_management,fundraising_tools,career_board,mentorship_platform,communication_tools,analytics_dashboard,mobile_app,integration_capabilities,custom_branding',
                'integration_needs' => 'nullable|array|max:10',
                'integration_needs.*' => 'in:crm,sis,lms,email_marketing,payment_processing,social_media,website,mobile_app,analytics,other',
                'compliance_requirements' => 'nullable|array',
                'compliance_requirements.*' => 'in:ferpa,gdpr,ccpa,hipaa,sox,pci_dss,other',
                'technical_requirements' => 'nullable|string|max:1000',
                'success_metrics' => 'nullable|array|max:8',
                'success_metrics.*' => 'in:engagement_rate,donation_increase,event_attendance,alumni_participation,data_quality,user_adoption,roi,time_savings',
                'urgency_reason' => 'nullable|string|max:500',
                'competitive_evaluation' => 'nullable|array',
                'competitive_evaluation.*' => 'string|max:100',
                'referral_source' => 'nullable|in:web_search,social_media,referral,conference,webinar,email,advertisement,partner,other',
                'follow_up_consent' => 'required|accepted',
                'data_sharing_consent' => 'required|accepted',
            ]
        );
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'contact_name.regex' => 'The contact name should only contain letters, spaces, hyphens, apostrophes, and periods.',
            'primary_goals.min' => 'Please select at least one primary goal.',
            'primary_goals.max' => 'Please select no more than 8 primary goals.',
            'primary_goals.*.in' => 'Please select valid primary goals from the available options.',
            'current_challenges.max' => 'Current challenges description cannot exceed 2000 characters.',
            'demo_preferences.max' => 'Demo preferences cannot exceed 1000 characters.',
            'additional_attendees.max' => 'Maximum 25 additional attendees allowed.',
            'attendee_roles.max' => 'Maximum 10 attendee roles can be specified.',
            'specific_features.max' => 'Please select no more than 15 specific features.',
            'integration_needs.max' => 'Please select no more than 10 integration needs.',
            'technical_requirements.max' => 'Technical requirements cannot exceed 1000 characters.',
            'success_metrics.max' => 'Please select no more than 8 success metrics.',
            'urgency_reason.max' => 'Urgency reason cannot exceed 500 characters.',
            'follow_up_consent.accepted' => 'You must consent to follow-up communications to proceed.',
            'data_sharing_consent.accepted' => 'You must consent to data sharing for demo purposes.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
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
            'attendee_roles' => 'attendee roles',
            'specific_features' => 'specific features of interest',
            'integration_needs' => 'integration needs',
            'compliance_requirements' => 'compliance requirements',
            'technical_requirements' => 'technical requirements',
            'success_metrics' => 'success metrics',
            'urgency_reason' => 'urgency reason',
            'competitive_evaluation' => 'competitive solutions being evaluated',
            'referral_source' => 'referral source',
            'follow_up_consent' => 'follow-up consent',
            'data_sharing_consent' => 'data sharing consent',
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateBudgetTimeline($validator);
            $this->validateInstitutionSize($validator);
            $this->validateDecisionRole($validator);
        });
    }

    /**
     * Validate budget and timeline consistency
     */
    private function validateBudgetTimeline($validator): void
    {
        $budget = $this->input('budget_range');
        $timeline = $this->input('implementation_timeline');
        
        if ($budget && $timeline) {
            // Large budgets with immediate timeline might be unrealistic
            if (in_array($budget, ['>250k', '100k-250k']) && $timeline === 'immediate') {
                $validator->errors()->add('implementation_timeline', 'Large budget implementations typically require more planning time.');
            }
            
            // Small budgets with long timelines might indicate low priority
            if (in_array($budget, ['<10k', '10k-25k']) && $timeline === '>12months') {
                $validator->errors()->add('budget_range', 'Extended timelines may require larger budget allocations.');
            }
        }
    }

    /**
     * Validate institution size consistency
     */
    private function validateInstitutionSize($validator): void
    {
        $institutionSize = $this->input('institution_size');
        $alumniCount = $this->input('alumni_count');
        
        if ($institutionSize && $alumniCount) {
            // Large institutions should have more alumni
            if ($institutionSize === '>30000' && in_array($alumniCount, ['<1000', '1000-5000'])) {
                $validator->errors()->add('alumni_count', 'Large institutions typically have more alumni.');
            }
            
            // Small institutions shouldn't have too many alumni
            if ($institutionSize === '<1000' && in_array($alumniCount, ['>100000', '50000-100000'])) {
                $validator->errors()->add('alumni_count', 'Small institutions typically have fewer alumni.');
            }
        }
    }

    /**
     * Validate decision role and urgency
     */
    private function validateDecisionRole($validator): void
    {
        $decisionRole = $this->input('decision_role');
        $timeline = $this->input('implementation_timeline');
        $urgencyReason = $this->input('urgency_reason');
        
        // Decision makers with immediate timeline should provide urgency reason
        if ($decisionRole === 'decision_maker' && $timeline === 'immediate' && empty($urgencyReason)) {
            $validator->errors()->add('urgency_reason', 'Please explain the reason for immediate implementation needs.');
        }
        
        // Researchers with immediate timeline might be inconsistent
        if ($decisionRole === 'researcher' && $timeline === 'immediate') {
            $validator->errors()->add('implementation_timeline', 'Research phase typically requires more time for evaluation.');
        }
    }

    /**
     * Get the maximum number of attempts allowed for institution demo requests.
     */
    protected function maxAttempts(): int
    {
        return 5; // More restrictive for high-value leads
    }

    /**
     * Get the decay time in minutes for institution demo requests.
     */
    protected function decayMinutes(): int
    {
        return 120; // 2 hours for high-value leads
    }
}
