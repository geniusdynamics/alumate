<?php

declare(strict_types=1);

namespace App\Services\Homepage;

use Illuminate\Support\Facades\Cache;

/**
 * Homepage A/B Testing Service
 *
 * Manages A/B test variants, user assignment, and conversion tracking for the homepage.
 * Replaces A/B testing methods from the monolithic HomepageService.
 */
class HomepageABTestingService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get A/B test variants for the homepage
     */
    public function getABTestVariants(string $testName = 'homepage_test'): array
    {
        return Cache::remember("homepage.ab.{$testName}", self::CACHE_TTL, function () use ($testName) {
            // TODO: Load from database (ab_tests table)
            return [
                'test_name' => $testName,
                'variants' => [
                    'control' => [
                        'weight' => 50,
                        'modifications' => [],
                    ],
                    'variant_a' => [
                        'weight' => 25,
                        'modifications' => [
                            'hero_cta_text' => 'Start Free Trial',
                            'hero_background' => 'gradient-blue',
                        ],
                    ],
                    'variant_b' => [
                        'weight' => 25,
                        'modifications' => [
                            'hero_cta_text' => 'See It In Action',
                            'hero_background' => 'gradient-purple',
                        ],
                    ],
                ],
            ];
        });
    }

    /**
     * Assign user to an A/B test variant
     */
    public function assignUserToVariant(string $testName, ?int $userId = null): string
    {
        // If user already has a variant assigned, return it
        if ($userId) {
            $existing = Cache::get("homepage.ab.user.{$userId}.{$testName}");
            if ($existing) {
                return $existing;
            }
        }

        // Get test variants and their weights
        $test = $this->getABTestVariants($testName);
        $variants = $test['variants'];

        // Weighted random selection
        $totalWeight = array_sum(array_column($variants, 'weight'));
        $random = mt_rand(1, $totalWeight);
        $cumulative = 0;

        foreach ($variants as $name => $config) {
            $cumulative += $config['weight'];
            if ($random <= $cumulative) {
                // Cache the assignment for the user
                if ($userId) {
                    Cache::put("homepage.ab.user.{$userId}.{$testName}", $name, 86400 * 30); // 30 days
                }

                return $name;
            }
        }

        return 'control';
    }

    /**
     * Track conversion for an A/B test
     */
    public function trackConversion(string $testName, string $variant, string $conversionType, ?int $userId = null): void
    {
        $key = "homepage.ab.conversion.{$testName}.{$variant}.{$conversionType}";
        $count = Cache::get($key, 0);
        Cache::put($key, $count + 1, 86400 * 7); // 7 days

        if ($userId) {
            Cache::put("homepage.ab.user.{$userId}.{$testName}.converted", true, 86400 * 30);
        }
    }

    /**
     * Get test results summary
     */
    public function getTestResults(string $testName): array
    {
        $test = $this->getABTestVariants($testName);
        $results = [];

        foreach ($test['variants'] as $variantName => $config) {
            $conversions = Cache::get("homepage.ab.conversion.{$testName}.{$variantName}.signup", 0);
            $results[$variantName] = [
                'name' => $variantName,
                'weight' => $config['weight'],
                'conversions' => $conversions,
                'modifications' => $config['modifications'],
            ];
        }

        return $results;
    }
}
