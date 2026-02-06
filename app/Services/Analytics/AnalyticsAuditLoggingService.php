<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Services\TenantContextService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Analytics Audit Logging Service
 *
 * Provides comprehensive audit logging capabilities for analytics data including:
 * - Audit trail logging for all data access and modifications
 * - Configuration change tracking
 * - Audit log querying and filtering
 * - Audit log export functionality
 * - Tenant-aware audit logging with proper isolation
 */
class AnalyticsAuditLoggingService
{
    /**
     * Audit event types
     */
    public const EVENT_DATA_ACCESS = 'data_access';
    public const EVENT_DATA_CREATE = 'data_create';
    public const EVENT_DATA_UPDATE = 'data_update';
    public const EVENT_DATA_DELETE = 'data_delete';
    public const EVENT_CONFIG_CHANGE = 'config_change';
    public const EVENT_USER_ACTION = 'user_action';
    public const EVENT_SYSTEM_ACTION = 'system_action';
    public const EVENT_EXPORT = 'export';
    public const EVENT_LOGIN = 'login';
    public const EVENT_LOGOUT = 'logout';
    public const EVENT_PERMISSION_CHANGE = 'permission_change';

    /**
     * Resource types for analytics
     */
    public const RESOURCE_DASHBOARD = 'dashboard';
    public const RESOURCE_REPORT = 'report';
    public const RESOURCE_ANALYTICS = 'analytics';
    public const RESOURCE_CONFIG = 'configuration';
    public const RESOURCE_USER = 'user';
    public const RESOURCE_EXPORT = 'export';
    public const RESOURCE_SETTINGS = 'settings';

    public function __construct(
        private TenantContextService $tenantContextService
    ) {}

    /**
     * Log a generic audit event
     *
     * @param array $event Event details including type, description, metadata
     * @return int The ID of the created audit log
     */
    public function logAuditEvent(array $event): int
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        $userId = auth()->check() ? auth()->id() : null;

        $logData = [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'event_type' => $event['type'] ?? self::EVENT_USER_ACTION,
            'action' => $event['action'] ?? 'unknown',
            'resource_type' => $event['resource_type'] ?? null,
            'resource_id' => $event['resource_id'] ?? null,
            'description' => $event['description'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => json_encode($event['metadata'] ?? []),
            'created_at' => now(),
        ];

        $logId = DB::table('audit_logs')->insertGetId($logData);

        Log::info('Audit event logged', [
            'log_id' => $logId,
            'tenant_id' => $tenantId,
            'event_type' => $logData['event_type'],
            'action' => $logData['action'],
        ]);

        return $logId;
    }

    /**
     * Log data access event
     *
     * @param int|null $userId User accessing the data
     * @param string $resource Resource type being accessed
     * @param string $action Action being performed (view, export, etc.)
     * @param array $context Additional context information
     * @return int The ID of the created audit log
     */
    public function logDataAccess(?int $userId, string $resource, string $action, array $context = []): int
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $logData = [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'event_type' => self::EVENT_DATA_ACCESS,
            'action' => $action,
            'resource_type' => $resource,
            'resource_id' => $context['resource_id'] ?? null,
            'description' => "User {$userId} accessed {$resource}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => json_encode($context),
            'created_at' => now(),
        ];

        $logId = DB::table('audit_logs')->insertGetId($logData);

        Log::info('Data access logged', [
            'log_id' => $logId,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'resource' => $resource,
            'action' => $action,
        ]);

