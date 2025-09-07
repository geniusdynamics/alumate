// Form Templates
import type { FormTemplate } from '@/types/components'

export const individualSignupTemplate: FormTemplate = {
  id: 'individual-signup',
  name: 'Individual Alumni Signup',
  description: 'Comprehensive signup form for individual alumni with personal information fields',
  category: 'lead-capture',
  audienceType: 'individual',
  config: {
    title: 'Join Our Alumni Network',
    description: 'Connect with fellow alumni and unlock exclusive opportunities. Complete your profile to get personalized recommendations.',
    fields: [
      {
        id: 'first-name',
        type: 'text',
        name: 'first_name',
        label: 'First Name',
        placeholder: 'Enter your first name',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'First name is required' },
          { rule: 'minLength', value: 2, message: 'First name must be at least 2 characters' },
          { rule: 'maxLength', value: 50, message: 'First name cannot exceed 50 characters' }
        ]
      },
      {
        id: 'last-name',
        type: 'text',
        name: 'last_name',
        label: 'Last Name',
        placeholder: 'Enter your last name',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Last name is required' },
          { rule: 'minLength', value: 2, message: 'Last name must be at least 2 characters' },
          { rule: 'maxLength', value: 50, message: 'Last name cannot exceed 50 characters' }
        ]
      },
      {
        id: 'email',
        type: 'email',
        name: 'email',
        label: 'Email Address',
        placeholder: 'Enter your email address',
        required: true,
        width: 'full',
        validation: [
          { rule: 'required', message: 'Email is required' },
          { rule: 'email', message: 'Please enter a valid email address' }
        ]
      },
      {
        id: 'phone',
        type: 'phone',
        name: 'phone',
        label: 'Phone Number',
        placeholder: 'Enter your phone number',
        required: false,
        width: 'half',
        validation: [
          { rule: 'phone', message: 'Please enter a valid phone number' }
        ]
      },
      {
        id: 'date-of-birth',
        type: 'date',
        name: 'date_of_birth',
        label: 'Date of Birth',
        required: false,
        width: 'half',
        validation: [
          { rule: 'max', value: new Date().toISOString().split('T')[0], message: 'Date of birth cannot be in the future' }
        ]
      },
      {
        id: 'graduation-year',
        type: 'select',
        name: 'graduation_year',
        label: 'Graduation Year',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Graduation year is required' }
        ],
        options: Array.from({ length: 60 }, (_, i) => {
          const year = new Date().getFullYear() - i + 5
          return { label: year.toString(), value: year.toString() }
        })
      },
      {
        id: 'degree-level',
        type: 'select',
        name: 'degree_level',
        label: 'Degree Level',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Degree level is required' }
        ],
        options: [
          { label: 'Associate Degree', value: 'associate' },
          { label: 'Bachelor\'s Degree', value: 'bachelor' },
          { label: 'Master\'s Degree', value: 'master' },
          { label: 'Doctoral Degree', value: 'doctoral' },
          { label: 'Professional Degree', value: 'professional' },
          { label: 'Certificate Program', value: 'certificate' }
        ]
      },
      {
        id: 'major',
        type: 'text',
        name: 'major',
        label: 'Major/Field of Study',
        placeholder: 'e.g., Computer Science, Business Administration',
        required: true,
        width: 'full',
        validation: [
          { rule: 'required', message: 'Major/field of study is required' },
          { rule: 'minLength', value: 2, message: 'Major must be at least 2 characters' }
        ]
      },
      {
        id: 'current-job-title',
        type: 'text',
        name: 'current_job_title',
        label: 'Current Job Title',
        placeholder: 'Enter your current job title',
        required: false,
        width: 'half'
      },
      {
        id: 'current-company',
        type: 'text',
        name: 'current_company',
        label: 'Current Company',
        placeholder: 'Enter your current company',
        required: false,
        width: 'half'
      },
      {
        id: 'industry',
        type: 'select',
        name: 'industry',
        label: 'Current Industry',
        required: false,
        width: 'half',
        options: [
          { label: 'Technology', value: 'technology' },
          { label: 'Healthcare', value: 'healthcare' },
          { label: 'Finance & Banking', value: 'finance' },
          { label: 'Education', value: 'education' },
          { label: 'Marketing & Advertising', value: 'marketing' },
          { label: 'Manufacturing', value: 'manufacturing' },
          { label: 'Retail', value: 'retail' },
          { label: 'Consulting', value: 'consulting' },
          { label: 'Government', value: 'government' },
          { label: 'Non-Profit', value: 'nonprofit' },
          { label: 'Entrepreneurship', value: 'entrepreneurship' },
          { label: 'Other', value: 'other' }
        ]
      },
      {
        id: 'experience-level',
        type: 'select',
        name: 'experience_level',
        label: 'Years of Experience',
        required: false,
        width: 'half',
        options: [
          { label: 'Entry Level (0-2 years)', value: '0-2' },
          { label: 'Mid Level (3-5 years)', value: '3-5' },
          { label: 'Senior Level (6-10 years)', value: '6-10' },
          { label: 'Executive Level (10+ years)', value: '10+' }
        ]
      },
      {
        id: 'location',
        type: 'text',
        name: 'location',
        label: 'Current Location',
        placeholder: 'City, State/Country',
        required: false,
        width: 'full'
      },
      {
        id: 'interests',
        type: 'checkbox',
        name: 'interests',
        label: 'Areas of Interest',
        required: false,
        width: 'full',
        multiple: true,
        options: [
          { label: 'Career Development', value: 'career_development' },
          { label: 'Networking Events', value: 'networking' },
          { label: 'Mentorship Programs', value: 'mentorship' },
          { label: 'Professional Development', value: 'professional_development' },
          { label: 'Alumni News & Updates', value: 'news_updates' },
          { label: 'Volunteer Opportunities', value: 'volunteering' },
          { label: 'Fundraising Events', value: 'fundraising' }
        ]
      },
      {
        id: 'newsletter',
        type: 'checkbox',
        name: 'newsletter_opt_in',
        label: 'I would like to receive alumni newsletters and updates',
        required: false,
        width: 'full'
      },
      {
        id: 'privacy-consent',
        type: 'checkbox',
        name: 'privacy_consent',
        label: 'I agree to the privacy policy and terms of service',
        required: true,
        width: 'full',
        validation: [
          { rule: 'required', message: 'You must agree to the privacy policy to continue' }
        ]
      }
    ],
    layout: 'two-column',
    spacing: 'default',
    submission: {
      method: 'POST',
      action: '/api/forms/individual-signup',
      successMessage: 'Welcome to our alumni network! Check your email for next steps.',
      redirectUrl: '/dashboard/welcome',
      crmIntegration: {
        enabled: true,
        provider: 'hubspot',
        endpoint: '/api/crm/hubspot/contacts',
        mapping: {
          'first_name': 'firstname',
          'last_name': 'lastname',
          'email': 'email',
          'phone': 'phone',
          'current_company': 'company',
          'current_job_title': 'jobtitle',
          'graduation_year': 'hs_graduation_year',
          'major': 'hs_field_of_study',
          'industry': 'industry'
        },
        leadScore: 60,
        tags: ['alumni', 'individual', 'signup', 'qualified']
      },
      notifications: {
        enabled: true,
        recipients: ['alumni@company.com'],
        template: 'individual-signup-notification',
        subject: 'New Alumni Registration: {{first_name}} {{last_name}}'
      }
    },
    theme: 'modern',
    colorScheme: 'primary',
    validateOnBlur: true,
    showValidationSummary: true,
    enableAutoSave: true,
    autoSaveInterval: 30,
    trackingEnabled: true,
    trackingEvents: ['form_view', 'field_focus', 'form_submit', 'auto_save'],
    honeypot: true,
    recaptcha: {
      enabled: true,
      theme: 'light'
    }
  }
}

