<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        $user = Auth::user();

        $notifications = $this->notificationService->getNotificationHistory($user, 50);
        $unreadCount = $user->unreadNotifications()->count();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function preferences()
    {
        $user = Auth::user();
        $preferences = NotificationPreference::getUserPreferences($user->id);

        return Inertia::render('Notifications/Preferences', [
            'preferences' => $preferences,
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'preferences' => 'required|array',
            'preferences.*.notification_type' => 'required|string',
            'preferences.*.email_enabled' => 'boolean',
            'preferences.*.sms_enabled' => 'boolean',
            'preferences.*.in_app_enabled' => 'boolean',
            'preferences.*.push_enabled' => 'boolean',
        ]);

        foreach ($request->preferences as $preference) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'notification_type' => $preference['notification_type'],
                ],
                [
                    'email_enabled' => $preference['email_enabled'] ?? true,
                    'sms_enabled' => $preference['sms_enabled'] ?? false,
                    'in_app_enabled' => $preference['in_app_enabled'] ?? true,
                    'push_enabled' => $preference['push_enabled'] ?? true,
                ]
            );
        }

        return back()->with('success', 'Notification preferences updated successfully!');
    }

    public function markAsRead(Request $request, $notificationId)
    {
        $user = Auth::user();
        $this->notificationService->markNotificationAsRead($user, $notificationId);

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        $user = Auth::user();
        $this->notificationService->markAllNotificationsAsRead($user);

        return response()->json(['success' => true]);
    }

    public function getUnread()
    {
        $user = Auth::user();
        $notifications = $this->notificationService->getUnreadNotifications($user);

        return response()->json([
            'notifications' => $notifications,
            'count' => $notifications->count(),
        ]);
    }

    public function testNotification(Request $request)
    {
        if (! app()->environment('local')) {
            abort(404);
        }

        $user = Auth::user();
        $type = $request->get('type', 'job_match');

        switch ($type) {
            case 'job_match':
                // Create a dummy job for testing
                $job = (object) [
                    'id' => 1,
                    'title' => 'Software Developer',
                    'employer' => (object) ['company_name' => 'Tech Company Inc.'],
                    'application_deadline' => now()->addDays(7),
                ];
                $this->notificationService->sendJobMatchNotification($user, $job);
                break;

            case 'application_status':
                // Create a dummy application for testing
                $application = (object) [
                    'id' => 1,
                    'job' => (object) [
                        'title' => 'Software Developer',
                        'employer' => (object) ['company_name' => 'Tech Company Inc.'],
                    ],
                ];
                $this->notificationService->sendApplicationStatusNotification($user, $application, 'pending', 'reviewed');
                break;

            case 'system_updates':
                $this->notificationService->sendSystemUpdateNotification(
                    [$user],
                    'System Maintenance',
                    'The system will be under maintenance tonight from 2 AM to 4 AM.'
                );
                break;
        }

        return response()->json(['message' => 'Test notification sent!']);
    }
}
