<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run()
    {
        $templates = [
            // Job Match Notifications
            [
                'name' => 'job_match',
                'type' => 'email',
                'subject' => 'New Job Match: {{job_title}} at {{company_name}}',
                'content' => 'Hi {{user_name}},

We found a job that matches your profile!

Job Title: {{job_title}}
Company: {{company_name}}
Application Deadline: {{application_deadline}}

View and apply for this job: {{job_url}}

Best regards,
Graduate Tracking System',
                'variables' => ['user_name', 'job_title', 'company_name', 'job_url', 'application_deadline'],
            ],
            [
                'name' => 'job_match',
                'type' => 'sms',
                'subject' => null,
                'content' => 'Hi {{user_name}}! New job match: {{job_title}} at {{company_name}}. Apply now: {{job_url}}',
                'variables' => ['user_name', 'job_title', 'company_name', 'job_url'],
            ],
            [
                'name' => 'job_match',
                'type' => 'push',
                'subject' => 'New Job Match Available',
                'content' => '{{job_title}} at {{company_name}} - Apply now!',
                'variables' => ['job_title', 'company_name'],
            ],

            // Application Status Notifications
            [
                'name' => 'application_status',
                'type' => 'email',
                'subject' => 'Application Status Update: {{job_title}}',
                'content' => 'Hi {{user_name}},

Your application status has been updated!

Job: {{job_title}} at {{company_name}}
Status changed from: {{old_status}} to {{new_status}}

View your application: {{application_url}}

Best regards,
Graduate Tracking System',
                'variables' => ['user_name', 'job_title', 'company_name', 'old_status', 'new_status', 'application_url'],
            ],
            [
                'name' => 'application_status',
                'type' => 'sms',
                'subject' => null,
                'content' => 'Application update: {{job_title}} status changed to {{new_status}}. View details: {{application_url}}',
                'variables' => ['job_title', 'new_status', 'application_url'],
            ],
            [
                'name' => 'application_status',
                'type' => 'push',
                'subject' => 'Application Status Updated',
                'content' => '{{job_title}} - Status: {{new_status}}',
                'variables' => ['job_title', 'new_status'],
            ],

            // Interview Reminder Notifications
            [
                'name' => 'interview_reminder',
                'type' => 'email',
                'subject' => 'Interview Reminder: {{job_title}} at {{company_name}}',
                'content' => 'Hi {{user_name}},

This is a reminder about your upcoming interview!

Job: {{job_title}}
Company: {{company_name}}
Interview Date & Time: {{interview_datetime}}

View application details: {{application_url}}

Good luck with your interview!

Best regards,
Graduate Tracking System',
                'variables' => ['user_name', 'job_title', 'company_name', 'interview_datetime', 'application_url'],
            ],
            [
                'name' => 'interview_reminder',
                'type' => 'sms',
                'subject' => null,
                'content' => 'Interview reminder: {{job_title}} at {{company_name}} on {{interview_datetime}}. Good luck!',
                'variables' => ['job_title', 'company_name', 'interview_datetime'],
            ],
            [
                'name' => 'interview_reminder',
                'type' => 'push',
                'subject' => 'Interview Reminder',
                'content' => '{{job_title}} interview at {{interview_datetime}}',
                'variables' => ['job_title', 'interview_datetime'],
            ],

            // Job Deadline Notifications
            [
                'name' => 'job_deadline',
                'type' => 'email',
                'subject' => 'Application Deadline Reminder: {{job_title}}',
                'content' => 'Hi {{user_name}},

Don\'t miss out! The application deadline for this job is approaching.

Job: {{job_title}} at {{company_name}}
Deadline: {{deadline_date}} ({{days_left}} days left)

Apply now: {{job_url}}

Best regards,
Graduate Tracking System',
                'variables' => ['user_name', 'job_title', 'company_name', 'days_left', 'deadline_date', 'job_url'],
            ],
            [
                'name' => 'job_deadline',
                'type' => 'push',
                'subject' => 'Application Deadline Soon',
                'content' => '{{job_title}} - {{days_left}} days left to apply!',
                'variables' => ['job_title', 'days_left'],
            ],

            // System Update Notifications
            [
                'name' => 'system_updates',
                'type' => 'email',
                'subject' => 'System Update: {{update_title}}',
                'content' => 'Hi {{user_name}},

{{update_message}}

Visit your dashboard: {{dashboard_url}}

Best regards,
Graduate Tracking System',
                'variables' => ['user_name', 'update_title', 'update_message', 'dashboard_url'],
            ],
            [
                'name' => 'system_updates',
                'type' => 'push',
                'subject' => '{{update_title}}',
                'content' => '{{update_message}}',
                'variables' => ['update_title', 'update_message'],
            ],

            // Employer Contact Notifications
            [
                'name' => 'employer_contact',
                'type' => 'email',
                'subject' => 'Message from {{employer_name}}',
                'content' => 'Hi {{user_name}},

You have received a message from {{employer_name}}:

{{contact_message}}

View employer profile: {{employer_url}}

Best regards,
Graduate Tracking System',
                'variables' => ['user_name', 'employer_name', 'contact_message', 'employer_url'],
            ],
            [
                'name' => 'employer_contact',
                'type' => 'push',
                'subject' => 'Message from {{employer_name}}',
                'content' => '{{contact_message}}',
                'variables' => ['employer_name', 'contact_message'],
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::create($template);
        }
    }
}