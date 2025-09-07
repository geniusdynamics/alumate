<?php

namespace Database\Factories;

use App\Models\EmailAnalytics;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailAnalytics>
 */
class EmailAnalyticsFactory extends Factory
{
    protected $model = EmailAnalytics::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $sendDate = fake()->dateTimeBetween('-30 days', 'now');
        $status = fake()->randomElement([
            'sent',
            'delivered',
            'opened',
            'clicked',
            'converted',
            'bounced',
            'complaint',
            'unsubscribed'
        ]);

        // Generate realistic timestamps based on status
        $deliveredAt = null;
        $openedAt = null;
        $clickedAt = null;
        $convertedAt = null;
        $bouncedAt = null;
        $complainedAt = null;
        $unsubscribedAt = null;

        if (in_array($status, ['delivered', 'opened', 'clicked', 'converted'])) {
            $deliveredAt = fake()->dateTimeBetween($sendDate, Carbon::parse($sendDate)->addMinutes(30));
        }

        if (in_array($status, ['opened', 'clicked', 'converted'])) {
            $openedAt = fake()->dateTimeBetween($deliveredAt ?? $sendDate, Carbon::parse($deliveredAt ?? $sendDate)->addHours(2));
        }

        if (in_array($status, ['clicked', 'converted'])) {
            $clickedAt = fake()->dateTimeBetween($openedAt ?? $sendDate, Carbon::parse($openedAt ?? $sendDate)->addHours(1));
        }

        if ($status === 'converted') {
            $convertedAt = fake()->dateTimeBetween($clickedAt ?? $sendDate, Carbon::parse($clickedAt ?? $sendDate)->addDays(7));
        }

        if ($status === 'bounced') {
            $bouncedAt = fake()->dateTimeBetween($sendDate, Carbon::parse($sendDate)->addMinutes(30));
        }

        if ($status === 'complaint') {
            $complainedAt = fake()->dateTimeBetween($sendDate, Carbon::parse($sendDate)->addDays(7));
        }

        if ($status === 'unsubscribed') {
            $unsubscribedAt = fake()->dateTimeBetween($sendDate, Carbon::parse($sendDate)->addDays(7));
        }

