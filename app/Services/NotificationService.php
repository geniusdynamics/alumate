<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendJobMatchNotification($user, $job)
    {
        $preferences = NotificationPreference::getUserPreferences($user->id);
        $jobMatchPrefs = $preferences['job_match'] ?? [];

        $variables = [
            'user_name' => $user->name,
            'job_title' => $job->title,
            'company_name' => $job->employer->company_name,
            'job_url' => route('jobs.public.show', $job->id),
            'application_deadline' => $job->application_deadline ? $job->application_deadline->format('M d, Y') : 'Not specified',
        ];

        $this->sendMultiChannelNotification($user, 'job_match', $jobMatchPrefs, $variables);
    }

    public function sendApplicationStatusNotification($user, $application, $oldStatus, $newStatus)
    {
        $preferences = NotificationPreference::getUserPreferences($user->id);
        $statusPrefs = $preferences['application_status'] ?? [];

        $variables = [
            'user_name' => $user->name,
            'job_title' => $application->job->title,
            'company_name' => $application->job->employer->company_name,
            'old_status' => ucfirst(str_replace('_', ' ', $oldStatus)),
            'new_status' => ucfirst(str_replace('_', ' ', $newStatus)),
            'application_url' => route('applications.show', $application->id),
        ];

        $this->sendMultiChannelNotification($user, 'application_status', $statusPrefs, $variables);
    }

    public function sendInterviewReminderNotification($user, $application)
    {
        $preferences = NotificationPreference::getUserPreferences($user->id);
        $reminderPrefs = $preferences['interview_reminder'] ?? [];

        $variables = [
            'user_name' => $user->name,
            'job_title' => $application->job->title,
            'company_name' => $application->job->employer->company_name,
            'interview_datetime' => $application->interview_datetime->format('M d, Y \a\t g:i A'),
            'application_url' => route('applications.show', $application->id),
        ];

        $this->sendMultiChannelNotification($user, 'interview_reminder', $reminderPrefs, $variables);
    }

    public function sendJobDeadlineNotification($user, $job, $daysLeft)
    {
        $preferences = NotificationPreference::getUserPreferences($user->id);
        $deadlinePrefs = $preferences['job_deadline'] ?? [];

        $variables = [
            'user_name' => $user->name,
            'job_title' => $job->title,
            'company_name' => $job->employer->company_name,
            'days_left' => $daysLeft,
            'deadline_date' => $job->application_deadline->format('M d, Y'),
            'job_url' => route('jobs.public.show', $job->id),
        ];

        $this->sendMultiChannelNotification($user, 'job_deadline', $deadlinePrefs, $variables);
    }

    public function sendSystemUpdateNotification($users, $title, $message)
    {
        foreach ($users as $user) {
            $preferences = NotificationPreference::getUserPreferences($user->id);
            $systemPrefs = $preferences['system_updates'] ?? [];

            $variables = [
                'user_name' => $user->name,
                'update_title' => $title,
                'update_message' => $message,
                'dashboard_url' => route('dashboard'),
            ];

            $this->sendMultiChannelNotification($user, 'system_updates', $systemPrefs, $variables);
        }
    }

    public function sendEmployerContactNotification($user, $employer, $message)
    {
        $preferences = NotificationPreference::getUserPreferences($user->id);
        $contactPrefs = $preferences['employer_contact'] ?? [];

        $variables = [
            'user_name' => $user->name,
            'employer_name' => $employer->company_name,
            'contact_message' => $message,
            'employer_url' => route('employers.show', $employer->id),
        ];

        $this->sendMultiChannelNotification($user, 'employer_contact', $contactPrefs, $variables);
    }

    private function sendMultiChannelNotification($user, $type, $preferences, $variables)
    {
        // Create in-app notification first
        $notification = $user->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Notifications\\' . ucfirst(str_replace('_', '', $type)) . 'Notification',
            'data' => json_encode($variables),
            'read_at' => null,
        ]);

        // Send email notification
        if ($preferences['email_enabled'] ?? true) {
            $this->sendEmailNotification($user, $type, $variables, $notification->id);
        }

        // Send SMS notification
        if ($preferences['sms_enabled'] ?? false) {
            $this->sendSmsNotification($user, $type, $variables, $notification->id);
        }

        // Send push notification
        if ($preferences['push_enabled'] ?? true) {
            $this->sendPushNotification($user, $type, $variables, $notification->id);
        }
    }

    private function sendEmailNotification($user, $type, $variables, $notificationId)
    {
        try {
            $template = NotificationTemplate::getTemplate($type, 'email');
            if (!$template) {
                Log::warning("Email template not found for type: {$type}");
                return;
            }

            $rendered = $template->render($variables);
            
            // Log the notification attempt
            $log = NotificationLog::create([
                'notification_id' => $notificationId,
                'channel' => 'email',
                'status' => 'pending',
            ]);

            // Send email using Laravel's mail system
            Mail::raw($rendered['content'], function ($message) use ($user, $rendered) {
                $message->to($user->email)
                        ->subject($rendered['subject']);
            });

            $log->markAsSent();
        } catch (\Exception $e) {
            Log::error("Failed to send email notification: " . $e->getMessage());
            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }
        }
    }

    private function sendSmsNotification($user, $type, $variables, $notificationId)
    {
        try {
            $template = NotificationTemplate::getTemplate($type, 'sms');
            if (!$template) {
                Log::warning("SMS template not found for type: {$type}");
                return;
            }

            $rendered = $template->render($variables);
            
            // Log the notification attempt
            $log = NotificationLog::create([
                'notification_id' => $notificationId,
                'channel' => 'sms',
                'status' => 'pending',
            ]);

            // Get user's phone number
            $phoneNumber = $user->graduate->personal_information['phone'] ?? null;
            if (!$phoneNumber) {
                $log->markAsFailed('No phone number available');
                return;
            }

            // Here you would integrate with an SMS service like Twilio
            // For now, we'll just log it
            Log::info("SMS would be sent to {$phoneNumber}: " . $rendered['content']);
            
            $log->markAsSent();
        } catch (\Exception $e) {
            Log::error("Failed to send SMS notification: " . $e->getMessage());
            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }
        }
    }

    private function sendPushNotification($user, $type, $variables, $notificationId)
    {
        try {
            $template = NotificationTemplate::getTemplate($type, 'push');
            if (!$template) {
                Log::warning("Push template not found for type: {$type}");
                return;
            }

            $rendered = $template->render($variables);
            
            // Log the notification attempt
            $log = NotificationLog::create([
                'notification_id' => $notificationId,
                'channel' => 'push',
                'status' => 'pending',
            ]);

            // Here you would integrate with a push notification service
            // For now, we'll just log it
            Log::info("Push notification would be sent to user {$user->id}: " . $rendered['content']);
            
            $log->markAsSent();
        } catch (\Exception $e) {
            Log::error("Failed to send push notification: " . $e->getMessage());
            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }
        }
    }

    public function getUnreadNotifications($user, $limit = 10)
    {
        return $user->unreadNotifications()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function markNotificationAsRead($user, $notificationId)
    {
        return $user->unreadNotifications()
            ->where('id', $notificationId)
            ->update(['read_at' => now()]);
    }

    public function markAllNotificationsAsRead($user)
    {
        return $user->unreadNotifications()
            ->update(['read_at' => now()]);
    }

    public function getNotificationHistory($user, $limit = 50)
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}