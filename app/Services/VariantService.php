<?php

namespace App\Services;

use App\Models\Template;
use App\Models\TemplateVariant;
use App\Models\TemplateAbTest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Variant Service
 *
 * Core business logic for A/B testing functionality, traffic splitting, and statistical analysis.
 * Provides comprehensive template variant management with tenant isolation and performance optimization.
 */
class VariantService
{
    /**
     * Cache keys and durations
     */
    private const CACHE_PREFIX = 'variants_';
    private const CACHE_DURATION = 300; // 5 minutes
    private const TRAFFIC_CACHE_DURATION = 60; // 1 minute

    /**
     * Traffic splitting algorithms
     */
    private const DISTRIBUTION_METHODS = [
        'even',
        'weighted',
        'random',
        'percentage_based',
    ];

    /**
     * Get all variants for a template with optional filtering
     *
     * @param int $templateId
     * @param array $filters Available filters: is_active, is_control, has_significance
     * @param array $options Pagination and sorting options
     * @return LengthAwarePaginator|EloquentCollection
     */
    public function getTemplateVariants(int $templateId, array $filters = [], array $options = []): LengthAwarePaginator|EloquentCollection
    {
        $query = TemplateVariant::forTemplate($templateId)->active();

        // Apply filters
        $query = $this->applyFilters($query, $filters);

        // Apply sorting
        $query = $this->applySorting($query, $options['sort'] ?? 'name');

        // Apply pagination or return collection
        if (isset($options['paginate']) && $options['paginate'] === true) {
            return $query->paginate($options['per_page'] ?? 15);
        }

        return $query->get();
    }

    /**
     * Get active A/B test for a template
     *
     * @param int $templateId
     * @return TemplateAbTest|null
     */
    public function getActiveTestForTemplate(int $templateId): ?TemplateAbTest
    {
        return TemplateAbTest::forTenant($this->getCurrentTenantId())
            ->whereHas('variants', function ($query) use ($templateId) {
                $query->where('template_id', $templateId)
                      ->active();
            })
            ->running()
            ->first();
    }

    /**
     * Split traffic between variants using specified algorithm
     *
     * @param TemplateAbTest $test
     * @param string $userIdentifier Unique identifier for traffic splitting consistency
     * @return TemplateVariant|null
     */
    public function splitTraffic(TemplateAbTest $test, string $userIdentifier): ?TemplateVariant
    {
        $cacheKey = self::CACHE_PREFIX . "traffic_split_{$test->id}_{$userIdentifier}";

        return Cache::remember($cacheKey, self::TRAFFIC_CACHE_DURATION, function () use ($test, $userIdentifier) {
            return $this->performTrafficSplit($test, $userIdentifier);
        });
    }

    /**
     * Perform the actual traffic splitting logic
     */
    private function performTrafficSplit(TemplateAbTest $test, string $userIdentifier): ?TemplateVariant
    {
        $variants = $test->getActiveVariants();

        if ($variants->isEmpty()) {
            Log::warning("No active variants found for test", ['test_id' => $test->id]);
            return null;
        }

        $distributionMethod = $test->distribution_method ?? 'even';

        return match ($distributionMethod) {
            'weighted' => $this->weightedTrafficDistribution($variants, $userIdentifier),
            'random' => $this->randomTrafficDistribution($variants),
            'percentage_based' => $this->percentageBasedDistribution($variants, $userIdentifier),
            default => $this->evenTrafficDistribution($variants, $userIdentifier),
        };
    }

    /**
     * Even distribution algorithm - consistent hashing
     */
    private function evenTrafficDistribution(EloquentCollection $variants, string $userIdentifier): ?TemplateVariant
    {
        $variantCount = $variants->count();

        if ($variantCount === 0) return null;

        $hash = crc32($userIdentifier) % $variantCount;
        $selectedVariant = $variants->values()->get($hash);

        if ($selectedVariant) {
            $selectedVariant->recordImpression();
        }

        return $selectedVariant;
    }

    /**
     * Weighted distribution algorithm
     */
    private function weightedTrafficDistribution(EloquentCollection $variants, string $userIdentifier): ?TemplateVariant
    {
        $distribution = $this->getDistributionFromPivot($variants);
        $totalWeight = array_sum($distribution);
        $hash = crc32($userIdentifier) % $totalWeight;

        $cumulativeWeight = 0;
        foreach ($distribution as $variantId => $weight) {
            $cumulativeWeight += $weight;
            if ($hash < $cumulativeWeight) {
                $selectedVariant = $variants->find($variantId);
                if ($selectedVariant) {
                    $selectedVariant->recordImpression();
                }
                return $selectedVariant;
            }
        }

        return null;
    }