export const institutionDemoRequestTemplate: FormTemplate = {
  id: 'institution-demo-request',
  name: 'Institution Demo Request',
  description: 'Comprehensive demo request form for educational institutions with qualification fields',
  category: 'demo-request',
  audienceType: 'institution',
  config: {
    title: 'Request a Personalized Demo',
    description: 'Discover how our alumni engagement platform can transform your institution\'s relationship with graduates. Get a customized demonstration tailored to your specific needs.',
    fields: [
      {
        id: 'contact-name',
        type: 'text',
        name: 'contact_name',
        label: 'Contact Name',
        placeholder: 'Enter your full name',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Contact name is required' },
          { rule: 'minLength', value: 2, message: 'Name must be at least 2 characters' }
        ]
      },
      {
        id: 'contact-title',
        type: 'text',
        name: 'contact_title',
        label: 'Job Title',
        placeholder: 'e.g., Alumni Relations Director, VP of Advancement',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Job title is required' }
        ]
      },
      {
        id: 'institution-name',
        type: 'text',
        name: 'institution_name',
        label: 'Institution Name',
        placeholder: 'Enter your institution name',
        required: true,
        width: 'full',
        validation: [
          { rule: 'required', message: 'Institution name is required' },
          { rule: 'minLength', value: 2, message: 'Institution name must be at least 2 characters' }
        ]
      },
      {
        id: 'institution-type',
        type: 'select',
        name: 'institution_type',
        label: 'Institution Type',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Institution type is required' }
        ],
        options: [
          { label: 'Public University', value: 'public_university' },
          { label: 'Private University', value: 'private_university' },
          { label: 'Community College', value: 'community_college' },
          { label: 'Liberal Arts College', value: 'liberal_arts' },
          { label: 'Technical/Trade School', value: 'technical' },
          { label: 'Graduate School', value: 'graduate' },
          { label: 'Professional School', value: 'professional' },
          { label: 'Other', value: 'other' }
        ]
      },
      {
        id: 'institution-size',
        type: 'select',
        name: 'institution_size',
        label: 'Institution Size (Total Enrollment)',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Institution size is required' }
        ],
        options: [
          { label: 'Less than 1,000 students', value: '<1000' },
          { label: '1,000 - 5,000 students', value: '1000-5000' },
          { label: '5,000 - 15,000 students', value: '5000-15000' },
          { label: '15,000 - 30,000 students', value: '15000-30000' },
          { label: 'More than 30,000 students', value: '>30000' }
        ]
      },
      {
        id: 'email',
        type: 'email',
        name: 'email',
        label: 'Work Email',
        placeholder: 'Enter your institutional email address',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Work email is required' },
          { rule: 'email', message: 'Please enter a valid email address' }
        ]
      },
      {
        id: 'phone',
        type: 'phone',
        name: 'phone',
        label: 'Phone Number',
        placeholder: 'Enter your direct phone number',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Phone number is required' },
          { rule: 'phone', message: 'Please enter a valid phone number' }
        ]
      },
      {
        id: 'department',
        type: 'select',
        name: 'department',
        label: 'Department',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Department is required' }
        ],
        options: [
          { label: 'Alumni Relations', value: 'alumni_relations' },
          { label: 'Advancement/Development', value: 'advancement' },
          { label: 'Marketing/Communications', value: 'marketing' },
          { label: 'Student Affairs', value: 'student_affairs' },
          { label: 'IT/Technology', value: 'it' },
          { label: 'Administration', value: 'administration' },
          { label: 'Other', value: 'other' }
        ]
      },
      {
        id: 'decision-role',
        type: 'select',
        name: 'decision_role',
        label: 'Role in Decision Making',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Decision role is required' }
        ],
        options: [
          { label: 'Final Decision Maker', value: 'decision_maker' },
          { label: 'Key Influencer', value: 'influencer' },
          { label: 'Evaluator/Researcher', value: 'evaluator' },
          { label: 'End User', value: 'end_user' }
        ]
      },
      {
        id: 'alumni-count',
        type: 'select',
        name: 'alumni_count',
        label: 'Total Number of Alumni',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Alumni count is required' }
        ],
        options: [
          { label: 'Less than 1,000', value: '<1000' },
          { label: '1,000 - 5,000', value: '1000-5000' },
          { label: '5,000 - 15,000', value: '5000-15000' },
          { label: '15,000 - 50,000', value: '15000-50000' },
          { label: '50,000 - 100,000', value: '50000-100000' },
          { label: 'More than 100,000', value: '>100000' }
        ]
      },
      {
        id: 'current-system',
        type: 'select',
        name: 'current_system',
        label: 'Current Alumni Management System',
        required: false,
        width: 'half',
        options: [
          { label: 'No current system', value: 'none' },
          { label: 'Spreadsheets/Manual tracking', value: 'manual' },
          { label: 'CRM (Salesforce, etc.)', value: 'crm' },
          { label: 'Specialized alumni software', value: 'specialized' },
          { label: 'Custom built solution', value: 'custom' },
          { label: 'Other', value: 'other' }
        ]
      },
      {
        id: 'budget-range',
        type: 'select',
        name: 'budget_range',
        label: 'Annual Budget Range',
        required: false,
        width: 'half',
        options: [
          { label: 'Under $10,000', value: '<10k' },
          { label: '$10,000 - $25,000', value: '10k-25k' },
          { label: '$25,000 - $50,000', value: '25k-50k' },
          { label: '$50,000 - $100,000', value: '50k-100k' },
          { label: 'Over $100,000', value: '>100k' },
          { label: 'Not yet determined', value: 'tbd' }
        ]
      },
      {
        id: 'timeline',
        type: 'select',
        name: 'implementation_timeline',
        label: 'Implementation Timeline',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Implementation timeline is required' }
        ],
        options: [
          { label: 'Immediate (within 1 month)', value: 'immediate' },
          { label: '1-3 months', value: '1-3months' },
          { label: '3-6 months', value: '3-6months' },
          { label: '6-12 months', value: '6-12months' },
          { label: 'More than 12 months', value: '>12months' },
          { label: 'Just exploring options', value: 'exploring' }
        ]
      },
      {
        id: 'primary-goals',
        type: 'checkbox',
        name: 'primary_goals',
        label: 'Primary Goals (Select all that apply)',
        required: true,
        width: 'full',
        multiple: true,
        validation: [
          { rule: 'required', message: 'Please select at least one primary goal' }
        ],
        options: [
          { label: 'Increase alumni engagement', value: 'engagement' },
          { label: 'Improve fundraising efforts', value: 'fundraising' },
          { label: 'Better alumni data management', value: 'data_management' },
          { label: 'Enhance communication channels', value: 'communication' },
          { label: 'Organize events more effectively', value: 'events' },
          { label: 'Career services for alumni', value: 'career_services' },
          { label: 'Mentorship programs', value: 'mentorship' },
          { label: 'Alumni directory/networking', value: 'networking' }
        ]
      },
      {
        id: 'current-challenges',
        type: 'textarea',
        name: 'current_challenges',
        label: 'Current Alumni Engagement Challenges',
        placeholder: 'Please describe your biggest challenges with alumni engagement, data management, or communication...',
        required: false,
        width: 'full',
        rows: 4,
        validation: [
          { rule: 'maxLength', value: 1000, message: 'Please limit your response to 1000 characters' }
        ]
      },
      {
        id: 'demo-preferences',
        type: 'textarea',
        name: 'demo_preferences',
        label: 'Demo Preferences & Specific Areas of Interest',
        placeholder: 'What specific features or areas would you like to see in the demo?',
        required: false,
        width: 'full',
        rows: 3,
        validation: [
          { rule: 'maxLength', value: 500, message: 'Please limit your response to 500 characters' }
        ]
      },
      {
        id: 'preferred-demo-time',
        type: 'select',
        name: 'preferred_demo_time',
        label: 'Preferred Demo Time',
        required: false,
        width: 'half',
        options: [
          { label: 'Morning (9 AM - 12 PM)', value: 'morning' },
          { label: 'Afternoon (12 PM - 5 PM)', value: 'afternoon' },
          { label: 'Evening (5 PM - 7 PM)', value: 'evening' },
          { label: 'Flexible', value: 'flexible' }
        ]
      },
      {
        id: 'additional-attendees',
        type: 'number',
        name: 'additional_attendees',
        label: 'Number of Additional Demo Attendees',
        placeholder: '0',
        required: false,
        width: 'half',
        min: 0,
        max: 20,
        validation: [
          { rule: 'min', value: 0, message: 'Cannot be negative' },
          { rule: 'max', value: 20, message: 'Maximum 20 attendees' }
        ]
      }
    ],
    layout: 'two-column',
    spacing: 'default',
    submission: {
      method: 'POST',
      action: '/api/forms/institution-demo-request',
      successMessage: 'Thank you for your interest! Our team will contact you within 24 hours to schedule your personalized demo.',
      redirectUrl: '/demo-confirmation',
      crmIntegration: {
        enabled: true,
        provider: 'salesforce',
        endpoint: '/api/crm/salesforce/leads',
        mapping: {
          'contact_name': 'Name',
          'contact_title': 'Title',
          'institution_name': 'Company',
          'email': 'Email',
          'phone': 'Phone',
          'institution_type': 'Institution_Type__c',
          'alumni_count': 'Alumni_Count__c',
          'budget_range': 'Budget_Range__c',
          'implementation_timeline': 'Timeline__c',
          'current_challenges': 'Description'
        },
        leadScore: 85,
        tags: ['institution', 'demo-request', 'qualified-lead', 'high-priority']
      },
      notifications: {
        enabled: true,
        recipients: ['sales@company.com', 'demos@company.com'],
        template: 'institution-demo-request-notification',
        subject: 'High Priority Demo Request: {{institution_name}} ({{alumni_count}} alumni)'
      }
    },
    theme: 'modern',
    colorScheme: 'primary',
    validateOnBlur: true,
    showValidationSummary: true,
    enableAutoSave: true,
    autoSaveInterval: 45,
    trackingEnabled: true,
    trackingEvents: ['demo_request_view', 'demo_request_submit', 'qualification_complete'],
    honeypot: true,
    recaptcha: {
      enabled: true,
      theme: 'light'
    }
  }
}

