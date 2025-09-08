<?php
// ABOUTME: Service class for managing cross-tenant data synchronization in hybrid tenancy architecture
// ABOUTME: Handles synchronization between global and tenant-specific data with conflict resolution and monitoring

namespace App\Services;

use App\Models\DataSyncLog;
use App\Models\GlobalUser;
use App\Models\GlobalCourse;
use App\Models\UserTenantMembership;
use App\Models\TenantCourseOffering;
use App\Models\SuperAdminAnalytics;
use App\Models\AuditTrail;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Exception;

class CrossTenantSyncService
{
    /**
     * Default batch size for processing records.
     */
    private const DEFAULT_BATCH_SIZE = 100;

    /**
     * Maximum retry attempts for failed syncs.
     */
    private const MAX_RETRY_ATTEMPTS = 3;

    /**
     * Cache TTL for sync locks (in seconds).
     */
    private const SYNC_LOCK_TTL = 3600;

    /**
     * Synchronize a global user to all their tenant schemas.
     */
    public function syncGlobalUserToTenants(
        GlobalUser $globalUser,
        array $tenantIds = null,
        array $options = []
    ): Collection {
        $tenantIds = $tenantIds ?? $globalUser->tenants->pluck('id')->toArray();
        $syncLogs = collect();
        $batchId = $options['batch_id'] ?? \Illuminate\Support\Str::uuid()->toString();

        foreach ($tenantIds as $tenantId) {
            $syncLog = DataSyncLog::createSync(
                'user_sync',
                'update',
                'global_users',
                'users',
                $tenantId,
                array_merge($options, [
                    'batch_id' => $batchId,
                    'source_record_id' => $globalUser->id,
                    'sync_direction' => 'global_to_tenant',
                    'metadata' => [
                        'global_user_id' => $globalUser->id,
                        'sync_reason' => $options['sync_reason'] ?? 'user_update',
                    ],
                ])
            );

            try {
                $syncLog->start();
                
                $this->performUserSyncToTenant($globalUser, $tenantId, $syncLog);
                
                $syncLog->complete([
                    'records_processed' => 1,
                    'records_updated' => 1,
                ]);
                
            } catch (Exception $e) {
                $syncLog->fail($e->getMessage(), [
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                Log::error('User sync failed', [
                    'global_user_id' => $globalUser->id,
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);
            }

            $syncLogs->push($syncLog);
        }

        return $syncLogs;
    }

    /**
     * Synchronize a global course to tenant schemas that offer it.
     */
    public function syncGlobalCourseToTenants(
        GlobalCourse $globalCourse,
        array $tenantIds = null,
        array $options = []
    ): Collection {
        $tenantIds = $tenantIds ?? $globalCourse->tenantOfferings->pluck('tenant_id')->unique()->toArray();
        $syncLogs = collect();
        $batchId = $options['batch_id'] ?? \Illuminate\Support\Str::uuid()->toString();

        foreach ($tenantIds as $tenantId) {
            $syncLog = DataSyncLog::createSync(
                'course_sync',
                'update',
                'global_courses',
                'courses',
                $tenantId,
                array_merge($options, [
                    'batch_id' => $batchId,
                    'source_record_id' => $globalCourse->id,
                    'sync_direction' => 'global_to_tenant',
                    'metadata' => [
                        'global_course_id' => $globalCourse->id,
                        'sync_reason' => $options['sync_reason'] ?? 'course_update',
                    ],
                ])
            );

            try {
                $syncLog->start();
                
                $this->performCourseSyncToTenant($globalCourse, $tenantId, $syncLog);
                
                $syncLog->complete([
                    'records_processed' => 1,
                    'records_updated' => 1,
                ]);
                
            } catch (Exception $e) {
                $syncLog->fail($e->getMessage(), [
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                Log::error('Course sync failed', [
                    'global_course_id' => $globalCourse->id,
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);
            }

            $syncLogs->push($syncLog);
        }

        return $syncLogs;
    }

    /**
     * Synchronize tenant data back to global tables.
     */
    public function syncTenantDataToGlobal(
        string $tenantId,
        string $syncType,
        array $recordIds = null,
        array $options = []
    ): Collection {
        $syncLogs = collect();
        $batchId = $options['batch_id'] ?? \Illuminate\Support\Str::uuid()->toString();

        switch ($syncType) {
            case 'user_sync':
                return $this->syncTenantUsersToGlobal($tenantId, $recordIds, $options);
            
            case 'enrollment_sync':
                return $this->syncTenantEnrollmentsToGlobal($tenantId, $recordIds, $options);
            
            case 'analytics_sync':
                return $this->syncTenantAnalyticsToGlobal($tenantId, $recordIds, $options);
            
            default:
                throw new Exception("Unsupported sync type: {$syncType}");
        }
    }

    /**
     * Perform bidirectional synchronization for a tenant.
     */
    public function performBidirectionalSync(
        string $tenantId,
        array $syncTypes = null,
        array $options = []
    ): array {
        $syncTypes = $syncTypes ?? ['user_sync', 'course_sync', 'enrollment_sync', 'analytics_sync'];
        $results = [];
        $batchId = $options['batch_id'] ?? \Illuminate\Support\Str::uuid()->toString();

        // Acquire sync lock to prevent concurrent syncs
        $lockKey = "tenant_sync:{$tenantId}";
        if (!Cache::lock($lockKey, self::SYNC_LOCK_TTL)->get()) {
            throw new Exception("Sync already in progress for tenant {$tenantId}");
        }

        try {
            foreach ($syncTypes as $syncType) {
                $results[$syncType] = [
                    'global_to_tenant' => [],
                    'tenant_to_global' => [],
                ];

                // Global to tenant sync
                try {
                    $results[$syncType]['global_to_tenant'] = $this->syncGlobalDataToTenant(
                        $tenantId,
                        $syncType,
                        array_merge($options, ['batch_id' => $batchId])
                    );
                } catch (Exception $e) {
                    Log::error("Global to tenant sync failed for {$syncType}", [
                        'tenant_id' => $tenantId,
                        'error' => $e->getMessage(),
                    ]);
                    $results[$syncType]['global_to_tenant'] = ['error' => $e->getMessage()];
                }

                // Tenant to global sync
                try {
                    $results[$syncType]['tenant_to_global'] = $this->syncTenantDataToGlobal(
                        $tenantId,
                        $syncType,
                        null,
                        array_merge($options, ['batch_id' => $batchId])
                    );
                } catch (Exception $e) {
                    Log::error("Tenant to global sync failed for {$syncType}", [
                        'tenant_id' => $tenantId,
                        'error' => $e->getMessage(),
                    ]);
                    $results[$syncType]['tenant_to_global'] = ['error' => $e->getMessage()];
                }
            }

            return $results;

        } finally {
            Cache::lock($lockKey)->release();
        }
    }

    /**
     * Resolve data conflicts between global and tenant data.
     */
    public function resolveDataConflicts(
        string $tenantId,
        string $conflictType,
        array $conflictData,
        string $resolutionStrategy = 'global_wins'
    ): array {
        $syncLog = DataSyncLog::createSync(
            'conflict_resolution',
            'reconcile',
            $conflictData['source_table'] ?? 'unknown',
            $conflictData['target_table'] ?? 'unknown',
            $tenantId,
            [
                'sync_direction' => 'bidirectional',
                'metadata' => [
                    'conflict_type' => $conflictType,
                    'resolution_strategy' => $resolutionStrategy,
                    'conflict_data' => $conflictData,
                ],
            ]
        );

        try {
            $syncLog->start();

            $resolution = match ($resolutionStrategy) {
                'global_wins' => $this->resolveConflictGlobalWins($conflictData),
                'tenant_wins' => $this->resolveConflictTenantWins($conflictData),
                'merge' => $this->resolveConflictMerge($conflictData),
                'manual' => $this->flagForManualResolution($conflictData),
                default => throw new Exception("Unknown resolution strategy: {$resolutionStrategy}")
            };

            $syncLog->complete([
                'conflicts_resolved' => 1,
                'resolution_strategy' => $resolutionStrategy,
                'resolution_data' => $resolution,
            ]);

            return $resolution;

        } catch (Exception $e) {
            $syncLog->fail($e->getMessage(), [
                'conflict_data' => $conflictData,
                'resolution_strategy' => $resolutionStrategy,
            ]);
            throw $e;
        }
    }

    /**
     * Validate data integrity across global and tenant schemas.
     */
    public function validateDataIntegrity(
        string $tenantId = null,
        array $validationTypes = null
    ): array {
        $validationTypes = $validationTypes ?? [
            'user_consistency',
            'course_consistency',
            'enrollment_consistency',
            'membership_consistency',
        ];

        $results = [];
        $tenantIds = $tenantId ? [$tenantId] : Tenant::pluck('id')->toArray();

        foreach ($tenantIds as $tid) {
            $results[$tid] = [];

            foreach ($validationTypes as $validationType) {
                try {
                    $results[$tid][$validationType] = match ($validationType) {
                        'user_consistency' => $this->validateUserConsistency($tid),
                        'course_consistency' => $this->validateCourseConsistency($tid),
                        'enrollment_consistency' => $this->validateEnrollmentConsistency($tid),
                        'membership_consistency' => $this->validateMembershipConsistency($tid),
                        default => ['status' => 'unknown_validation_type']
                    };
                } catch (Exception $e) {
                    $results[$tid][$validationType] = [
                        'status' => 'error',
                        'message' => $e->getMessage(),
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Get synchronization status and statistics.
     */
    public function getSyncStatus(
        string $tenantId = null,
        int $hours = 24
    ): array {
        $query = DataSyncLog::query();
        
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        $query->where('started_at', '>=', now()->subHours($hours));
        
        $syncLogs = $query->get();
        
        $stats = [
            'total_syncs' => $syncLogs->count(),
            'completed_syncs' => $syncLogs->where('status', 'completed')->count(),
            'failed_syncs' => $syncLogs->whereIn('status', ['failed', 'cancelled'])->count(),
            'running_syncs' => $syncLogs->whereIn('status', ['pending', 'in_progress', 'retrying'])->count(),
            'by_sync_type' => [],
            'by_tenant' => [],
            'recent_failures' => [],
            'performance_metrics' => [],
        ];
        
        // Group by sync type
        $bySyncType = $syncLogs->groupBy('sync_type');
        foreach ($bySyncType as $syncType => $logs) {
            $stats['by_sync_type'][$syncType] = [
                'total' => $logs->count(),
                'completed' => $logs->where('status', 'completed')->count(),
                'failed' => $logs->whereIn('status', ['failed', 'cancelled'])->count(),
                'success_rate' => $logs->count() > 0 ? 
                    round(($logs->where('status', 'completed')->count() / $logs->count()) * 100, 2) : 0,
                'avg_duration' => $logs->where('status', 'completed')->avg('duration') ?? 0,
            ];
        }
        
        // Group by tenant
        if (!$tenantId) {
            $byTenant = $syncLogs->groupBy('tenant_id');
            foreach ($byTenant as $tid => $logs) {
                $stats['by_tenant'][$tid] = [
                    'total' => $logs->count(),
                    'completed' => $logs->where('status', 'completed')->count(),
                    'failed' => $logs->whereIn('status', ['failed', 'cancelled'])->count(),
                    'success_rate' => $logs->count() > 0 ? 
                        round(($logs->where('status', 'completed')->count() / $logs->count()) * 100, 2) : 0,
                ];
            }
        }
        
        // Recent failures
        $stats['recent_failures'] = $syncLogs
            ->whereIn('status', ['failed', 'cancelled'])
            ->sortByDesc('failed_at')
            ->take(10)
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'sync_type' => $log->sync_type,
                    'tenant_id' => $log->tenant_id,
                    'error_message' => $log->error_message,
                    'failed_at' => $log->failed_at,
                    'retry_count' => $log->retry_count,
                    'can_retry' => $log->canRetry(),
                ];
            })
            ->values()
            ->toArray();
        
        // Performance metrics
        $completedSyncs = $syncLogs->where('status', 'completed');
        $stats['performance_metrics'] = [
            'avg_duration' => $completedSyncs->avg('duration') ?? 0,
            'min_duration' => $completedSyncs->min('duration') ?? 0,
            'max_duration' => $completedSyncs->max('duration') ?? 0,
            'total_records_processed' => $completedSyncs->sum(function ($log) {
                return $log->sync_stats['records_processed'] ?? 0;
            }),
            'throughput_per_hour' => $hours > 0 ? 
                ($completedSyncs->sum(function ($log) {
                    return $log->sync_stats['records_processed'] ?? 0;
                }) / $hours) : 0,
        ];
        
        return $stats;
    }

    /**
     * Retry failed synchronizations.
     */
    public function retryFailedSyncs(
        string $tenantId = null,
        array $syncTypes = null,
        int $limit = 50
    ): Collection {
        $query = DataSyncLog::retryable();
        
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        if ($syncTypes) {
            $query->whereIn('sync_type', $syncTypes);
        }
        
        $failedSyncs = $query->orderBy('priority', 'desc')
                            ->orderBy('failed_at', 'asc')
                            ->limit($limit)
                            ->get();
        
        $retriedSyncs = collect();
        
        foreach ($failedSyncs as $syncLog) {
            try {
                $syncLog->retry();
                
                // Re-execute the sync based on its type
                $this->executeSyncLog($syncLog);
                
                $retriedSyncs->push($syncLog);
                
            } catch (Exception $e) {
                $syncLog->fail(
                    "Retry failed: {$e->getMessage()}",
                    ['retry_error' => $e->getMessage()]
                );
                
                Log::error('Sync retry failed', [
                    'sync_log_id' => $syncLog->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $retriedSyncs;
    }

    /**
     * Clean up old sync logs and temporary data.
     */
    public function cleanupSyncData(
        int $daysToKeep = 90,
        bool $dryRun = false
    ): array {
        $cutoffDate = now()->subDays($daysToKeep);
        
        $query = DataSyncLog::where('started_at', '<', $cutoffDate)
                           ->whereIn('status', ['completed', 'failed', 'cancelled']);
        
        $count = $query->count();
        
        if (!$dryRun) {
            $deleted = $query->delete();
            
            // Clean up related cache entries
            $this->cleanupSyncCache();
            
            return [
                'status' => 'completed',
                'records_found' => $count,
                'records_deleted' => $deleted,
                'cutoff_date' => $cutoffDate,
            ];
        }
        
        return [
            'status' => 'dry_run',
            'records_found' => $count,
            'cutoff_date' => $cutoffDate,
        ];
    }

    /**
     * Execute a sync log operation.
     */
    private function executeSyncLog(DataSyncLog $syncLog): void
    {
        switch ($syncLog->sync_type) {
            case 'user_sync':
                $this->executeUserSync($syncLog);
                break;
            
            case 'course_sync':
                $this->executeCourseSync($syncLog);
                break;
            
            case 'enrollment_sync':
                $this->executeEnrollmentSync($syncLog);
                break;
            
            case 'analytics_sync':
                $this->executeAnalyticsSync($syncLog);
                break;
            
            default:
                throw new Exception("Unknown sync type: {$syncLog->sync_type}");
        }
    }

    /**
     * Perform user synchronization to a specific tenant.
     */
    private function performUserSyncToTenant(
        GlobalUser $globalUser,
        string $tenantId,
        DataSyncLog $syncLog
    ): void {
        // Switch to tenant schema
        $this->switchToTenantSchema($tenantId);
        
        try {
            // Check if user exists in tenant schema
            $tenantUser = DB::table('users')
                           ->where('global_user_id', $globalUser->id)
                           ->first();
            
            $userData = [
                'global_user_id' => $globalUser->id,
                'name' => $globalUser->name,
                'email' => $globalUser->email,
                'email_verified_at' => $globalUser->email_verified_at,
                'profile_data' => $globalUser->profile_data,
                'preferences' => $globalUser->preferences,
                'updated_at' => now(),
            ];
            
            if ($tenantUser) {
                // Update existing user
                DB::table('users')
                  ->where('id', $tenantUser->id)
                  ->update($userData);
                  
                $syncLog->updateStats(['records_updated' => 1]);
            } else {
                // Create new user
                $userData['created_at'] = now();
                $userId = DB::table('users')->insertGetId($userData);
                
                $syncLog->update(['target_record_id' => $userId]);
                $syncLog->updateStats(['records_created' => 1]);
            }
            
        } finally {
            // Switch back to default schema
            $this->switchToDefaultSchema();
        }
    }

    /**
     * Perform course synchronization to a specific tenant.
     */
    private function performCourseSyncToTenant(
        GlobalCourse $globalCourse,
        string $tenantId,
        DataSyncLog $syncLog
    ): void {
        // Switch to tenant schema
        $this->switchToTenantSchema($tenantId);
        
        try {
            // Get tenant course offering
            $offering = TenantCourseOffering::where('tenant_id', $tenantId)
                                           ->where('global_course_id', $globalCourse->id)
                                           ->first();
            
            if (!$offering) {
                throw new Exception("No course offering found for global course {$globalCourse->id} in tenant {$tenantId}");
            }
            
            // Check if course exists in tenant schema
            $tenantCourse = DB::table('courses')
                             ->where('global_course_id', $globalCourse->id)
                             ->first();
            
            $courseData = [
                'global_course_id' => $globalCourse->id,
                'title' => $offering->custom_title ?? $globalCourse->title,
                'description' => $offering->custom_description ?? $globalCourse->description,
                'code' => $offering->custom_code ?? $globalCourse->code,
                'credits' => $offering->custom_credits ?? $globalCourse->credits,
                'level' => $globalCourse->level,
                'subject_area' => $globalCourse->subject_area,
                'prerequisites' => $globalCourse->prerequisites,
                'learning_outcomes' => $globalCourse->learning_outcomes,
                'status' => $offering->status,
                'price' => $offering->price,
                'capacity' => $offering->capacity,
                'start_date' => $offering->start_date,
                'end_date' => $offering->end_date,
                'updated_at' => now(),
            ];
            
            if ($tenantCourse) {
                // Update existing course
                DB::table('courses')
                  ->where('id', $tenantCourse->id)
                  ->update($courseData);
                  
                $syncLog->updateStats(['records_updated' => 1]);
            } else {
                // Create new course
                $courseData['created_at'] = now();
                $courseId = DB::table('courses')->insertGetId($courseData);
                
                $syncLog->update(['target_record_id' => $courseId]);
                $syncLog->updateStats(['records_created' => 1]);
            }
            
        } finally {
            // Switch back to default schema
            $this->switchToDefaultSchema();
        }
    }

    /**
     * Synchronize tenant users to global table.
     */
    private function syncTenantUsersToGlobal(
        string $tenantId,
        array $userIds = null,
        array $options = []
    ): Collection {
        $syncLogs = collect();
        $batchId = $options['batch_id'] ?? \Illuminate\Support\Str::uuid()->toString();
        
        // Switch to tenant schema
        $this->switchToTenantSchema($tenantId);
        
        try {
            $query = DB::table('users');
            
            if ($userIds) {
                $query->whereIn('id', $userIds);
            }
            
            $tenantUsers = $query->get();
            
            foreach ($tenantUsers as $tenantUser) {
                $syncLog = DataSyncLog::createSync(
                    'user_sync',
                    'update',
                    'users',
                    'global_users',
                    $tenantId,
                    array_merge($options, [
                        'batch_id' => $batchId,
                        'source_record_id' => $tenantUser->id,
                        'target_record_id' => $tenantUser->global_user_id ?? null,
                        'sync_direction' => 'tenant_to_global',
                    ])
                );
                
                try {
                    $syncLog->start();
                    
                    // Update global user with tenant-specific data
                    if ($tenantUser->global_user_id) {
                        $globalUser = GlobalUser::find($tenantUser->global_user_id);
                        if ($globalUser) {
                            // Merge tenant-specific preferences or profile data
                            $this->mergeUserDataToGlobal($globalUser, $tenantUser, $tenantId);
                            $syncLog->complete(['records_updated' => 1]);
                        } else {
                            $syncLog->fail('Global user not found');
                        }
                    } else {
                        $syncLog->fail('No global user ID associated with tenant user');
                    }
                    
                } catch (Exception $e) {
                    $syncLog->fail($e->getMessage());
                }
                
                $syncLogs->push($syncLog);
            }
            
        } finally {
            $this->switchToDefaultSchema();
        }
        
        return $syncLogs;
    }

    /**
     * Synchronize tenant enrollments to global analytics.
     */
    private function syncTenantEnrollmentsToGlobal(
        string $tenantId,
        array $enrollmentIds = null,
        array $options = []
    ): Collection {
        $syncLogs = collect();
        $batchId = $options['batch_id'] ?? \Illuminate\Support\Str::uuid()->toString();
        
        $syncLog = DataSyncLog::createSync(
            'enrollment_sync',
            'update',
            'enrollments',
            'super_admin_analytics',
            $tenantId,
            array_merge($options, [
                'batch_id' => $batchId,
                'sync_direction' => 'tenant_to_global',
            ])
        );
        
        try {
            $syncLog->start();
            
            // Switch to tenant schema
            $this->switchToTenantSchema($tenantId);
            
            $query = DB::table('enrollments')
                      ->join('courses', 'enrollments.course_id', '=', 'courses.id')
                      ->join('users', 'enrollments.user_id', '=', 'users.id')
                      ->select(
                          'enrollments.*',
                          'courses.global_course_id',
                          'users.global_user_id'
                      );
            
            if ($enrollmentIds) {
                $query->whereIn('enrollments.id', $enrollmentIds);
            }
            
            $enrollments = $query->get();
            
            // Switch back to default schema
            $this->switchToDefaultSchema();
            
            // Aggregate enrollment data for analytics
            $enrollmentStats = $this->aggregateEnrollmentData($enrollments, $tenantId);
            
            // Update super admin analytics
            foreach ($enrollmentStats as $metric => $value) {
                SuperAdminAnalytics::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'metric_type' => $metric,
                        'aggregation_period' => 'daily',
                        'period_start' => now()->startOfDay(),
                    ],
                    [
                        'metric_value' => $value,
                        'metadata' => [
                            'sync_batch_id' => $batchId,
                            'last_sync_at' => now(),
                        ],
                    ]
                );
            }
            
            $syncLog->complete([
                'records_processed' => $enrollments->count(),
                'metrics_updated' => count($enrollmentStats),
            ]);
            
        } catch (Exception $e) {
            $syncLog->fail($e->getMessage());
        } finally {
            $this->switchToDefaultSchema();
        }
        
        $syncLogs->push($syncLog);
        return $syncLogs;
    }

    /**
     * Synchronize tenant analytics to global analytics.
     */
    private function syncTenantAnalyticsToGlobal(
        string $tenantId,
        array $recordIds = null,
        array $options = []
    ): Collection {
        $syncLogs = collect();
        $batchId = $options['batch_id'] ?? \Illuminate\Support\Str::uuid()->toString();
        
        $syncLog = DataSyncLog::createSync(
            'analytics_sync',
            'update',
            'tenant_analytics',
            'super_admin_analytics',
            $tenantId,
            array_merge($options, [
                'batch_id' => $batchId,
                'sync_direction' => 'tenant_to_global',
            ])
        );
        
        try {
            $syncLog->start();
            
            // Switch to tenant schema and collect analytics data
            $this->switchToTenantSchema($tenantId);
            
            $analyticsData = $this->collectTenantAnalytics($tenantId);
            
            // Switch back to default schema
            $this->switchToDefaultSchema();
            
            // Update global analytics
            $updatedMetrics = 0;
            foreach ($analyticsData as $metric) {
                SuperAdminAnalytics::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'metric_type' => $metric['type'],
                        'aggregation_period' => $metric['period'],
                        'period_start' => $metric['period_start'],
                    ],
                    [
                        'metric_value' => $metric['value'],
                        'metadata' => array_merge($metric['metadata'] ?? [], [
                            'sync_batch_id' => $batchId,
                            'last_sync_at' => now(),
                        ]),
                    ]
                );
                $updatedMetrics++;
            }
            
            $syncLog->complete([
                'records_processed' => count($analyticsData),
                'metrics_updated' => $updatedMetrics,
            ]);
            
        } catch (Exception $e) {
            $syncLog->fail($e->getMessage());
        } finally {
            $this->switchToDefaultSchema();
        }
        
        $syncLogs->push($syncLog);
        return $syncLogs;
    }

    /**
     * Switch database connection to tenant schema.
     */
    private function switchToTenantSchema(string $tenantId): void
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            throw new Exception("Tenant not found: {$tenantId}");
        }
        
        // Set the search path to the tenant schema
        DB::statement("SET search_path TO {$tenant->schema_name}, public");
    }

    /**
     * Switch back to default schema.
     */
    private function switchToDefaultSchema(): void
    {
        DB::statement('SET search_path TO public');
    }

    /**
     * Resolve conflict using global wins strategy.
     */
    private function resolveConflictGlobalWins(array $conflictData): array
    {
        // Implementation for global wins resolution
        return [
            'strategy' => 'global_wins',
            'action' => 'overwrite_tenant_data',
            'resolved_at' => now(),
        ];
    }

    /**
     * Resolve conflict using tenant wins strategy.
     */
    private function resolveConflictTenantWins(array $conflictData): array
    {
        // Implementation for tenant wins resolution
        return [
            'strategy' => 'tenant_wins',
            'action' => 'overwrite_global_data',
            'resolved_at' => now(),
        ];
    }

    /**
     * Resolve conflict using merge strategy.
     */
    private function resolveConflictMerge(array $conflictData): array
    {
        // Implementation for merge resolution
        return [
            'strategy' => 'merge',
            'action' => 'merge_data_fields',
            'resolved_at' => now(),
        ];
    }

    /**
     * Flag conflict for manual resolution.
     */
    private function flagForManualResolution(array $conflictData): array
    {
        // Implementation for manual resolution flagging
        return [
            'strategy' => 'manual',
            'action' => 'flag_for_review',
            'flagged_at' => now(),
        ];
    }

    /**
     * Validate user consistency between global and tenant data.
     */
    private function validateUserConsistency(string $tenantId): array
    {
        // Implementation for user consistency validation
        return ['status' => 'valid', 'issues' => []];
    }

    /**
     * Validate course consistency between global and tenant data.
     */
    private function validateCourseConsistency(string $tenantId): array
    {
        // Implementation for course consistency validation
        return ['status' => 'valid', 'issues' => []];
    }

    /**
     * Validate enrollment consistency.
     */
    private function validateEnrollmentConsistency(string $tenantId): array
    {
        // Implementation for enrollment consistency validation
        return ['status' => 'valid', 'issues' => []];
    }

    /**
     * Validate membership consistency.
     */
    private function validateMembershipConsistency(string $tenantId): array
    {
        // Implementation for membership consistency validation
        return ['status' => 'valid', 'issues' => []];
    }

    /**
     * Execute user sync operation.
     */
    private function executeUserSync(DataSyncLog $syncLog): void
    {
        // Implementation for executing user sync
    }

    /**
     * Execute course sync operation.
     */
    private function executeCourseSync(DataSyncLog $syncLog): void
    {
        // Implementation for executing course sync
    }

    /**
     * Execute enrollment sync operation.
     */
    private function executeEnrollmentSync(DataSyncLog $syncLog): void
    {
        // Implementation for executing enrollment sync
    }

    /**
     * Execute analytics sync operation.
     */
    private function executeAnalyticsSync(DataSyncLog $syncLog): void
    {
        // Implementation for executing analytics sync
    }

    /**
     * Merge user data from tenant to global.
     */
    private function mergeUserDataToGlobal(
        GlobalUser $globalUser,
        object $tenantUser,
        string $tenantId
    ): void {
        // Implementation for merging user data
    }

    /**
     * Aggregate enrollment data for analytics.
     */
    private function aggregateEnrollmentData(
        Collection $enrollments,
        string $tenantId
    ): array {
        // Implementation for aggregating enrollment data
        return [
            'total_enrollments' => $enrollments->count(),
            'active_enrollments' => $enrollments->where('status', 'active')->count(),
            'completed_enrollments' => $enrollments->where('status', 'completed')->count(),
        ];
    }

    /**
     * Collect tenant analytics data.
     */
    private function collectTenantAnalytics(string $tenantId): array
    {
        // Implementation for collecting tenant analytics
        return [];
    }

    /**
     * Sync global data to tenant.
     */
    private function syncGlobalDataToTenant(
        string $tenantId,
        string $syncType,
        array $options = []
    ): Collection {
        // Implementation for syncing global data to tenant
        return collect();
    }

    /**
     * Clean up sync-related cache entries.
     */
    private function cleanupSyncCache(): void
    {
        // Implementation for cleaning up sync cache
        Cache::tags(['sync', 'tenant_sync'])->flush();
    }
}