    /**
     * Random distribution algorithm
     */
    private function randomTrafficDistribution(EloquentCollection $variants): ?TemplateVariant
    {
        $selectedVariant = $variants->random();

        if ($selectedVariant) {
            $selectedVariant->recordImpression();
        }

        return $selectedVariant;
    }

    /**
     * Percentage-based distribution algorithm
     */
    private function percentageBasedDistribution(EloquentCollection $variants, string $userIdentifier): ?TemplateVariant
    {
        $hash = crc32($userIdentifier) % 100;

        $cumulativePercentage = 0;
        foreach ($variants as $variant) {
            $percentage = $this->getVariantTrafficPercentage($variant) ?? (100 / $variants->count());
            $cumulativePercentage += $percentage;

            if ($hash < $cumulativePercentage) {
                $variant->recordImpression();
                return $variant;
            }
        }

        return null;
    }

    /**
     * Create a new A/B test for a template
     *
     * @param array $testData
     * @param Template $template
     * @return TemplateAbTest
     */
    public function createAbTest(array $testData, Template $template): TemplateAbTest
    {
        // Create test
        $test = TemplateAbTest::create(array_merge($testData, [
            'tenant_id' => $this->getCurrentTenantId(),
            'status' => 'draft',
        ]));

        // Create control variant from template
        $controlVariant = TemplateVariant::create([
            'tenant_id' => $this->getCurrentTenantId(),
            'template_id' => $template->id,
            'variant_name' => 'Control',
            'custom_structure' => [],
            'is_control' => true,
            'is_active' => true,
            'created_by' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        // Attach control variant to test
        $test->variants()->attach($controlVariant->id, [
            'traffic_weight' => 50,
            'is_control' => true
        ]);

        return $test;
    }

    /**
     * Add variant to an A/B test
     *
     * @param TemplateAbTest $test
     * @param array $variantData
     * @return TemplateVariant
     */
    public function addVariantToTest(TemplateAbTest $test, array $variantData): TemplateVariant
    {
        $variant = TemplateVariant::create(array_merge($variantData, [
            'tenant_id' => $this->getCurrentTenantId(),
            'is_control' => false,
            'is_active' => true,
            'created_by' => \Illuminate\Support\Facades\Auth::id(),
        ]));

        // Attach to test with default weight
        $test->variants()->attach($variant->id, [
            'traffic_weight' => $this->calculateDefaultWeight($test),
            'is_control' => false
        ]);

        return $variant;
    }

    /**
     * Record a conversion for a variant
     *
     * @param int $variantId
     * @param array $conversionData Additional conversion data
     * @return bool
     */
    public function recordConversion(int $variantId, array $conversionData = []): bool
    {
        try {
            $variant = TemplateVariant::findOrFail($variantId);
            $variant->recordConversion();

            // Clear related caches
            $this->clearVariantCache($variant);

            // Log conversion event
            Log::info('Variant conversion recorded', [
                'variant_id' => $variant->id,
                'variant_name' => $variant->name,
                'test_data' => $conversionData,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to record variant conversion', [
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Run statistical analysis on A/B test results
     *
     * @param TemplateAbTest $test
     * @return array
     */
    public function analyzeTestResults(TemplateAbTest $test): array
    {
        $variants = $test->getActiveVariants();

        $results = [
            'test_id' => $test->id,
            'total_impressions' => 0,
            'total_conversions' => 0,
            'variants' => [],
            'has_statistical_significance' => false,
            'recommended_winner' => null,
            'confidence_intervals' => [],
        ];

        foreach ($variants as $variant) {
            $conversionRate = $variant->conversion_rate;
            $impressions = $variant->impressions;

            $results['total_impressions'] += $impressions;
            $results['total_conversions'] += $variant->conversions;
            $results['overall_conversion_rate'] = $results['total_impressions'] > 0
                ? ($results['total_conversions'] / $results['total_impressions']) * 100
                : 0;

            $results['variants'][] = [
                'variant_id' => $variant->id,
                'variant_name' => $variant->variant_name,
                'impressions' => $impressions,
                'conversions' => $variant->conversions,
                'conversion_rate' => $conversionRate,
                'statistical_significance' => $variant->statistical_significance,
                'performance_comparison' => $variant->getPerformanceComparison(),
            ];
        }

        $results['has_statistical_significance'] = $this->calculateStatisticalSignificance($variants);
        $results['recommended_winner'] = $this->determineWinner($variants);

        return $results;
    }

    /**
     * Apply filters to variant query
     */
    private function applyFilters($query, array $filters)
    {
        foreach ($filters as $filter => $value) {
            switch ($filter) {
                case 'is_active':
                    if ($value !== null) {
                        $query->where('is_active', $value);
                    }
                    break;
                case 'is_control':
                    if ($value !== null) {
                        $query->where('is_control', $value);
                    }
                    break;
                case 'has_significance':
                    if ($value) {
                        $query->where('statistical_significance', '>=', 95);
                    }
                    break;
                case 'conversion_rate_gt':
                    $query->where('conversion_rate', '>', $value);
                    break;
            }
        }

        return $query;
    }

    /**
     * Apply sorting to query
     */
    private function applySorting($query, string $sort)
    {
        [$field, $direction] = explode(':', $sort . ':asc');
        $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'asc';

        $allowedFields = [
            'variant_name',
            'impressions',
            'conversions',
            'conversion_rate',
            'created_at',
        ];

        if (in_array($field, $allowedFields)) {
            $query->orderBy($field, $direction);
        }

        return $query;
    }

    /**
     * Get distribution from pivot table
     */
    private function getDistributionFromPivot(EloquentCollection $variants): array
    {
        $distribution = [];

        foreach ($variants as $variant) {
            // Get traffic weight from pivot table
            $pivotData = $variant->pivot ?? [];
            $distribution[$variant->id] = $pivotData['traffic_weight'] ?? 50;
        }

        return $distribution;
    }

    /**
     * Get variant traffic percentage
     */
    private function getVariantTrafficPercentage(TemplateVariant $variant): ?float
    {
        $pivotData = $variant->pivot ?? [];
        return $pivotData['traffic_weight'] ?? null;
    }

    /**
     * Calculate default weight for new variants
     */
    private function calculateDefaultWeight(TemplateAbTest $test): int
    {
        $variantCount = $test->variants()->count();

        if ($variantCount <= 1) {
            return 50; // First variant after control
        }

        return (int) (100 / ($variantCount + 1)); // Divide remaining traffic
    }

    /**
     * Calculate statistical significance for variants
     */
    private function calculateStatisticalSignificance(EloquentCollection $variants): bool
    {
        $controlVariant = $variants->where('is_control', true)->first();

        if (!$controlVariant) {
            return false;
        }

        // Simple significance check - in production, use proper statistical tests
        foreach ($variants as $variant) {
            if (!$variant->is_control && $variant->impressions >= 1000) {
                $improvement = (($variant->conversion_rate - $controlVariant->conversion_rate) / $controlVariant->conversion_rate) * 100;

                if (abs($improvement) > 5) { // 5% improvement threshold
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine the winning variant
     */
    private function determineWinner(EloquentCollection $variants): ?TemplateVariant
    {
        $controlVariant = $variants->where('is_control', true)->first();

        if (!$controlVariant) {
            return $variants->sortByDesc('conversion_rate')->first();
        }

        // Find variant with best improvement over control
        $bestVariant = null;
        $bestImprovement = -1;

        foreach ($variants as $variant) {
            if ($variant->is_control) continue;

            if ($controlVariant->conversion_rate > 0) {
                $improvement = (($variant->conversion_rate - $controlVariant->conversion_rate) / $controlVariant->conversion_rate) * 100;

                if ($improvement > $bestImprovement) {
                    $bestImprovement = $improvement;
                    $bestVariant = $variant;
                }
            }
        }

        return $bestVariant;
    }

    /**
     * Clear variant-related caches
     */
    private function clearVariantCache(TemplateVariant $variant): void
    {
        Cache::forget(self::CACHE_PREFIX . "traffic_split_*");
        Cache::forget(self::CACHE_PREFIX . "test_{$variant->abTests()->first()?->id}_*");
    }

    /**
     * Get current tenant ID
     */
    private function getCurrentTenantId(): string
    {
        try {
            return tenant() ? tenant()->id : 'default';
        } catch (\Exception $e) {
            return 'default';
        }
    }
}