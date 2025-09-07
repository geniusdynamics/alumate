<?php

namespace App\Http\Requests\Forms;

use App\Rules\PhoneNumber;

class IndividualSignupRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(
            $this->getPersonalInfoRules(),
            $this->getSpamProtectionRules(),
            [
                'date_of_birth' => 'nullable|date|before:today|after:1900-01-01',
                'graduation_year' => 'required|integer|min:1950|max:' . (date('Y') + 5),
                'degree_level' => 'required|in:associate,bachelor,master,doctoral,professional,certificate',
                'major' => 'required|string|min:2|max:100|regex:/^[a-zA-Z\s\-&,\.]+$/',
                'current_job_title' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9\s\-&,\.\/]+$/',
                'current_company' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9\s\-&,\.\/]+$/',
                'industry' => 'nullable|string|max:100',
                'experience_level' => 'nullable|in:0-2,3-5,6-10,10+',
                'location' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9\s\-,\.]+$/',
                'interests' => 'nullable|array|max:10',
                'interests.*' => 'string|max:50',
                'newsletter_opt_in' => 'boolean',
                'privacy_consent' => 'required|accepted',
                'terms_consent' => 'required|accepted',
                
                // Additional validation for data quality
                'linkedin_profile' => 'nullable|url|regex:/^https?:\/\/(www\.)?linkedin\.com\/in\/[a-zA-Z0-9\-]+\/?$/',
                'portfolio_url' => 'nullable|url|max:255',
                'bio' => 'nullable|string|max:500|min:10',
                'skills' => 'nullable|array|max:20',
                'skills.*' => 'string|max:30',
                'career_goals' => 'nullable|string|max:1000',
                'mentorship_interest' => 'nullable|in:mentor,mentee,both,none',
                'networking_preferences' => 'nullable|array',
                'networking_preferences.*' => 'in:events,online,local,industry_specific,alumni_only',
                'communication_preferences' => 'nullable|array',
                'communication_preferences.*' => 'in:email,sms,phone,app_notifications',
                'profile_visibility' => 'nullable|in:public,alumni_only,private',
                'job_search_status' => 'nullable|in:actively_looking,open_to_opportunities,not_looking,employed_satisfied',
            ]
        );
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'graduation_year.min' => 'The graduation year must be 1950 or later.',
            'graduation_year.max' => 'The graduation year cannot be more than 5 years in the future.',
            'degree_level.in' => 'Please select a valid degree level.',
            'major.regex' => 'The major field should only contain letters, spaces, hyphens, ampersands, commas, and periods.',
            'current_job_title.regex' => 'The job title contains invalid characters.',
            'current_company.regex' => 'The company name contains invalid characters.',
            'location.regex' => 'The location contains invalid characters.',
            'interests.max' => 'You can select up to 10 interests.',
            'interests.*.max' => 'Each interest must be 50 characters or less.',
            'privacy_consent.accepted' => 'You must accept the privacy policy to continue.',
            'terms_consent.accepted' => 'You must accept the terms of service to continue.',
            'linkedin_profile.regex' => 'Please enter a valid LinkedIn profile URL.',
            'bio.min' => 'Your bio should be at least 10 characters long.',
            'bio.max' => 'Your bio cannot exceed 500 characters.',
            'skills.max' => 'You can list up to 20 skills.',
            'skills.*.max' => 'Each skill must be 30 characters or less.',
            'career_goals.max' => 'Career goals cannot exceed 1000 characters.',
            'mentorship_interest.in' => 'Please select a valid mentorship preference.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'date_of_birth' => 'date of birth',
            'graduation_year' => 'graduation year',
            'degree_level' => 'degree level',
            'major' => 'field of study',
            'current_job_title' => 'current job title',
            'current_company' => 'current company',
            'experience_level' => 'experience level',
            'newsletter_opt_in' => 'newsletter subscription',
            'privacy_consent' => 'privacy policy consent',
            'terms_consent' => 'terms of service consent',
            'linkedin_profile' => 'LinkedIn profile',
            'portfolio_url' => 'portfolio URL',
            'career_goals' => 'career goals',
            'mentorship_interest' => 'mentorship interest',
            'networking_preferences' => 'networking preferences',
            'communication_preferences' => 'communication preferences',
            'profile_visibility' => 'profile visibility',
            'job_search_status' => 'job search status',
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation logic
            $this->validateGraduationYear($validator);
            $this->validateAgeConsistency($validator);
            $this->validateExperienceConsistency($validator);
        });
    }

    /**
     * Validate graduation year consistency
     */
    private function validateGraduationYear($validator): void
    {
        $graduationYear = $this->input('graduation_year');
        $degreeLevel = $this->input('degree_level');
        
        if ($graduationYear && $degreeLevel) {
            $currentYear = date('Y');
            $yearsAgo = $currentYear - $graduationYear;
            
            // Check if graduation year is reasonable for degree level
            if ($degreeLevel === 'doctoral' && $yearsAgo < 4) {
                $validator->errors()->add('graduation_year', 'Doctoral degree graduation year seems too recent.');
            }
            
            if ($degreeLevel === 'associate' && $yearsAgo > 50) {
                $validator->errors()->add('graduation_year', 'Graduation year seems too far in the past for this degree level.');
            }
        }
    }

    /**
     * Validate age and graduation year consistency
     */
    private function validateAgeConsistency($validator): void
    {
        $dateOfBirth = $this->input('date_of_birth');
        $graduationYear = $this->input('graduation_year');
        
        if ($dateOfBirth && $graduationYear) {
            $birthYear = date('Y', strtotime($dateOfBirth));
            $ageAtGraduation = $graduationYear - $birthYear;
            
            // Typical graduation ages
            if ($ageAtGraduation < 16) {
                $validator->errors()->add('graduation_year', 'Graduation year seems too early based on your date of birth.');
            }
            
            if ($ageAtGraduation > 65) {
                $validator->errors()->add('graduation_year', 'Graduation year seems too late based on your date of birth.');
            }
        }
    }

    /**
     * Validate experience level consistency
     */
    private function validateExperienceConsistency($validator): void
    {
        $graduationYear = $this->input('graduation_year');
        $experienceLevel = $this->input('experience_level');
        
        if ($graduationYear && $experienceLevel) {
            $currentYear = date('Y');
            $yearsSinceGraduation = $currentYear - $graduationYear;
            
            // Check experience level consistency
            if ($experienceLevel === '10+' && $yearsSinceGraduation < 8) {
                $validator->errors()->add('experience_level', 'Experience level seems inconsistent with graduation year.');
            }
            
            if ($experienceLevel === '0-2' && $yearsSinceGraduation > 5) {
                $validator->errors()->add('experience_level', 'Experience level seems low for your graduation year.');
            }
        }
    }
}
