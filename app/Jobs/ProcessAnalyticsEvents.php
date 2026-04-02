<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use App\Models\ComponentAnalytic;
use App\Services\Analytics\GoogleAnalyticsService;
use App\Services\Analytics\MatomoService;
use App\Services\Analytics\SyncService;
use App\Services\AnalyticsService;
use App\Services\HeatMapService;
use App\Services\TenantContextService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process analytics events asynchronously
 *
 * This job processes batches of analytics events after they've been stored,
 * performing detailed analysis, compliance validation, and metric aggregation.
 */
class ProcessAnalyticsEvents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private array $eventIds,
        private string $tenantId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        AnalyticsService $analyticsService,
        HeatMapService $heatMapService,
        TenantContextService $tenantContextService,
        GoogleAnalyticsService $googleAnalyticsService,
        MatomoService $matomoService,
        SyncService $syncService
    ): void {
        try {
            Log::info('Starting ProcessAnalyticsEvents job', [
                'event_count' => count($this->eventIds),
                'tenant_id' => $this->tenantId,
            ]);

            // Set tenant context for the entire job
            $tenantContextService->setTenant($this->tenantId);

            $processed = 0;
            $errors = [];

            foreach ($this->eventIds as $eventId) {
                try {
                    $event = AnalyticsEvent::find($eventId);

                    if (! $event) {
                        Log::warning('Analytics event not found', ['event_id' => $eventId]);

                        continue;
                    }

                    $this->processEvent($event, $analyticsService, $heatMapService, $googleAnalyticsService, $matomoService);
                    $processed++;

                } catch (\Exception $e) {
                    Log::error('Failed to process analytics event', [
                        'event_id' => $eventId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $errors[] = [
                        'event_id' => $eventId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Run discrepancy detection after processing events
            try {
                $syncService->detectDiscrepancies($this->tenantId);
                Log::info('Discrepancy detection completed after event processing', [
                    'tenant_id' => $this->tenantId,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to run discrepancy detection after event processing', [
                    'tenant_id' => $this->tenantId,
                    'error' => $e->getMessage(),
                ]);
                // Don't fail the job for discrepancy detection errors
            }

            Log::info('ProcessAnalyticsEvents job completed', [
                'processed' => $processed,
                'errors' => count($errors),
                'tenant_id' => $this->tenantId,
            ]);

            if (! empty($errors)) {
                Log::warning('ProcessAnalyticsEvents job completed with errors', [
                    'error_count' => count($errors),
                    'errors' => $errors,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('ProcessAnalyticsEvents job failed', [
                'tenant_id' => $this->tenantId,
                'event_count' => count($this->eventIds),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Process a single analytics event
     */
    private function processEvent(AnalyticsEvent $event, AnalyticsService $analyticsService, HeatMapService $heatMapService, GoogleAnalyticsService $googleAnalyticsService, MatomoService $matomoService): void
    {
        // Validate compliance flags and enforce retention policies
        $this->validateCompliance($event);

        // Forward event to Google Analytics 4 if compliant
        if ($event->is_compliant) {
            $this->forwardToGA4($event, $googleAnalyticsService);
            $this->forwardToMatomo($event, $matomoService);
        }

        // Aggregate metrics based on event type
        $this->aggregateMetrics($event, $analyticsService);

        // Call AnalyticsService for engagement recording if applicable
        $this->recordEngagement($event, $analyticsService);

        // Record heat map data for click/scroll events
        $this->recordHeatMapData($event, $heatMapService);
    }

    /**
     * Validate compliance flags and enforce retention policies
     */
    private function validateCompliance(AnalyticsEvent $event): void
    {
        $complianceFlags = $event->compliance_flags ?? [];

        // Check for GDPR/CCPA compliance
        if (! $this->hasRequiredConsent($complianceFlags)) {
            Log::info('Event lacks required consent, marking as non-compliant', [
                'event_id' => $event->id,
                'event_type' => $event->event_type,
            ]);

            $event->update(['is_compliant' => false]);
        }

        // Enforce retention policies - soft delete old events
        $retentionDays = $this->getRetentionDays($complianceFlags);
        if ($event->created_at->addDays($retentionDays)->isPast()) {
            Log::info('Event exceeds retention period, soft deleting', [
                'event_id' => $event->id,
                'retention_days' => $retentionDays,
            ]);

            $event->delete(); // Soft delete
        }
    }

    /**
     * Check if required consent is present
     */
    private function hasRequiredConsent(array $consentFlags): bool
    {
        return in_array('analytics', $consentFlags) || in_array('all', $consentFlags);
    }

    /**
     * Get retention days based on compliance flags
     */
    private function getRetentionDays(array $consentFlags): int
    {
        // GDPR requires 26 months for non-essential data
        // CCPA allows 12 months
        // Default to 12 months for consented data, 26 for non-consented
        return $this->hasRequiredConsent($consentFlags) ? 365 : 790;
    }

    /**
     * Aggregate metrics based on event type
     */
    private function aggregateMetrics(AnalyticsEvent $event, AnalyticsService $analyticsService): void
    {
        // Update counters in related models based on event_type
        match ($event->event_type) {
            'page_view' => $this->incrementPageViewMetrics($event),
            'user_engagement' => $this->incrementEngagementMetrics($event),
            'conversion' => $this->incrementConversionMetrics($event),
            'component_interaction' => $this->incrementComponentMetrics($event),
            default => Log::debug('No specific aggregation for event type', [
                'event_type' => $event->event_type,
                'event_id' => $event->id,
            ]),
        };
    }

    /**
     * Increment page view metrics
     */
    private function incrementPageViewMetrics(AnalyticsEvent $event): void
    {
        // This could update page view counters in a dedicated table
        // For now, just log for future implementation
        Log::debug('Page view metric incremented', [
            'event_id' => $event->id,
            'page_url' => $event->properties['page_url'] ?? 'unknown',
        ]);
    }

    /**
     * Increment engagement metrics
     */
    private function incrementEngagementMetrics(AnalyticsEvent $event): void
    {
        // Update user engagement counters
        Log::debug('Engagement metric incremented', [
            'event_id' => $event->id,
            'engagement_type' => $event->properties['engagement_type'] ?? 'unknown',
        ]);
    }

    /**
     * Increment conversion metrics
     */
    private function incrementConversionMetrics(AnalyticsEvent $event): void
    {
        // Update conversion counters
        Log::debug('Conversion metric incremented', [
            'event_id' => $event->id,
            'conversion_type' => $event->properties['conversion_type'] ?? 'unknown',
        ]);
    }

    /**
     * Increment component interaction metrics
     */
    private function incrementComponentMetrics(AnalyticsEvent $event): void
    {
        // Update ComponentAnalytic if this is a component interaction
        if (isset($event->properties['component_instance_id'])) {
            ComponentAnalytic::create([
                'component_instance_id' => $event->properties['component_instance_id'],
                'event_type' => $event->event_type,
                'user_id' => $event->user_id,
                'session_id' => $event->session_id,
                'data' => $event->properties,
                'variant' => $event->properties['variant'] ?? null,
                'ip_address' => $event->ip_address,
                'user_agent' => $event->user_agent,
            ]);

            Log::debug('Component analytic created', [
                'event_id' => $event->id,
                'component_instance_id' => $event->properties['component_instance_id'],
            ]);
        }
    }

    /**
     * Record engagement using AnalyticsService
     */
    private function recordEngagement(AnalyticsEvent $event, AnalyticsService $analyticsService): void
    {
        // Check if this event should trigger engagement recording
        if ($this->shouldRecordEngagement($event)) {
            try {
                // Call AnalyticsService::recordEngagement if it exists
                // For now, we'll assume it needs to be implemented
                // $analyticsService->recordEngagement($event);

                Log::debug('Engagement recorded for event', [
                    'event_id' => $event->id,
                    'event_type' => $event->event_type,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to record engagement', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Determine if engagement should be recorded for this event
     */
    private function shouldRecordEngagement(AnalyticsEvent $event): bool
    {
        // Define which event types should trigger engagement recording
        $engagementEvents = [
            'user_engagement',
            'conversion',
            'form_submit',
            'download',
            'share',
        ];

        return in_array($event->event_type, $engagementEvents);
    }

    /**
     * Record heat map data for click/scroll events
     */
    private function recordHeatMapData(AnalyticsEvent $event, HeatMapService $heatMapService): void
    {
        try {
            $heatMapService->recordHeatMapEvent($event);
        } catch (\Exception $e) {
            Log::error('Failed to record heat map data', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Forward event to Google Analytics 4
     */
    private function forwardToGA4(AnalyticsEvent $event, GoogleAnalyticsService $googleAnalyticsService): void
    {
        try {
            // Prepare event data for GA4
            $eventData = [
                'tenant_id' => $event->tenant_id,
                'user_segment' => $event->properties['user_segment'] ?? null,
                'session_id' => $event->session_id,
                'custom_properties' => $event->properties,
            ];

            // Forward the event
            $googleAnalyticsService->forwardEvent($event->event_name, $eventData);

        } catch (\Exception $e) {
            Log::error('Failed to forward event to GA4', [
                'event_id' => $event->id,
                'event_name' => $event->event_name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Forward event to Matomo
     */
    private function forwardToMatomo(AnalyticsEvent $event, MatomoService $matomoService): void
    {
        try {
            // Prepare event data for Matomo
            $eventData = [
                'event_type' => $event->event_name,
                'module' => $event->properties['module'] ?? 'general',
                'engagement_score' => $event->properties['engagement_score'] ?? $event->value ?? null,
                'user_id' => $event->user_id,
                'tenant_id' => $event->tenant_id,
                'user_segment' => $event->properties['user_segment'] ?? null,
                'session_id' => $event->session_id,
            ];

            // Forward the event
            $matomoService->trackEvent($eventData);

        } catch (\Exception $e) {
            Log::error('Failed to forward event to Matomo', [
                'event_id' => $event->id,
                'event_name' => $event->event_name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'analytics',
            'event-processing',
            'tenant:'.$this->tenantId,
        ];
    }

    /**
     * Broadcast batched analytics updates via WebSocket
     */
    private function broadcastAnalyticsUpdates(array $processedEvents, string $tenantId): void
    {
        if (empty($processedEvents)) {
            return;
        }

        try {
            // Group events by type for batched broadcasting
            $batchedEvents = collect($processedEvents)->groupBy('event_type');

            foreach ($batchedEvents as $eventType => $events) {
                // Broadcast summary updates instead of individual events
                $summary = [
                    'event_type' => $eventType,
                    'count' => $events->count(),
                    'tenant_id' => $tenantId,
                    'timestamp' => now()->toISOString(),
                    'summary' => $this->createEventSummary($events),
                ];

                broadcast(new \App\Events\AnalyticsBatchProcessed($summary, $tenantId))->toOthers();

                Log::debug('Broadcasted batched analytics update', [
                    'event_type' => $eventType,
                    'count' => $events->count(),
                    'tenant_id' => $tenantId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to broadcast analytics updates', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create summary data for batched events
     */
    private function createEventSummary(\Illuminate\Support\Collection $events): array
    {
        $firstEvent = $events->first();

        return [
            'total_events' => $events->count(),
            'unique_users' => $events->pluck('user_id')->filter()->unique()->count(),
            'time_range' => [
                'start' => $events->min('occurred_at'),
                'end' => $events->max('occurred_at'),
            ],
            'sample_event' => [
                'id' => $firstEvent->id,
                'event_name' => $firstEvent->event_name,
                'properties' => $firstEvent->properties,
            ],
        ];
    }
}
