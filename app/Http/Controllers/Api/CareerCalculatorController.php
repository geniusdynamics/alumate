<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CareerCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CareerCalculatorController extends Controller
{
    public function __construct(
        private CareerCalculatorService $calculatorService
    ) {}

    /**
     * Calculate career value based on user input
     */
    public function calculate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'currentRole' => 'required|string',
            'industry' => 'required|string',
            'experienceYears' => 'required|integer|min:0|max:50',
            'careerGoals' => 'required|array|min:1',
            'careerGoals.*' => 'string',
            'location' => 'nullable|string|max:255',
            'educationLevel' => 'nullable|string',
            'currentSalary' => 'nullable|integer|min:0',
            'targetRole' => 'nullable|string|max:255',
            'preferredCompanySize' => 'nullable|string',
            'workStyle' => 'nullable|string',
            'skillsToLearn' => 'nullable|string|max:1000',
            'goalTimeline' => 'nullable|string',
            'primaryChallenge' => 'nullable|string',
            'networkingLevel' => 'nullable|integer|min:1|max:5',
            'timeInvestment' => 'nullable|string',
            'additionalInfo' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->calculatorService->calculateCareerValue($request->all());

            // Track analytics event
            $this->trackCalculatorCompletion($request->all(), $result);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Career value calculated successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while calculating career value',
            ], 500);
        }
    }

    /**
     * Send email report to user
     */
    public function emailReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'formData' => 'required|array',
            'result' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $success = $this->calculatorService->sendEmailReport(
                $request->input('email'),
                $request->input('formData'),
                $request->input('result')
            );

            if ($success) {
                // Track email report request
                $this->trackEmailReport($request->input('email'));

                return response()->json([
                    'success' => true,
                    'message' => 'Email report sent successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email report',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending email report',
            ], 500);
        }
    }

    /**
     * Get industry benchmarks for calculator
     */
    public function benchmarks(Request $request): JsonResponse
    {
        $industry = $request->query('industry');
        $location = $request->query('location');

        try {
            // This would typically fetch from database
            $benchmarks = [
                'averageSalary' => $this->getIndustryAverageSalary($industry, $location),
                'salaryGrowthRate' => $this->getIndustryGrowthRate($industry),
                'networkingValue' => $this->getNetworkingValue($industry),
                'jobPlacementRate' => $this->getJobPlacementRate($industry),
                'topSkills' => $this->getTopSkills($industry),
                'careerPaths' => $this->getCareerPaths($industry),
            ];

            return response()->json([
                'success' => true,
                'data' => $benchmarks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch benchmarks',
            ], 500);
        }
    }

    /**
     * Track calculator completion for analytics
     */
    private function trackCalculatorCompletion(array $formData, array $result): void
    {
        // This would integrate with your analytics service
        // For now, we'll just log it
        \Log::info('Calculator completed', [
            'industry' => $formData['industry'],
            'experience_years' => $formData['experienceYears'],
            'career_goals' => $formData['careerGoals'],
            'projected_increase' => $result['projectedSalaryIncrease'],
            'success_probability' => $result['successProbability'],
            'roi_estimate' => $result['roiEstimate'],
        ]);
    }

    /**
     * Track email report request
     */
    private function trackEmailReport(string $email): void
    {
        \Log::info('Email report requested', [
            'email' => $email,
            'timestamp' => now(),
        ]);
    }

    /**
     * Helper methods for benchmarks
     */
    private function getIndustryAverageSalary(?string $industry = null, ?string $location = null): int
    {
        // Mock data - in real implementation, this would query the database
        $baseSalaries = [
            'technology' => 95000,
            'finance' => 85000,
            'healthcare' => 75000,
            'consulting' => 90000,
            'education' => 55000,
            'default' => 70000,
        ];

        $salary = $baseSalaries[$industry] ?? $baseSalaries['default'];

        // Apply location multiplier
        if ($location && str_contains(strtolower($location), 'san francisco')) {
            $salary *= 1.6;
        } elseif ($location && str_contains(strtolower($location), 'new york')) {
            $salary *= 1.5;
        }

        return (int) $salary;
    }

    private function getIndustryGrowthRate(?string $industry = null): float
    {
        $growthRates = [
            'technology' => 0.08,
            'finance' => 0.05,
            'healthcare' => 0.06,
            'consulting' => 0.07,
            'education' => 0.03,
            'default' => 0.05,
        ];

        return $growthRates[$industry] ?? $growthRates['default'];
    }

    private function getNetworkingValue(?string $industry = null): int
    {
        // Mock networking value score
        return rand(70, 95);
    }

    private function getJobPlacementRate(?string $industry = null): int
    {
        $placementRates = [
            'technology' => 92,
            'finance' => 88,
            'healthcare' => 85,
            'consulting' => 90,
            'education' => 78,
            'default' => 82,
        ];

        return $placementRates[$industry] ?? $placementRates['default'];
    }

    private function getTopSkills(?string $industry = null): array
    {
        $skills = [
            'technology' => ['Python', 'JavaScript', 'Cloud Computing', 'Data Analysis', 'Project Management'],
            'finance' => ['Financial Modeling', 'Risk Management', 'Excel', 'SQL', 'Regulatory Compliance'],
            'healthcare' => ['Patient Care', 'Medical Technology', 'Healthcare Administration', 'Quality Improvement'],
            'consulting' => ['Strategic Planning', 'Data Analysis', 'Client Management', 'Presentation Skills'],
            'default' => ['Communication', 'Leadership', 'Problem Solving', 'Project Management', 'Data Analysis'],
        ];

        return $skills[$industry] ?? $skills['default'];
    }

    private function getCareerPaths(?string $industry = null): array
    {
        $paths = [
            'technology' => [
                'Individual Contributor → Senior Engineer → Staff Engineer → Principal Engineer',
                'Individual Contributor → Team Lead → Engineering Manager → Director',
                'Individual Contributor → Product Manager → Senior PM → VP Product',
            ],
            'finance' => [
                'Analyst → Senior Analyst → Associate → VP → Managing Director',
                'Analyst → Portfolio Manager → Senior PM → CIO',
                'Analyst → Risk Manager → Senior Risk Manager → Chief Risk Officer',
            ],
            'default' => [
                'Individual Contributor → Senior IC → Team Lead → Manager → Director',
                'Specialist → Senior Specialist → Subject Matter Expert → Consultant',
                'Coordinator → Manager → Senior Manager → VP',
            ],
        ];

        return $paths[$industry] ?? $paths['default'];
    }
}
