<?php

namespace Tests\Feature;

use App\Services\CareerCalculatorService;
use App\Mail\CareerCalculatorReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CareerCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private CareerCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CareerCalculatorService();
    }

    public function test_calculates_career_value_with_basic_input()
    {
        $input = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase', 'promotion'],
            'location' => 'San Francisco, CA',
            'educationLevel' => 'bachelor'
        ];

        $result = $this->service->calculateCareerValue($input);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('projectedSalaryIncrease', $result);
        $this->assertArrayHasKey('networkingValue', $result);
        $this->assertArrayHasKey('careerAdvancementTimeline', $result);
        $this->assertArrayHasKey('personalizedRecommendations', $result);
        $this->assertArrayHasKey('successProbability', $result);
        $this->assertArrayHasKey('roiEstimate', $result);

        $this->assertIsInt($result['projectedSalaryIncrease']);
        $this->assertIsString($result['networkingValue']);
        $this->assertIsString($result['careerAdvancementTimeline']);
        $this->assertIsArray($result['personalizedRecommendations']);
        $this->assertIsInt($result['successProbability']);
        $this->assertIsFloat($result['roiEstimate']);

        $this->assertGreaterThan(0, $result['projectedSalaryIncrease']);
        $this->assertGreaterThanOrEqual(40, $result['successProbability']);
        $this->assertLessThanOrEqual(95, $result['successProbability']);
        $this->assertGreaterThan(0, $result['roiEstimate']);
    }

    public function test_applies_industry_multipliers_correctly()
    {
        $techInput = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase'],
            'location' => 'Austin, TX',
            'educationLevel' => 'bachelor'
        ];

        $educationInput = [
            'currentRole' => 'software_engineer',
            'industry' => 'education',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase'],
            'location' => 'Austin, TX',
            'educationLevel' => 'bachelor'
        ];

        $techResult = $this->service->calculateCareerValue($techInput);
        $educationResult = $this->service->calculateCareerValue($educationInput);

        // Technology should have higher projected increase than education
        $this->assertGreaterThan(
            $educationResult['projectedSalaryIncrease'],
            $techResult['projectedSalaryIncrease']
        );
    }

    public function test_applies_location_multipliers_correctly()
    {
        $sfInput = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase'],
            'location' => 'San Francisco, CA',
            'educationLevel' => 'bachelor'
        ];

        $austinInput = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase'],
            'location' => 'Austin, TX',
            'educationLevel' => 'bachelor'
        ];

        $sfResult = $this->service->calculateCareerValue($sfInput);
        $austinResult = $this->service->calculateCareerValue($austinInput);

        // San Francisco should have higher projected increase than Austin
        $this->assertGreaterThan(
            $austinResult['projectedSalaryIncrease'],
            $sfResult['projectedSalaryIncrease']
        );
    }

    public function test_generates_appropriate_recommendations_based_on_goals()
    {
        $input = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 3,
            'careerGoals' => ['salary_increase', 'networking'],
            'location' => 'Seattle, WA',
            'educationLevel' => 'bachelor',
            'networkingLevel' => 2,
            'primaryChallenge' => 'finding_opportunities'
        ];

        $result = $this->service->calculateCareerValue($input);
        $recommendations = $result['personalizedRecommendations'];

        $this->assertIsArray($recommendations);
        $this->assertGreaterThan(0, count($recommendations));
        $this->assertLessThanOrEqual(5, count($recommendations));

        // Check that recommendations have required fields
        foreach ($recommendations as $recommendation) {
            $this->assertArrayHasKey('category', $recommendation);
            $this->assertArrayHasKey('action', $recommendation);
            $this->assertArrayHasKey('priority', $recommendation);
            $this->assertArrayHasKey('timeframe', $recommendation);
            $this->assertArrayHasKey('expectedOutcome', $recommendation);

            $this->assertContains($recommendation['priority'], ['high', 'medium', 'low']);
        }

        // Should have networking recommendation due to low networking level
        $networkingRecommendation = collect($recommendations)->first(function ($rec) {
            return str_contains(strtolower($rec['category']), 'networking');
        });
        $this->assertNotNull($networkingRecommendation);

        // Should have salary-related recommendation
        $salaryRecommendation = collect($recommendations)->first(function ($rec) {
            return str_contains(strtolower($rec['category']), 'salary') || 
                   str_contains(strtolower($rec['action']), 'salary');
        });
        $this->assertNotNull($salaryRecommendation);
    }

    public function test_calculates_timeline_based_on_goal_timeline()
    {
        $urgentInput = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['job_change'],
            'goalTimeline' => '3_months',
            'networkingLevel' => 4,
            'timeInvestment' => '10_hours'
        ];

        $longTermInput = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['job_change'],
            'goalTimeline' => '2_years',
            'networkingLevel' => 2,
            'timeInvestment' => '1_hour'
        ];

        $urgentResult = $this->service->calculateCareerValue($urgentInput);
        $longTermResult = $this->service->calculateCareerValue($longTermInput);

        // Urgent timeline should result in shorter projected timeline
        $this->assertContains($urgentResult['careerAdvancementTimeline'], ['3-6 months', '6-12 months']);
        $this->assertContains($longTermResult['careerAdvancementTimeline'], ['12-18 months', '18-24 months']);
    }

    public function test_success_probability_factors()
    {
        $highSuccessInput = [
            'currentRole' => 'senior_professional',
            'industry' => 'technology',
            'experienceYears' => 12,
            'careerGoals' => ['promotion'],
            'educationLevel' => 'master',
            'networkingLevel' => 5,
            'timeInvestment' => '10_hours'
        ];

        $lowSuccessInput = [
            'currentRole' => 'recent_graduate',
            'industry' => 'nonprofit',
            'experienceYears' => 1,
            'careerGoals' => ['salary_increase'],
            'educationLevel' => 'bachelor',
            'networkingLevel' => 1,
            'timeInvestment' => '1_hour'
        ];

        $highResult = $this->service->calculateCareerValue($highSuccessInput);
        $lowResult = $this->service->calculateCareerValue($lowSuccessInput);

        $this->assertGreaterThan($lowResult['successProbability'], $highResult['successProbability']);
        $this->assertGreaterThanOrEqual(40, $lowResult['successProbability']);
        $this->assertLessThanOrEqual(95, $highResult['successProbability']);
    }

    public function test_roi_calculation()
    {
        $input = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase'],
            'timeInvestment' => '6_hours'
        ];

        $result = $this->service->calculateCareerValue($input);

        $this->assertIsFloat($result['roiEstimate']);
        $this->assertGreaterThan(0, $result['roiEstimate']);
        
        // ROI should be reasonable (between 1x and 20x)
        $this->assertLessThan(20, $result['roiEstimate']);
    }

    public function test_validates_required_input_fields()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: currentRole');

        $this->service->calculateCareerValue([
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase']
        ]);
    }

    public function test_validates_career_goals_array()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Career goals must be a non-empty array');

        $this->service->calculateCareerValue([
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => []
        ]);
    }

    public function test_sends_email_report_successfully()
    {
        Mail::fake();

        $formData = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase']
        ];

        $result = [
            'projectedSalaryIncrease' => 25000,
            'networkingValue' => 'High networking potential',
            'careerAdvancementTimeline' => '12-18 months',
            'personalizedRecommendations' => [],
            'successProbability' => 85,
            'roiEstimate' => 5.2
        ];

        $success = $this->service->sendEmailReport('test@example.com', $formData, $result);

        $this->assertTrue($success);

        Mail::assertSent(CareerCalculatorReport::class, function ($mail) use ($formData, $result) {
            return $mail->hasTo('test@example.com') &&
                   $mail->formData === $formData &&
                   $mail->result === $result;
        });
    }

    public function test_handles_email_sending_failure()
    {
        Mail::fake();
        Mail::shouldReceive('to')->andThrow(new \Exception('Mail server error'));

        $formData = ['currentRole' => 'software_engineer'];
        $result = ['projectedSalaryIncrease' => 25000];

        $success = $this->service->sendEmailReport('test@example.com', $formData, $result);

        $this->assertFalse($success);
    }

    public function test_networking_value_description_varies_by_input()
    {
        $techInput = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 8,
            'careerGoals' => ['networking'],
        ];

        $financeInput = [
            'currentRole' => 'analyst',
            'industry' => 'finance',
            'experienceYears' => 3,
            'careerGoals' => ['promotion'],
        ];

        $techResult = $this->service->calculateCareerValue($techInput);
        $financeResult = $this->service->calculateCareerValue($financeInput);

        $this->assertStringContainsString('technology', $techResult['networkingValue']);
        $this->assertStringContainsString('finance', $financeResult['networkingValue']);
        $this->assertNotEquals($techResult['networkingValue'], $financeResult['networkingValue']);
    }

    public function test_uses_current_salary_when_provided()
    {
        $withSalaryInput = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase'],
            'currentSalary' => 120000
        ];

        $withoutSalaryInput = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase']
        ];

        $withSalaryResult = $this->service->calculateCareerValue($withSalaryInput);
        $withoutSalaryResult = $this->service->calculateCareerValue($withoutSalaryInput);

        // Results should be different when current salary is provided
        $this->assertNotEquals(
            $withSalaryResult['projectedSalaryIncrease'],
            $withoutSalaryResult['projectedSalaryIncrease']
        );
    }

    public function test_calculation_metadata_is_included()
    {
        $input = [
            'currentRole' => 'software_engineer',
            'industry' => 'technology',
            'experienceYears' => 5,
            'careerGoals' => ['salary_increase'],
            'location' => 'San Francisco, CA'
        ];

        $result = $this->service->calculateCareerValue($input);

        $this->assertArrayHasKey('calculationMetadata', $result);
        $metadata = $result['calculationMetadata'];

        $this->assertArrayHasKey('calculated_at', $metadata);
        $this->assertArrayHasKey('industry_multiplier', $metadata);
        $this->assertArrayHasKey('location_multiplier', $metadata);
        $this->assertArrayHasKey('experience_factor', $metadata);

        $this->assertEquals(1.4, $metadata['industry_multiplier']); // Technology multiplier
        $this->assertEquals(1.6, $metadata['location_multiplier']); // San Francisco multiplier
    }
}