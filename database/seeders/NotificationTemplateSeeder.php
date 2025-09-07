<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'welcome_email',
                'type' => 'email',
                'subject' => 'Welcome to {{institution_name}}',
                'content' => 'Dear {{user_name}},

Welcome to {{institution_name}}! We\'re excited to have you join our community.

Your account has been successfully created and you can now access all our features.

Best regards,
{{institution_name}} Team',
                'variables' => ['user_name', 'institution_name'],
                'is_active' => true,
            ],
            [
                'name' => 'job_match_notification',
                'type' => 'email',
                'subject' => 'New Job Match: {{job_title}}',
                'content' => 'Hi {{user_name}},

Great news! We found a job that matches your profile:

Job Title: {{job_title}}
Company: {{company_name}}
Location: {{job_location}}

Click here to view the full job details and apply: {{job_url}}

Best regards,
{{institution_name}} Career Services',
                'variables' => ['user_name', 'job_title', 'company_name', 'job_location', 'job_url', 'institution_name'],
                'is_active' => true,
            ],
            [
                'name' => 'application_status_update',
                'type' => 'email',
                'subject' => 'Application Status Update: {{job_title}}',
                'content' => 'Dear {{user_name}},

Your application status for {{job_title}} at {{company_name}} has been updated.

New Status: {{status}}

{{additional_message}}

You can view your application details here: {{application_url}}

Best regards,
{{institution_name}} Team',
                'variables' => ['user_name', 'job_title', 'company_name', 'status', 'additional_message', 'application_url', 'institution_name'],
                'is_active' => true,
            ],
            [
                'name' => 'interview_reminder',
                'type' => 'email',
                'subject' => 'Interview Reminder: {{job_title}}',
                'content' => 'Hi {{user_name}},

This is a reminder about your upcoming interview:

Job: {{job_title}}
Company: {{company_name}}
Date & Time: {{interview_datetime}}
Location: {{interview_location}}
Interviewer: {{interviewer_name}}

Please arrive 15 minutes early and bring your resume.

Best regards,
{{institution_name}} Career Services',
                'variables' => ['user_name', 'job_title', 'company_name', 'interview_datetime', 'interview_location', 'interviewer_name', 'institution_name'],
                'is_active' => true,
            ],
            [
                'name' => 'password_reset',
                'type' => 'email',
                'subject' => 'Password Reset Request',
                'content' => 'Hi {{user_name}},

You recently requested to reset your password for your {{institution_name}} account.

Click the link below to reset your password:
{{reset_url}}

This link will expire in 24 hours.

If you didn\'t request this password reset, please ignore this email.

Best regards,
{{institution_name}} Team',
                'variables' => ['user_name', 'institution_name', 'reset_url'],
                'is_active' => true,
            ],
            [
                'name' => 'account_verification',
                'type' => 'email',
                'subject' => 'Verify Your Email Address',
                'content' => 'Hi {{user_name}},

Thank you for creating an account with {{institution_name}}!

Please verify your email address by clicking the link below:
{{verification_url}}

This link will expire in 24 hours.

Best regards,
{{institution_name}} Team',
                'variables' => ['user_name', 'institution_name', 'verification_url'],
                'is_active' => true,
            ],
            [
                'name' => 'job_deadline_reminder',
                'type' => 'email',
                'subject' => 'Application Deadline Approaching: {{job_title}}',
                'content' => 'Hi {{user_name}},

The application deadline for {{job_title}} at {{company_name}} is approaching.

Deadline: {{deadline_date}}
Time Remaining: {{time_remaining}}

Don\'t miss out on this opportunity! Apply now: {{job_url}}

Best regards,
{{institution_name}} Career Services',
                'variables' => ['user_name', 'job_title', 'company_name', 'deadline_date', 'time_remaining', 'job_url', 'institution_name'],
                'is_active' => true,
            ],
            [
                'name' => 'employer_contact_notification',
                'type' => 'email',
                'subject' => 'New Employer Contact Request',
                'content' => 'Hi {{user_name}},

{{employer_name}} from {{company_name}} has expressed interest in connecting with you.

They mentioned: "{{message}}"

You can view their profile and respond here: {{profile_url}}

Best regards,
{{institution_name}} Team',
                'variables' => ['user_name', 'employer_name', 'company_name', 'message', 'profile_url', 'institution_name'],
                'is_active' => true,
            ],
            [
                'name' => 'system_maintenance',
                'type' => 'email',
                'subject' => 'Scheduled System Maintenance',
                'content' => 'Hi {{user_name}},

We will be performing scheduled maintenance on our system.

Maintenance Window: {{maintenance_start}} - {{maintenance_end}}
Expected Downtime: {{downtime_duration}}

During this time, some features may be unavailable. We apologize for any inconvenience.

Best regards,
{{institution_name}} Team',
                'variables' => ['user_name', 'maintenance_start', 'maintenance_end', 'downtime_duration', 'institution_name'],
                'is_active' => true,
            ],
            [
                'name' => 'welcome_email',
                'type' => 'sms',
                'subject' => null,
                'content' => 'Welcome to {{institution_name}}! Your account is ready. Visit {{login_url}} to get started.',
                'variables' => ['institution_name', 'login_url'],
                'is_active' => true,
            ],
            [
                'name' => 'job_match_notification',
                'type' => 'sms',
                'subject' => null,
                'content' => 'New job match: {{job_title}} at {{company_name}}. Apply now: {{job_url}}',
                'variables' => ['job_title', 'company_name', 'job_url'],
                'is_active' => true,
            ],
            [
                'name' => 'interview_reminder',
                'type' => 'sms',
                'subject' => null,
                'content' => 'Interview reminder: {{job_title}} at {{interview_datetime}}. Don\'t be late!',
                'variables' => ['job_title', 'interview_datetime'],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                [
                    'name' => $template['name'],
                    'type' => $template['type'],
                ],
                $template
            );
        }
    }
}