export const contactTemplate: FormTemplate = {
  id: 'contact-general',
  name: 'General Contact Form',
  description: 'Comprehensive contact form with inquiry categorization and intelligent routing',
  category: 'contact',
  config: {
    title: 'Contact Us',
    description: 'Get in touch with our team. We\'ll route your inquiry to the right department for a faster response.',
    fields: [
      {
        id: 'name',
        type: 'text',
        name: 'name',
        label: 'Full Name',
        placeholder: 'Enter your full name',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Full name is required' },
          { rule: 'minLength', value: 2, message: 'Name must be at least 2 characters' }
        ]
      },
      {
        id: 'organization',
        type: 'text',
        name: 'organization',
        label: 'Organization/Company',
        placeholder: 'Enter your organization name',
        required: false,
        width: 'half'
      },
      {
        id: 'email',
        type: 'email',
        name: 'email',
        label: 'Email Address',
        placeholder: 'Enter your email address',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Email address is required' },
          { rule: 'email', message: 'Please enter a valid email address' }
        ]
      },
      {
        id: 'phone',
        type: 'phone',
        name: 'phone',
        label: 'Phone Number',
        placeholder: 'Enter your phone number',
        required: false,
        width: 'half',
        validation: [
          { rule: 'phone', message: 'Please enter a valid phone number' }
        ]
      },
      {
        id: 'contact-role',
        type: 'select',
        name: 'contact_role',
        label: 'I am a...',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Please select your role' }
        ],
        options: [
          { label: 'Current Alumni', value: 'alumni' },
          { label: 'Prospective Student', value: 'prospective_student' },
          { label: 'Current Student', value: 'current_student' },
          { label: 'Institution Staff/Faculty', value: 'institution_staff' },
          { label: 'Employer/Recruiter', value: 'employer' },
          { label: 'Partner Organization', value: 'partner' },
          { label: 'Media/Press', value: 'media' },
          { label: 'Other', value: 'other' }
        ]
      },
      {
        id: 'inquiry-category',
        type: 'select',
        name: 'inquiry_category',
        label: 'Inquiry Category',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Please select an inquiry category' }
        ],
        options: [
          { label: 'General Information', value: 'general' },
          { label: 'Technical Support', value: 'technical_support' },
          { label: 'Account/Login Issues', value: 'account_issues' },
          { label: 'Sales & Pricing', value: 'sales' },
          { label: 'Demo Request', value: 'demo_request' },
          { label: 'Partnership Opportunities', value: 'partnership' },
          { label: 'Event Inquiries', value: 'events' },
          { label: 'Career Services', value: 'career_services' },
          { label: 'Alumni Directory', value: 'alumni_directory' },
          { label: 'Mentorship Programs', value: 'mentorship' },
          { label: 'Fundraising/Donations', value: 'fundraising' },
          { label: 'Media/Press Inquiries', value: 'media' },
          { label: 'Bug Report', value: 'bug_report' },
          { label: 'Feature Request', value: 'feature_request' },
          { label: 'Privacy/Data Concerns', value: 'privacy' },
          { label: 'Other', value: 'other' }
        ]
      },
      {
        id: 'priority-level',
        type: 'select',
        name: 'priority_level',
        label: 'Priority Level',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Please select a priority level' }
        ],
        options: [
          { label: 'Low - General inquiry', value: 'low' },
          { label: 'Medium - Need response within 2-3 days', value: 'medium' },
          { label: 'High - Need response within 24 hours', value: 'high' },
          { label: 'Urgent - Need immediate attention', value: 'urgent' }
        ]
      },
      {
        id: 'preferred-contact-method',
        type: 'select',
        name: 'preferred_contact_method',
        label: 'Preferred Contact Method',
        required: false,
        width: 'half',
        options: [
          { label: 'Email', value: 'email' },
          { label: 'Phone Call', value: 'phone' },
          { label: 'Text Message', value: 'text' },
          { label: 'No preference', value: 'no_preference' }
        ]
      },
      {
        id: 'subject',
        type: 'text',
        name: 'subject',
        label: 'Subject',
        placeholder: 'Brief subject line for your inquiry',
        required: true,
        width: 'full',
        validation: [
          { rule: 'required', message: 'Subject is required' },
          { rule: 'minLength', value: 5, message: 'Subject must be at least 5 characters' },
          { rule: 'maxLength', value: 100, message: 'Subject cannot exceed 100 characters' }
        ]
      },
      {
        id: 'message',
        type: 'textarea',
        name: 'message',
        label: 'Message',
        placeholder: 'Please provide detailed information about your inquiry...',
        required: true,
        width: 'full',
        rows: 6,
        validation: [
          { rule: 'required', message: 'Message is required' },
          { rule: 'minLength', value: 20, message: 'Message must be at least 20 characters' },
          { rule: 'maxLength', value: 2000, message: 'Message cannot exceed 2000 characters' }
        ]
      },
      {
        id: 'attachments-needed',
        type: 'checkbox',
        name: 'attachments_needed',
        label: 'I have files or screenshots to share related to this inquiry',
        required: false,
        width: 'full'
      },
      {
        id: 'follow-up-consent',
        type: 'checkbox',
        name: 'follow_up_consent',
        label: 'I consent to follow-up communications regarding this inquiry',
        required: true,
        width: 'full',
        validation: [
          { rule: 'required', message: 'You must consent to follow-up communications' }
        ]
      }
    ],
    layout: 'two-column',
    spacing: 'default',
    submission: {
      method: 'POST',
      action: '/api/forms/contact',
      successMessage: 'Thank you for contacting us! Your inquiry has been received and routed to the appropriate team. You can expect a response based on your selected priority level.',
      redirectUrl: '/contact-confirmation',
      crmIntegration: {
        enabled: true,
        provider: 'hubspot',
        endpoint: '/api/crm/hubspot/tickets',
        mapping: {
          'name': 'contact_name',
          'email': 'email',
          'phone': 'phone',
          'organization': 'company',
          'subject': 'subject',
          'message': 'content',
          'inquiry_category': 'hs_ticket_category',
          'priority_level': 'hs_ticket_priority'
        },
        leadScore: 30,
        tags: ['contact_form', 'support_inquiry']
      },
      notifications: {
        enabled: true,
        recipients: [], // Dynamic routing based on inquiry category
        template: 'contact-form-notification',
        subject: '[{{priority_level|upper}}] {{inquiry_category|title}}: {{subject}}'
      }
    },
    theme: 'modern',
    colorScheme: 'primary',
    validateOnBlur: true,
    showValidationSummary: true,
    enableAutoSave: true,
    autoSaveInterval: 60,
    trackingEnabled: true,
    trackingEvents: ['contact_form_view', 'contact_form_submit', 'inquiry_categorized'],
    honeypot: true,
    recaptcha: {
      enabled: true,
      theme: 'light'
    }
  }
}

