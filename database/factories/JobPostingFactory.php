<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobPosting>
 */
class JobPostingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jobTitles = [
            'Software Engineer',
            'Senior Developer',
            'Product Manager',
            'Data Scientist',
            'UX Designer',
            'DevOps Engineer',
            'Marketing Manager',
            'Sales Representative',
            'Business Analyst',
            'Project Manager',
            'Frontend Developer',
            'Backend Developer',
            'Full Stack Developer',
            'Mobile Developer',
            'QA Engineer',
            'Technical Writer',
            'Customer Success Manager',
            'HR Specialist',
            'Financial Analyst',
            'Operations Manager'
        ];

        $skills = [
            'PHP', 'Laravel', 'JavaScript', 'Vue.js', 'React', 'Node.js',
            'Python', 'Django', 'Java', 'Spring Boot', 'C#', '.NET',
            'SQL', 'MySQL', 'PostgreSQL', 'MongoDB', 'Redis',
            'AWS', 'Docker', 'Kubernetes', 'Git', 'CI/CD',
            'HTML', 'CSS', 'TypeScript', 'Angular', 'Flutter',
            'Machine Learning', 'Data Analysis', 'Excel', 'Tableau',
            'Project Management', 'Agile', 'Scrum', 'Communication'
        ];

        $requirements = [
            'Bachelor\'s degree in Computer Science or related field',
            '3+ years of professional experience',
            'Strong problem-solving skills',
            'Excellent communication skills',
            'Experience with agile development methodologies',
            'Ability to work in a fast-paced environment',
            'Strong attention to detail',
            'Team player with leadership qualities',
            'Experience with version control systems',
            'Knowledge of software development best practices'
        ];

        return [
            'company_id' => Company::factory(),
            'title' => $this->faker->randomElement($jobTitles),
            'description' => $this->generateJobDescription(),
            'requirements' => $this->faker->randomElements($requirements, $this->faker->numberBetween(3, 6)),
            'location' => $this->faker->city() . ', ' . $this->faker->stateAbbr(),
            'salary_range' => $this->generateSalaryRange(),
            'posted_by' => User::factory(),
            'expires_at' => $this->faker->dateTimeBetween('now', '+3 months'),
            'is_active' => $this->faker->boolean(85), // 85% chance of being active
            'remote_allowed' => $this->faker->boolean(60), // 60% chance of remote work
            'employment_type' => $this->faker->randomElement(['full_time', 'part_time', 'contract', 'internship']),
            'experience_level' => $this->faker->randomElement(['entry', 'mid', 'senior', 'executive']),
            'skills_required' => $this->faker->randomElements($skills, $this->faker->numberBetween(3, 8)),
        ];
    }

    /**
     * Generate a realistic job description.
     */
    private function generateJobDescription(): string
    {
        $descriptions = [
            "We are seeking a talented professional to join our growing team. You will be responsible for developing and maintaining high-quality software solutions that meet our clients' needs. This role offers excellent opportunities for growth and learning in a collaborative environment.",
            
            "Join our innovative team and help us build the next generation of products. We're looking for someone who is passionate about technology and eager to make a meaningful impact. You'll work closely with cross-functional teams to deliver exceptional results.",
            
            "This is an exciting opportunity to work with cutting-edge technologies and contribute to projects that reach millions of users. We value creativity, collaboration, and continuous learning. The ideal candidate will bring fresh ideas and a strong technical background.",
            
            "We're expanding our team and looking for a dedicated professional who thrives in a dynamic environment. You'll have the chance to work on challenging projects, mentor junior team members, and help shape our technical direction.",
            
            "Be part of a company that's transforming the industry through innovation and excellence. This role offers the perfect blend of technical challenges and creative problem-solving. We provide comprehensive benefits and a supportive work culture."
        ];

        return $this->faker->randomElement($descriptions) . "\n\n" . 
               "Key Responsibilities:\n" .
               "• " . implode("\n• ", $this->faker->randomElements([
                   "Develop and maintain software applications",
                   "Collaborate with cross-functional teams",
                   "Participate in code reviews and technical discussions",
                   "Write clean, maintainable, and efficient code",
                   "Troubleshoot and debug applications",
                   "Stay up-to-date with industry trends and technologies",
                   "Contribute to architectural decisions",
                   "Mentor junior developers",
                   "Participate in agile development processes",
                   "Ensure code quality and best practices"
               ], $this->faker->numberBetween(4, 7)));
    }

    /**
     * Generate a realistic salary range.
     */
    private function generateSalaryRange(): string
    {
        $baseSalary = $this->faker->numberBetween(50000, 200000);
        $rangeDiff = $this->faker->numberBetween(10000, 30000);
        
        $minSalary = $baseSalary;
        $maxSalary = $baseSalary + $rangeDiff;
        
        return '$' . number_format($minSalary) . ' - $' . number_format($maxSalary);
    }

    /**
     * Indicate that the job posting is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'expires_at' => $this->faker->dateTimeBetween('+1 week', '+3 months'),
        ]);
    }

    /**
     * Indicate that the job posting is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the job posting allows remote work.
     */
    public function remote(): static
    {
        return $this->state(fn (array $attributes) => [
            'remote_allowed' => true,
            'location' => 'Remote',
        ]);
    }

    /**
     * Indicate that the job posting is for a senior position.
     */
    public function senior(): static
    {
        return $this->state(fn (array $attributes) => [
            'experience_level' => 'senior',
            'title' => 'Senior ' . $this->faker->randomElement([
                'Software Engineer',
                'Developer',
                'Product Manager',
                'Data Scientist',
                'UX Designer'
            ]),
        ]);
    }

    /**
     * Indicate that the job posting is for an entry-level position.
     */
    public function entry(): static
    {
        return $this->state(fn (array $attributes) => [
            'experience_level' => 'entry',
            'title' => 'Junior ' . $this->faker->randomElement([
                'Software Engineer',
                'Developer',
                'Data Analyst',
                'UX Designer',
                'Marketing Associate'
            ]),
        ]);
    }
}