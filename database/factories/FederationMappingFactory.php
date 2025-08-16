<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FederationMapping>
 */
class FederationMappingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $protocol = $this->faker->randomElement(['matrix', 'activitypub']);
        $localType = $this->faker->randomElement(['post', 'user', 'group', 'circle']);

        return [
            'local_type' => $localType,
            'local_id' => $this->faker->numberBetween(1, 1000),
            'protocol' => $protocol,
            'federation_id' => $this->generateFederationId($protocol, $localType),
            'federation_data' => $this->generateFederationData($protocol, $localType),
            'server_name' => $this->faker->domainName(),
            'federated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Create a Matrix protocol mapping
     */
    public function matrix(): static
    {
        return $this->state(function (array $attributes) {
            $localType = $attributes['local_type'] ?? 'post';

            return [
                'protocol' => 'matrix',
                'federation_id' => $this->generateMatrixId($localType),
                'federation_data' => $this->generateMatrixData($localType),
                'server_name' => $this->faker->domainName(),
            ];
        });
    }

    /**
     * Create an ActivityPub protocol mapping
     */
    public function activitypub(): static
    {
        return $this->state(function (array $attributes) {
            $localType = $attributes['local_type'] ?? 'post';

            return [
                'protocol' => 'activitypub',
                'federation_id' => $this->generateActivityPubId($localType),
                'federation_data' => $this->generateActivityPubData($localType),
                'server_name' => $this->faker->domainName(),
            ];
        });
    }

    /**
     * Create mapping for a specific local type
     */
    public function forLocalType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'local_type' => $type,
        ]);
    }

    /**
     * Create mapping for a specific local entity
     */
    public function forLocalEntity(string $type, int $id): static
    {
        return $this->state(fn (array $attributes) => [
            'local_type' => $type,
            'local_id' => $id,
        ]);
    }

    /**
     * Generate federation ID based on protocol and type
     */
    protected function generateFederationId(string $protocol, string $localType): string
    {
        return match ($protocol) {
            'matrix' => $this->generateMatrixId($localType),
            'activitypub' => $this->generateActivityPubId($localType),
            default => $this->faker->uuid(),
        };
    }

    /**
     * Generate Matrix-style ID
     */
    protected function generateMatrixId(string $localType): string
    {
        $domain = $this->faker->domainName();

        return match ($localType) {
            'user' => "@{$this->faker->userName()}:{$domain}",
            'group', 'circle' => "!{$this->faker->lexify('?????')}:{$domain}",
            'post' => "\${$this->faker->lexify('?????')}_{$this->faker->unixTime()}:{$domain}",
            default => "\${$this->faker->uuid()}:{$domain}",
        };
    }

    /**
     * Generate ActivityPub-style ID
     */
    protected function generateActivityPubId(string $localType): string
    {
        $baseUrl = $this->faker->url();

        return match ($localType) {
            'user' => "{$baseUrl}/users/{$this->faker->userName()}",
            'group' => "{$baseUrl}/groups/{$this->faker->numberBetween(1, 1000)}",
            'circle' => "{$baseUrl}/circles/{$this->faker->numberBetween(1, 1000)}",
            'post' => "{$baseUrl}/posts/{$this->faker->uuid()}",
            default => "{$baseUrl}/objects/{$this->faker->uuid()}",
        };
    }

    /**
     * Generate federation data based on protocol and type
     */
    protected function generateFederationData(string $protocol, string $localType): array
    {
        return match ($protocol) {
            'matrix' => $this->generateMatrixData($localType),
            'activitypub' => $this->generateActivityPubData($localType),
            default => [],
        };
    }

    /**
     * Generate Matrix-specific data
     */
    protected function generateMatrixData(string $localType): array
    {
        $baseData = [
            'type' => match ($localType) {
                'user' => 'm.room.member',
                'post' => 'm.room.message',
                'group', 'circle' => 'm.room.create',
                default => 'm.room.message',
            },
            'sender' => "@{$this->faker->userName()}:{$this->faker->domainName()}",
            'origin_server_ts' => $this->faker->unixTime() * 1000,
        ];

        if ($localType === 'post') {
            $baseData['content'] = [
                'msgtype' => 'm.text',
                'body' => $this->faker->sentence(),
                'alumni.post_id' => $this->faker->numberBetween(1, 1000),
            ];
        }

        return $baseData;
    }

    /**
     * Generate ActivityPub-specific data
     */
    protected function generateActivityPubData(string $localType): array
    {
        $baseData = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => match ($localType) {
                'user' => 'Person',
                'group' => 'Group',
                'circle' => 'Collection',
                'post' => 'Note',
                default => 'Object',
            },
            'id' => $this->generateActivityPubId($localType),
            'published' => $this->faker->iso8601(),
        ];

        if ($localType === 'post') {
            $baseData['content'] = $this->faker->sentence();
            $baseData['attributedTo'] = $this->faker->url().'/users/'.$this->faker->userName();
        }

        return $baseData;
    }
}
