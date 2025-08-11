<?php

namespace Tests\Feature;

use App\Services\CareerCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CareerCalculatorControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_endpoint_returns_successful_response()
    {
        $requestData = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase', 'promotion'],
            'location' => 'San Francisco, CA',
            'educationLevel' => 'bachelor',
        ];

        $response = $this->postJson('/api/homepage/calculator/calculate', $requestData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'projectedSalaryIncrease',
                    'networkingValue',
                    'careerAdvancementTimeline',
                    'personalizedRecommendations',
                    'successProbability',
                    'roiEstimate',
                    'baseSalary',
                    'calculationMetadata',
                ],
                'message',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertIsInt($response->json('data.projectedSalaryIncrease'));
        $this->assertIsArray($response->json('data.personalizedRecommendations'));
    }

    public function test_calculate_endpoint_validates_required_fields()
    {
        $response = $this->postJson('/api/homepage/calculator/calculate', [
            'industry' => 'technology',
            'experienceYears' => 5,
            // Missing currentRole and careerGoals
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'currentRole',
                    'careerGoals',
                ],
            ]);

        $this->assertFalse($response->json('success'));
    }

    public function test_calculate_endpoint_validates_experience_years_range()
    {
        $requestData = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => -1, // Invalid
            'careerGoals' => ['salary_increase'],
        ];

        $response = $this->postJson('/api/homepage/calculator/calculate', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['experienceYears']);
    }

    public function test_calculate_endpoint_validates_career_goals_array()
    {
        $requestData = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => [], // Empty array
        ];

        $response = $this->postJson('/api/homepage/calculator/calculate', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['careerGoals']);
    }

    public function test_calculate_endpoint_validates_networking_level_range()
    {
        $requestData = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase'],
            'networkingLevel' => 6, // Invalid (max is 5)
        ];

        $response = $this->postJson('/api/homepage/calculator/calculate', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['networkingLevel']);
    }

    public function test_calculate_endpoint_handles_service_exceptions()
    {
        // Mock the service to throw an exception
        $this->mock(CareerCalculatorService::class, function ($mock) {
            $mock->shouldReceive('calculateCareerValue')
                ->andThrow(new \InvalidArgumentException('Test error'));
        });

        $requestData = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase'],
        ];

        $response = $this->postJson('/api/homepage/calculator/calculate', $requestData);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Test error',
            ]);
    }

    public function test_email_report_endpoint_sends_email_successfully()
    {
        Mail::fake();

        $requestData = [
            'email' => 'test@example.com',
            'formData' => [
                'currentRole' => 'software_engineer',
                'industry' => 'technology',
                'experienceYears' => 5,
                'careerGoals' => ['salary_increase'],
            ],
            'result' => [
                'projectedSalaryIncrease' => 25000,
                'networkingValue' => 'High networking potential',
                'careerAdvancementTimeline' => '12-18 months',
                'personalizedRecommendations' => [],
                'successProbability' => 85,
                'roiEstimate' => 5.2,
            ],
        ];

        $response = $this->postJson('/api/homepage/calculator/email-report', $requestData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Email report sent successfully',
            ]);
    }

    public function test_email_report_endpoint_validates_email_format()
    {
        $requestData = [
            'email' => 'invalid-email',
            'formData' => [],
            'result' => [],
        ];

        $response = $this->postJson('/api/homepage/calculator/email-report', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_email_report_endpoint_validates_required_fields()
    {
        $response = $this->postJson('/api/homepage/calculator/email-report', [
            'email' => 'test@example.com',
            // Missing formData and result
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['formData', 'result']);
    }

    public function test_email_report_endpoint_handles_service_failure()
    {
        // Mock the service to return false (failure)
        $this->mock(CareerCalculatorService::class, function ($mock) {
            $mock->shouldReceive('sendEmailReport')->andReturn(false);
        });

        $requestData = [
            'email' => 'test@example.com',
            'formData' => ['currentRole' => 'software_engineer'],
            'result' => ['projectedSalaryIncrease' => 25000],
        ];

        $response = $this->postJson('/api/homepage/calculator/email-report', $requestData);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to send email report',
            ]);
    }

    public function test_benchmarks_endpoint_returns_industry_data()
    {
        $response = $this->getJson('/api/homepage/calculator/benchmarks?industry=technology&location=San Francisco, CA');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'averageSalary',
                    'salaryGrowthRate',
                    'networkingValue',
                    'jobPlacementRate',
                    'topSkills',
                    'careerPaths',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertIsInt($response->json('data.averageSalary'));
        $this->assertIsFloat($response->json('data.salaryGrowthRate'));
        $this->assertIsArray($response->json('data.topSkills'));
        $this->assertIsArray($response->json('data.careerPaths'));
    }

    public function test_benchmarks_endpoint_applies_location_multiplier()
    {
        $sfResponse = $this->getJson('/api/homepage/calculator/benchmarks?industry=technology&location=San Francisco, CA');
        $austinResponse = $this->getJson('/api/homepage/calculator/benchmarks?industry=technology&location=Austin, TX');

        $sfSalary = $sfResponse->json('data.averageSalary');
        $austinSalary = $austinResponse->json('data.averageSalary');

        // San Francisco should have higher average salary
        $this->assertGreaterThan($austinSalary, $sfSalary);
    }

    public function test_benchmarks_endpoint_returns_industry_specific_skills()
    {
        $techResponse = $this->getJson('/api/homepage/calculator/benchmarks?industry=technology');
        $financeResponse = $this->getJson('/api/homepage/calculator/benchmarks?industry=finance');

        $techSkills = $techResponse->json('data.topSkills');
        $financeSkills = $financeResponse->json('data.topSkills');

        $this->assertContains('Python', $techSkills);
        $this->assertContains('JavaScript', $techSkills);
        $this->assertContains('Financial Modeling', $financeSkills);
        $this->assertContains('Risk Management', $financeSkills);
    }

    public function test_benchmarks_endpoint_returns_industry_specific_career_paths()
    {
        $techResponse = $this->getJson('/api/homepage/calculator/benchmarks?industry=technology');
        $financeResponse = $this->getJson('/api/homepage/calculator/benchmarks?industry=finance');

        $techPaths = $techResponse->json('data.careerPaths');
        $financePaths = $financeResponse->json('data.careerPaths');

        $this->assertIsArray($techPaths);
        $this->assertIsArray($financePaths);
        $this->assertNotEmpty($techPaths);
        $this->assertNotEmpty($financePaths);

        // Check that paths contain industry-specific terms
        $techPathsString = implode(' ', $techPaths);
        $financePathsString = implode(' ', $financePaths);

        $this->assertStringContainsString('Engineer', $techPathsString);
        $this->assertStringContainsString('Analyst', $financePathsString);
    }

    public function test_benchmarks_endpoint_handles_unknown_industry()
    {
        $response = $this->getJson('/api/homepage/calculator/benchmarks?industry=unknown_industry');

        $response->assertStatus(200);

        // Should return default values
        $data = $response->json('data');
        $this->assertIsInt($data['averageSalary']);
        $this->assertIsFloat($data['salaryGrowthRate']);
        $this->assertIsArray($data['topSkills']);
        $this->assertIsArray($data['careerPaths']);
    }

    public function test_calculate_endpoint_with_all_optional_fields()
    {
        $requestData = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase', 'promotion'],
            'location' => 'San Francisco, CA',
            'educationLevel' => 'master',
            'currentSalary' => 120000,
            'targetRole' => 'Senior Software Engineer',
            'preferredCompanySize' => 'startup',
            'workStyle' => 'remote',
            'skillsToLearn' => 'Python, Machine Learning, Leadership',
            'goalTimeline' => '6_months',
            'primaryChallenge' => 'finding_opportunities',
            'networkingLevel' => 3,
            'timeInvestment' => '6_hours',
            'additionalInfo' => 'Looking to transition into AI/ML field',
        ];

        $response = $this->postJson('/api/homepage/calculator/calculate', $requestData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertIsInt($data['projectedSalaryIncrease']);
        $this->assertIsString($data['networkingValue']);
        $this->assertIsArray($data['personalizedRecommendations']);
        $this->assertGreaterThan(0, count($data['personalizedRecommendations']));
    }

    public function test_calculate_endpoint_validates_string_length_limits()
    {
        $requestData = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase'],
            'additionalInfo' => str_repeat('a', 2001), // Exceeds 2000 character limit
        ];

        $response = $this->postJson('/api/homepage/calculator/calculate', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['additionalInfo']);
    }

    public function test_calculate_endpoint_accepts_valid_current_salary()
    {
        $requestData = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase'],
            'currentSalary' => 85000,
        ];

        $response = $this->postJson('/api/homepage/calculator/calculate', $requestData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_calculate_endpoint_rejects_negative_salary()
    {
        $requestData = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase'],
            'currentSalary' => -1000,
        ];

        $response = $this->postJson('/api/homepage/calculator/calculate', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['currentSalary']);
    }
}
