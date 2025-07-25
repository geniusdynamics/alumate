<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_type',
        'email_enabled',
        'sms_enabled',
        'in_app_enabled',
        'push_enabled',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'in_app_enabled' => 'boolean',
        'push_enabled' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public static function getDefaultPreferences()
    {
        return [
            'job_match' => [
                'email_enabled' => true,
                'sms_enabled' => false,
                'in_app_enabled' => true,
                'push_enabled' => true,
            ],
            'application_status' => [
                'email_enabled' => true,
                'sms_enabled' => true,
                'in_app_enabled' => true,
                'push_enabled' => true,
            ],
            'interview_reminder' => [
                'email_enabled' => true,
                'sms_enabled' => true,
                'in_app_enabled' => true,
                'push_enabled' => true,
            ],
            'job_deadline' => [
                'email_enabled' => true,
                'sms_enabled' => false,
                'in_app_enabled' => true,
                'push_enabled' => true,
            ],
            'system_updates' => [
                'email_enabled' => true,
                'sms_enabled' => false,
                'in_app_enabled' => true,
                'push_enabled' => false,
            ],
            'employer_contact' => [
                'email_enabled' => true,
                'sms_enabled' => false,
                'in_app_enabled' => true,
                'push_enabled' => true,
            ],
        ];
    }

    public static function createDefaultPreferences($userId)
    {
        $defaults = self::getDefaultPreferences();
        
        foreach ($defaults as $type => $settings) {
            self::create(array_merge([
                'user_id' => $userId,
                'notification_type' => $type,
            ], $settings));
        }
    }

    public static function getUserPreferences($userId)
    {
        $preferences = self::where('user_id', $userId)->get()->keyBy('notification_type');
        $defaults = self::getDefaultPreferences();
        
        $result = [];
        foreach ($defaults as $type => $defaultSettings) {
            if (isset($preferences[$type])) {
                $result[$type] = $preferences[$type]->toArray();
            } else {
                $result[$type] = array_merge([
                    'user_id' => $userId,
                    'notification_type' => $type,
                ], $defaultSettings);
            }
        }
        
        return $result;
    }
}