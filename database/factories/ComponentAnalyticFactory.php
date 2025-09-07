<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ComponentAnalytic>
 */
class ComponentAnalyticFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventTypes = ['view', 'click', 'conversion', 'form_submit'];
        $eventType = $eventTypes[array_rand($eventTypes)];

        return [
            'component_instance_id' => \App\Models\ComponentInstance::factory(),
            'event_type' => $eventType,
            'user_id' => rand(1, 10) <= 7 ? \App\Models\User::factory() : null,
            'session_id' => 'session-'.rand(1000, 9999),
            'data' => $this->generateEventData($eventType),
            'created_at' => now()->subDays(rand(0, 30)),
        ];
    }

    /**
     * Generate event-specific data based on event type.
     */
    private function generateEventData(string $eventType): array
    {
        $baseData = [
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'ip_address' => '192.168.1.'.rand(1, 255),
            'referrer' => rand(1, 10) <= 5 ? 'https://example.com' : null,
        ];

        return match ($eventType) {
            'view' => array_merge($baseData, [
                'viewport_width' => rand(320, 1920),
                'viewport_height' => rand(568, 1080),
                'scroll_depth' => rand(0, 100),
            ]),
            'click' => array_merge($baseData, [
                'element_id' => rand(1, 10) <= 5 ? 'btn-'.rand(1, 100) : null,
                'element_class' => rand(1, 10) <= 5 ? 'class-'.rand(1, 100) : null,
                'click_x' => rand(0, 1920),
                'click_y' => rand(0, 1080),
            ]),
            'conversion' => array_merge($baseData, [
                'conversion_value' => rand(10, 1000) + (rand(0, 99) / 100),
                'conversion_type' => ['signup', 'purchase', 'download', 'contact'][array_rand(['signup', 'purchase', 'download', 'contact'])],
                'funnel_step' => rand(1, 5),
            ]),
            'form_submit' => array_merge($baseData, [
                'form_id' => 'form-'.rand(1, 100),
                'fields_count' => rand(3, 15),
                'completion_time' => rand(30, 600), // seconds
                'validation_errors' => rand(0, 3),
            ]),
            default => $baseData,
        };
    }

    /**
     * Create analytics for A/B testing with variants.
     */
    public function withVariant(?string $variant = null): static
    {
        return $this->state(function (array $attributes) use ($variant) {
            $variantName = $variant ?? ['A', 'B', 'C'][array_rand(['A', 'B', 'C'])];

            $data = $attributes['data'] ?? [];
            $data['variant'] = $variantName;
            $data['test_id'] = 'test-'.rand(1000, 9999);

            return [
                'data' => $data,
            ];
        });
    }

    /**
     * Create view event analytics.
     */
    public function view(): static
    {
        return $this->state([
            'event_type' => 'view',
            'data' => $this->generateEventData('view'),
        ]);
    }

    /**
     * Create click event analytics.
     */
    public function click(): static
    {
        return $this->state([
            'event_type' => 'click',
            'data' => $this->generateEventData('click'),
        ]);
    }

    /**
     * Create conversion event analytics.
     */
    public function conversion(): static
    {
        return $this->state([
            'event_type' => 'conversion',
            'data' => $this->generateEventData('conversion'),
        ]);
    }

    /**
     * Create form submit event analytics.
     */
    public function formSubmit(): static
    {
        return $this->state([
            'event_type' => 'form_submit',
            'data' => $this->generateEventData('form_submit'),
        ]);
    }

    /**
     * Create analytics for a specific date range.
     */
    public function createdBetween(string $startDate, string $endDate): static
    {
        return $this->state([
            'created_at' => now()->subDays(rand(1, 30)),
        ]);
    }
}
