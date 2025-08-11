<?php

namespace App\Services;

use App\Mail\CareerCalculatorReport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CareerCalculatorService
{
    /**
     * Industry salary multipliers based on real market data
     */
    private const INDUSTRY_MULTIPLIERS = [
        'technology' => 1.4,
        'finance' => 1.3,
        'consulting' => 1.25,
        'healthcare' => 1.15,
        'engineering' => 1.2,
        'marketing' => 1.1,
        'sales' => 1.15,
        'legal' => 1.35,
        'education' => 0.9,
        'nonprofit' => 0.85,
        'government' => 0.95,
        'retail' => 0.9,
        'manufacturing' => 1.05,
        'media' => 1.0,
        'hospitality' => 0.85,
        'transportation' => 0.95,
        'energy' => 1.2,
        'agriculture' => 0.9,
        'real_estate' => 1.1,
        'other' => 1.0,
    ];

    /**
     * Location cost of living and salary adjustments
     */
    private const LOCATION_MULTIPLIERS = [
        'san francisco' => 1.6,
        'new york' => 1.5,
        'seattle' => 1.4,
        'boston' => 1.3,
        'los angeles' => 1.3,
        'washington dc' => 1.25,
        'chicago' => 1.15,
        'austin' => 1.2,
        'denver' => 1.1,
        'atlanta' => 1.05,
        'dallas' => 1.1,
        'miami' => 1.05,
        'philadelphia' => 1.1,
        'phoenix' => 1.0,
        'san diego' => 1.25,
        'portland' => 1.15,
        'nashville' => 1.0,
        'raleigh' => 1.05,
        'charlotte' => 1.0,
        'tampa' => 0.95,
        'remote' => 1.0,
        'default' => 1.0,
    ];

    /**
     * Base salary ranges by role and experience
     */
    private const BASE_SALARIES = [
        'recent_graduate' => 55000,
        'junior_professional' => 65000,
        'mid_level' => 85000,
        'senior_professional' => 120000,
        'manager' => 140000,
        'senior_manager' => 180000,
        'director' => 220000,
        'vp_executive' => 300000,
        'entrepreneur' => 75000,
        'consultant' => 95000,
        'between_jobs' => 70000,
        'career_change' => 70000,
    ];

    /**
     * Calculate career value based on user input
     */
    public function calculateCareerValue(array $input): array
    {
        try {
            // Validate input
            $this->validateInput($input);

            // Calculate base metrics
            $baseSalary = $this->calculateBaseSalary($input);
            $projectedIncrease = $this->calculateSalaryIncrease($input, $baseSalary);
            $timeline = $this->calculateTimeline($input);
            $successProbability = $this->calculateSuccessProbability($input);
            $roiEstimate = $this->calculateROI($input, $projectedIncrease);
            $networkingValue = $this->calculateNetworkingValue($input);
            $recommendations = $this->generateRecommendations($input);

            return [
                'projectedSalaryIncrease' => $projectedIncrease,
                'networkingValue' => $networkingValue,
                'careerAdvancementTimeline' => $timeline,
                'personalizedRecommendations' => $recommendations,
                'successProbability' => $successProbability,
                'roiEstimate' => $roiEstimate,
                'baseSalary' => $baseSalary,
                'calculationMetadata' => [
                    'calculated_at' => now(),
                    'industry_multiplier' => $this->getIndustryMultiplier($input['industry']),
                    'location_multiplier' => $this->getLocationMultiplier($input['location'] ?? ''),
                    'experience_factor' => $this->getExperienceFactor($input['experienceYears']),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Career calculator error', [
                'input' => $input,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate base salary estimate
     */
    private function calculateBaseSalary(array $input): int
    {
        $baseSalary = self::BASE_SALARIES[$input['currentRole']] ?? 70000;

        // Apply current salary if provided
        if (! empty($input['currentSalary']) && $input['currentSalary'] > 0) {
            $baseSalary = $input['currentSalary'];
        }

        // Apply industry multiplier
        $industryMultiplier = $this->getIndustryMultiplier($input['industry']);
        $baseSalary *= $industryMultiplier;

        // Apply location multiplier
        $locationMultiplier = $this->getLocationMultiplier($input['location'] ?? '');
        $baseSalary *= $locationMultiplier;

        // Apply experience factor
        $experienceFactor = $this->getExperienceFactor($input['experienceYears']);
        $baseSalary *= $experienceFactor;

        return (int) round($baseSalary);
    }

    /**
     * Calculate projected salary increase
     */
    private function calculateSalaryIncrease(array $input, int $baseSalary): int
    {
        $increasePercentage = 0.25; // Base 25% increase

        // Adjust based on career goals
        if (in_array('salary_increase', $input['careerGoals'])) {
            $increasePercentage += 0.15;
        }
        if (in_array('promotion', $input['careerGoals'])) {
            $increasePercentage += 0.20;
        }
        if (in_array('job_change', $input['careerGoals'])) {
            $increasePercentage += 0.18;
        }
        if (in_array('leadership_role', $input['careerGoals'])) {
            $increasePercentage += 0.30;
        }

        // Adjust based on education level
        $educationBonus = $this->getEducationBonus($input['educationLevel'] ?? '');
        $increasePercentage += $educationBonus;

        // Adjust based on networking level
        $networkingLevel = $input['networkingLevel'] ?? 3;
        $networkingBonus = ($networkingLevel - 3) * 0.05;
        $increasePercentage += $networkingBonus;

        // Cap the increase at 80%
        $increasePercentage = min($increasePercentage, 0.80);

        return (int) round($baseSalary * $increasePercentage);
    }

    /**
     * Calculate career advancement timeline
     */
    private function calculateTimeline(array $input): string
    {
        $baseMonths = 18; // Base 18 months

        // Adjust based on goal timeline
        if (! empty($input['goalTimeline'])) {
            switch ($input['goalTimeline']) {
                case '3_months':
                    $baseMonths = 6;
                    break;
                case '6_months':
                    $baseMonths = 9;
                    break;
                case '1_year':
                    $baseMonths = 12;
                    break;
                case '2_years':
                    $baseMonths = 24;
                    break;
            }
        }

        // Adjust based on networking level
        $networkingLevel = $input['networkingLevel'] ?? 3;
        if ($networkingLevel >= 4) {
            $baseMonths -= 3;
        } elseif ($networkingLevel <= 2) {
            $baseMonths += 3;
        }

        // Adjust based on time investment
        if (! empty($input['timeInvestment'])) {
            switch ($input['timeInvestment']) {
                case '10_hours':
                    $baseMonths -= 3;
                    break;
                case '6_hours':
                    $baseMonths -= 1;
                    break;
                case '1_hour':
                    $baseMonths += 2;
                    break;
            }
        }

        $baseMonths = max($baseMonths, 3); // Minimum 3 months

        if ($baseMonths <= 6) {
            return '3-6 months';
        } elseif ($baseMonths <= 12) {
            return '6-12 months';
        } elseif ($baseMonths <= 18) {
            return '12-18 months';
        } else {
            return '18-24 months';
        }
    }

    /**
     * Calculate success probability
     */
    private function calculateSuccessProbability(array $input): int
    {
        $baseProbability = 70; // Base 70%

        // Adjust based on education level
        $educationLevel = $input['educationLevel'] ?? '';
        if (in_array($educationLevel, ['master', 'mba', 'doctorate', 'professional'])) {
            $baseProbability += 10;
        }

        // Adjust based on experience
        $experience = $input['experienceYears'];
        if ($experience >= 10) {
            $baseProbability += 10;
        } elseif ($experience >= 5) {
            $baseProbability += 5;
        }

        // Adjust based on networking level
        $networkingLevel = $input['networkingLevel'] ?? 3;
        $baseProbability += ($networkingLevel - 3) * 5;

        // Adjust based on time investment
        if (! empty($input['timeInvestment'])) {
            switch ($input['timeInvestment']) {
                case '10_hours':
                    $baseProbability += 15;
                    break;
                case '6_hours':
                    $baseProbability += 10;
                    break;
                case '3_hours':
                    $baseProbability += 5;
                    break;
            }
        }

        // Adjust based on industry
        $industryMultiplier = $this->getIndustryMultiplier($input['industry']);
        if ($industryMultiplier > 1.2) {
            $baseProbability += 5;
        }

        return min(max($baseProbability, 40), 95); // Cap between 40% and 95%
    }

    /**
     * Calculate ROI estimate
     */
    private function calculateROI(array $input, int $salaryIncrease): float
    {
        $membershipCost = 500; // Estimated annual membership cost
        $timeInvestmentCost = $this->calculateTimeInvestmentCost($input);
        $totalInvestment = $membershipCost + $timeInvestmentCost;

        $annualBenefit = $salaryIncrease;
        $twoYearBenefit = $annualBenefit * 2;

        $roi = $twoYearBenefit / $totalInvestment;

        return round($roi, 1);
    }

    /**
     * Calculate networking value description
     */
    private function calculateNetworkingValue(array $input): string
    {
        $industry = $input['industry'];
        $experience = $input['experienceYears'];
        $goals = $input['careerGoals'];

        $connections = 50 + ($experience * 5);
        $mentors = max(1, floor($experience / 3));

        if (in_array('networking', $goals)) {
            $connections += 30;
            $mentors += 2;
        }

        return "Access to {$connections}+ alumni in {$industry}, {$mentors} potential mentors, and exclusive industry events";
    }

    /**
     * Generate personalized recommendations
     */
    private function generateRecommendations(array $input): array
    {
        $recommendations = [];
        $goals = $input['careerGoals'];
        $challenge = $input['primaryChallenge'] ?? '';
        $networkingLevel = $input['networkingLevel'] ?? 3;

        // Networking recommendations
        if ($networkingLevel <= 2 || in_array('networking', $goals)) {
            $recommendations[] = [
                'category' => 'Networking Strategy',
                'action' => 'Join 2-3 industry-specific alumni groups and attend monthly events',
                'priority' => 'high',
                'timeframe' => '1-2 months',
                'expectedOutcome' => 'Build 15-20 new professional connections',
            ];
        }

        // Career goal specific recommendations
        if (in_array('salary_increase', $goals)) {
            $recommendations[] = [
                'category' => 'Salary Negotiation',
                'action' => 'Connect with alumni in similar roles to benchmark compensation and negotiation strategies',
                'priority' => 'high',
                'timeframe' => '2-3 months',
                'expectedOutcome' => 'Gain market insights for 15-25% salary increase',
            ];
        }

        if (in_array('job_change', $goals)) {
            $recommendations[] = [
                'category' => 'Job Search Strategy',
                'action' => 'Leverage alumni network for referrals and insider information on job openings',
                'priority' => 'high',
                'timeframe' => '1-3 months',
                'expectedOutcome' => 'Access to hidden job market and referral opportunities',
            ];
        }

        if (in_array('skill_development', $goals)) {
            $recommendations[] = [
                'category' => 'Professional Development',
                'action' => 'Find mentors in your target skill areas and join relevant professional development groups',
                'priority' => 'medium',
                'timeframe' => '2-4 months',
                'expectedOutcome' => 'Accelerated skill acquisition and career guidance',
            ];
        }

        if (in_array('leadership_role', $goals)) {
            $recommendations[] = [
                'category' => 'Leadership Development',
                'action' => 'Connect with alumni in executive positions for mentorship and leadership insights',
                'priority' => 'high',
                'timeframe' => '3-6 months',
                'expectedOutcome' => 'Leadership skills and executive presence development',
            ];
        }

        // Challenge-specific recommendations
        if ($challenge === 'finding_opportunities') {
            $recommendations[] = [
                'category' => 'Opportunity Discovery',
                'action' => 'Set up informational interviews with alumni in your target companies',
                'priority' => 'high',
                'timeframe' => '1-2 months',
                'expectedOutcome' => 'Insider knowledge of upcoming opportunities',
            ];
        }

        if ($challenge === 'career_direction') {
            $recommendations[] = [
                'category' => 'Career Clarity',
                'action' => 'Schedule career coaching sessions with experienced alumni mentors',
                'priority' => 'medium',
                'timeframe' => '1-3 months',
                'expectedOutcome' => 'Clear career path and actionable next steps',
            ];
        }

        // Ensure we have at least 3 recommendations
        if (count($recommendations) < 3) {
            $recommendations[] = [
                'category' => 'Platform Engagement',
                'action' => 'Complete your profile and actively participate in alumni discussions',
                'priority' => 'medium',
                'timeframe' => '2-4 weeks',
                'expectedOutcome' => 'Increased visibility and networking opportunities',
            ];
        }

        return array_slice($recommendations, 0, 5); // Limit to 5 recommendations
    }

    /**
     * Send email report
     */
    public function sendEmailReport(string $email, array $formData, array $result): bool
    {
        try {
            Mail::to($email)->send(new CareerCalculatorReport($formData, $result));

            // Log the email send for analytics
            Log::info('Career calculator email report sent', [
                'email' => $email,
                'projected_increase' => $result['projectedSalaryIncrease'],
                'success_probability' => $result['successProbability'],
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send career calculator email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Helper methods
     */
    private function validateInput(array $input): void
    {
        $required = ['currentRole', 'industry', 'experienceYears', 'careerGoals'];

        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if (! is_array($input['careerGoals']) || empty($input['careerGoals'])) {
            throw new \InvalidArgumentException('Career goals must be a non-empty array');
        }
    }

    private function getIndustryMultiplier(string $industry): float
    {
        return self::INDUSTRY_MULTIPLIERS[$industry] ?? 1.0;
    }

    private function getLocationMultiplier(string $location): float
    {
        $location = strtolower($location);

        foreach (self::LOCATION_MULTIPLIERS as $key => $multiplier) {
            if (str_contains($location, $key)) {
                return $multiplier;
            }
        }

        return self::LOCATION_MULTIPLIERS['default'];
    }

    private function getExperienceFactor(int $years): float
    {
        if ($years <= 1) {
            return 0.8;
        }
        if ($years <= 3) {
            return 0.9;
        }
        if ($years <= 5) {
            return 1.0;
        }
        if ($years <= 10) {
            return 1.1;
        }
        if ($years <= 15) {
            return 1.2;
        }

        return 1.3;
    }

    private function getEducationBonus(string $level): float
    {
        $bonuses = [
            'high_school' => 0,
            'associate' => 0.02,
            'bachelor' => 0.05,
            'master' => 0.08,
            'mba' => 0.12,
            'doctorate' => 0.10,
            'professional' => 0.15,
            'certification' => 0.03,
            'bootcamp' => 0.04,
            'self_taught' => 0.02,
        ];

        return $bonuses[$level] ?? 0;
    }

    private function calculateTimeInvestmentCost(array $input): int
    {
        $timeInvestment = $input['timeInvestment'] ?? '3_hours';
        $hourlyRate = 50; // Estimated opportunity cost per hour

        $hoursPerWeek = match ($timeInvestment) {
            '1_hour' => 1.5,
            '3_hours' => 4,
            '6_hours' => 8,
            '10_hours' => 12,
            default => 4
        };

        return (int) ($hoursPerWeek * 52 * $hourlyRate); // Annual cost
    }
}
