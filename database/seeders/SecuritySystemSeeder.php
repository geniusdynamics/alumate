<?php

namespace Database\Seeders;

use App\Models\BackupLog;
use App\Models\NotificationTemplate;
use App\Models\SecurityEvent;
use App\Models\SystemHealthLog;
use Illuminate\Database\Seeder;

class SecuritySystemSeeder extends Seeder
{
    public function run()
    {
        $this->seedNotificationTemplates();
        $this->seedSampleSecurityEvents();
        $this->seedSystemHealthLogs();
        $this->seedBackupLogs();
    }

    private function seedNotificationTemplates()
    {
        $securityTemplates = [
            [
                'name' => 'security_alert',
                'type' => 'email',
                'subject' => 'Security Alert: {{event_type}}',
                'content' => 'A security event has been detected:

Event Type: {{event_type}}
Severity: {{severity}}
Description: {{description}}
IP Address: {{ip_address}}
Time: {{timestamp}}

Please review this event in the security dashboard: {{dashboard_url}}

Best regards,
Security Team',
                'variables' => ['event_type', 'severity', 'description', 'ip_address', 'timestamp', 'dashboard_url'],
                'is_active' => true,
            ],
            [
                'name' => 'system_health_alert',
                'type' => 'email',
                'subject' => 'System Health Alert: {{component}} Status {{status}}',
                'content' => 'System health monitoring has detected an issue:

Component: {{component}}
Status: {{status}}
Message: {{message}}
Time: {{timestamp}}

Please check the system health dashboard: {{dashboard_url}}

Best regards,
System Monitoring',
                'variables' => ['component', 'status', 'message', 'timestamp', 'dashboard_url'],
                'is_active' => true,
            ],
            [
                'name' => 'backup_failure',
                'type' => 'email',
                'subject' => 'Backup Failed: {{backup_type}}',
                'content' => 'A system backup has failed:

Backup Type: {{backup_type}}
Started: {{started_at}}
Error: {{error_message}}

Please check the backup logs and resolve the issue immediately.

Best regards,
System Administration',
                'variables' => ['backup_type', 'started_at', 'error_message'],
                'is_active' => true,
            ],
        ];

        foreach ($securityTemplates as $template) {
            NotificationTemplate::updateOrCreate(
                ['name' => $template['name'], 'type' => $template['type']],
                $template
            );
        }
    }

    private function seedSampleSecurityEvents()
    {
        // Only seed in non-production environments
        if (app()->environment('production')) {
            return;
        }

        $events = [
            [
                'event_type' => SecurityEvent::TYPE_FAILED_LOGIN,
                'severity' => SecurityEvent::SEVERITY_MEDIUM,
                'ip_address' => '192.168.1.100',
                'description' => 'Multiple failed login attempts detected',
                'metadata' => ['attempts' => 5, 'email' => 'test@example.com'],
                'resolved' => true,
                'resolved_at' => now()->subHours(2),
                'created_at' => now()->subHours(3),
            ],
            [
                'event_type' => SecurityEvent::TYPE_SUSPICIOUS_ACTIVITY,
                'severity' => SecurityEvent::SEVERITY_HIGH,
                'ip_address' => '10.0.0.50',
                'description' => 'Suspicious login from new location detected',
                'metadata' => ['user_id' => 1, 'flags' => ['new_ip_address', 'new_user_agent']],
                'resolved' => false,
                'created_at' => now()->subHour(),
            ],
            [
                'event_type' => SecurityEvent::TYPE_RATE_LIMIT_EXCEEDED,
                'severity' => SecurityEvent::SEVERITY_MEDIUM,
                'ip_address' => '203.0.113.10',
                'description' => 'Rate limit exceeded for API requests',
                'metadata' => ['requests' => 150, 'limit' => 100],
                'resolved' => true,
                'resolved_at' => now()->subMinutes(30),
                'created_at' => now()->subHour(),
            ],
        ];

        foreach ($events as $event) {
            SecurityEvent::create($event);
        }
    }

    private function seedSystemHealthLogs()
    {
        // Only seed in non-production environments
        if (app()->environment('production')) {
            return;
        }

        $components = [
            SystemHealthLog::COMPONENT_DATABASE,
            SystemHealthLog::COMPONENT_CACHE,
            SystemHealthLog::COMPONENT_STORAGE,
            SystemHealthLog::COMPONENT_QUEUE,
            SystemHealthLog::COMPONENT_MEMORY,
            SystemHealthLog::COMPONENT_DISK_SPACE,
        ];

        foreach ($components as $component) {
            SystemHealthLog::create([
                'component' => $component,
                'status' => SystemHealthLog::STATUS_HEALTHY,
                'metrics' => $this->generateSampleMetrics($component),
                'message' => ucfirst($component).' is operating normally',
                'checked_at' => now(),
            ]);
        }
    }

    private function seedBackupLogs()
    {
        // Only seed in non-production environments
        if (app()->environment('production')) {
            return;
        }

        $backups = [
            [
                'backup_type' => BackupLog::TYPE_FULL,
                'status' => BackupLog::STATUS_COMPLETED,
                'file_path' => 'backups/2024-01-15_full_backup.tar.gz',
                'file_size' => 1024 * 1024 * 500, // 500MB
                'started_at' => now()->subDays(1),
                'completed_at' => now()->subDays(1)->addHours(2),
                'metadata' => [
                    'compressed' => true,
                    'database_size' => '250 MB',
                    'files_count' => 15000,
                ],
            ],
            [
                'backup_type' => BackupLog::TYPE_INCREMENTAL,
                'status' => BackupLog::STATUS_COMPLETED,
                'file_path' => 'backups/2024-01-16_incremental_backup.tar.gz',
                'file_size' => 1024 * 1024 * 50, // 50MB
                'started_at' => now()->subHours(6),
                'completed_at' => now()->subHours(6)->addMinutes(30),
                'metadata' => [
                    'compressed' => true,
                    'database_size' => '25 MB',
                    'files_count' => 1500,
                ],
            ],
        ];

        foreach ($backups as $backup) {
            BackupLog::create($backup);
        }
    }

    private function generateSampleMetrics($component)
    {
        switch ($component) {
            case SystemHealthLog::COMPONENT_DATABASE:
                return [
                    'response_time_ms' => rand(10, 50),
                    'query_time_ms' => rand(5, 25),
                    'database_size_mb' => rand(100, 500),
                ];

            case SystemHealthLog::COMPONENT_CACHE:
                return [
                    'response_time_ms' => rand(1, 10),
                    'hit_rate_percent' => rand(85, 95),
                ];

            case SystemHealthLog::COMPONENT_STORAGE:
                return [
                    'response_time_ms' => rand(20, 100),
                    'available_space_gb' => rand(50, 200),
                ];

            case SystemHealthLog::COMPONENT_QUEUE:
                return [
                    'queue_size' => rand(0, 50),
                    'failed_jobs' => rand(0, 5),
                ];

            case SystemHealthLog::COMPONENT_MEMORY:
                return [
                    'memory_usage_bytes' => rand(1024 * 1024 * 100, 1024 * 1024 * 500),
                    'memory_limit_bytes' => 1024 * 1024 * 1024,
                    'memory_usage_percent' => rand(40, 70),
                ];

            case SystemHealthLog::COMPONENT_DISK_SPACE:
                return [
                    'disk_free_bytes' => rand(1024 * 1024 * 1024 * 10, 1024 * 1024 * 1024 * 50),
                    'disk_total_bytes' => 1024 * 1024 * 1024 * 100,
                    'disk_used_percent' => rand(30, 60),
                ];

            default:
                return [];
        }
    }
}
