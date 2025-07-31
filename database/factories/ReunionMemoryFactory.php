<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReunionMemory>
 */
class ReunionMemoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['story', 'achievement', 'memory', 'tribute', 'update']);
        
        return [
            'event_id' => Event::factory(),
            'submitted_by' => User::factory(),
            'title' => $this->getTitle($type),
            'content' => $this->getContent($type),
            'type' => $type,
            'media_urls' => $this->faker->optional()->randomElements([
                'https://example.com/photo1.jpg',
                'https://example.com/photo2.jpg',
                'https://example.com/photo3.jpg',
            ], $this->faker->numberBetween(0, 3)),
            'tagged_users' => $this->faker->optional()->randomElements(
                range(1, 10),
                $this->faker->numberBetween(0, 5)
            ),
            'is_featured' => $this->faker->boolean(15), // 15% chance of being featured
            'is_approved' => $this->faker->boolean(95), // 95% chance of being approved
            'visibility' => $this->faker->randomElement(['public', 'alumni_only', 'class_only']),
            'likes_count' => $this->faker->numberBetween(0, 100),
            'comments_count' => $this->faker->numberBetween(0, 30),
            'memory_date' => $this->faker->optional()->dateTimeBetween('-10 years', 'now'),
        ];
    }

    /**
     * Get a title based on memory type.
     */
    private function getTitle(string $type): string
    {
        return match ($type) {
            'story' => $this->faker->randomElement([
                'The Time We Snuck Into the Library',
                'Our Epic Road Trip Adventure',
                'Late Night Study Sessions',
                'The Great Cafeteria Food Fight',
                'When We Got Lost on Campus',
            ]),
            'achievement' => $this->faker->randomElement([
                'Graduated Summa Cum Laude',
                'Started My Own Company',
                'Published My First Novel',
                'Completed My PhD',
                'Won the Innovation Award',
            ]),
            'memory' => $this->faker->randomElement([
                'Freshman Year Roommate Stories',
                'Spring Break Memories',
                'Graduation Day Emotions',
                'Professor Johnson\'s Funny Quotes',
                'Dorm Life Adventures',
            ]),
            'tribute' => $this->faker->randomElement([
                'Remembering Our Dear Friend Sarah',
                'In Memory of Professor Williams',
                'Honoring Our Classmate John',
                'Tribute to Our Mentor',
                'Remembering Good Times Together',
            ]),
            'update' => $this->faker->randomElement([
                'Life Update: New Job and Family',
                'Moving to a New City',
                'Career Change Adventure',
                'Family Milestones',
                'Recent Accomplishments',
            ]),
            default => $this->faker->sentence(4),
        };
    }

    /**
     * Get content based on memory type.
     */
    private function getContent(string $type): string
    {
        return match ($type) {
            'story' => $this->faker->paragraphs(3, true) . ' Those were the days!',
            'achievement' => 'I\'m excited to share this milestone with all of you. ' . $this->faker->paragraphs(2, true),
            'memory' => 'Looking back on our time together, ' . $this->faker->paragraphs(2, true) . ' I miss those days!',
            'tribute' => 'I wanted to take a moment to remember ' . $this->faker->paragraphs(2, true) . ' They will be deeply missed.',
            'update' => 'Hey everyone! Here\'s what\'s been happening in my life: ' . $this->faker->paragraphs(2, true),
            default => $this->faker->paragraphs(2, true),
        };
    }

    /**
     * Indicate that the memory is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the memory is not approved.
     */
    public function unapproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
        ]);
    }

    /**
     * Set the memory type.
     */
    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
            'title' => $this->getTitle($type),
            'content' => $this->getContent($type),
        ]);
    }

    /**
     * Set the memory visibility.
     */
    public function visibility(string $visibility): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => $visibility,
        ]);
    }

    /**
     * Set the memory for a specific event.
     */
    public function forEvent(Event $event): static
    {
        return $this->state(fn (array $attributes) => [
            'event_id' => $event->id,
        ]);
    }

    /**
     * Set the memory submitter.
     */
    public function submittedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'submitted_by' => $user->id,
        ]);
    }
}