        return [
            'tenant_id' => $tenant->id,
            'email_campaign_id' => null, // Will be set by state methods if needed
            'email_template_id' => Template::factory()->create(['tenant_id' => $tenant->id])->id,
            'recipient_id' => User::factory()->create()->id,
            'recipient_email' => fake()->safeEmail(),
            'subject_line' => fake()->sentence(8),
            'send_date' => $sendDate,
            'delivered_at' => $deliveredAt,
            'opened_at' => $openedAt,
            'clicked_at' => $clickedAt,
            'converted_at' => $convertedAt,
            'unsubscribed_at' => $unsubscribedAt,
            'bounced_at' => $bouncedAt,
            'complained_at' => $complainedAt,
            'delivery_status' => $status,
            'open_count' => $openedAt ? fake()->numberBetween(1, 5) : 0,
            'click_count' => $clickedAt ? fake()->numberBetween(1, 3) : 0,
            'conversion_count' => $convertedAt ? 1 : 0,
            'bounce_reason' => $bouncedAt ? fake()->randomElement([
                'Mailbox full',
                'Invalid address',
                'Domain not found',
                'Recipient not found',
                'Message too large'
            ]) : null,
            'complaint_reason' => $complainedAt ? fake()->randomElement([
                'Spam',
                'Unsolicited',
                'Offensive content'
            ]) : null,
            'ip_address' => $openedAt ? fake()->ipv4() : null,
            'user_agent' => $openedAt ? fake()->userAgent() : null,
            'device_type' => $openedAt ? fake()->randomElement(['desktop', 'mobile', 'tablet']) : null,
            'browser' => $openedAt ? fake()->randomElement(['chrome', 'firefox', 'safari', 'edge', 'other']) : null,
            'location' => $openedAt ? fake()->city() . ', ' . fake()->country() : null,
            'referrer_url' => $clickedAt ? fake()->url() : null,
            'conversion_value' => $convertedAt ? fake()->randomFloat(2, 10, 1000) : 0.00,
            'conversion_type' => $convertedAt ? fake()->randomElement([
                'purchase',
                'signup',
                'download',
                'contact',
                'custom'
            ]) : null,
            'funnel_stage' => $convertedAt ? 5 : fake()->numberBetween(1, 4),
            'ab_test_variant' => fake()->optional(0.3)->randomElement(['A', 'B', 'C']),
            'tags' => fake()->optional(0.5)->randomElements([
                'newsletter',
                'promotional',
                'transactional',
                'marketing',
                'educational',
                'event',
                'survey'
            ], rand(1, 3)),
            'custom_data' => $this->generateCustomData($status, $sendDate),
            'created_by' => User::factory()->create()->id,
            'updated_by' => User::factory()->create()->id,
        ];
    }

    private function generateCustomData(string $status, $sendDate): array
    {
        $customData = [];

        // Add click tracking data if clicked
        if (in_array($status, ['clicked', 'converted'])) {
            $clicks = [];
            $clickCount = fake()->numberBetween(1, 3);

            for ($i = 0; $i < $clickCount; $i++) {
                $clicks[] = [
                    'url' => fake()->url(),
                    'timestamp' => fake()->dateTimeBetween($sendDate, 'now')->format('Y-m-d H:i:s'),
                    'metadata' => [
                        'link_position' => fake()->numberBetween(1, 10),
                        'link_text' => fake()->words(3, true),
                        'section' => fake()->randomElement(['header', 'body', 'footer', 'sidebar']),
                    ]
                ];
            }
            $customData['clicks'] = $clicks;
        }

        // Add conversion data if converted
        if ($status === 'converted') {
            $customData['conversions'] = [
                [
                    'type' => fake()->randomElement(['purchase', 'signup', 'download', 'contact']),
                    'value' => fake()->randomFloat(2, 10, 500),
                    'timestamp' => fake()->dateTimeBetween($sendDate, 'now')->format('Y-m-d H:i:s'),
                    'metadata' => [
                        'source' => 'email',
                        'campaign' => fake()->word(),
                        'channel' => 'newsletter'
                    ]
                ]
            ];
        }

        // Add engagement metrics
        $customData['engagement_score'] = $this->calculateEngagementScore($status);
        $customData['time_to_engage'] = $this->calculateTimeToEngage($status, $sendDate);

        return $customData;
    }

    private function calculateEngagementScore(string $status): float
    {
        return match ($status) {
            'converted' => fake()->randomFloat(2, 8.0, 10.0),
            'clicked' => fake()->randomFloat(2, 5.0, 8.0),
            'opened' => fake()->randomFloat(2, 2.0, 5.0),
            'delivered' => fake()->randomFloat(2, 0.5, 2.0),
            default => 0.0
        };
    }

    private function calculateTimeToEngage(string $status, $sendDate): ?int
    {
        if ($status === 'sent') {
            return null;
        }

        return fake()->numberBetween(1, 604800); // 1 second to 7 days in seconds
    }

    // State methods for different scenarios

    public function delivered(): static
    {
        return $this->state([
            'delivery_status' => 'delivered',
            'delivered_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    public function opened(): static
    {
        return $this->state(function (array $attributes) {
            $sendDate = $attributes['send_date'] ?? now();
            $deliveredAt = fake()->dateTimeBetween($sendDate, Carbon::parse($sendDate)->addMinutes(30));

            return [
                'delivery_status' => 'opened',
                'delivered_at' => $deliveredAt,
                'opened_at' => fake()->dateTimeBetween($deliveredAt, Carbon::parse($deliveredAt)->addHours(2)),
                'open_count' => fake()->numberBetween(1, 3),
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
                'browser' => fake()->randomElement(['chrome', 'firefox', 'safari', 'edge']),
                'location' => fake()->city() . ', ' . fake()->country(),
            ];
        });
    }

    public function clicked(): static
    {
        return $this->state(function (array $attributes) {
            $sendDate = $attributes['send_date'] ?? now();
            $deliveredAt = fake()->dateTimeBetween($sendDate, Carbon::parse($sendDate)->addMinutes(30));
            $openedAt = fake()->dateTimeBetween($deliveredAt, Carbon::parse($deliveredAt)->addHours(2));

            return [
                'delivery_status' => 'clicked',
                'delivered_at' => $deliveredAt,
                'opened_at' => $openedAt,
                'clicked_at' => fake()->dateTimeBetween($openedAt, Carbon::parse($openedAt)->addHours(1)),
                'open_count' => fake()->numberBetween(1, 3),
                'click_count' => fake()->numberBetween(1, 3),
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
                'browser' => fake()->randomElement(['chrome', 'firefox', 'safari', 'edge']),
                'location' => fake()->city() . ', ' . fake()->country(),
                'referrer_url' => fake()->url(),
            ];
        });
    }

    public function converted(): static
    {
        return $this->state(function (array $attributes) {
            $sendDate = $attributes['send_date'] ?? now();
            $deliveredAt = fake()->dateTimeBetween($sendDate, Carbon::parse($sendDate)->addMinutes(30));
            $openedAt = fake()->dateTimeBetween($deliveredAt, Carbon::parse($deliveredAt)->addHours(2));
            $clickedAt = fake()->dateTimeBetween($openedAt, Carbon::parse($openedAt)->addHours(1));

            return [
                'delivery_status' => 'converted',
                'delivered_at' => $deliveredAt,
                'opened_at' => $openedAt,
                'clicked_at' => $clickedAt,
                'converted_at' => fake()->dateTimeBetween($clickedAt, Carbon::parse($clickedAt)->addDays(7)),
                'open_count' => fake()->numberBetween(1, 3),
                'click_count' => fake()->numberBetween(1, 3),
                'conversion_count' => 1,
                'conversion_value' => fake()->randomFloat(2, 10, 1000),
                'conversion_type' => fake()->randomElement(['purchase', 'signup', 'download', 'contact']),
                'funnel_stage' => 5,
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
                'browser' => fake()->randomElement(['chrome', 'firefox', 'safari', 'edge']),
                'location' => fake()->city() . ', ' . fake()->country(),
                'referrer_url' => fake()->url(),
            ];
        });
    }

    public function bounced(): static
    {
        return $this->state([
            'delivery_status' => 'bounced',
            'bounced_at' => fake()->dateTimeBetween('-1 day', 'now'),
            'bounce_reason' => fake()->randomElement([
                'Mailbox full',
                'Invalid address',
                'Domain not found',
                'Recipient not found',
                'Message too large'
            ]),
        ]);
    }

    public function complained(): static
    {
        return $this->state([
            'delivery_status' => 'complaint',
            'complained_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'complaint_reason' => fake()->randomElement([
                'Spam',
                'Unsolicited',
                'Offensive content'
            ]),
        ]);
    }

    public function unsubscribed(): static
    {
        return $this->state([
            'delivery_status' => 'unsubscribed',
            'unsubscribed_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function forDesktop(): static
    {
        return $this->state([
            'device_type' => 'desktop',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'browser' => 'chrome',
        ]);
    }

    public function forMobile(): static
    {
        return $this->state([
            'device_type' => 'mobile',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Mobile/15E148 Safari/604.1',
            'browser' => 'safari',
        ]);
    }

    public function forTablet(): static
    {
        return $this->state([
            'device_type' => 'tablet',
            'user_agent' => 'Mozilla/5.0 (iPad; CPU OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Mobile/15E148 Safari/604.1',
            'browser' => 'safari',
        ]);
    }

    public function withAbTestVariant(string $variant): static
    {
        return $this->state([
            'ab_test_variant' => $variant,
        ]);
    }

    public function withTags(array $tags): static
    {
        return $this->state([
            'tags' => $tags,
        ]);
    }

    public function withCustomData(array $data): static
    {
        return $this->state(function (array $attributes) use ($data) {
            $existingData = $attributes['custom_data'] ?? [];
            return [
                'custom_data' => array_merge($existingData, $data),
            ];
        });
    }

    public function forTenant($tenantId): static
    {
        return $this->state([
            'tenant_id' => $tenantId,
        ]);
    }

    public function withCampaign($campaignId): static
    {
        return $this->state([
            'email_campaign_id' => $campaignId,
        ]);
    }

    public function withTemplate($templateId): static
    {
        return $this->state([
            'email_template_id' => $templateId,
        ]);
    }

    public function highEngagement(): static
    {
        return $this->state(function (array $attributes) {
            $sendDate = $attributes['send_date'] ?? now();
            $deliveredAt = fake()->dateTimeBetween($sendDate, Carbon::parse($sendDate)->addMinutes(5));
            $openedAt = fake()->dateTimeBetween($deliveredAt, Carbon::parse($deliveredAt)->addMinutes(10));
            $clickedAt = fake()->dateTimeBetween($openedAt, Carbon::parse($openedAt)->addMinutes(5));

            return [
                'delivery_status' => 'clicked',
                'delivered_at' => $deliveredAt,
                'opened_at' => $openedAt,
                'clicked_at' => $clickedAt,
                'open_count' => fake()->numberBetween(2, 5),
                'click_count' => fake()->numberBetween(2, 4),
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
                'browser' => fake()->randomElement(['chrome', 'firefox', 'safari', 'edge']),
                'location' => fake()->city() . ', ' . fake()->country(),
                'referrer_url' => fake()->url(),
            ];
        });
    }

    public function lowEngagement(): static
    {
        return $this->state(function (array $attributes) {
            $sendDate = $attributes['send_date'] ?? now();

            return [
                'delivery_status' => 'sent',
                'delivered_at' => null,
                'opened_at' => null,
                'clicked_at' => null,
                'converted_at' => null,
                'open_count' => 0,
                'click_count' => 0,
                'conversion_count' => 0,
            ];
        });
    }
}