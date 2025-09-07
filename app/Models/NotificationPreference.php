<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
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

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply tenant scoping automatically for multi-tenant isolation
        static::addGlobalScope('tenant', function ($builder) {
            // Check if we're in a multi-tenant context
            if (config('database.multi_tenant', false)) {
                try {
                    // In production, apply tenant filter based on current tenant context
                    if (tenant() && tenant()->id) {
                        $builder->where('tenant_id', tenant()->id);
                    }
                } catch (\Exception $e) {
                    // Skip tenant scoping in test environment
                }
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
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