        return $logId;
    }

    /**
     * Log data modification event
     *
     * @param int|null $userId User modifying the data
     * @param string $resource Resource type being modified
     * @param array $changes Changes made (before/after)
     * @return int The ID of the created audit log
     */
    public function logDataModification(?int $userId, string $resource, array $changes): int
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $logData = [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'event_type' => $changes['event_type'] ?? self::EVENT_DATA_UPDATE,
            'action' => $changes['action'] ?? 'update',
            'resource_type' => $resource,
            'resource_id' => $changes['resource_id'] ?? null,
            'description' => "User {$userId} modified {$resource}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => json_encode([
                'before' => $changes['before'] ?? null,
                'after' => $changes['after'] ?? null,
                'fields_modified' => $changes['fields'] ?? [],
            ]),
            'created_at' => now(),
        ];

        $logId = DB::table('audit_logs')->insertGetId($logData);

        Log::info('Data modification logged', [
            'log_id' => $logId,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'resource' => $resource,
            'event_type' => $logData['event_type'],
        ]);

        return $logId;
    }

    /**
     * Log configuration change event
     *
     * @param int|null $userId User making the configuration change
     * @param string $config Configuration key being changed
     * @param array $changes Changes made to the configuration
     * @return int The ID of the created audit log
     */
    public function logConfigurationChange(?int $userId, string $config, array $changes): int
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $logData = [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'event_type' => self::EVENT_CONFIG_CHANGE,
            'action' => 'configuration_update',
            'resource_type' => self::RESOURCE_CONFIG,
            'resource_id' => null,
            'description' => "Configuration '{$config}' was modified by user {$userId}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => json_encode([
                'config_key' => $config,
                'old_value' => $changes['old_value'] ?? null,
                'new_value' => $changes['new_value'] ?? null,
            ]),
            'created_at' => now(),
        ];

        $logId = DB::table('audit_logs')->insertGetId($logData);

        Log::info('Configuration change logged', [
            'log_id' => $logId,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'config' => $config,
        ]);

        return $logId;
    }

    /**
     * Get audit logs with filters
     *
     * @param array $filters Filter parameters
     * @param int $perPage Number of results per page
     * @return LengthAwarePaginator Paginated audit logs
     */
    public function getAuditLogs(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $query = DB::table('audit_logs')
            ->where('tenant_id', $tenantId);

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['resource_type'])) {
            $query->where('resource_type', $filters['resource_type']);
        }

        if (isset($filters['resource_id'])) {
            $query->where('resource_id', $filters['resource_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['ip_address'])) {
            $query->where('ip_address', 'like', '%' . $filters['ip_address'] . '%');
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $page = $filters['page'] ?? 1;

        return new LengthAwarePaginator(
            $query->forPage($page, $perPage)->get(),
            $query->count(),
            $perPage,
            $page,
            ['path' => request()->url()]
        );
    }

    /**
     * Get a single audit log by ID
     *
     * @param int $logId The audit log ID
     * @return object|null The audit log or null if not found
     */
    public function getAuditLogById(int $logId): ?object
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $log = DB::table('audit_logs')
            ->where('id', $logId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($log && isset($log->metadata)) {
            $log->metadata = json_decode($log->metadata, true);
        }

        return $log;
    }

    /**
     * Export audit logs
     *
     * @param array $filters Filter parameters
     * @param string $format Export format (json, csv)
     * @return string Exported data
     */
    public function exportAuditLogs(array $filters = [], string $format = 'json'): string
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $query = DB::table('audit_logs')
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (isset($filters['resource_type'])) {
            $query->where('resource_type', $filters['resource_type']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Limit export size
        $limit = min($filters['limit'] ?? 10000, 10000);
        $logs = $query->limit($limit)->get();

        // Decode metadata for export
        $logs = $logs->map(function ($log) {
            if (isset($log->metadata)) {
                $log->metadata = json_decode($log->metadata, true);
            }
            return $log;
        });

        // Log the export action
        $this->logAuditEvent([
            'type' => self::EVENT_EXPORT,
            'action' => 'audit_log_export',
            'resource_type' => self::RESOURCE_EXPORT,
            'metadata' => [
                'format' => $format,
                'record_count' => count($logs),
                'filters' => $filters,
            ],
        ]);

        return match ($format) {
            'csv' => $this->convertToCsv($logs),
            'json' => $logs->toJson(),
            default => $logs->toJson(),
        };
    }

    /**
     * Get audit summary for a date range
     *
     * @param array $dateRange Date range with 'from' and 'to' keys
     * @return array Audit summary statistics
     */
    public function getAuditSummary(array $dateRange): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $query = DB::table('audit_logs')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $dateRange['from'] ?? now()->subWeek())
            ->where('created_at', '<=', $dateRange['to'] ?? now());

        // Total events
        $totalEvents = $query->count();

        // Events by type
        $eventsByType = (clone $query)
            ->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        // Events by action
        $eventsByAction = (clone $query)
            ->select('action', DB::raw('count(*) as count'))
            ->groupBy('action')
            ->pluck('count', 'action')
            ->toArray();

        // Events by user
        $eventsByUser = (clone $query)
            ->select('user_id', DB::raw('count(*) as count'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->pluck('count', 'user_id')
            ->toArray();

        // Events by resource type
        $eventsByResource = (clone $query)
            ->select('resource_type', DB::raw('count(*) as count'))
            ->groupBy('resource_type')
            ->pluck('count', 'resource_type')
            ->toArray();

        // Daily breakdown
        $dailyBreakdown = (clone $query)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Top IP addresses
        $topIpAddresses = (clone $query)
            ->select('ip_address', DB::raw('count(*) as count'))
            ->groupBy('ip_address')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'ip_address')
            ->toArray();

        return [
            'period' => [
                'from' => $dateRange['from'] ?? now()->subWeek(),
                'to' => $dateRange['to'] ?? now(),
            ],
            'total_events' => $totalEvents,
            'events_by_type' => $eventsByType,
            'events_by_action' => $eventsByAction,
            'events_by_user' => $eventsByUser,
            'events_by_resource' => $eventsByResource,
            'daily_breakdown' => $dailyBreakdown,
            'top_ip_addresses' => $topIpAddresses,
        ];
    }

    /**
     * Get audit trail for a specific user
     *
     * @param int $userId The user ID
     * @param int $limit Maximum number of records
     * @return Collection User's audit trail
     */
    public function getAuditTrail(int $userId, int $limit = 100): Collection
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $logs = DB::table('audit_logs')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $logs->map(function ($log) {
            if (isset($log->metadata)) {
                $log->metadata = json_decode($log->metadata, true);
            }
            return $log;
        });
    }

    /**
     * Search audit logs by query
     *
     * @param string $query Search query
     * @param int $limit Maximum number of results
     * @return Collection Matching audit logs
     */
    public function searchAuditLogs(string $query, int $limit = 50): Collection
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $searchTerm = '%' . $query . '%';

        $logs = DB::table('audit_logs')
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($searchTerm) {
                $q->where('action', 'like', $searchTerm)
                    ->orWhere('description', 'like', $searchTerm)
                    ->orWhere('resource_type', 'like', $searchTerm)
                    ->orWhere('ip_address', 'like', $searchTerm);
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $logs->map(function ($log) {
            if (isset($log->metadata)) {
                $log->metadata = json_decode($log->metadata, true);
            }
            return $log;
        });
    }

    /**
     * Convert collection to CSV format
     *
     * @param Collection $logs Audit logs collection
     * @return string CSV formatted string
     */
    protected function convertToCsv(Collection $logs): string
    {
        if ($logs->isEmpty()) {
            return '';
        }

        $headers = array_keys((array) $logs->first());
        $csv = implode(',', $headers) . "\n";

        foreach ($logs as $log) {
            $row = [];
            foreach ($headers as $header) {
                $value = $log->$header ?? '';
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                // Escape quotes and wrap in quotes if contains comma or quote
                if (str_contains($value, ',') || str_contains($value, '"')) {
                    $value = '"' . str_replace('"', '""', $value) . '"';
                }
                $row[] = $value;
            }
            $csv .= implode(',', $row) . "\n";
        }

        return $csv;
    }

    /**
     * Clean up old audit logs based on retention policy
     *
     * @param int $daysToKeep Number of days to retain logs
     * @return int Number of deleted records
     */
    public function cleanupOldLogs(int $daysToKeep = 365): int
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        $cutoffDate = now()->subDays($daysToKeep);

        $deleted = DB::table('audit_logs')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        Log::info('Old audit logs cleaned up', [
            'tenant_id' => $tenantId,
            'deleted_count' => $deleted,
            'cutoff_date' => $cutoffDate,
        ]);

        return $deleted;
    }

    /**
     * Get audit statistics for dashboard
     *
     * @return array Dashboard statistics
     */
    public function getDashboardStats(): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        return [
            'today_events' => DB::table('audit_logs')
                ->where('tenant_id', $tenantId)
                ->where('created_at', '>=', $today)
                ->count(),
            'week_events' => DB::table('audit_logs')
                ->where('tenant_id', $tenantId)
                ->where('created_at', '>=', $thisWeek)
                ->count(),
            'month_events' => DB::table('audit_logs')
                ->where('tenant_id', $tenantId)
                ->where('created_at', '>=', $thisMonth)
                ->count(),
            'total_users' => DB::table('audit_logs')
                ->where('tenant_id', $tenantId)
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
            'top_actions' => DB::table('audit_logs')
                ->where('tenant_id', $tenantId)
                ->select('action', DB::raw('count(*) as count'))
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(5)
                ->pluck('count', 'action')
                ->toArray(),
        ];
    }
}
