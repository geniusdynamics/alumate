<?php

namespace App\Services;

use App\Models\TemplateAbTest;
use App\Models\Template;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * A/B Testing Service
 *
 * Core business logic for managing A/B tests, traffic distribution,
 * and statistical analysis.
 */
class AbTestService
{
    /**
     * Cache keys and durations
     */
    private const CACHE_PREFIX = 'ab_tests_';
    private const CACHE_DURATION = 300; // 5 minutes

    /**
     * Create a new A/B test
     */
    public function createAbTest(array $data): TemplateAbTest
    {
        // Validate template exists and is active
        $template = Template::findOrFail($data['template_id']);

        if (!$template->is_active) {
            throw new \InvalidArgumentException('Cannot create A/B test for inactive template');
        }

        // Validate variants structure
        $this->validateVariants($data['variants']);

        $abTest = TemplateAbTest::create($data);

        // Clear relevant caches
        $this->clearTemplateCache($template->id);

        Log::info('A/B test created', [
            'ab_test_id' => $abTest->id,
            'template_id' => $template->id,
            'variant_count' => count($data['variants'])
        ]);

        return $abTest;
    }

    /**
     * Get A/B test by ID
     */
    public function getAbTestById(int $abTestId): TemplateAbTest
    {
        return TemplateAbTest::with(['template', 'events'])->findOrFail($abTestId);
    }

    /**
     * Get all A/B tests for a template
     */
    public function getAbTestsForTemplate(int $templateId): Collection
    {
        $cacheKey = self::CACHE_PREFIX . "template_{$templateId}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($templateId) {
            return TemplateAbTest::where('template_id', $templateId)
                ->with('template')
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Get active A/B tests
     */
    public function getActiveAbTests(): Collection
    {
        $cacheKey = self::CACHE_PREFIX . 'active';

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
            return TemplateAbTest::active()
                ->with('template')
                ->get();
        });
    }

    /**
     * Start an A/B test
     */
    public function startAbTest(int $abTestId): bool
    {
        $abTest = $this->getAbTestById($abTestId);

        if ($abTest->start()) {
            $this->clearTemplateCache($abTest->template_id);
            Log::info('A/B test started', ['ab_test_id' => $abTestId]);
            return true;
        }

        return false;
    }

    /**
     * Stop an A/B test
     */
    public function stopAbTest(int $abTestId): bool
    {
        $abTest = $this->getAbTestById($abTestId);

        if ($abTest->stop()) {
            $this->clearTemplateCache($abTest->template_id);
            Log::info('A/B test stopped', ['ab_test_id' => $abTestId]);
            return true;
        }

        return false;
    }

    /**
     * Get variant for session
     */
    public function getVariantForSession(int $templateId, string $sessionId): ?array
    {
        // Check if there's an active A/B test for this template
        $activeTest = $this->getActiveTestForTemplate($templateId);

        if (!$activeTest) {
            return null; // No active test, use original template
        }

        $variantId = $activeTest->getVariantForSession($sessionId);
        $variant = collect($activeTest->variants)->firstWhere('id', $variantId);

        // Record page view event
        $activeTest->recordEvent($variantId, 'page_view', $sessionId);

        return $variant;
    }

    /**
     * Record conversion event
     */
    public function recordConversion(int $templateId, string $sessionId, string $eventType = 'conversion'): bool
    {
        $activeTest = $this->getActiveTestForTemplate($templateId);

        if (!$activeTest) {
            return false; // No active test
        }

        $variantId = $activeTest->getVariantForSession($sessionId);
        $activeTest->recordEvent($variantId, $eventType, $sessionId);

        Log::info('A/B test conversion recorded', [
            'ab_test_id' => $activeTest->id,
            'variant_id' => $variantId,
            'event_type' => $eventType,
            'session_id' => $sessionId
        ]);

        return true;
    }

    /**
     * Get A/B test results
     */
    public function getAbTestResults(int $abTestId): array
    {
        $abTest = $this->getAbTestById($abTestId);

        if ($abTest->status !== 'completed') {
            // Calculate current results for active tests
            $results = $abTest->calculateResults();
        } else {
            $results = $abTest->results ?? [];
        }

        return array_merge($results, [
            'ab_test' => $abTest->toArray(),
            'is_running' => $abTest->isRunning(),
            'has_significance' => $abTest->hasStatisticalSignificance(),
            'winning_variant' => $abTest->getWinningVariant()
        ]);
    }

    /**
     * Get active A/B test for template
     */
    private function getActiveTestForTemplate(int $templateId): ?TemplateAbTest
    {
        $cacheKey = self::CACHE_PREFIX . "active_template_{$templateId}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($templateId) {
            return TemplateAbTest::where('template_id', $templateId)
                ->active()
                ->first();
        });
    }

    /**
     * Validate variants structure
     */
    private function validateVariants(array $variants): void
    {
        if (count($variants) < 2) {
            throw new \InvalidArgumentException('A/B test must have at least 2 variants');
        }

        if (count($variants) > 10) {
            throw new \InvalidArgumentException('A/B test cannot have more than 10 variants');
        }

        $variantIds = [];
        foreach ($variants as $variant) {
            if (!isset($variant['id'], $variant['name'])) {
                throw new \InvalidArgumentException('Each variant must have id and name');
            }

            if (in_array($variant['id'], $variantIds)) {
                throw new \InvalidArgumentException('Variant IDs must be unique');
            }

            $variantIds[] = $variant['id'];
        }
    }

    /**
     * Clear template-related caches
     */
    private function clearTemplateCache(int $templateId): void
    {
        Cache::forget(self::CACHE_PREFIX . "template_{$templateId}");
        Cache::forget(self::CACHE_PREFIX . "active_template_{$templateId}");
        Cache::forget(self::CACHE_PREFIX . 'active');
    }

    /**
     * Get A/B test statistics overview
     */
    public function getAbTestStatistics(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'statistics';

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
            $totalTests = TemplateAbTest::count();
            $activeTests = TemplateAbTest::active()->count();
            $completedTests = TemplateAbTest::completed()->count();

            $avgConversionImprovement = TemplateAbTest::completed()
                ->whereNotNull('results')
                ->get()
                ->map(function ($test) {
                    $results = $test->results;
                    if (!$results || !isset($results['variants'])) {
                        return 0;
                    }

                    $conversionRates = array_column($results['variants'], 'conversion_rate');
                    $maxRate = max($conversionRates);
                    $minRate = min($conversionRates);

                    return $maxRate - $minRate;
                })
                ->avg();

            return [
                'total_tests' => $totalTests,
                'active_tests' => $activeTests,
                'completed_tests' => $completedTests,
                'avg_conversion_improvement' => round($avgConversionImprovement, 2),
                'total_conversions_recorded' => DB::table('ab_test_events')
                    ->where('event_type', 'conversion')
                    ->count()
            ];
        });
    }

    /**
     * Clean up old A/B test data
     */
    public function cleanupOldTests(int $daysOld = 90): int
    {
        $cutoffDate = now()->subDays($daysOld);

        $oldTests = TemplateAbTest::where('ended_at', '<', $cutoffDate)
            ->orWhere(function ($query) use ($cutoffDate) {
                $query->where('status', 'draft')
                      ->where('created_at', '<', $cutoffDate);
            })
            ->get();

        $deletedCount = 0;
        foreach ($oldTests as $test) {
            // Delete associated events first
            $test->events()->delete();
            $test->delete();
            $deletedCount++;
        }

        if ($deletedCount > 0) {
            Log::info('Cleaned up old A/B tests', ['deleted_count' => $deletedCount]);
        }

        return $deletedCount;
    }
}