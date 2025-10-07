<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AnalyticsEvent;
use App\Models\CustomEventTracking;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

/**
 * Bulk Event Factory
 *
 * Generates high-volume test data for performance testing analytics systems.
 * Supports both AnalyticsEvent and CustomEventTracking models with realistic data patterns.
 */
class BulkEventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = AnalyticsEvent::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $eventTypes = [
            'learning' => 60,
            'page_view' => 20,
            'user_interaction' => 15,
            'system' => 5,
        ];

        $eventType = $this->weightedRandom($eventTypes);

        return [
            'tenant_id' => Tenant::factory(),
            'event_type' => $eventType,
            'event_name' => $this->getEventNameForType($eventType),
            'user_id' => User::factory(),
            'properties' => $this->getPropertiesForEventType($eventType),
            'occurred_at' => $this->faker->dateTimeBetween('-90 days', 'now'),
            'is_compliant' => $this->faker->boolean(95), // 95% compliant
            'consent_given' => $this->faker->boolean(90), // 90% consent given
            'analytics_version' => '1.0',
        ];
    }

    /**
     * Create bulk analytics events for performance testing
     */
    public function createBulkAnalyticsEvents(int $count, array $overrides = []): Collection
    {
        $events = collect();

        for ($i = 0; $i < $count; $i++) {
            $event = $this->make(array_merge([
                'tenant_id' => $overrides['tenant_id'] ?? Tenant::factory()->create()->id,
                'user_id' => $overrides['user_id'] ?? User::factory()->create()->id,
            ], $overrides));

            $events->push($event);
        }

        // Bulk insert for performance
        if ($count > 100) {
            AnalyticsEvent::insert($events->map(fn($event) => $event->toArray())->toArray());
            return AnalyticsEvent::whereIn('id', $events->pluck('id'))->get();
        }

        return $events->map(fn($event) => $event->save())->filter();
    }

    /**
     * Create bulk custom events for performance testing
     */
    public function createBulkCustomEvents(int $count, array $overrides = []): Collection
    {
        $events = collect();

        for ($i = 0; $i < $count; $i++) {
            $eventData = array_merge([
                'tenant_id' => $overrides['tenant_id'] ?? Tenant::factory()->create()->id,
                'event_name' => $overrides['event_name'] ?? $this->faker->randomElement([
                    'button_click', 'form_submit', 'page_scroll', 'video_play', 'download'
                ]),
                'event_data' => $this->generateCustomEventData(),
                'context' => $this->generateEventContext(),
                'user_id' => $overrides['user_id'] ?? User::factory()->create()->id,
                'session_id' => $this->faker->uuid(),
                'user_agent' => $this->faker->userAgent(),
                'ip_address' => $this->faker->ipv4(),
                'referrer' => $this->faker->url(),
                'page_url' => $this->faker->url(),
                'occurred_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'is_compliant' => $this->faker->boolean(95),
                'consent_given' => $this->faker->boolean(90),
                'data_retention_until' => $this->faker->dateTimeBetween('now', '+2 years'),
                'analytics_version' => '1.0',
            ], $overrides);

            $events->push($eventData);
        }

        // Bulk insert for performance
        if ($count > 100) {
            CustomEventTracking::insert($events->toArray());
            return CustomEventTracking::whereIn('id', [])->get(); // Would need to get IDs after insert
        }

        return $events->map(fn($event) => CustomEventTracking::create($event));
    }

    /**
     * Create mixed event types for comprehensive testing
     */
    public function createMixedEventLoad(int $analyticsCount, int $customCount, array $tenantOverrides = []): array
    {
        $tenants = [];
        $totalTenants = $tenantOverrides['count'] ?? 2;

        // Create tenants
        for ($i = 0; $i < $totalTenants; $i++) {
            $tenants[] = Tenant::factory()->create([
                'name' => "Bulk Test Tenant {$i}",
                'domain' => "bulk-test-{$i}.test",
            ]);
        }

        $results = [
            'tenants' => $tenants,
            'analytics_events' => collect(),
            'custom_events' => collect(),
        ];

        // Distribute analytics events across tenants
        $analyticsPerTenant = (int) ($analyticsCount / $totalTenants);
        foreach ($tenants as $tenant) {
            $events = $this->createBulkAnalyticsEvents($analyticsPerTenant, [
                'tenant_id' => $tenant->id,
            ]);
            $results['analytics_events'] = $results['analytics_events']->merge($events);
        }

        // Distribute custom events across tenants
        $customPerTenant = (int) ($customCount / $totalTenants);
        foreach ($tenants as $tenant) {
            $events = $this->createBulkCustomEvents($customPerTenant, [
                'tenant_id' => $tenant->id,
            ]);
            $results['custom_events'] = $results['custom_events']->merge($events);
        }

        return $results;
    }

    /**
     * Create time-series event data for trend analysis testing
     */
    public function createTimeSeriesEvents(int $days, int $eventsPerDay, array $overrides = []): Collection
    {
        $events = collect();
        $baseDate = $overrides['start_date'] ?? now()->subDays($days);

        for ($day = 0; $day < $days; $day++) {
            $date = $baseDate->copy()->addDays($day);

            for ($event = 0; $event < $eventsPerDay; $event++) {
                $eventData = $this->make(array_merge($overrides, [
                    'occurred_at' => $date->copy()->addMinutes(rand(0, 1439)), // Random time during day
                ]));

                $events->push($eventData);
            }
        }

        // Bulk insert
        AnalyticsEvent::insert($events->map(fn($event) => $event->toArray())->toArray());

        return AnalyticsEvent::whereIn('id', $events->pluck('id'))->get();
    }

    /**
     * Create events with specific performance characteristics
     */
    public function createPerformanceTestEvents(array $config): array
    {
        $results = [
            'fast_events' => collect(),
            'slow_events' => collect(),
            'memory_intensive_events' => collect(),
        ];

        // Fast events (simple page views)
        if (isset($config['fast_count'])) {
            $results['fast_events'] = $this->createBulkAnalyticsEvents($config['fast_count'], [
                'event_type' => 'page_view',
                'properties' => ['page' => '/dashboard', 'load_time' => rand(100, 300)],
            ]);
        }

        // Slow events (complex calculations)
        if (isset($config['slow_count'])) {
            $results['slow_events'] = $this->createBulkAnalyticsEvents($config['slow_count'], [
                'event_type' => 'learning',
                'event_name' => 'course_completion',
                'properties' => $this->getComplexLearningProperties(),
            ]);
        }

        // Memory intensive events (large data payloads)
        if (isset($config['memory_count'])) {
            $results['memory_intensive_events'] = $this->createBulkCustomEvents($config['memory_count'], [
                'event_data' => $this->generateLargeEventData(),
            ]);
        }

        return $results;
    }

    // Helper methods

    private function weightedRandom(array $weights): string
    {
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);

        foreach ($weights as $item => $weight) {
            $random -= $weight;
            if ($random <= 0) {
                return $item;
            }
        }

        return array_key_first($weights);
    }

    private function getEventNameForType(string $eventType): string
    {
        $eventNames = [
            'learning' => ['course_interaction', 'quiz_completed', 'module_viewed', 'certification_earned'],
            'page_view' => ['page_view', 'navigation'],
            'user_interaction' => ['button_click', 'form_submit', 'search'],
            'system' => ['login', 'logout', 'error'],
        ];

        return $this->faker->randomElement($eventNames[$eventType] ?? ['generic_event']);
    }

    private function getPropertiesForEventType(string $eventType): array
    {
        switch ($eventType) {
            case 'learning':
                return [
                    'course_id' => rand(1, 100),
                    'module_id' => rand(1, 20),
                    'duration' => rand(60, 3600),
                    'score' => rand(0, 100),
                    'interaction_type' => $this->faker->randomElement(['view', 'completion', 'quiz']),
                ];
            case 'page_view':
                return [
                    'page' => $this->faker->randomElement(['/dashboard', '/courses', '/profile', '/analytics']),
                    'referrer' => $this->faker->url(),
                    'load_time' => rand(200, 2000),
                ];
            case 'user_interaction':
                return [
                    'element' => $this->faker->randomElement(['button', 'link', 'form', 'search']),
                    'action' => $this->faker->randomElement(['click', 'submit', 'focus', 'blur']),
                    'value' => $this->faker->word(),
                ];
            default:
                return ['generic_property' => $this->faker->word()];
        }
    }

    private function generateCustomEventData(): array
    {
        return [
            'action' => $this->faker->randomElement(['click', 'submit', 'scroll', 'play', 'pause']),
            'element' => $this->faker->randomElement(['button', 'link', 'video', 'form', 'image']),
            'element_id' => 'elem_' . rand(1000, 9999),
            'position' => [
                'x' => rand(0, 1920),
                'y' => rand(0, 1080),
            ],
            'timestamp' => $this->faker->dateTime()->getTimestamp(),
            'metadata' => [
                'browser' => $this->faker->randomElement(['chrome', 'firefox', 'safari', 'edge']),
                'device' => $this->faker->randomElement(['desktop', 'mobile', 'tablet']),
                'os' => $this->faker->randomElement(['windows', 'macos', 'linux', 'ios', 'android']),
            ],
        ];
    }

    private function generateEventContext(): array
    {
        return [
            'session_id' => $this->faker->uuid(),
            'user_agent' => $this->faker->userAgent(),
            'ip_address' => $this->faker->ipv4(),
            'referrer' => $this->faker->url(),
            'page_url' => $this->faker->url(),
            'viewport' => [
                'width' => rand(800, 1920),
                'height' => rand(600, 1080),
            ],
            'locale' => $this->faker->locale(),
            'timezone' => $this->faker->timezone(),
        ];
    }

    private function getComplexLearningProperties(): array
    {
        return [
            'course_id' => rand(1, 100),
            'module_id' => rand(1, 20),
            'lesson_id' => rand(1, 50),
            'duration' => rand(300, 7200),
            'score' => rand(0, 100),
            'attempts' => rand(1, 5),
            'interaction_type' => 'completion',
            'progress' => rand(10, 100),
            'time_spent' => rand(1800, 10800),
            'resources_accessed' => rand(5, 25),
            'quiz_answers' => array_fill(0, rand(5, 15), rand(0, 1)),
            'feedback' => $this->faker->sentence(),
            'completion_certificate' => $this->faker->boolean(80),
        ];
    }

    private function generateLargeEventData(): array
    {
        // Generate large event data for memory testing
        return [
            'large_array' => array_fill(0, 1000, $this->faker->sentence()),
            'nested_objects' => array_fill(0, 100, [
                'id' => $this->faker->uuid(),
                'data' => array_fill(0, 50, $this->faker->word()),
                'metadata' => [
                    'created' => $this->faker->dateTime()->format('c'),
                    'tags' => array_fill(0, 20, $this->faker->word()),
                ],
            ]),
            'binary_data' => base64_encode(random_bytes(1024)), // 1KB of random data
            'text_content' => $this->faker->paragraphs(10, true),
        ];
    }
}