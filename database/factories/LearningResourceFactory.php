<?php

namespace Database\Factories;

use App\Models\LearningResource;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LearningResourceFactory extends Factory
{
    protected $model = LearningResource::class;

    public function definition(): array
    {
        $types = ['Course', 'Article', 'Video', 'Book', 'Workshop', 'Certification'];
        $type = $this->faker->randomElement($types);

        $titles = [
            'Course' => [
                'Complete JavaScript Bootcamp',
                'Advanced React Development',
                'Python for Data Science',
                'Full Stack Web Development',
                'Machine Learning Fundamentals',
            ],
            'Article' => [
                'Best Practices for Clean Code',
                'Understanding Design Patterns',
                'Modern JavaScript Features',
                'Building Scalable Applications',
                'Leadership in Tech Teams',
            ],
            'Video' => [
                'JavaScript Crash Course',
                'React Hooks Explained',
                'Docker for Beginners',
                'AWS Fundamentals',
                'Git and GitHub Tutorial',
            ],
            'Book' => [
                'Clean Code: A Handbook',
                'The Pragmatic Programmer',
                'Design Patterns Explained',
                'You Don\'t Know JS',
                'The Manager\'s Path',
            ],
            'Workshop' => [
                'Hands-on React Workshop',
                'Leadership Skills Workshop',
                'API Design Workshop',
                'Database Optimization Workshop',
                'Team Building Workshop',
            ],
            'Certification' => [
                'AWS Certified Developer',
                'Google Cloud Professional',
                'Certified Scrum Master',
                'Microsoft Azure Fundamentals',
                'Oracle Database Certification',
            ],
        ];

        $title = $this->faker->randomElement($titles[$type]);

        return [
            'title' => $title,
            'description' => $this->faker->paragraph(3),
            'type' => $type,
            'url' => $this->faker->url(),
            'skill_ids' => [Skill::factory()->create()->id], // Will be overridden in tests
            'created_by' => User::factory(),
            'rating' => $this->faker->randomFloat(2, 1, 5),
            'rating_count' => $this->faker->numberBetween(1, 100),
        ];
    }

    public function course(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Course',
            'title' => $this->faker->randomElement([
                'Complete JavaScript Bootcamp',
                'Advanced React Development',
                'Python for Data Science',
                'Full Stack Web Development',
                'Machine Learning Fundamentals',
            ]),
        ]);
    }

    public function article(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Article',
            'title' => $this->faker->randomElement([
                'Best Practices for Clean Code',
                'Understanding Design Patterns',
                'Modern JavaScript Features',
                'Building Scalable Applications',
                'Leadership in Tech Teams',
            ]),
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Video',
            'title' => $this->faker->randomElement([
                'JavaScript Crash Course',
                'React Hooks Explained',
                'Docker for Beginners',
                'AWS Fundamentals',
                'Git and GitHub Tutorial',
            ]),
        ]);
    }

    public function highRated(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->randomFloat(2, 4.0, 5.0),
            'rating_count' => $this->faker->numberBetween(20, 200),
        ]);
    }

    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->randomFloat(2, 3.5, 5.0),
            'rating_count' => $this->faker->numberBetween(50, 500),
        ]);
    }

    public function forSkills(array $skillIds): static
    {
        return $this->state(fn (array $attributes) => [
            'skill_ids' => $skillIds,
        ]);
    }
}
