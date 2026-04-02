<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\CustomEvent;
use App\Models\CustomEventDefinition;
use App\Services\TenantContextService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Custom Event Tracking Service
 *
 * Provides comprehensive custom event tracking functionality for flexible analytics.
 * Enables business-specific event definitions and tracking with tenant isolation,
 * event context capture, user property tracking, and advanced analysis capabilities.
 */
class CustomEventTrackingService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const CHUNK_SIZE = 1000;

    private TenantContextService $tenantContextService;

    private ?ConsentService $consentService = null;

    /**
     * Constructor
     */
    public function __construct(TenantContextService $tenantContextService)
    {
        $this->tenantContextService = $tenantContextService;
    }

    /**
     * Get ConsentService instance lazily
     */
    private function getConsentService(): ConsentService
    {
        if ($this->consentService === null) {
            $this->consentService = app(ConsentService::class);
        }

        return $this->consentService;
    }

    /**
     * Define a custom event with properties and validation rules
     *
     * @param  array  $definition  Event definition data
     */
    public function defineEvent(array $definition): CustomEventDefinition
    {
        try {
            $tenantId = $this->getCurrentTenantId();

            // Validate the definition
            $this->validateEventDefinition($definition);

            // Check for unique name within tenant
            if (CustomEventDefinition::byTenant($tenantId)
                ->where('name', $definition['name'])
                ->exists()) {
                throw new Exception("Event definition with name '{$definition['name']}' already exists for this tenant");
            }

            $customEventDefinition = CustomEventDefinition::create([
                'tenant_id' => $tenantId,
                'name' => $definition['name'],
                'description' => $definition['description'] ?? null,
                'parameters_json' => $definition['parameters'] ?? [],
                'created_by' => $definition['created_by'] ?? null,
                'status' => $definition['status'] ?? 'active',
            ]);

            // Clear cache
            $this->clearEventDefinitionCache($tenantId);

            Log::info('Custom event definition created', [
                'tenant_id' => $tenantId,
                'event_name' => $definition['name'],
                'definition_id' => $customEventDefinition->id,
            ]);

            return $customEventDefinition;

        } catch (Exception $e) {
            Log::error('Failed to define custom event', [
                'definition' => $definition,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Track a custom event with properties
     *
     * @param  string  $eventName  Event name
     * @param  array  $properties  Event properties
     * @param  int  $userId  User ID
     */
    public function trackEvent(string $eventName, array $properties, int $userId): CustomEvent
    {
        try {
            $tenantId = $this->getCurrentTenantId();

            // Get event definition
            $definition = $this->getEventDefinitionByName($eventName);

            if (! $definition) {
                throw new Exception("Event definition '{$eventName}' not found");
            }

            if ($definition->status !== 'active') {
                throw new Exception("Event definition '{$eventName}' is not active");
            }

            // Check consent
            if (! $this->getConsentService()->hasConsent($userId, 'analytics')) {
                throw new Exception('User has not consented to analytics tracking');
            }

            // Validate event properties against definition
            $this->validateEvent($eventName, $properties);

            // Create the event
            $event = CustomEvent::create([
                'tenant_id' => $tenantId,
                'definition_id' => $definition->id,
                'user_id' => $userId,
                'data_json' => $properties,
                'timestamp' => now(),
            ]);

            // Clear cache
            $this->clearEventDefinitionCache($tenantId);

            Log::info('Custom event tracked', [
                'tenant_id' => $tenantId,
                'event_name' => $eventName,
                'user_id' => $userId,
                'event_id' => $event->id,
            ]);

            return $event;

        } catch (Exception $e) {
            Log::error('Failed to track custom event', [
                'event_name' => $eventName,
                'properties' => $properties,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate event data against definition
     *
     * @param  string  $eventName  Event name
     * @param  array  $properties  Event properties to validate
     */
    public function validateEvent(string $eventName, array $properties): bool
    {
        $definition = $this->getEventDefinitionByName($eventName);

        if (! $definition) {
            throw new Exception("Event definition '{$eventName}' not found");
        }

        $parameters = $definition->parameters_json ?? [];

        foreach ($parameters as $param) {
            $paramName = $param['name'] ?? null;
            $paramType = $param['type'] ?? 'string';
            $required = $param['required'] ?? true;

            if ($required && ! array_key_exists($paramName, $properties)) {
                throw new Exception("Required parameter '{$paramName}' is missing for event '{$eventName}'");
            }

            if (array_key_exists($paramName, $properties)) {
                $value = $properties[$paramName];
                $this->validateParameterType($value, $paramType, $paramName, $eventName);
            }
        }

        return true;
    }

    /**
     * Get event definition by name
     *
     * @param  string  $eventName  Event name
     */
    public function getEventDefinition(string $eventName): ?CustomEventDefinition
    {
        return $this->getEventDefinitionByName($eventName);
    }

    /**
     * Get event definition by name (internal)
     */
    private function getEventDefinitionByName(string $eventName): ?CustomEventDefinition
    {
        $tenantId = $this->getCurrentTenantId();
        $cacheKey = $this->getEventDefinitionCacheKey($tenantId, $eventName);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId, $eventName) {
            return CustomEventDefinition::byTenant($tenantId)
                ->active()
                ->where('name', $eventName)
                ->first();
        });
    }

    /**
     * List all defined events for the current tenant
     */
    public function listEvents(): Collection
    {
        $tenantId = $this->getCurrentTenantId();
        $cacheKey = "custom_events_list_{$tenantId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId) {
            return CustomEventDefinition::byTenant($tenantId)
                ->active()
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Analyze event data with filters
     *
     * @param  string  $eventName  Event name
     * @param  array  $filters  Optional filters
     */
    public function analyzeEvents(string $eventName, array $filters = []): array
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $definition = $this->getEventDefinitionByName($eventName);

            if (! $definition) {
                throw new Exception("Event definition '{$eventName}' not found");
            }

            $cacheKey = "event_analysis_{$tenantId}_{$eventName}_".md5(serialize($filters));

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($definition, $filters, $tenantId, $eventName) {
                $query = CustomEvent::byTenant($tenantId)
                    ->byDefinition($definition->id)
                    ->with('definition');

                // Apply filters
                if (isset($filters['user_id'])) {
                    $query->byUser($filters['user_id']);
                }

                if (isset($filters['start_date']) && isset($filters['end_date'])) {
                    $query->byPeriod($filters['start_date'], $filters['end_date']);
                }

                $events = $query->orderBy('timestamp', 'desc')->get();

                if ($events->isEmpty()) {
                    return $this->getEmptyAnalysisResponse($eventName);
                }

                return [
                    'event_name' => $eventName,
                    'total_events' => $events->count(),
                    'unique_users' => $events->unique('user_id')->count(),
                    'date_range' => [
                        'start' => $events->min('timestamp')->toIso8601String(),
                        'end' => $events->max('timestamp')->toIso8601String(),
                    ],
                    'properties_analysis' => $this->analyzeProperties($events, $definition->parameters_json),
                    'time_series' => $this->generateTimeSeries($events, $filters),
                    'top_users' => $this->getTopUsers($events),
                    'analyzed_at' => now()->toIso8601String(),
                ];
            });

        } catch (Exception $e) {
            Log::error('Failed to analyze events', [
                'event_name' => $eventName,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => $e->getMessage(),
                'event_name' => $eventName,
            ];
        }
    }

    /**
     * Create funnel analysis from events
     *
     * @param  array  $events  Array of event names in funnel order
     */
    public function createFunnel(array $events): array
    {
        try {
            if (count($events) < 2) {
                throw new Exception('Funnel requires at least 2 events');
            }

            $tenantId = $this->getCurrentTenantId();
            $cacheKey = "funnel_analysis_{$tenantId}_".md5(serialize($events));

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($events, $tenantId) {
                $funnelSteps = [];
                $previousCount = null;
                $previousEventName = null;

                foreach ($events as $index => $eventName) {
                    $definition = $this->getEventDefinitionByName($eventName);

                    if (! $definition) {
                        throw new Exception("Event definition '{$eventName}' not found in funnel");
                    }

                    $count = CustomEvent::byTenant($tenantId)
                        ->byDefinition($definition->id)
                        ->distinct('user_id')
                        ->count('user_id');

                    $stepData = [
                        'step' => $index + 1,
                        'event_name' => $eventName,
                        'unique_users' => $count,
                        'definition_id' => $definition->id,
                    ];

                    // Calculate conversion rate from previous step
                    if ($previousCount !== null && $previousCount > 0) {
                        $stepData['conversion_rate'] = round(($count / $previousCount) * 100, 2);
                        $stepData['drop_off'] = $previousCount - $count;
                        $stepData['drop_off_rate'] = round((($previousCount - $count) / $previousCount) * 100, 2);
                    } else {
                        $stepData['conversion_rate'] = 100.0;
                        $stepData['drop_off'] = 0;
                        $stepData['drop_off_rate'] = 0;
                    }

                    $funnelSteps[] = $stepData;
                    $previousCount = $count;
                    $previousEventName = $eventName;
                }

                // Calculate overall conversion rate
                $firstStepUsers = $funnelSteps[0]['unique_users'] ?? 0;
                $lastStepUsers = $funnelSteps[count($funnelSteps) - 1]['unique_users'] ?? 0;
                $overallConversion = $firstStepUsers > 0
                    ? round(($lastStepUsers / $firstStepUsers) * 100, 2)
                    : 0;

                return [
                    'steps' => $funnelSteps,
                    'overall_conversion_rate' => $overallConversion,
                    'total_steps' => count($events),
                    'created_at' => now()->toIso8601String(),
                ];
            });

        } catch (Exception $e) {
            Log::error('Failed to create funnel analysis', [
                'events' => $events,
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => $e->getMessage(),
                'steps' => [],
            ];
        }
    }

    /**
     * Analyze user behavior flow
     *
     * @param  int  $userId  User ID
     * @param  string|null  $startDate  Start date
     * @param  string|null  $endDate  End date
     */
    public function analyzeBehaviorFlow(int $userId, ?string $startDate = null, ?string $endDate = null): array
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $cacheKey = "behavior_flow_{$tenantId}_{$userId}_".md5(serialize([$startDate, $endDate]));

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $startDate, $endDate, $tenantId) {
                $query = CustomEvent::byTenant($tenantId)
                    ->byUser($userId)
                    ->with('definition')
                    ->orderBy('timestamp');

                if ($startDate && $endDate) {
                    $query->byPeriod($startDate, $endDate);
                }

                $events = $query->get();

                if ($events->isEmpty()) {
                    return $this->getEmptyBehaviorFlowResponse($userId);
                }

                // Extract event sequence
                $eventSequence = [];
                foreach ($events as $event) {
                    $eventSequence[] = [
                        'event_name' => $event->definition->name,
                        'timestamp' => $event->timestamp->toIso8601String(),
                        'properties' => $event->data_json,
                    ];
                }

                // Calculate statistics
                $uniqueEventNames = $events->pluck('definition.name')->unique();
                $timeSpan = $events->max('timestamp')->diffInSeconds($events->min('timestamp'));

                return [
                    'user_id' => $userId,
                    'total_events' => $events->count(),
                    'unique_events' => $uniqueEventNames->count(),
                    'event_types' => $uniqueEventNames->values(),
                    'event_sequence' => $eventSequence,
                    'time_span_seconds' => $timeSpan,
                    'events_per_minute' => $timeSpan > 0 ? round(($events->count() / $timeSpan) * 60, 2) : 0,
                    'first_event' => $events->min('timestamp')->toIso8601String(),
                    'last_event' => $events->max('timestamp')->toIso8601String(),
                    'date_range' => [
                        'start' => $startDate ?? $events->min('timestamp')->toDateString(),
                        'end' => $endDate ?? $events->max('timestamp')->toDateString(),
                    ],
                    'analyzed_at' => now()->toIso8601String(),
                ];
            });

        } catch (Exception $e) {
            Log::error('Failed to analyze behavior flow', [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ];
        }
    }

    // Private helper methods

    /**
     * Get current tenant ID
     */
    private function getCurrentTenantId(): int
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        return $tenantId ? (int) $tenantId : 1;
    }

    /**
     * Validate event definition data
     *
     * @throws Exception
     */
    private function validateEventDefinition(array $definition): void
    {
        if (empty($definition['name'])) {
            throw new Exception('Event name is required');
        }

        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $definition['name'])) {
            throw new Exception('Event name must be alphanumeric with underscores, starting with letter or underscore');
        }

        $parameters = $definition['parameters'] ?? [];
        if (! is_array($parameters)) {
            throw new Exception('Parameters must be an array');
        }

        foreach ($parameters as $index => $param) {
            if (! is_array($param)) {
                throw new Exception("Parameter at index {$index} must be an array");
            }

            if (! isset($param['name']) || ! is_string($param['name'])) {
                throw new Exception("Parameter at index {$index} must have a valid name");
            }

            if (! isset($param['type']) || ! in_array($param['type'], ['string', 'number', 'boolean', 'array', 'object'])) {
                throw new Exception("Parameter '{$param['name']}' has invalid type. Allowed types: string, number, boolean, array, object");
            }
        }
    }

    /**
     * Validate parameter type
     *
     * @param  mixed  $value
     *
     * @throws Exception
     */
    private function validateParameterType($value, string $expectedType, string $paramName, string $eventName): void
    {
        $valid = match ($expectedType) {
            'string' => is_string($value),
            'number' => is_numeric($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_object($value),
            default => false,
        };

        if (! $valid) {
            throw new Exception("Parameter '{$paramName}' for event '{$eventName}' must be of type '{$expectedType}'");
        }
    }

    /**
     * Clear event definition cache for tenant
     */
    private function clearEventDefinitionCache(int $tenantId): void
    {
        Cache::forget("custom_events_list_{$tenantId}");
        // Note: Individual event definition caches will expire naturally
    }

    /**
     * Get event definition cache key
     */
    private function getEventDefinitionCacheKey(int $tenantId, string $eventName): string
    {
        return "event_definition_{$tenantId}_{$eventName}";
    }

    /**
     * Get empty analysis response
     */
    private function getEmptyAnalysisResponse(string $eventName): array
    {
        return [
            'event_name' => $eventName,
            'total_events' => 0,
            'unique_users' => 0,
            'date_range' => null,
            'properties_analysis' => [],
            'time_series' => [],
            'top_users' => [],
            'analyzed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get empty behavior flow response
     */
    private function getEmptyBehaviorFlowResponse(int $userId): array
    {
        return [
            'user_id' => $userId,
            'total_events' => 0,
            'unique_events' => 0,
            'event_types' => [],
            'event_sequence' => [],
            'time_span_seconds' => 0,
            'events_per_minute' => 0,
            'first_event' => null,
            'last_event' => null,
            'date_range' => null,
            'analyzed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Analyze properties from events
     */
    private function analyzeProperties(Collection $events, array $parameters): array
    {
        $analysis = [];

        foreach ($parameters as $param) {
            $paramName = $param['name'];
            $paramType = $param['type'];

            $values = $events->pluck('data_json')->pluck($paramName)->filter()->values();

            if ($values->isEmpty()) {
                continue;
            }

            if (in_array($paramType, ['number'])) {
                $analysis[$paramName] = [
                    'type' => 'numeric',
                    'count' => $values->count(),
                    'sum' => $values->sum(),
                    'avg' => round($values->avg(), 2),
                    'min' => $values->min(),
                    'max' => $values->max(),
                    'median' => $this->calculateMedian($values->all()),
                ];
            } elseif ($paramType === 'string') {
                $valueCounts = $values->countBy()->sortDesc()->take(10);
                $analysis[$paramName] = [
                    'type' => 'categorical',
                    'count' => $values->count(),
                    'unique_values' => $valueCounts->count(),
                    'top_values' => $valueCounts->toArray(),
                ];
            } else {
                $analysis[$paramName] = [
                    'type' => $paramType,
                    'count' => $values->count(),
                ];
            }
        }

        return $analysis;
    }

    /**
     * Generate time series from events
     */
    private function generateTimeSeries(Collection $events, array $filters): array
    {
        $startDate = isset($filters['start_date'])
            ? Carbon::parse($filters['start_date'])
            : Carbon::parse($events->min('timestamp'))->startOfDay();

        $endDate = isset($filters['end_date'])
            ? Carbon::parse($filters['end_date'])
            : Carbon::parse($events->max('timestamp'))->endOfDay();

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

    /**
     * Get top users by event count
     */
    private function getTopUsers(Collection $events): array
    {
        $userCounts = $events->groupBy('user_id')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->take(10);

        return $userCounts->map(fn ($count, $userId) => [
            'user_id' => $userId,
            'event_count' => $count,
        ])->values()->toArray();
    }

    /**
     * Calculate median of array
     */
    private function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0;
        }

        sort($values);
        $count = count($values);
        $middle = floor($count / 2);

        if ($count % 2) {
            return (float) $values[$middle];
        }

        return (float) (($values[$middle - 1] + $values[$middle]) / 2);
    }
}
