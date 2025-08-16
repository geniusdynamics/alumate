<?php

namespace Tests\Unit;

use App\Models\SalaryProgression;
use App\Models\CareerPath;
use App\Models\IndustryPlacement;
use App\Models\ProgramEffectiveness;
use Tests\TestCase;

class CareerOutcomeModelsTest extends TestCase
{
    public function test_salary_progression_annualized_salary_calculation()
    {
        $salaryProgression = new SalaryProgression([
            'salary' => 80000,
            'salary_type' => 'annual',
        ]);

        $this->assertEquals(80000, $salaryProgression->annualized_salary);

        $salaryProgression->salary_type = 'hourly';
        $salaryProgression->salary = 40;
        $this->assertEquals(83200, $salaryProgression->annualized_salary); // 40 * 40 * 52
    }

    public function test_salary_progression_formatted_salary()
    {
        $salaryProgression = new SalaryProgression([
            'salary' => 75000,
            'currency' => 'USD',
        ]);

        $this->assertEquals('75,000 USD', $salaryProgression->formatted_salary);
    }

    public function test_career_path_type_display()
    {
        $careerPath = new CareerPath([
            'path_type' => CareerPath::PATH_LINEAR,
        ]);

        $this->assertEquals('Linear Career Progression', $careerPath->path_type_display);

        $careerPath->path_type = CareerPath::PATH_ENTREPRENEURIAL;
        $this->assertEquals('Entrepreneurial Path', $careerPath->path_type_display);
    }

    public function test_career_path_job_stability_score()
    {
        $careerPath = new CareerPath([
            'total_job_changes' => 0,
        ]);

        $this->assertEquals(100, $careerPath->job_stability_score);

        $careerPath->total_job_changes = 4;
        // Mock the user relationship for testing
        $careerPath->setRelation('user', (object)['graduationYear' => 2020]);
        
        $this->assertLessThan(100, $careerPath->job_stability_score);
        $this->assertGreaterThanOrEqual(0, $careerPath->job_stability_score);
    }

    public function test_industry_placement_salary_growth()
    {
        $placement = new IndustryPlacement([
            'avg_starting_salary' => 60000,
            'avg_current_salary' => 75000,
        ]);

        $this->assertEquals(25.0, $placement->salary_growth);

        $placement->avg_starting_salary = null;
        $this->assertNull($placement->salary_growth);
    }

    public function test_program_effectiveness_employment_trend()
    {
        $effectiveness = new ProgramEffectiveness([
            'employment_rate_6_months' => 80,
            'employment_rate_1_year' => 85,
            'employment_rate_2_years' => 90,
        ]);

        $this->assertEquals('improving', $effectiveness->employment_trend);

        $effectiveness->employment_rate_2_years = 75;
        $this->assertEquals('declining', $effectiveness->employment_trend);

        $effectiveness->employment_rate_2_years = 82;
        $this->assertEquals('stable', $effectiveness->employment_trend);
    }

    public function test_program_effectiveness_salary_growth_rate()
    {
        $effectiveness = new ProgramEffectiveness([
            'avg_starting_salary' => 50000,
            'avg_salary_2_years' => 60000,
        ]);

        $this->assertEquals(20.0, $effectiveness->salary_growth_rate);

        $effectiveness->avg_starting_salary = null;
        $this->assertNull($effectiveness->salary_growth_rate);
    }

    public function test_program_effectiveness_performance_grade()
    {
        $effectiveness = new ProgramEffectiveness([
            'employment_rate_1_year' => 95,
            'avg_starting_salary' => 70000,
            'job_satisfaction_score' => 4.5,
            'alumni_engagement_score' => 4.0,
        ]);

        $grade = $effectiveness->performance_grade;
        $this->assertContains($grade, ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D']);
    }

    public function test_career_path_static_methods()
    {
        $pathTypes = CareerPath::getPathTypes();
        
        $this->assertIsArray($pathTypes);
        $this->assertArrayHasKey(CareerPath::PATH_LINEAR, $pathTypes);
        $this->assertArrayHasKey(CareerPath::PATH_PIVOT, $pathTypes);
        $this->assertArrayHasKey(CareerPath::PATH_ENTREPRENEURIAL, $pathTypes);
    }
}