// Newsletter signup template
export const newsletterSignupTemplate: FormTemplate = {
  id: 'newsletter-signup',
  name: 'Newsletter Signup',
  description: 'Simple newsletter subscription form with preference options',
  category: 'newsletter',
  config: {
    title: 'Stay Connected',
    description: 'Subscribe to our newsletter for the latest alumni news, events, and opportunities.',
    fields: [
      {
        id: 'email',
        type: 'email',
        name: 'email',
        label: 'Email Address',
        placeholder: 'Enter your email address',
        required: true,
        width: 'full',
        validation: [
          { rule: 'required', message: 'Email address is required' },
          { rule: 'email', message: 'Please enter a valid email address' }
        ]
      },
      {
        id: 'first-name',
        type: 'text',
        name: 'first_name',
        label: 'First Name',
        placeholder: 'Enter your first name',
        required: false,
        width: 'half'
      },
      {
        id: 'interests',
        type: 'checkbox',
        name: 'newsletter_interests',
        label: 'What would you like to hear about?',
        required: false,
        width: 'full',
        multiple: true,
        options: [
          { label: 'Alumni Events', value: 'events' },
          { label: 'Career Opportunities', value: 'careers' },
          { label: 'Alumni Spotlights', value: 'spotlights' },
          { label: 'Institution News', value: 'news' },
          { label: 'Fundraising Updates', value: 'fundraising' }
        ]
      },
      {
        id: 'frequency',
        type: 'select',
        name: 'email_frequency',
        label: 'Email Frequency',
        required: false,
        width: 'half',
        defaultValue: 'monthly',
        options: [
          { label: 'Weekly', value: 'weekly' },
          { label: 'Monthly', value: 'monthly' },
          { label: 'Quarterly', value: 'quarterly' }
        ]
      }
    ],
    layout: 'single-column',
    spacing: 'compact',
    submission: {
      method: 'POST',
      action: '/api/forms/newsletter-signup',
      successMessage: 'Thank you for subscribing! Check your email to confirm your subscription.',
      crmIntegration: {
        enabled: true,
        provider: 'hubspot',
        leadScore: 25,
        tags: ['newsletter', 'subscriber']
      }
    },
    theme: 'minimal',
    colorScheme: 'primary',
    validateOnBlur: true,
    trackingEnabled: true,
    trackingEvents: ['newsletter_signup_view', 'newsletter_signup_submit']
  }
}

