<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ABTest;
use App\Models\ABTestAssignment;
use App\Models\ABTestConversion;
use App\Models\AnalyticsEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * A/B Testing Service for managing experiments and tracking results
 *
 * Handles test creation, variant assignment, exposure tracking, and conversion analysis
 * with tenant isolation and statistical significance calculation.
 */
class ABTestingService extends BaseService
{
    /**
     * Create a new A/B test
     *
     * @param array{name: string, description?: string, variants: array, audience_criteria?: array, goal_event: string} $data
     * @return string Test ID
     */
    public function createTest(array $data): string
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if (!$tenantId) {
            throw new \RuntimeException('No tenant context available');
        }

        $test = ABTest::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'variants' => $data['variants'],
            'distribution' => $this->calculateDistribution($data['variants']),
            'status' => 'active',
            'started_at' => now(),
            'goal_metric' => $data['goal_event'],
            'target_audience' => $data['audience_criteria']['audience'] ?? null,
        ]);

        Log::info('A/B test created', [
            'test_id' => $test->id,
            'tenant_id' => $tenantId,
            'name' => $data['name'],
        ]);

        return (string) $test->id;
    }

    /**
     * Get test by ID
     */
    public function getTest(int $id): ?ABTest
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if (!$tenantId) {
            return null;
        }

        return ABTest::where('id', $id)->first();
    }

    /**
     * Update test
     */
    public function updateTest(int $id, array $data): bool
    {
        $test = $this->getTest($id);
        if (!$test) {
            return false;
        }

        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }
        if (isset($data['variants'])) {
            $updateData['variants'] = $data['variants'];
            $updateData['distribution'] = $this->calculateDistribution($data['variants']);
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        return $test->update($updateData);
    }

    /**
     * Delete test
     */
    public function deleteTest(int $id): bool
    {
        $test = $this->getTest($id);
        if (!$test) {
            return false;
        }

        return $test->delete();
    }

    /**
     * Assign variant to user/session
     *
     * @param string $userIdOrSessionId User ID or session ID
     * @param int $testId Test ID
     * @return string Variant name
     */
    public function assignVariant(string $userIdOrSessionId, int $testId): string
    {
        $test = $this->getTest($testId);
        if (!$test || $test->status !== 'active') {
            return 'control';
        }

        // Check cache first for consistent assignment
        $cacheKey = "ab_assignment_{$testId}_{$userIdOrSessionId}";
        $cachedVariant = Cache::get($cacheKey);
        if ($cachedVariant) {
            return $cachedVariant;
        }

        // Generate hash for deterministic assignment
        $hash = crc32($userIdOrSessionId . $testId) & 0x7FFFFFFF;
        $variant = $this->selectVariantByHash($hash, $test->variants, $test->distribution);

        // Cache assignment for performance
        Cache::put($cacheKey, $variant, 86400); // 24 hours

        // Record assignment in database
        ABTestAssignment::create([
            'ab_test_id' => $testId,
            'user_id' => is_numeric($userIdOrSessionId) ? (int) $userIdOrSessionId : null,
            'session_id' => $userIdOrSessionId,
            'variant' => $variant,
            'assigned_at' => now(),
        ]);

        return $variant;
    }

    /**
     * Get test results with metrics and statistical significance
     *
     * @param int $testId Test ID
     * @param array{start_date?: string, end_date?: string} $dateRange
     * @return array{test: ABTest, variants: array, overall_significance: bool}
     */
    public function getResults(int $testId, array $dateRange = []): array
    {
        $test = $this->getTest($testId);
        if (!$test) {
            return ['test' => null, 'variants' => [], 'overall_significance' => false];
        }

        $startDate = isset($dateRange['start_date']) ? Carbon::parse($dateRange['start_date']) : $test->started_at;
        $endDate = isset($dateRange['end_date']) ? Carbon::parse($dateRange['end_date']) : now();

        $variants = [];
        $totalImpressions = 0;
        $totalConversions = 0;

        foreach ($test->variants as $variant) {
            $variantName = $variant['name'];
            $impressions = $this->getImpressions($testId, $variantName, $startDate, $endDate);
            $conversions = $this->getConversions($testId, $variantName, $startDate, $endDate);

            $conversionRate = $impressions > 0 ? ($conversions / $impressions) * 100 : 0;

            $variants[$variantName] = [
                'name' => $variantName,
                'impressions' => $impressions,
                'conversions' => $conversions,
                'conversion_rate' => round($conversionRate, 2),
            ];

            $totalImpressions += $impressions;
            $totalConversions += $conversions;
        }

        $overallSignificance = $this->calculateOverallSignificance($variants);

        return [
            'test' => $test,
            'variants' => $variants,
            'overall_significance' => $overallSignificance,
        ];
    }

    /**
     * Record exposure (impression) for A/B test
     */
    public function recordExposure(int $eventId): void
    {
        $event = AnalyticsEvent::find($eventId);
        if (!$event || !isset($event->properties['ab_variant'])) {
            return;
        }

        $variant = $event->properties['ab_variant'];
        $testId = $event->properties['ab_test_id'] ?? null;

        if (!$testId) {
            return;
        }

        // Update cache for quick access
        $cacheKey = "ab_impressions_{$testId}_{$variant}_" . now()->format('Y-m-d');
        $impressions = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $impressions + 1, 86400);

        Log::debug('A/B test exposure recorded', [
            'event_id' => $eventId,
            'test_id' => $testId,
            'variant' => $variant,
        ]);
    }

    /**
     * Record conversion for A/B test
     */
    public function recordConversion(int $eventId): void
    {
        $event = AnalyticsEvent::find($eventId);
        if (!$event || !isset($event->properties['ab_variant'])) {
            return;
        }

        $variant = $event->properties['ab_variant'];
        $testId = $event->properties['ab_test_id'] ?? null;

        if (!$testId) {
            return;
        }

        // Check if this matches the goal event
        $test = $this->getTest($testId);
        if (!$test || $event->event_type !== $test->goal_metric) {
            return;
        }

        // Record conversion
        ABTestConversion::create([
            'ab_test_id' => $testId,
            'variant' => $variant,
            'user_id' => $event->user_id,
            'session_id' => $event->session_id,
            'event_id' => $eventId,
            'converted_at' => $event->occurred_at,
        ]);

        // Update cache
        $cacheKey = "ab_conversions_{$testId}_{$variant}_" . now()->format('Y-m-d');
        $conversions = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $conversions + 1, 86400);

        Log::debug('A/B test conversion recorded', [
            'event_id' => $eventId,
            'test_id' => $testId,
            'variant' => $variant,
            'goal' => $test->goal_metric,
        ]);
    }

    /**
     * Calculate distribution array from variants
     */
    private function calculateDistribution(array $variants): array
    {
        $totalWeight = array_sum(array_column($variants, 'weight'));
        $distribution = [];

        foreach ($variants as $variant) {
            $distribution[$variant['name']] = $totalWeight > 0 ? $variant['weight'] / $totalWeight : 0;
        }

        return $distribution;
    }

    /**
     * Select variant based on hash and distribution
     */
    private function selectVariantByHash(int $hash, array $variants, array $distribution): string
    {
        $randomValue = $hash % 1000000; // Use large modulus for better distribution
        $cumulative = 0;

        foreach ($distribution as $variantName => $probability) {
            $cumulative += $probability * 1000000;
            if ($randomValue < $cumulative) {
                return $variantName;
            }
        }

        // Fallback to first variant
        return array_key_first($distribution) ?? 'control';
    }

    /**
     * Get impressions for variant in date range
     */
    private function getImpressions(int $testId, string $variant, Carbon $startDate, Carbon $endDate): int
    {
        // Try cache first
        $cacheKey = "ab_impressions_{$testId}_{$variant}_" . $startDate->format('Y-m-d');
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Query database
        $count = AnalyticsEvent::whereBetween('occurred_at', [$startDate, $endDate])
            ->where('event_type', 'page_view') // Assuming impressions are page views
            ->whereJsonContains('properties->ab_test_id', $testId)
            ->whereJsonContains('properties->ab_variant', $variant)
            ->count();

        Cache::put($cacheKey, $count, 3600); // Cache for 1 hour
        return $count;
    }

    /**
     * Get conversions for variant in date range
     */
    private function getConversions(int $testId, string $variant, Carbon $startDate, Carbon $endDate): int
    {
        // Try cache first
        $cacheKey = "ab_conversions_{$testId}_{$variant}_" . $startDate->format('Y-m-d');
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Query database
        $count = ABTestConversion::where('ab_test_id', $testId)
            ->where('variant', $variant)
            ->whereBetween('converted_at', [$startDate, $endDate])
            ->count();

        Cache::put($cacheKey, $count, 3600); // Cache for 1 hour
        return $count;
    }

    /**
     * Calculate overall statistical significance using chi-square test
     */
    private function calculateOverallSignificance(array $variants): bool
    {
        if (count($variants) < 2) {
            return false;
        }

        $totalImpressions = array_sum(array_column($variants, 'impressions'));
        $totalConversions = array_sum(array_column($variants, 'conversions'));

        if ($totalImpressions < 100 || $totalConversions < 10) {
            return false; // Not enough data
        }

        // Simple chi-square calculation for significance
        $expectedConversionRate = $totalConversions / $totalImpressions;
        $chiSquare = 0;

        foreach ($variants as $variant) {
            $expectedConversions = $variant['impressions'] * $expectedConversionRate;
            if ($expectedConversions > 0) {
                $chiSquare += pow($variant['conversions'] - $expectedConversions, 2) / $expectedConversions;
            }
        }

        // Chi-square critical value for 95% confidence with df = k-1
        $degreesOfFreedom = count($variants) - 1;
        $criticalValue = $this->getChiSquareCriticalValue($degreesOfFreedom, 0.95);

        return $chiSquare > $criticalValue;
    }

    /**
     * Get chi-square critical value (simplified approximation)
     */
    private function getChiSquareCriticalValue(int $df, float $confidence): float
    {
        // Simplified critical values for common degrees of freedom
        $criticalValues = [
            1 => 3.84,  // 95% confidence
            2 => 5.99,
            3 => 7.81,
            4 => 9.49,
            5 => 11.07,
        ];

        return $criticalValues[$df] ?? 9.49; // Default to df=4
    }
}
