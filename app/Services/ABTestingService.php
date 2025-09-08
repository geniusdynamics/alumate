<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ABTestingService extends BaseService
{
    /**
     * Get the variant for a user in a specific test
     */
    public function getVariant(string $testId, string $userId, string $audience): array
    {
        $test = $this->getTest($testId);

        if (! $test || ! $test['active']) {
            // Log missing test or inactive test as warning since it's an A/B test anomaly
            if (! $test) {
                logger()->warning('A/B test not found, using control variant', [
                    'test_id' => $testId,
                    'user_id' => $userId,
                    'audience' => $audience,
                ]);
            }

            return $this->getControlVariant($test);
        }

        // Check if user is in the target audience
        if (! empty($test['target_audience']) && $test['target_audience'] !== $audience) {
            logger()->warning('User not in target audience for A/B test, using control variant', [
                'test_id' => $testId,
                'user_id' => $userId,
                'user_audience' => $audience,
                'target_audience' => $test['target_audience'],
            ]);

            return $this->getControlVariant($test);
        }

        // Get consistent variant assignment based on user ID
        $hash = $this->hashUserId($userId, $testId);
        $variant = $this->assignVariant($hash, $test['variants']);

        // Track variant assignment
        $this->trackVariantAssignment($testId, $variant['id'], $userId, $audience);

        return $variant;
    }

    /**
     * Track a conversion event for A/B testing
     */
    public function trackConversion(string $testId, string $variantId, string $goal, string $userId, array $additionalData = []): void
    {
        try {
            $conversionData = [
                'test_id' => $testId,
                'variant_id' => $variantId,
                'goal' => $goal,
                'user_id' => $userId,
                'timestamp' => now(),
                'additional_data' => $additionalData,
                'session_id' => session()->getId(),
                'user_agent' => request()->userAgent(),
                'ip' => request()->ip(),
            ];

            // Store conversion in cache for immediate access
            $cacheKey = "ab_conversion_{$testId}_{$variantId}_{$goal}_".date('Y-m-d');
            $conversions = Cache::get($cacheKey, []);
            $conversions[] = $conversionData;
            Cache::put($cacheKey, $conversions, 86400); // 24 hours

            // Log for permanent storage
            Log::info('AB Test Conversion', $conversionData);

        } catch (\Exception $e) {
            logger()->error('A/B test conversion tracking failed', [
                'error' => $e->getMessage(),
                'test_id' => $testId,
                'variant_id' => $variantId,
                'goal' => $goal,
                'user_id' => $userId,
                'audience' => $this->getCurrentAudience(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get test configuration
     */
    public function getTest(string $testId): ?array
    {
        $tests = $this->getTestConfigurations();

        return $tests[$testId] ?? null;
    }

    /**
     * Get all active tests for a user
     */
    public function getActiveTests(string $userId, string $audience): array
    {
        $tests = $this->getTestConfigurations();
        $activeTests = [];

        foreach ($tests as $testId => $test) {
            if ($test['active'] &&
                (empty($test['target_audience']) || $test['target_audience'] === $audience)) {

                $variant = $this->getVariant($testId, $userId, $audience);
                $activeTests[$testId] = [
                    'test' => $test,
                    'variant' => $variant,
                ];
            }
        }

        return $activeTests;
    }

    /**
     * Get test results and statistics
     */
    public function getTestResults(string $testId): array
    {
        $test = $this->getTest($testId);
        if (! $test) {
            return [];
        }

        $results = [];
        foreach ($test['variants'] as $variant) {
            $results[$variant['id']] = [
                'variant' => $variant,
                'assignments' => $this->getVariantAssignments($testId, $variant['id']),
                'conversions' => $this->getVariantConversions($testId, $variant['id']),
                'conversion_rate' => $this->calculateConversionRate($testId, $variant['id']),
                'statistical_significance' => $this->calculateStatisticalSignificance($testId, $variant['id']),
            ];
        }

        return [
            'test' => $test,
            'results' => $results,
            'winner' => $this->determineWinner($results),
            'confidence_level' => $this->calculateConfidenceLevel($results),
        ];
    }

    /**
     * Create a new A/B test
     */
    public function createTest(array $testData): string
    {
        $testId = Str::slug($testData['name']).'_'.time();

        $test = [
            'id' => $testId,
            'name' => $testData['name'],
            'description' => $testData['description'] ?? '',
            'target_audience' => $testData['target_audience'] ?? null,
            'variants' => $testData['variants'],
            'conversion_goals' => $testData['conversion_goals'],
            'traffic_allocation' => $testData['traffic_allocation'] ?? 100,
            'start_date' => $testData['start_date'] ?? now(),
            'end_date' => $testData['end_date'] ?? null,
            'active' => $testData['active'] ?? true,
            'created_at' => now(),
            'created_by' => auth()->id(),
        ];

        // Store test configuration
        $this->storeTestConfiguration($testId, $test);

        return $testId;
    }

    /**
     * Update test status
     */
    public function updateTestStatus(string $testId, bool $active): bool
    {
        $test = $this->getTest($testId);
        if (! $test) {
            return false;
        }

        $test['active'] = $active;
        $test['updated_at'] = now();

        return $this->storeTestConfiguration($testId, $test);
    }

    /**
     * Get test configurations (mock data - replace with database)
     */
    private function getTestConfigurations(): array
    {
        return Cache::remember('ab_test_configurations', 3600, function () {
            return [
                'hero_message_dual_audience' => [
                    'id' => 'hero_message_dual_audience',
                    'name' => 'Hero Message Dual Audience Test',
                    'description' => 'Testing different hero messages for individual vs institutional audiences',
                    'target_audience' => null, // Both audiences
                    'variants' => [
                        [
                            'id' => 'control',
                            'name' => 'Control',
                            'weight' => 34,
                            'component_overrides' => [
                                'individual' => [
                                    'headline' => 'Accelerate Your Career Through Alumni Connections',
                                    'subtitle' => 'Join thousands of alumni advancing their careers through meaningful professional networking',
                                ],
                                'institutional' => [
                                    'headline' => 'Transform Alumni Engagement with Your Branded Platform',
                                    'subtitle' => 'Increase alumni participation by 300% with custom mobile apps and comprehensive analytics',
                                ],
                            ],
                        ],
                        [
                            'id' => 'career_focus',
                            'name' => 'Career Focus',
                            'weight' => 33,
                            'component_overrides' => [
                                'individual' => [
                                    'headline' => 'Unlock Your Career Potential with Alumni Network',
                                    'subtitle' => 'Connect with successful alumni and fast-track your career growth with proven strategies',
                                ],
                                'institutional' => [
                                    'headline' => 'Build a Thriving Alumni Community',
                                    'subtitle' => 'Engage alumni with branded apps, powerful analytics, and comprehensive management tools',
                                ],
                            ],
                        ],
                        [
                            'id' => 'success_focus',
                            'name' => 'Success Focus',
                            'weight' => 33,
                            'component_overrides' => [
                                'individual' => [
                                    'headline' => 'Your Next Career Move Starts Here',
                                    'subtitle' => 'Leverage the power of alumni connections for career success and professional growth',
                                ],
                                'institutional' => [
                                    'headline' => 'The Complete Alumni Engagement Solution',
                                    'subtitle' => 'From mobile apps to analytics - everything you need to build a thriving alumni community',
                                ],
                            ],
                        ],
                    ],
                    'conversion_goals' => [
                        'individual' => ['trial_signup', 'waitlist_join', 'hero_cta_click'],
                        'institutional' => ['demo_request', 'case_study_download', 'contact_sales'],
                    ],
                    'traffic_allocation' => 100,
                    'start_date' => now()->subDays(7),
                    'end_date' => now()->addDays(30),
                    'active' => true,
                    'created_at' => now()->subDays(7),
                ],
                'cta_button_text' => [
                    'id' => 'cta_button_text',
                    'name' => 'CTA Button Text Test',
                    'description' => 'Testing different CTA button texts for conversion optimization',
                    'target_audience' => 'individual',
                    'variants' => [
                        [
                            'id' => 'control',
                            'name' => 'Control - Start Free Trial',
                            'weight' => 50,
                            'component_overrides' => [
                                'primary_cta_text' => 'Start Free Trial',
                            ],
                        ],
                        [
                            'id' => 'variant_a',
                            'name' => 'Join Now',
                            'weight' => 50,
                            'component_overrides' => [
                                'primary_cta_text' => 'Join Now',
                            ],
                        ],
                    ],
                    'conversion_goals' => ['trial_signup', 'hero_cta_click'],
                    'traffic_allocation' => 50,
                    'start_date' => now()->subDays(3),
                    'end_date' => now()->addDays(14),
                    'active' => true,
                    'created_at' => now()->subDays(3),
                ],
            ];
        });
    }

    /**
     * Store test configuration
     */
    private function storeTestConfiguration(string $testId, array $test): bool
    {
        try {
            $tests = Cache::get('ab_test_configurations', []);
            $tests[$testId] = $test;
            Cache::put('ab_test_configurations', $tests, 86400);

            // Also log for persistence
            Log::info('AB Test Configuration Updated', [
                'test_id' => $testId,
                'test' => $test,
            ]);

            return true;
        } catch (\Exception $e) {
            logger()->error('A/B test configuration storage failed', [
                'error' => $e->getMessage(),
                'test_id' => $testId,
                'audience' => $test['target_audience'] ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Hash user ID for consistent variant assignment
     */
    private function hashUserId(string $userId, string $testId): int
    {
        return crc32($userId.$testId) & 0x7FFFFFFF;
    }

    /**
     * Assign variant based on hash and weights
     */
    private function assignVariant(int $hash, array $variants): array
    {
        // Validate variants structure
        if (empty($variants) || ! is_array($variants)) {
            logger()->warning('A/B test variants array is empty or invalid', [
                'variants' => $variants,
                'hash' => $hash,
            ]);

            return $this->getDefaultControlVariant();
        }

        $totalWeight = array_sum(array_column($variants, 'weight'));

        // Validate weights sum to avoid division by zero or invalid distribution
        if ($totalWeight <= 0) {
            logger()->warning('A/B test variants have invalid weight distribution', [
                'total_weight' => $totalWeight,
                'variants_count' => count($variants),
            ]);

            return $variants[0] ?? $this->getDefaultControlVariant();
        }

        $randomValue = $hash % $totalWeight;

        $currentWeight = 0;
        foreach ($variants as $variant) {
            // Validate variant structure
            if (! isset($variant['weight']) || ! is_numeric($variant['weight'])) {
                logger()->warning('A/B test variant has invalid weight', [
                    'variant' => $variant,
                ]);

                continue;
            }

            $currentWeight += $variant['weight'];
            if ($randomValue < $currentWeight) {
                return $variant;
            }
        }

        // Fallback to first variant
        return $variants[0] ?? $this->getDefaultControlVariant();
    }

    /**
     * Get control variant
     */
    private function getControlVariant(?array $test): array
    {
        if (! $test || empty($test['variants'])) {
            return [
                'id' => 'control',
                'name' => 'Control',
                'component_overrides' => [],
            ];
        }

        // Return first variant as control
        return $test['variants'][0];
    }

    /**
     * Track variant assignment
     */
    private function trackVariantAssignment(string $testId, string $variantId, string $userId, string $audience): void
    {
        try {
            $assignmentData = [
                'test_id' => $testId,
                'variant_id' => $variantId,
                'user_id' => $userId,
                'audience' => $audience,
                'timestamp' => now(),
                'session_id' => session()->getId(),
                'user_agent' => request()->userAgent(),
                'ip' => request()->ip(),
            ];

            // Store assignment in cache
            $cacheKey = "ab_assignment_{$testId}_{$variantId}_".date('Y-m-d');
            $assignments = Cache::get($cacheKey, []);
            $assignments[] = $assignmentData;
            Cache::put($cacheKey, $assignments, 86400);

            // Log for permanent storage
            Log::info('AB Test Assignment', $assignmentData);

        } catch (\Exception $e) {
            logger()->error('A/B test assignment tracking failed', [
                'error' => $e->getMessage(),
                'test_id' => $testId,
                'variant_id' => $variantId,
                'user_id' => $userId,
                'audience' => $audience,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get variant assignments count
     */
    private function getVariantAssignments(string $testId, string $variantId): int
    {
        // Mock implementation - replace with database query
        $cacheKey = "ab_assignment_{$testId}_{$variantId}_".date('Y-m-d');
        $assignments = Cache::get($cacheKey, []);

        return count($assignments);
    }

    /**
     * Get variant conversions count
     */
    private function getVariantConversions(string $testId, string $variantId): int
    {
        // Mock implementation - replace with database query
        $cacheKey = "ab_conversion_{$testId}_{$variantId}_*";
        $totalConversions = 0;

        // This is simplified - in production, you'd query the database
        for ($i = 0; $i < 7; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyKey = "ab_conversion_{$testId}_{$variantId}_goal_{$date}";
            $conversions = Cache::get($dailyKey, []);
            $totalConversions += count($conversions);
        }

        return $totalConversions;
    }

    /**
     * Calculate conversion rate
     */
    private function calculateConversionRate(string $testId, string $variantId): float
    {
        $assignments = $this->getVariantAssignments($testId, $variantId);
        $conversions = $this->getVariantConversions($testId, $variantId);

        if ($assignments === 0) {
            return 0.0;
        }

        return round(($conversions / $assignments) * 100, 2);
    }

    /**
     * Calculate statistical significance (simplified)
     */
    private function calculateStatisticalSignificance(string $testId, string $variantId): float
    {
        // Simplified calculation - in production, use proper statistical methods
        $assignments = $this->getVariantAssignments($testId, $variantId);

        if ($assignments < 100) {
            return 0.0; // Not enough data
        }

        // Mock significance calculation
        return min(95.0, ($assignments / 1000) * 95);
    }

    /**
     * Determine test winner
     */
    private function determineWinner(array $results): ?array
    {
        $winner = null;
        $highestConversionRate = 0;

        foreach ($results as $variantId => $result) {
            if ($result['conversion_rate'] > $highestConversionRate &&
                $result['statistical_significance'] >= 95) {
                $highestConversionRate = $result['conversion_rate'];
                $winner = [
                    'variant_id' => $variantId,
                    'conversion_rate' => $result['conversion_rate'],
                    'significance' => $result['statistical_significance'],
                ];
            }
        }

        return $winner;
    }

    /**
     * Calculate overall confidence level
     */
    private function calculateConfidenceLevel(array $results): float
    {
        $significances = array_column($results, 'statistical_significance');

        return empty($significances) ? 0.0 : max($significances);
    }

    /**
     * Get default control variant for fallback scenarios
     */
    private function getDefaultControlVariant(): array
    {
        return [
            'id' => 'control',
            'name' => 'Control (Default)',
            'weight' => 100,
            'component_overrides' => [],
        ];
    }

    /**
     * Get current audience from request context
     */
    private function getCurrentAudience(): string
    {
        return request()->header('X-Audience', 'unknown');
    }
}
