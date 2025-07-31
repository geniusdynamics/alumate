<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use App\Models\EventHighlight;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventHighlightFactory extends Factory
{
    protected $model = EventHighlight::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['photo', 'video', 'quote', 'moment', 'achievement']);
        
        return [
            'event_id' => Event::factory(),
            'created_by' => User::factory(),
            'type' => $type,
            'title' => $this->getTypeSpecificTitle($type),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'media_urls' => $this->getTypeSpecificMedia($type),
            'metadata' => $this->getTypeSpecificMetadata($type),
            'likes_count' => $this->faker->numberBetween(0, 50),
            'shares_count' => $this->faker->numberBetween(0, 20),
            'is_featured' => $this->faker->boolean(15),
            'is_approved' => $this->faker->boolean(95),
            'featured_at' => function (array $attributes) {
                return $attributes['is_featured'] ? $this->faker->dateTimeBetween('-1 week', 'now') : null;
            },
        ];
    }

    public function photo(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'photo',
            'title' => $this->faker->randomElement([
                'Great networking session',
                'Amazing keynote speaker',
                'Group photo with classmates',
                'Beautiful venue setup',
                'Award ceremony moment'
            ]),
            'media_urls' => [
                'https://picsum.photos/800/600?random=' . $this->faker->numberBetween(1, 1000),
                'https://picsum.photos/800/600?random=' . $this->faker->numberBetween(1001, 2000),
            ],
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'video',
            'title' => $this->faker->randomElement([
                'Keynote speech highlights',
                'Alumni success stories',
                'Event recap video',
                'Behind the scenes',
                'Q&A session'
            ]),
            'media_urls' => [
                'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4'
            ],
        ]);
    }

    public function quote(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'quote',
            'title' => 'Inspiring Quote',
            'media_urls' => null,
            'metadata' => [
                'quote_content' => $this->faker->randomElement([
                    'Success is not final, failure is not fatal: it is the courage to continue that counts.',
                    'The future belongs to those who believe in the beauty of their dreams.',
                    'Innovation distinguishes between a leader and a follower.',
                    'The only way to do great work is to love what you do.',
                    'Your network is your net worth.'
                ]),
                'quote_author' => $this->faker->name(),
            ],
        ]);
    }

    public function moment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'moment',
            'title' => $this->faker->randomElement([
                'Unexpected reunion',
                'Standing ovation moment',
                'Breakthrough announcement',
                'Emotional speech',
                'Surprise guest appearance'
            ]),
        ]);
    }

    public function achievement(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'achievement',
            'title' => $this->faker->randomElement([
                'Alumni of the Year Award',
                'Outstanding Contribution Recognition',
                'Innovation Excellence Award',
                'Community Service Honor',
                'Lifetime Achievement Award'
            ]),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'featured_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'likes_count' => $this->faker->numberBetween(20, 100),
            'shares_count' => $this->faker->numberBetween(5, 30),
        ]);
    }

    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'likes_count' => $this->faker->numberBetween(50, 200),
            'shares_count' => $this->faker->numberBetween(10, 50),
        ]);
    }

    private function getTypeSpecificTitle(string $type): string
    {
        return match($type) {
            'photo' => $this->faker->randomElement([
                'Great networking session',
                'Amazing keynote speaker',
                'Group photo with classmates',
                'Beautiful venue setup'
            ]),
            'video' => $this->faker->randomElement([
                'Keynote speech highlights',
                'Alumni success stories',
                'Event recap video'
            ]),
            'quote' => 'Inspiring Quote',
            'moment' => $this->faker->randomElement([
                'Unexpected reunion',
                'Standing ovation moment',
                'Breakthrough announcement'
            ]),
            'achievement' => $this->faker->randomElement([
                'Alumni of the Year Award',
                'Outstanding Contribution Recognition',
                'Innovation Excellence Award'
            ]),
            default => $this->faker->sentence(3)
        };
    }

    private function getTypeSpecificMedia(string $type): ?array
    {
        return match($type) {
            'photo' => [
                'https://picsum.photos/800/600?random=' . $this->faker->numberBetween(1, 1000)
            ],
            'video' => [
                'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4'
            ],
            default => null
        };
    }

    private function getTypeSpecificMetadata(string $type): ?array
    {
        return match($type) {
            'quote' => [
                'quote_content' => $this->faker->randomElement([
                    'Success is not final, failure is not fatal: it is the courage to continue that counts.',
                    'The future belongs to those who believe in the beauty of their dreams.',
                    'Innovation distinguishes between a leader and a follower.'
                ]),
                'quote_author' => $this->faker->name(),
            ],
            'achievement' => [
                'award_category' => $this->faker->randomElement(['Academic', 'Professional', 'Community Service', 'Innovation']),
                'recipient' => $this->faker->name(),
            ],
            default => null
        };
    }
}