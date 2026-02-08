<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\CustomEvent;
use App\Models\CustomEventDefinition;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Custom Event Service
 *
 * Provides comprehensive custom event tracking functionality for flexible analytics.
 * Enables business-specific event definitions and tracking with tenant isolation.
 */
class CustomEventService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const CHUNK_SIZE = 1000;

    /**
     * Define a new custom event
     *
     * @param  array  $data  Event definition data
     */
    public function defineEvent(array $data): CustomEventDefinition
    {
        try {
            $tenantId = $this->getCurrentTenantId();

            // Validate data
            $this->validateEventDefinitionData($data);

            // Check for unique name within tenant
            if (CustomEventDefinition::byTenant($tenantId)->where('name', $data['name'])->exists()) {
                throw new Exception("Event definition with name '{$data['name']}' already exists for this tenant");
            }

            $definition = CustomEventDefinition::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'parameters_json' => $data['parameters_json'],
                'created_by' => Auth::id(),
                'status' => 'active',
            ]);

            // Clear cache
            $this->clearTenantCache($tenantId);

            return $definition;

        } catch (Exception $e) {
            Log::error('Failed to define custom event', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Track a custom event
     *
     * @param  array  $data  Event tracking data
     */
    public function trackEvent(array $data): CustomEvent
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $consentService = app(ConsentService::class);

            // Validate data
            $this->validateEventTrackingData($data);

            // Check consent
            if (! $consentService->checkConsent($data['user_id'], 'analytics')) {
                throw new Exception('User has not consented to analytics tracking');
            }

            // Validate event data against definition
            $definition = CustomEventDefinition::byTenant($tenantId)
                ->active()
                ->find($data['definition_id']);

            if (! $definition) {
                throw new Exception('Event definition not found or inactive');
            }

            $this->validateEventData($data['data_json'], $definition->parameters_json);

            $event = CustomEvent::create([
                'tenant_id' => $tenantId,
                'definition_id' => $data['definition_id'],
                'user_id' => $data['user_id'],
                'data_json' => $data['data_json'],
                'timestamp' => $data['timestamp'] ?? now(),
            ]);

            // Clear cache
            $this->clearTenantCache($tenantId);

            // Check if this is a conversion event for attribution
            if ($this->isConversionEvent($definition->name)) {
                $this->integrateWithAttribution($event);
            }

            return $event;

        } catch (Exception $e) {
            Log::error('Failed to track custom event', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Aggregate events for analytics
     *
     * @param  array  $filters  Optional filters
     * @return array Aggregated data
     */
    public function aggregateEvents(int $definitionId, array $filters = []): array
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $cacheKey = "custom_event_aggregate_{$tenantId}_{$definitionId}_".md5(serialize($filters));

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($definitionId, $filters, $tenantId) {
                $query = CustomEvent::byTenant($tenantId)
                    ->byDefinition($definitionId)
                    ->with('definition');

                // Apply filters
                if (isset($filters['user_id'])) {
                    $query->byUser($filters['user_id']);
                }

                if (isset($filters['start_date']) && isset($filters['end_date'])) {
                    $query->byPeriod($filters['start_date'], $filters['end_date']);
                }

                $events = $query->get();

                if ($events->isEmpty()) {
                    return [
                        'total_events' => 0,
                        'unique_users' => 0,
                        'aggregates' => [],
                        'time_series' => [],
                    ];
                }

                $definition = $events->first()->definition;
                $parameters = $definition->parameters_json;

                // Calculate aggregates
                $aggregates = $this->calculateAggregates($events, $parameters);

                // Generate time series data
                $timeSeries = $this->generateTimeSeries($events, $filters);

                return [
                    'total_events' => $events->count(),
                    'unique_users' => $events->unique('user_id')->count(),
                    'aggregates' => $aggregates,
                    'time_series' => $timeSeries,
                ];
            });

        } catch (Exception $e) {
            Log::error('Failed to aggregate custom events', [
                'definition_id' => $definitionId,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get event definitions for tenant
     */
    public function getEventDefinitions(): Collection
    {
        $tenantId = $this->getCurrentTenantId();
        $cacheKey = "custom_event_definitions_{$tenantId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId) {
            return CustomEventDefinition::byTenant($tenantId)
                ->active()
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Queue large aggregation jobs
     *
     * @return string Job ID
     */
    public function queueAggregation(int $definitionId, array $filters = []): string
    {
        // This would dispatch a job for large aggregations
        // For now, return a placeholder
        return 'job_'.uniqid();
    }

    // Private helper methods

    private function getCurrentTenantId(): int
    {
        return (int) session('tenant_id', 1);
    }

    private function clearTenantCache(int $tenantId): void
    {
        Cache::forget("custom_event_definitions_{$tenantId}");
        // Clear aggregation caches (would need pattern-based clearing in production)
    }

    private function validateEventDefinitionData(array $data): void
    {
        if (empty($data['name'])) {
            throw new Exception('Event name is required');
        }

        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $data['name'])) {
            throw new Exception('Event name must be alphanumeric with underscores, starting with letter or underscore');
        }

        if (empty($data['parameters_json']) || ! is_array($data['parameters_json'])) {
            throw new Exception('Parameters JSON must be a non-empty array');
        }

        foreach ($data['parameters_json'] as $param) {
            if (! isset($param['name']) || ! isset($param['type'])) {
                throw new Exception('Each parameter must have name and type');
            }

            if (! in_array($param['type'], ['string', 'number', 'boolean'])) {
                throw new Exception('Parameter type must be string, number, or boolean');
            }
        }
    }

    private function validateEventTrackingData(array $data): void
    {
        if (empty($data['definition_id'])) {
            throw new Exception('Definition ID is required');
        }

        if (empty($data['user_id'])) {
            throw new Exception('User ID is required');
        }

        if (! isset($data['data_json']) || ! is_array($data['data_json'])) {
            throw new Exception('Event data must be provided as JSON array');
        }
    }

    private function validateEventData(array $eventData, array $parameters): void
    {
        foreach ($parameters as $param) {
            $paramName = $param['name'];
            $paramType = $param['type'];

            if (! array_key_exists($paramName, $eventData)) {
                throw new Exception("Required parameter '{$paramName}' is missing");
            }

            $value = $eventData[$paramName];
            $this->validateParameterType($value, $paramType, $paramName);
        }
    }

    private function validateParameterType($value, string $type, string $paramName): void
    {
        $valid = match ($type) {
            'string' => is_string($value),
            'number' => is_numeric($value),
            'boolean' => is_bool($value),
            default => false,
        };

        if (! $valid) {
            throw new Exception("Parameter '{$paramName}' must be of type '{$type}'");
        }
    }

    private function calculateAggregates(Collection $events, array $parameters): array
    {
        $aggregates = [];

        foreach ($parameters as $param) {
            $paramName = $param['name'];
            $paramType = $param['type'];

            if ($paramType === 'number') {
                $values = $events->pluck('data_json')->pluck($paramName)->filter()->values();
                if ($values->isNotEmpty()) {
                    $aggregates[$paramName] = [
                        'count' => $values->count(),
                        'sum' => $values->sum(),
                        'avg' => round($values->avg(), 2),
                        'min' => $values->min(),
                        'max' => $values->max(),
                    ];
                }
            } elseif ($paramType === 'string') {
                $valueCounts = $events->pluck('data_json')
                    ->pluck($paramName)
                    ->filter()
                    ->countBy()
                    ->sortDesc()
                    ->take(10); // Top 10 values

                $aggregates[$paramName] = [
                    'unique_values' => $valueCounts->count(),
                    'top_values' => $valueCounts->toArray(),
                ];
            }
        }

        return $aggregates;
    }

    private function generateTimeSeries(Collection $events, array $filters): array
    {
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : $events->min('timestamp');
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : $events->max('timestamp');

        if (! $startDate || ! $endDate) {
            return [];
        }

        $timeSeries = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->toDateString();
            $dayEvents = $events->filter(function ($event) use ($dateStr) {
                return Carbon::parse($event->timestamp)->toDateString() === $dateStr;
            });

            $timeSeries[] = [
                'date' => $dateStr,
                'count' => $dayEvents->count(),
                'unique_users' => $dayEvents->unique('user_id')->count(),
            ];

            $currentDate->addDay();
        }

        return $timeSeries;
    }

    private function isConversionEvent(string $eventName): bool
    {
        // Define which events are considered conversions
        $conversionEvents = ['purchase', 'signup_complete', 'application_submit'];

        return in_array($eventName, $conversionEvents);
    }

    private function integrateWithAttribution(CustomEvent $event): void
    {
        // This would integrate with AttributionService if needed
        // For now, just log the integration point
        Log::info('Custom event integrated with attribution', [
            'event_id' => $event->id,
            'definition_name' => $event->definition->name,
        ]);
    }
}
