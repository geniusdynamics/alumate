<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\EmailSequence;
use App\Models\SequenceEnrollment;
use App\Models\BehaviorEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * Behavior Tracking Service
 *
 * Core business logic for tracking user behaviors, evaluating trigger conditions,
 * and managing automatic sequence enrollment based on lead engagement.
 */
class BehaviorTrackingService extends BaseService
{
    /**
     * Cache keys and durations
     */
    private const CACHE_PREFIX = 'behavior_tracking_';
    private const CACHE_DURATION = 300; // 5 minutes
    private const LEAD_SCORE_CACHE_DURATION = 600; // 10 minutes

    /**
     * Track user behavior event
     *
     * @param array $eventData Event data
     * @return BehaviorEvent
     * @throws \Illuminate\Validation\ValidationException
     */
    public function trackBehavior(array $eventData): BehaviorEvent
    {
        $this->validateEventData($eventData);

        try {
            DB::beginTransaction();

            $event = BehaviorEvent::create([
                'lead_id' => $eventData['lead_id'],
                'event_type' => $eventData['event_type'],
                'event_data' => $eventData['event_data'] ?? null,
                'metadata' => $eventData['metadata'] ?? null,
                'ip_address' => $eventData['ip_address'] ?? request()->ip(),
                'user_agent' => $eventData['user_agent'] ?? request()->userAgent(),
                'occurred_at' => $eventData['occurred_at'] ?? now(),
            ]);

            // Update lead score based on behavior
            $this->updateLeadScore($eventData['lead_id'], $eventData['event_type'], $eventData['event_data'] ?? []);

            // Check for sequence triggers
            $this->evaluateSequenceTriggers($event);

            DB::commit();

            Log::info('Behavior event tracked', [
                'event_id' => $event->id,
                'lead_id' => $event->lead_id,
                'event_type' => $event->event_type,
            ]);

            return $event;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to track behavior event', [
                'error' => $e->getMessage(),
                'event_data' => $eventData,
            ]);
            throw $e;
        }
    }

    /**
     * Track page visit behavior
     *
     * @param int $leadId
     * @param string $pageUrl
     * @param array $metadata Additional metadata
     * @return BehaviorEvent
     */
    public function trackPageVisit(int $leadId, string $pageUrl, array $metadata = []): BehaviorEvent
    {
        return $this->trackBehavior([
            'lead_id' => $leadId,
            'event_type' => 'page_visit',
            'event_data' => [
                'page_url' => $pageUrl,
                'visit_duration' => $metadata['visit_duration'] ?? null,
                'referrer' => $metadata['referrer'] ?? null,
            ],
            'metadata' => array_merge($metadata, [
                'page_title' => $metadata['page_title'] ?? null,
                'page_category' => $metadata['page_category'] ?? null,
            ]),
        ]);
    }

    /**
     * Track form interaction behavior
     *
     * @param int $leadId
     * @param string $formType
     * @param string $action
     * @param array $metadata Additional metadata
     * @return BehaviorEvent
     */
    public function trackFormInteraction(int $leadId, string $formType, string $action, array $metadata = []): BehaviorEvent
    {
        return $this->trackBehavior([
            'lead_id' => $leadId,
            'event_type' => 'form_interaction',
            'event_data' => [
                'form_type' => $formType,
                'action' => $action,
                'field_name' => $metadata['field_name'] ?? null,
                'field_value' => $metadata['field_value'] ?? null,
            ],
            'metadata' => $metadata,
        ]);
    }

    /**
     * Track content engagement behavior
     *
     * @param int $leadId
     * @param string $contentType
     * @param int $contentId
     * @param string $action
     * @param array $metadata Additional metadata
     * @return BehaviorEvent
     */
    public function trackContentEngagement(int $leadId, string $contentType, int $contentId, string $action, array $metadata = []): BehaviorEvent
    {
        return $this->trackBehavior([
            'lead_id' => $leadId,
            'event_type' => 'content_engagement',
            'event_data' => [
                'content_type' => $contentType,
                'content_id' => $contentId,
                'action' => $action,
                'engagement_duration' => $metadata['engagement_duration'] ?? null,
            ],
            'metadata' => $metadata,
        ]);
    }

    /**
     * Track email engagement behavior
     *
     * @param int $leadId
     * @param int $emailSendId
     * @param string $action
     * @param array $metadata Additional metadata
     * @return BehaviorEvent
     */
    public function trackEmailEngagement(int $leadId, int $emailSendId, string $action, array $metadata = []): BehaviorEvent
    {
        return $this->trackBehavior([
            'lead_id' => $leadId,
            'event_type' => 'email_engagement',
            'event_data' => [
                'email_send_id' => $emailSendId,
                'action' => $action,
                'link_url' => $metadata['link_url'] ?? null,
                'link_text' => $metadata['link_text'] ?? null,
            ],
            'metadata' => $metadata,
        ]);
    }

    /**
     * Track custom behavior event
     *
     * @param int $leadId
     * @param string $eventType
     * @param array $eventData
     * @param array $metadata Additional metadata
     * @return BehaviorEvent
     */
    public function trackCustomEvent(int $leadId, string $eventType, array $eventData = [], array $metadata = []): BehaviorEvent
    {
        return $this->trackBehavior([
            'lead_id' => $leadId,
            'event_type' => $eventType,
            'event_data' => $eventData,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Evaluate sequence triggers for a behavior event
     *
     * @param BehaviorEvent $event
     * @return array Array of triggered sequences
     */
    public function evaluateSequenceTriggers(BehaviorEvent $event): array
    {
        $triggeredSequences = [];

        // Get sequences that might be triggered by this event
        $sequences = EmailSequence::where('tenant_id', tenant()->id)
            ->where('is_active', true)
            ->where('trigger_type', 'behavior')
            ->get();

        foreach ($sequences as $sequence) {
            if ($this->matchesTriggerConditions($sequence, $event)) {
                try {
                    // Check if lead is already enrolled
                    $existingEnrollment = SequenceEnrollment::where('sequence_id', $sequence->id)
                        ->where('lead_id', $event->lead_id)
                        ->first();

                    if (!$existingEnrollment) {
                        // Auto-enroll the lead
                        $emailSequenceService = app(EmailSequenceService::class);
                        $enrollment = $emailSequenceService->enrollLead($sequence->id, $event->lead_id);
                        $triggeredSequences[] = $sequence;

                        Log::info('Lead auto-enrolled in sequence via behavior trigger', [
                            'sequence_id' => $sequence->id,
                            'lead_id' => $event->lead_id,
                            'trigger_event_id' => $event->id,
                            'enrollment_id' => $enrollment->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to auto-enroll lead in sequence', [
                        'sequence_id' => $sequence->id,
                        'lead_id' => $event->lead_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $triggeredSequences;
    }

    /**
     * Update lead score based on behavior
     *
     * @param int $leadId
     * @param string $eventType
     * @param array $eventData
     * @return bool
     */
    public function updateLeadScore(int $leadId, string $eventType, array $eventData = []): bool
    {
        $lead = Lead::where('tenant_id', tenant()->id)->findOrFail($leadId);
        $scoreChange = $this->calculateScoreChange($eventType, $eventData);

        if ($scoreChange === 0) {
            return true; // No score change needed
        }

        $currentScore = $lead->score ?? 0;
        $newScore = max(0, $currentScore + $scoreChange); // Ensure score doesn't go below 0

        $lead->update([
            'score' => $newScore,
            'last_activity_at' => now(),
        ]);

        // Clear score cache
        Cache::forget(self::CACHE_PREFIX . "lead_score_{$leadId}");

        Log::info('Lead score updated', [
            'lead_id' => $leadId,
            'previous_score' => $currentScore,
            'new_score' => $newScore,
            'change' => $scoreChange,
            'event_type' => $eventType,
        ]);

        return true;
    }

    /**
     * Get lead behavior history
     *
     * @param int $leadId
     * @param array $filters
     * @return Collection
     */
    public function getLeadBehaviorHistory(int $leadId, array $filters = []): Collection
    {
        $cacheKey = self::CACHE_PREFIX . "history_{$leadId}_" . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($leadId, $filters) {
            $query = BehaviorEvent::where('lead_id', $leadId)
                ->where('tenant_id', tenant()->id)
                ->orderBy('occurred_at', 'desc');

            // Apply filters
            if (isset($filters['event_type'])) {
                $query->where('event_type', $filters['event_type']);
            }

            if (isset($filters['date_from'])) {
                $query->where('occurred_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('occurred_at', '<=', $filters['date_to']);
            }

            if (isset($filters['limit'])) {
                $query->limit($filters['limit']);
            }

            return $query->get();
        });
    }

    /**
     * Get lead engagement metrics
     *
     * @param int $leadId
     * @param int $days Number of days to look back
     * @return array
     */
    public function getLeadEngagementMetrics(int $leadId, int $days = 30): array
    {
        $cacheKey = self::CACHE_PREFIX . "metrics_{$leadId}_{$days}";

        return Cache::remember($cacheKey, self::LEAD_SCORE_CACHE_DURATION, function () use ($leadId, $days) {
            $startDate = Carbon::now()->subDays($days);

            $events = BehaviorEvent::where('lead_id', $leadId)
                ->where('tenant_id', tenant()->id)
                ->where('occurred_at', '>=', $startDate)
                ->get();

            $metrics = [
                'total_events' => $events->count(),
                'page_visits' => $events->where('event_type', 'page_visit')->count(),
                'form_interactions' => $events->where('event_type', 'form_interaction')->count(),
                'content_engagements' => $events->where('event_type', 'content_engagement')->count(),
                'email_engagements' => $events->where('event_type', 'email_engagement')->count(),
                'unique_event_types' => $events->pluck('event_type')->unique()->count(),
                'avg_events_per_day' => $days > 0 ? round($events->count() / $days, 2) : 0,
                'last_activity' => $events->max('occurred_at'),
                'most_common_event' => $events->groupBy('event_type')->map->count()->sortDesc()->keys()->first(),
            ];

            // Calculate engagement score based on event types and frequency
            $engagementScore = $this->calculateEngagementScore($events);
            $metrics['engagement_score'] = $engagementScore;

            return $metrics;
        });
    }

    /**
     * Get behavior analytics for tenant
     *
     * @param array $filters
     * @return array
     */
    public function getBehaviorAnalytics(array $filters = []): array
    {
        $cacheKey = self::CACHE_PREFIX . 'analytics_' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filters) {
            $query = BehaviorEvent::where('tenant_id', tenant()->id);

            // Apply date filters
            if (isset($filters['date_from'])) {
                $query->where('occurred_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('occurred_at', '<=', $filters['date_to']);
            }

            $events = $query->get();

            return [
                'total_events' => $events->count(),
                'unique_leads' => $events->pluck('lead_id')->unique()->count(),
                'events_by_type' => $events->groupBy('event_type')->map->count()->toArray(),
                'events_by_day' => $events->groupBy(function ($event) {
                    return $event->occurred_at->format('Y-m-d');
                })->map->count()->toArray(),
                'top_pages' => $events->where('event_type', 'page_visit')
                    ->pluck('event_data.page_url')
                    ->countBy()
                    ->sortDesc()
                    ->take(10)
                    ->toArray(),
                'conversion_funnel' => $this->calculateConversionFunnel($events),
            ];
        });
    }

    /**
     * Process bulk behavior events
     *
     * @param array $events Array of event data
     * @return array Processing results
     */
    public function processBulkEvents(array $events): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($events as $eventData) {
            try {
                $this->trackBehavior($eventData);
                $results['processed']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'event_data' => $eventData,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Bulk behavior events processed', [
            'total_events' => count($events),
            'processed' => $results['processed'],
            'failed' => $results['failed'],
            'tenant_id' => tenant()->id,
        ]);

        return $results;
    }

    /**
     * Clean up old behavior events
     *
     * @param int $daysOld Events older than this will be deleted
     * @return int Number of deleted events
     */
    public function cleanupOldEvents(int $daysOld = 90): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);

        $deletedCount = BehaviorEvent::where('tenant_id', tenant()->id)
            ->where('occurred_at', '<', $cutoffDate)
            ->delete();

        // Clear all behavior caches
        Cache::forget(self::CACHE_PREFIX . '*');

        Log::info('Old behavior events cleaned up', [
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate,
            'tenant_id' => tenant()->id,
        ]);

        return $deletedCount;
    }

    /**
     * Validate event data
     *
     * @param array $data
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateEventData(array $data): void
    {
        $rules = [
            'lead_id' => 'required|exists:leads,id',
            'event_type' => 'required|string|max:255',
            'event_data' => 'nullable|array',
            'metadata' => 'nullable|array',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string',
            'occurred_at' => 'nullable|date',
        ];

        Validator::make($data, $rules)->validate();
    }

    /**
     * Check if sequence trigger conditions match the event
     *
     * @param EmailSequence $sequence
     * @param BehaviorEvent $event
     * @return bool
     */
    private function matchesTriggerConditions(EmailSequence $sequence, BehaviorEvent $event): bool
    {
        $conditions = $sequence->trigger_conditions;

        if (!$conditions) {
            return true; // No conditions means always trigger
        }

        // Check event type match
        if (isset($conditions['event_type']) && $conditions['event_type'] !== $event->event_type) {
            return false;
        }

        // Check event data conditions
        if (isset($conditions['event_data'])) {
            foreach ($conditions['event_data'] as $key => $expectedValue) {
                $actualValue = data_get($event->event_data, $key);
                if ($actualValue !== $expectedValue) {
                    return false;
                }
            }
        }

        // Check metadata conditions
        if (isset($conditions['metadata'])) {
            foreach ($conditions['metadata'] as $key => $expectedValue) {
                $actualValue = data_get($event->metadata, $key);
                if ($actualValue !== $expectedValue) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Calculate score change based on event type
     *
     * @param string $eventType
     * @param array $eventData
     * @return int
     */
    private function calculateScoreChange(string $eventType, array $eventData): int
    {
        $scoreMap = [
            'page_visit' => 5,
            'form_interaction' => 10,
            'content_engagement' => 15,
            'email_engagement' => 20,
            'form_submission' => 50,
            'purchase' => 100,
        ];

        $baseScore = $scoreMap[$eventType] ?? 0;

        // Apply multipliers based on event data
        if ($eventType === 'page_visit' && isset($eventData['visit_duration'])) {
            if ($eventData['visit_duration'] > 300) { // 5+ minutes
                $baseScore *= 2;
            } elseif ($eventData['visit_duration'] > 60) { // 1+ minute
                $baseScore *= 1.5;
            }
        }

        if ($eventType === 'email_engagement' && isset($eventData['action'])) {
            if ($eventData['action'] === 'clicked') {
                $baseScore *= 1.5;
            }
        }

        return (int) $baseScore;
    }

    /**
     * Calculate engagement score from events
     *
     * @param Collection $events
     * @return float
     */
    private function calculateEngagementScore(Collection $events): float
    {
        if ($events->isEmpty()) {
            return 0.0;
        }

        $score = 0;
        $eventWeights = [
            'page_visit' => 1,
            'form_interaction' => 2,
            'content_engagement' => 3,
            'email_engagement' => 4,
            'form_submission' => 10,
        ];

        foreach ($events as $event) {
            $weight = $eventWeights[$event->event_type] ?? 1;
            $score += $weight;

            // Bonus for recent events
            $daysSince = Carbon::parse($event->occurred_at)->diffInDays(now());
            if ($daysSince <= 7) {
                $score += $weight * 0.5; // 50% bonus for recent activity
            }
        }

        // Normalize score (0-100 scale)
        $maxPossibleScore = $events->count() * 10; // Assuming max weight of 10
        return $maxPossibleScore > 0 ? min(100, ($score / $maxPossibleScore) * 100) : 0;
    }

    /**
     * Calculate conversion funnel from events
     *
     * @param Collection $events
     * @return array
     */
    private function calculateConversionFunnel(Collection $events): array
    {
        return [
            'awareness' => $events->where('event_type', 'page_visit')->count(),
            'interest' => $events->whereIn('event_type', ['content_engagement', 'email_engagement'])->count(),
            'consideration' => $events->where('event_type', 'form_interaction')->count(),
            'action' => $events->where('event_type', 'form_submission')->count(),
        ];
    }
}