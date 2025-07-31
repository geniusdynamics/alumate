<?php

namespace Database\Factories;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkillFactory extends Factory
{
    protected $model = Skill::class;

    public function definition(): array
    {
        $categories = ['Technical', 'Leadership', 'Communication', 'Design', 'Business', 'Marketing'];
        $skills = [
            'Technical' => ['JavaScript', 'Python', 'React', 'Vue.js', 'Node.js', 'PHP', 'Laravel', 'Docker', 'AWS', 'MySQL'],
            'Leadership' => ['Team Management', 'Strategic Planning', 'Project Management', 'Mentoring', 'Decision Making'],
            'Communication' => ['Public Speaking', 'Technical Writing', 'Presentation Skills', 'Cross-functional Collaboration'],
            'Design' => ['UI/UX Design', 'Graphic Design', 'Prototyping', 'User Research', 'Design Systems'],
            'Business' => ['Business Analysis', 'Market Research', 'Financial Planning', 'Product Strategy'],
            'Marketing' => ['Digital Marketing', 'Content Marketing', 'SEO', 'Social Media Marketing', 'Brand Management']
        ];

        $category = $this->faker->randomElement($categories);
        $skillName = $this->faker->randomElement($skills[$category]);

        return [
            'name' => $skillName,
            'category' => $category,
            'description' => $this->faker->sentence(10),
            'is_verified' => $this->faker->boolean(70), // 70% chance of being verified
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    public function technical(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'Technical',
            'name' => $this->faker->randomElement([
                'JavaScript', 'Python', 'React', 'Vue.js', 'Node.js', 
                'PHP', 'Laravel', 'Docker', 'AWS', 'MySQL'
            ]),
        ]);
    }

    public function leadership(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'Leadership',
            'name' => $this->faker->randomElement([
                'Team Management', 'Strategic Planning', 'Project Management', 
                'Mentoring', 'Decision Making'
            ]),
        ]);
    }
}