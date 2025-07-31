<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReunionPhoto>
 */
class ReunionPhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'uploaded_by' => User::factory(),
            'title' => $this->faker->optional()->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'file_path' => 'reunion-photos/' . $this->faker->uuid() . '.jpg',
            'file_name' => $this->faker->uuid() . '.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => $this->faker->numberBetween(100000, 5000000),
            'metadata' => [
                'original_name' => $this->faker->word() . '.jpg',
                'dimensions' => [
                    'width' => $this->faker->numberBetween(800, 4000),
                    'height' => $this->faker->numberBetween(600, 3000),
                ],
                'exif' => [
                    'camera' => $this->faker->optional()->randomElement(['iPhone 12', 'Canon EOS R5', 'Nikon D850']),
                    'date_taken' => $this->faker->optional()->dateTimeThisYear()->format('Y:m:d H:i:s'),
                ],
            ],
            'tagged_users' => $this->faker->optional()->randomElements(
                range(1, 10),
                $this->faker->numberBetween(0, 5)
            ),
            'likes_count' => $this->faker->numberBetween(0, 50),
            'comments_count' => $this->faker->numberBetween(0, 20),
            'is_featured' => $this->faker->boolean(10), // 10% chance of being featured
            'is_approved' => $this->faker->boolean(95), // 95% chance of being approved
            'visibility' => $this->faker->randomElement(['public', 'alumni_only', 'class_only']),
            'taken_at' => $this->faker->optional()->dateTimeThisYear(),
        ];
    }

    /**
     * Indicate that the photo is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the photo is not approved.
     */
    public function unapproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
        ]);
    }

    /**
     * Set the photo visibility.
     */
    public function visibility(string $visibility): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => $visibility,
        ]);
    }

    /**
     * Set the photo for a specific event.
     */
    public function forEvent(Event $event): static
    {
        return $this->state(fn (array $attributes) => [
            'event_id' => $event->id,
        ]);
    }

    /**
     * Set the photo uploader.
     */
    public function uploadedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'uploaded_by' => $user->id,
        ]);
    }
}