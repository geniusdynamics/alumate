<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\CustomEvent;
use App\Models\CustomEventDefinition;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Behavior Flow Service
 *
 * Analyzes user behavior patterns and event sequences to understand
 * how users navigate through application and identify common paths.
 */
class BehaviorFlowService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const MAX_PATH_DEPTH = 10;

    private const MIN_PATH_FREQUENCY = 2;

    /**
     * Analyze behavior flow for a specific event
     */
    public function analyzeBehaviorFlow(int $definitionId, array $filters = []): array
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $cacheKey = "behavior_flow_{$tenantId}_{$definitionId}_".md5(serialize($filters));

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($definitionId, $filters, $tenantId) {
                $definition = CustomEventDefinition::byTenant($tenantId)
                    ->active()
                    ->find($definitionId);

                if (! $definition) {
                    throw new Exception('Event definition not found');
                }

                // Get events for definition
                $events = $this->getEvents($definitionId, $filters);

                if ($events->isEmpty()) {
                    return $this->getEmptyFlowResponse();
                }

                // Analyze user paths
                $userPaths = $this->extractUserPaths($events);

                // Build flow graph
                $flowGraph = $this->buildFlowGraph($userPaths);

                // Calculate metrics
                $metrics = $this->calculateFlowMetrics($userPaths, $flowGraph);

                // Identify common paths
                $commonPaths = $this->identifyCommonPaths($userPaths);

                // Generate optimization suggestions
                $suggestions = $this->generateOptimizationSuggestions($metrics, $flowGraph);

                return [
                    'definition' => [
                        'id' => $definition->id,
                        'name' => $definition->name,
                        'description' => $definition->description,
                    ],
                    'flow_graph' => $flowGraph,
                    'metrics' => $metrics,
                    'common_paths' => $commonPaths,
                    'optimization_suggestions' => $suggestions,
                    'total_users' => $events->unique('user_id')->count(),
                    'total_events' => $events->count(),
                ];
            });

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'flow_graph' => [],
                'metrics' => [],
                'common_paths' => [],
                'optimization_suggestions' => [],
            ];
        }
    }

    /**
     * Analyze funnel for a sequence of events
     *
     * @param  array  $eventSequence  Array of event definition IDs
     */
    public function analyzeFunnel(array $eventSequence, array $filters = []): array
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $cacheKey = "funnel_analysis_{$tenantId}_".md5(serialize($eventSequence).serialize($filters));

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($eventSequence, $filters, $tenantId) {
                if (count($eventSequence) < 2) {
                    throw new Exception('Funnel requires at least 2 events');
                }

                // Get event definitions
                $definitions = CustomEventDefinition::byTenant($tenantId)
                    ->active()
                    ->whereIn('id', $eventSequence)
                    ->get()
                    ->keyBy('id');

                if ($definitions->count() !== count($eventSequence)) {
                    throw new Exception('One or more event definitions not found');
                }

                // Analyze funnel steps
                $funnelSteps = $this->analyzeFunnelSteps($eventSequence, $filters);

                // Calculate conversion rates
                $conversionRates = $this->calculateConversionRates($funnelSteps);

                // Identify drop-off points
                $dropOffPoints = $this->identifyDropOffPoints($funnelSteps);

                // Generate funnel insights
                $insights = $this->generateFunnelInsights($funnelSteps, $conversionRates);

                return [
                    'steps' => $funnelSteps,
                    'conversion_rates' => $conversionRates,
                    'drop_off_points' => $dropOffPoints,
                    'insights' => $insights,
                    'overall_conversion_rate' => $conversionRates['overall'] ?? 0,
                ];
            });

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'steps' => [],
                'conversion_rates' => [],
                'drop_off_points' => [],
                'insights' => [],
            ];
        }
    }

    /**
     * Get events for analysis
     */
    private function getEvents(int $definitionId, array $filters): Collection
    {
        $query = CustomEvent::byTenant($this->getCurrentTenantId())
            ->byDefinition($definitionId)
            ->with('definition')
            ->orderBy('timestamp');

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('timestamp', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->get();
    }

    /**
     * Extract user paths from events
     */
    private function extractUserPaths(Collection $events): array
    {
        $userPaths = [];

        foreach ($events->groupBy('user_id') as $userId => $userEvents) {
            $path = [];
            foreach ($userEvents as $event) {
                $path[] = [
                    'event_id' => $event->id,
                    'definition_id' => $event->definition_id,
                    'event_name' => $event->definition->name,
                    'timestamp' => $event->timestamp->toIso8601String(),
                    'data' => $event->data_json,
                ];
            }
            $userPaths[$userId] = $path;
        }

        return $userPaths;
    }

    /**
     * Build flow graph from user paths
     */
    private function buildFlowGraph(array $userPaths): array
    {
        $graph = [];
        $transitions = [];

        foreach ($userPaths as $userId => $path) {
            for ($i = 0; $i < count($path) - 1; $i++) {
                $from = $path[$i]['event_name'];
                $to = $path[$i + 1]['event_name'];

                $key = "{$from}|{$to}";
                if (! isset($transitions[$key])) {
                    $transitions[$key] = [
                        'from' => $from,
                        'to' => $to,
                        'count' => 0,
                        'users' => [],
                    ];
                }

                $transitions[$key]['count']++;
                $transitions[$key]['users'][] = $userId;
            }
        }

        // Build graph structure
        foreach ($transitions as $transition) {
            $from = $transition['from'];
            $to = $transition['to'];

            if (! isset($graph[$from])) {
                $graph[$from] = [
                    'event' => $from,
                    'outgoing' => [],
                    'total_outgoing' => 0,
                ];
            }

            $graph[$from]['outgoing'][] = [
                'event' => $to,
                'count' => $transition['count'],
                'unique_users' => count(array_unique($transition['users'])),
                'percentage' => 0, // Will be calculated
            ];

            $graph[$from]['total_outgoing'] += $transition['count'];
        }

        // Calculate percentages
        foreach ($graph as &$node) {
            foreach ($node['outgoing'] as &$edge) {
                $edge['percentage'] = $node['total_outgoing'] > 0
                    ? round(($edge['count'] / $node['total_outgoing']) * 100, 2)
                    : 0;
            }
        }

        return array_values($graph);
    }

    /**
     * Calculate flow metrics
     */
    private function calculateFlowMetrics(array $userPaths, array $flowGraph): array
    {
        $totalUsers = count($userPaths);
        $totalEvents = array_sum(array_map('count', $userPaths));
        $avgPathLength = $totalUsers > 0 ? $totalEvents / $totalUsers : 0;

        // Calculate path length distribution
        $pathLengths = array_map('count', $userPaths);
        $pathLengthDistribution = [
            'min' => min($pathLengths) ?: 0,
            'max' => max($pathLengths) ?: 0,
            'avg' => round($avgPathLength, 2),
            'median' => $this->calculateMedian($pathLengths),
        ];

        // Calculate unique events
        $uniqueEvents = [];
        foreach ($userPaths as $path) {
            foreach ($path as $event) {
                $uniqueEvents[$event['event_name']] = true;
            }
        }

        return [
            'total_users' => $totalUsers,
            'total_events' => $totalEvents,
            'unique_events' => count($uniqueEvents),
            'path_length_distribution' => $pathLengthDistribution,
            'avg_path_length' => round($avgPathLength, 2),
        ];
    }

    /**
     * Identify common paths
     */
    private function identifyCommonPaths(array $userPaths): array
    {
        $pathCounts = [];
        $totalUsers = count($userPaths);

        foreach ($userPaths as $userId => $path) {
            // Extract sub-paths of different lengths
            for ($length = 2; $length <= min(count($path), self::MAX_PATH_DEPTH); $length++) {
                for ($i = 0; $i <= count($path) - $length; $i++) {
                    $subPath = array_slice($path, $i, $length);
                    $pathKey = implode(' -> ', array_column($subPath, 'event_name'));

                    if (! isset($pathCounts[$pathKey])) {
                        $pathCounts[$pathKey] = [
                            'path' => $pathKey,
                            'events' => $subPath,
                            'count' => 0,
                            'users' => [],
                        ];
                    }

                    $pathCounts[$pathKey]['count']++;
                    $pathCounts[$pathKey]['users'][] = $userId;
                }
            }
        }

        // Filter and sort by frequency
        $commonPaths = array_filter($pathCounts, function ($path) {
            return $path['count'] >= self::MIN_PATH_FREQUENCY;
        });

        usort($commonPaths, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        // Return top 10 paths
        return array_map(function ($path) use ($totalUsers) {
            return [
                'path' => $path['path'],
                'frequency' => $path['count'],
                'percentage' => $totalUsers > 0 ? round(($path['count'] / $totalUsers) * 100, 2) : 0,
                'unique_users' => count(array_unique($path['users'])),
            ];
        }, array_slice($commonPaths, 0, 10));
    }

    /**
     * Generate optimization suggestions
     */
    private function generateOptimizationSuggestions(array $metrics, array $flowGraph): array
    {
        $suggestions = [];

        // Check for high drop-off points
        foreach ($flowGraph as $node) {
            foreach ($node['outgoing'] as $edge) {
                if ($edge['percentage'] < 20) {
                    $suggestions[] = [
                        'type' => 'drop_off',
                        'severity' => 'high',
                        'message' => "Low transition rate ({$edge['percentage']}%) from '{$node['event']}' to '{$edge['event']}'",
                        'recommendation' => "Investigate why users are not proceeding from '{$node['event']}' to '{$edge['event']}'. Consider improving user experience or providing clearer guidance.",
                    ];
                } elseif ($edge['percentage'] < 40) {
                    $suggestions[] = [
                        'type' => 'drop_off',
                        'severity' => 'medium',
                        'message' => "Moderate transition rate ({$edge['percentage']}%) from '{$node['event']}' to '{$edge['event']}'",
                        'recommendation' => "Review user journey from '{$node['event']}' to '{$edge['event']}' for potential improvements.",
                    ];
                }
            }
        }

        // Check for path length issues
        if ($metrics['avg_path_length'] > 10) {
            $suggestions[] = [
                'type' => 'path_length',
                'severity' => 'medium',
                'message' => "Average path length is {$metrics['avg_path_length']} events, which may indicate complexity",
                'recommendation' => 'Consider simplifying user journey or providing shortcuts to reduce number of steps required to complete key actions.',
            ];
        }

        // Check for low engagement
        if ($metrics['total_users'] > 0 && $metrics['total_events'] / $metrics['total_users'] < 2) {
            $suggestions[] = [
                'type' => 'engagement',
                'severity' => 'high',
                'message' => 'Low user engagement with an average of '.round($metrics['total_events'] / $metrics['total_users'], 2).' events per user',
                'recommendation' => 'Investigate why users are not engaging more deeply with the application. Consider improving onboarding, adding features, or enhancing user experience.',
            ];
        }

        return $suggestions;
    }

    /**
     * Analyze funnel steps
     */
    private function analyzeFunnelSteps(array $eventSequence, array $filters): array
    {
        $steps = [];
        $tenantId = $this->getCurrentTenantId();

        foreach ($eventSequence as $index => $definitionId) {
            $definition = CustomEventDefinition::byTenant($tenantId)
                ->active()
                ->find($definitionId);

            if (! $definition) {
                continue;
            }

            $query = CustomEvent::byTenant($tenantId)
                ->byDefinition($definitionId);

            // Apply filters
            if (isset($filters['start_date']) && isset($filters['end_date'])) {
                $query->whereBetween('timestamp', [$filters['start_date'], $filters['end_date']]);
            }

            $events = $query->get();
            $uniqueUsers = $events->unique('user_id')->count();

            $steps[] = [
                'step' => $index + 1,
                'definition_id' => $definitionId,
                'event_name' => $definition->name,
                'total_events' => $events->count(),
                'unique_users' => $uniqueUsers,
            ];
        }

        return $steps;
    }

    /**
     * Calculate conversion rates
     */
    private function calculateConversionRates(array $funnelSteps): array
    {
        $rates = [];

        if (empty($funnelSteps)) {
            return $rates;
        }

        $initialUsers = $funnelSteps[0]['unique_users'];

        foreach ($funnelSteps as $index => $step) {
            $stepRate = $initialUsers > 0
                ? round(($step['unique_users'] / $initialUsers) * 100, 2)
                : 0;

            $stepKey = 'step_'.($index + 1);
            $rates[$stepKey] = [
                'step' => $index + 1,
                'users' => $step['unique_users'],
                'conversion_rate' => $stepRate,
            ];

            // Calculate step-to-step conversion
            if ($index > 0) {
                $previousUsers = $funnelSteps[$index - 1]['unique_users'];
                $stepToStepRate = $previousUsers > 0
                    ? round(($step['unique_users'] / $previousUsers) * 100, 2)
                    : 0;

                $rates[$stepKey]['step_conversion_rate'] = $stepToStepRate;
            }
        }

        // Calculate overall conversion rate
        $finalUsers = $funnelSteps[count($funnelSteps) - 1]['unique_users'];
        $rates['overall'] = $initialUsers > 0
            ? round(($finalUsers / $initialUsers) * 100, 2)
            : 0;

        return $rates;
    }

    /**
     * Identify drop-off points
     */
    private function identifyDropOffPoints(array $funnelSteps): array
    {
        $dropOffPoints = [];

        for ($i = 1; $i < count($funnelSteps); $i++) {
            $previousUsers = $funnelSteps[$i - 1]['unique_users'];
            $currentUsers = $funnelSteps[$i]['unique_users'];

            if ($previousUsers > 0) {
                $dropOffRate = round((($previousUsers - $currentUsers) / $previousUsers) * 100, 2);

                if ($dropOffRate > 50) {
                    $severity = 'high';
                } elseif ($dropOffRate > 30) {
                    $severity = 'medium';
                } else {
                    $severity = 'low';
                }

                $dropOffPoints[] = [
                    'from_step' => $i,
                    'to_step' => $i + 1,
                    'from_event' => $funnelSteps[$i - 1]['event_name'],
                    'to_event' => $funnelSteps[$i]['event_name'],
                    'drop_off_rate' => $dropOffRate,
                    'users_lost' => $previousUsers - $currentUsers,
                    'severity' => $severity,
                ];
            }
        }

        return $dropOffPoints;
    }

    /**
     * Generate funnel insights
     */
    private function generateFunnelInsights(array $funnelSteps, array $conversionRates): array
    {
        $insights = [];

        // Overall conversion insight
        $overallRate = $conversionRates['overall'] ?? 0;
        if ($overallRate > 70) {
            $insights[] = [
                'type' => 'positive',
                'message' => "Excellent overall conversion rate of {$overallRate}%",
                'recommendation' => 'Continue monitoring and look for opportunities to further optimize the funnel.',
            ];
        } elseif ($overallRate > 40) {
            $insights[] = [
                'type' => 'neutral',
                'message' => "Moderate overall conversion rate of {$overallRate}%",
                'recommendation' => 'Focus on improving steps with the highest drop-off rates.',
            ];
        } else {
            $insights[] = [
                'type' => 'negative',
                'message' => "Low overall conversion rate of {$overallRate}%",
                'recommendation' => 'Review the entire funnel and identify major barriers to conversion.',
            ];
        }

        return $insights;
    }

    /**
     * Calculate median
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
            return $values[$middle];
        }

        return ($values[$middle - 1] + $values[$middle]) / 2;
    }

    /**
     * Get empty flow response
     */
    private function getEmptyFlowResponse(): array
    {
        return [
            'flow_graph' => [],
            'metrics' => [
                'total_users' => 0,
                'total_events' => 0,
                'unique_events' => 0,
                'path_length_distribution' => [
                    'min' => 0,
                    'max' => 0,
                    'avg' => 0,
                    'median' => 0,
                ],
                'avg_path_length' => 0,
            ],
            'common_paths' => [],
            'optimization_suggestions' => [],
        ];
    }

    /**
     * Get current tenant ID
     */
    private function getCurrentTenantId(): int
    {
        return (int) session('tenant_id', 1);
    }
}
