<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $courseName = $this->faker->randomElement([
            'Software Development', 'Web Development', 'Mobile App Development',
            'Data Science', 'Cybersecurity', 'Network Administration',
            'Digital Marketing', 'Graphic Design', 'Project Management',
            'Business Administration', 'Accounting', 'Human Resources',
            'Electrical Engineering', 'Mechanical Engineering', 'Civil Engineering',
            'Nursing', 'Medical Assistant', 'Pharmacy Technician',
            'Culinary Arts', 'Hospitality Management', 'Tourism Management',
        ]);

        return [
            'institution_id' => Tenant::factory(),
            'name' => $courseName,
            'code' => strtoupper($this->faker->lexify('???')).$this->faker->numerify('###'),
            'description' => $this->faker->paragraph(3),
            'level' => $this->faker->randomElement(['certificate', 'diploma', 'advanced_diploma', 'degree', 'other']),
            'duration_months' => $this->faker->randomElement([6, 12, 18, 24, 36]),
            'study_mode' => $this->faker->randomElement(['full_time', 'part_time', 'online', 'hybrid']),
            'required_skills' => $this->faker->randomElements([
                'Basic Computer Skills', 'Mathematics', 'English Proficiency',
                'Problem Solving', 'Communication Skills', 'Teamwork',
            ], $this->faker->numberBetween(2, 4)),
            'skills_gained' => $this->getSkillsForCourse($courseName),
            'career_paths' => $this->getCareerPathsForCourse($courseName),
            'is_active' => $this->faker->boolean(90),
            'is_featured' => $this->faker->boolean(20),
            'total_enrolled' => $this->faker->numberBetween(20, 200),
            'total_graduated' => $this->faker->numberBetween(15, 180),
            'completion_rate' => $this->faker->randomFloat(2, 70, 95),
            'employment_rate' => $this->faker->randomFloat(2, 60, 90),
            'average_salary' => $this->faker->numberBetween(35000, 80000),
            'prerequisites' => $this->faker->optional(0.6)->randomElements([
                'High School Diploma', 'Basic Mathematics', 'English Proficiency',
                'Computer Literacy', 'Previous Experience',
            ], $this->faker->numberBetween(1, 3)),
            'learning_outcomes' => $this->faker->sentences(5),
            'department' => $this->getDepartmentForCourse($courseName),
        ];
    }

    private function getSkillsForCourse(string $courseName): array
    {
        $skillsMap = [
            'Software Development' => ['PHP', 'JavaScript', 'Python', 'Java', 'SQL', 'Git', 'Testing'],
            'Web Development' => ['HTML', 'CSS', 'JavaScript', 'React', 'Vue.js', 'Node.js', 'PHP'],
            'Mobile App Development' => ['Java', 'Kotlin', 'Swift', 'React Native', 'Flutter', 'UI/UX'],
            'Data Science' => ['Python', 'R', 'SQL', 'Machine Learning', 'Statistics', 'Data Visualization'],
            'Cybersecurity' => ['Network Security', 'Ethical Hacking', 'Risk Assessment', 'Compliance'],
            'Digital Marketing' => ['SEO', 'Social Media Marketing', 'Google Analytics', 'Content Marketing'],
            'Graphic Design' => ['Adobe Photoshop', 'Adobe Illustrator', 'InDesign', 'UI/UX Design'],
        ];

        return $skillsMap[$courseName] ?? ['Technical Skills', 'Problem Solving', 'Communication'];
    }

    private function getCareerPathsForCourse(string $courseName): array
    {
        $careerMap = [
            'Software Development' => ['Software Developer', 'Full Stack Developer', 'Backend Developer'],
            'Web Development' => ['Web Developer', 'Frontend Developer', 'UI/UX Developer'],
            'Mobile App Development' => ['Mobile App Developer', 'iOS Developer', 'Android Developer'],
            'Data Science' => ['Data Analyst', 'Data Scientist', 'Business Intelligence Analyst'],
            'Cybersecurity' => ['Security Analyst', 'Cybersecurity Specialist', 'IT Security Manager'],
            'Digital Marketing' => ['Digital Marketing Specialist', 'SEO Specialist', 'Social Media Manager'],
        ];

        return $careerMap[$courseName] ?? ['Specialist', 'Coordinator', 'Manager'];
    }

    private function getDepartmentForCourse(string $courseName): string
    {
        $departmentMap = [
            'Software Development' => 'Information Technology',
            'Web Development' => 'Information Technology',
            'Mobile App Development' => 'Information Technology',
            'Data Science' => 'Information Technology',
            'Cybersecurity' => 'Information Technology',
            'Digital Marketing' => 'Business',
            'Graphic Design' => 'Creative Arts',
            'Business Administration' => 'Business',
            'Accounting' => 'Business',
            'Engineering' => 'Engineering',
            'Nursing' => 'Health Sciences',
            'Culinary Arts' => 'Hospitality',
        ];

        foreach ($departmentMap as $keyword => $department) {
            if (str_contains($courseName, $keyword)) {
                return $department;
            }
        }

        return 'General Studies';
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'employment_rate' => $this->faker->randomFloat(2, 80, 95),
        ]);
    }

    public function highEmployment(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_rate' => $this->faker->randomFloat(2, 85, 95),
            'average_salary' => $this->faker->numberBetween(60000, 100000),
        ]);
    }
}