// Event registration template
export const eventRegistrationTemplate: FormTemplate = {
  id: 'event-registration',
  name: 'Event Registration',
  description: 'Event registration form with attendee information and preferences',
  category: 'custom',
  config: {
    title: 'Event Registration',
    description: 'Register for our upcoming alumni event.',
    fields: [
      {
        id: 'attendee-name',
        type: 'text',
        name: 'attendee_name',
        label: 'Full Name',
        placeholder: 'Enter your full name',
        required: true,
        width: 'full',
        validation: [
          { rule: 'required', message: 'Full name is required' }
        ]
      },
      {
        id: 'email',
        type: 'email',
        name: 'email',
        label: 'Email Address',
        placeholder: 'Enter your email address',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Email is required' },
          { rule: 'email', message: 'Please enter a valid email address' }
        ]
      },
      {
        id: 'phone',
        type: 'phone',
        name: 'phone',
        label: 'Phone Number',
        placeholder: 'Enter your phone number',
        required: true,
        width: 'half',
        validation: [
          { rule: 'required', message: 'Phone number is required' }
        ]
      },
      {
        id: 'graduation-year',
        type: 'select',
        name: 'graduation_year',
        label: 'Graduation Year',
        required: true,
        width: 'half',
        options: Array.from({ length: 50 }, (_, i) => {
          const year = new Date().getFullYear() - i + 5
          return { label: year.toString(), value: year.toString() }
        })
      },
      {
        id: 'guest-count',
        type: 'number',
        name: 'guest_count',
        label: 'Number of Guests',
        placeholder: '0',
        required: false,
        width: 'half',
        min: 0,
        max: 5,
        defaultValue: 0
      },
      {
        id: 'dietary-restrictions',
        type: 'textarea',
        name: 'dietary_restrictions',
        label: 'Dietary Restrictions or Special Needs',
        placeholder: 'Please list any dietary restrictions or accessibility needs...',
        required: false,
        width: 'full',
        rows: 3
      }
    ],
    layout: 'two-column',
    spacing: 'default',
    submission: {
      method: 'POST',
      action: '/api/forms/event-registration',
      successMessage: 'Registration successful! You will receive a confirmation email shortly.',
      crmIntegration: {
        enabled: true,
        provider: 'hubspot',
        leadScore: 40,
        tags: ['event_registration', 'attendee']
      }
    },
    theme: 'modern',
    colorScheme: 'primary',
    validateOnBlur: true,
    trackingEnabled: true,
    trackingEvents: ['event_registration_view', 'event_registration_submit']
  }
}

// Alias exports for backward compatibility
export const leadCaptureTemplate = individualSignupTemplate
export const demoRequestTemplate = institutionDemoRequestTemplate

export const formTemplates: FormTemplate[] = [
  individualSignupTemplate,
  institutionDemoRequestTemplate,
  contactTemplate,
  newsletterSignupTemplate,
  eventRegistrationTemplate
]

export const getTemplatesByCategory = (category: string): FormTemplate[] => {
  return formTemplates.filter(template => template.category === category)
}

export const getTemplatesByAudience = (audienceType: string): FormTemplate[] => {
  return formTemplates.filter(template => template.audienceType === audienceType)
}