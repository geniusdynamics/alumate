<?php

namespace Database\Factories;

use App\Models\BehaviorEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BehaviorEvent>
 */
class BehaviorEventFactory extends Factory
{
    protected $model = BehaviorEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventTypes = array_keys(BehaviorEvent::EVENT_TYPES);
        $selectedEventType = fake()->randomElement($eventTypes);

        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'event_type' => $selectedEventType,
            'event_data' => $this->generateEventData($selectedEventType),
            'timestamp' => fake()->dateTimeBetween('-30 days', 'now'),
            'metadata' => $this->generateMetadata($selectedEventType),
        ];
    }

    /**
     * Generate event-specific data based on event type.
     */
    private function generateEventData(string $eventType): array
    {
        return match ($eventType) {
            'login' => [
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'login_method' => fake()->randomElement(['email', 'google', 'linkedin']),
                'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            ],
            'logout' => [
                'session_duration' => fake()->numberBetween(300, 28800), // 5 minutes to 8 hours
                'logout_reason' => fake()->randomElement(['manual', 'timeout', 'forced']),
            ],
            'profile_update' => [
                'updated_fields' => fake()->randomElements(['name', 'bio', 'location', 'website', 'phone'], fake()->numberBetween(1, 3)),
                'update_source' => fake()->randomElement(['web', 'mobile', 'api']),
            ],
            'job_application' => [
                'job_id' => fake()->uuid(),
                'job_title' => fake()->jobTitle(),
                'application_method' => fake()->randomElement(['web', 'api', 'email']),
                'resume_version' => 'v' . fake()->numberBetween(1, 5),
            ],
            'job_view' => [
                'job_id' => fake()->uuid(),
                'job_title' => fake()->jobTitle(),
                'view_source' => fake()->randomElement(['search', 'recommendation', 'direct_link']),
                'time_spent' => fake()->numberBetween(10, 300), // seconds
            ],
            'event_registration' => [
                'event_id' => fake()->uuid(),
                'event_title' => fake()->sentence(4),
                'registration_type' => fake()->randomElement(['attendee', 'speaker', 'volunteer']),
                'ticket_price' => fake()->randomFloat(2, 0, 500),
            ],
            'course_enrollment' => [
                'course_id' => fake()->uuid(),
                'course_title' => fake()->sentence(3),
                'enrollment_type' => fake()->randomElement(['self-paced', 'scheduled', 'certification']),
                'expected_completion' => fake()->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            ],
            'forum_post' => [
                'post_id' => fake()->uuid(),
                'forum_topic' => fake()->sentence(5),
                'post_type' => fake()->randomElement(['question', 'discussion', 'announcement']),
                'word_count' => fake()->numberBetween(50, 1000),
            ],
            'email_opened' => [
                'email_id' => fake()->uuid(),
                'campaign_id' => fake()->uuid(),
                'email_subject' => fake()->sentence(6),
                'opened_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            ],
            'email_clicked' => [
                'email_id' => fake()->uuid(),
                'campaign_id' => fake()->uuid(),
                'link_url' => fake()->url(),
                'link_text' => fake()->words(3, true),
            ],
            'page_view' => [
                'page_url' => fake()->url(),
                'page_title' => fake()->sentence(4),
                'referrer' => fake()->randomElement([null, fake()->url()]),
                'time_spent' => fake()->numberBetween(5, 600),
            ],
            'search_performed' => [
                'query' => fake()->words(fake()->numberBetween(1, 5), true),
                'filters' => fake()->randomElements(['location', 'salary', 'type', 'experience'], fake()->numberBetween(0, 3)),
                'results_count' => fake()->numberBetween(0, 100),
            ],
            'donation_made' => [
                'campaign_id' => fake()->uuid(),
                'amount' => fake()->randomFloat(2, 10, 10000),
                'currency' => 'USD',
                'payment_method' => fake()->randomElement(['credit_card', 'paypal', 'bank_transfer']),
                'is_recurring' => fake()->boolean(20), // 20% recurring
            ],
            default => [
                'custom_field' => fake()->word(),
                'value' => fake()->randomElement([fake()->word(), fake()->numberBetween(1, 100), fake()->boolean()]),
            ],
        };
    }

    /**
     * Generate metadata for the event.
     */
    private function generateMetadata(string $eventType): array
    {
        $baseMetadata = [
            'session_id' => fake()->uuid(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'device_info' => [
                'type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
                'os' => fake()->randomElement(['Windows', 'macOS', 'Linux', 'iOS', 'Android']),
                'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            ],
            'geolocation' => [
                'country' => fake()->country(),
                'region' => fake()->state(),
                'city' => fake()->city(),
            ],
        ];

        // Add event-specific metadata
        return match ($eventType) {
            'login', 'logout' => array_merge($baseMetadata, [
                'security_level' => fake()->randomElement(['standard', 'elevated', 'admin']),
                'mfa_used' => fake()->boolean(70), // 70% use MFA
            ]),
            'job_application', 'job_view' => array_merge($baseMetadata, [
                'search_context' => [
                    'keywords' => fake()->words(3, true),
                    'location_filter' => fake()->city(),
                    'salary_range' => fake()->randomElement(['$50k-$70k', '$70k-$100k', '$100k+']),
                ],
            ]),
            'email_opened', 'email_clicked' => array_merge($baseMetadata, [
                'email_client' => fake()->randomElement(['Gmail', 'Outlook', 'Apple Mail', 'Yahoo']),
                'opened_in_mobile' => fake()->boolean(60), // 60% mobile opens
            ]),
            'donation_made' => array_merge($baseMetadata, [
                'donor_type' => fake()->randomElement(['individual', 'corporate', 'foundation']),
                'tax_deductible' => fake()->boolean(90), // 90% tax deductible
            ]),
            default => $baseMetadata,
        };
    }

    /**
     * Create a behavior event for a specific event type.
     */
    public function ofType(string $eventType): static
    {
        return $this->state([
            'event_type' => $eventType,
            'event_data' => $this->generateEventData($eventType),
            'metadata' => $this->generateMetadata($eventType),
        ]);
    }

    /**
     * Create a behavior event for a specific user.
     */
    public function forUser($userId): static
    {
        return $this->state([
            'user_id' => $userId,
        ]);
    }

    /**
     * Create a behavior event for a specific tenant.
     */
    public function forTenant($tenantId): static
    {
        return $this->state([
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Create a conversion event (job application, donation, etc.).
     */
    public function conversion(): static
    {
        $conversionTypes = ['job_application', 'donation_made', 'event_registration', 'course_enrollment'];
        $eventType = fake()->randomElement($conversionTypes);

        return $this->ofType($eventType);
    }

    /**
     * Create an engagement event (views, opens, clicks, etc.).
     */
    public function engagement(): static
    {
        $engagementTypes = ['job_view', 'email_opened', 'email_clicked', 'page_view', 'forum_post'];
        $eventType = fake()->randomElement($engagementTypes);

        return $this->ofType($eventType);
    }

    /**
     * Create a recent behavior event.
     */
    public function recent(int $days = 7): static
    {
        return $this->state([
            'timestamp' => fake()->dateTimeBetween("-{$days} days", 'now'),
        ]);
    }

    /**
     * Create an old behavior event.
     */
    public function old(int $days = 30): static
    {
        return $this->state([
            'timestamp' => fake()->dateTimeBetween("-{$days} days", "-{$days} days +1 day"),
        ]);
    }

    /**
     * Create a behavior event with custom event data.
     */
    public function withEventData(array $data): static
    {
        return $this->state([
            'event_data' => array_merge($this->generateEventData($this->getEventType()), $data),
        ]);
    }

    /**
     * Create a behavior event with custom metadata.
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state([
            'metadata' => array_merge($this->generateMetadata($this->getEventType()), $metadata),
        ]);
    }

    /**
     * Get the current event type (for internal use).
     */
    private function getEventType(): string
    {
        return $this->faker->randomElement(array_keys(BehaviorEvent::EVENT_TYPES));
    }
}
