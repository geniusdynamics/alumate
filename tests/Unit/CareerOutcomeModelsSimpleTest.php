<?php

namespace Tests\Unit;

use App\Models\SalaryProgression;
use App\Models\CareerPath;
use App\Models\IndustryPlacement;
use App\Models\ProgramEffectiveness;
use PHPUnit\Framework\TestCase;

class CareerOutcomeModelsSimpleTest extends TestCase
{
    public function test_salary_progression_annualized_salary_calculation()
    {
        $salaryProgression = new SalaryProgression();
        $salaryProgression->salary = 80000;
        $salaryProgression->salary_type = 'annual';

        $this->assertEquals(80000, $salaryProgression->annualized_salary);

        $salaryProgression->salary_type = 'hourly';
        $salaryProgression->salary = 40;
        $this->assertEquals(83200, $salaryProgression->annualized_salary); // 40 * 40 * 52
    }

    public function test_salary_progression_formatted_salary()
    {
        $salaryProgression = new SalaryProgression();
        $salaryProgression->salary = 75000;
        $salaryProgression->currency = 'USD';

        $this->assertEquals('75,000 USD', $salaryProgression->formatted_salary);
    }

    public function test_career_path_type_display()
    {
        $careerPath = new CareerPath();
        $careerPath->path_type = CareerPath::PATH_LINEAR;

        $this->assertEquals('Linear Career Progression', $careerPath->path_type_display);

        $careerPath->path_type = CareerPath::PATH_ENTREPRENEURIAL;
        $this->assertEquals('Entrepreneurial Path', $careerPath->path_type_display);
    }

    public function test_industry_placement_salary_growth()
    {
        $placement = new IndustryPlacement();
        $placement->avg_starting_salary = 60000;
        $placement->avg_current_salary = 75000;

        $this->assertEquals(25.0, $placement->salary_growth);

        $placement->avg_starting_salary = null;
        $this->assertNull($placement->salary_growth);
    }

    public function test_program_effectiveness_employment_trend()
    {
        $effectiveness = new ProgramEffectiveness();
        $effectiveness->employment_rate_6_months = 80;
        $effectiveness->employment_rate_1_year = 85;
        $effectiveness->employment_rate_2_years = 90;

        $this->assertEquals('improving', $effectiveness->employment_trend);

        $effectiveness->employment_rate_2_years = 74; // More than 5% decline to trigger 'declining'
        $this->assertEquals('declining', $effectiveness->employment_trend);

        $effectiveness->employment_rate_2_years = 82;
        $this->assertEquals('stable', $effectiveness->employment_trend);
    }

    public function test_program_effectiveness_salary_growth_rate()
    {
        $effectiveness = new ProgramEffectiveness();
        $effectiveness->avg_starting_salary = 50000;
        $effectiveness->avg_salary_2_years = 60000;

        $this->assertEquals(20.0, $effectiveness->salary_growth_rate);

        $effectiveness->avg_starting_salary = null;
        $this->assertNull($effectiveness->salary_growth_rate);
    }

    public function test_career_path_static_methods()
    {
        $pathTypes = CareerPath::getPathTypes();
        
        $this->assertIsArray($pathTypes);
        $this->assertArrayHasKey(CareerPath::PATH_LINEAR, $pathTypes);
        $this->assertArrayHasKey(CareerPath::PATH_PIVOT, $pathTypes);
        $this->assertArrayHasKey(CareerPath::PATH_ENTREPRENEURIAL, $pathTypes);
    }

    public function test_career_path_constants()
    {
        $this->assertEquals('linear', CareerPath::PATH_LINEAR);
        $this->assertEquals('pivot', CareerPath::PATH_PIVOT);
        $this->assertEquals('entrepreneurial', CareerPath::PATH_ENTREPRENEURIAL);
        $this->assertEquals('portfolio', CareerPath::PATH_PORTFOLIO);
        $this->assertEquals('academic', CareerPath::PATH_ACADEMIC);
    }

    public function test_industry_placement_formatted_retention_rate()
    {
        $placement = new IndustryPlacement();
        $placement->retention_rate = 85.5;

        $this->assertEquals('85.50%', $placement->formatted_retention_rate);

        $placement->retention_rate = null;
        $this->assertEquals('N/A', $placement->formatted_retention_rate);
    }
}