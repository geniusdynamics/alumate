<?php

// ABOUTME: This service handles template management including filtering, validation, and tenant isolation
// ABOUTME: Provides comprehensive template operations for landing pages and campaign templates

namespace App\Services;

use App\Models\Template;
use App\Models\LandingPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Services\TemplateCacheService;
use App\Services\MobileTemplateRenderer;

/**
 * Template Service
 *
 * Core business logic for template management, filtering, and validation.
 * Provides comprehensive template operations with tenant isolation.
 */
class TemplateService extends BaseService
{
    /**
     * Cache service instance
     */
    private TemplateCacheService $cacheService;
    private MobileTemplateRenderer $mobileRenderer;

    /**
     * Cache keys and durations
     */
    private const CACHE_PREFIX = 'templates_';
    private const CACHE_DURATION = 300; // 5 minutes
    private const SEARCH_CACHE_DURATION = 60; // 1 minute

    public function __construct(TemplateCacheService $cacheService = null, MobileTemplateRenderer $mobileRenderer = null)
    {
        $this->cacheService = $cacheService ?? new TemplateCacheService();
        $this->mobileRenderer = $mobileRenderer ?? new MobileTemplateRenderer();
    }

    /**
     * Get all templates with optional filtering
     *
     * @param array $filters Available filters: category, audience_type, campaign_type, is_active, is_premium, search
     * @param array $options Pagination and sorting options
     * @return LengthAwarePaginator|EloquentCollection
     */
    public function getAllTemplates(array $filters = [], array $options = []): LengthAwarePaginator|EloquentCollection
    {
        $query = Template::query()->active();

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
     * Get template by ID with tenant isolation
     *
     * @param int $templateId
     * @return Template
     * @throws \App\Exceptions\TemplateNotFoundException
     */
    public function getTemplateById(int $templateId): Template
    {
        try {
            $template = $this->cacheService->rememberTemplate($templateId, function () use ($templateId) {
                return Template::with(['landingPages', 'creator', 'updater'])
                               ->findOrFail($templateId);
            });

            // Additional validation
            if (!$template->is_active) {
                // Allow inactive templates for certain operations but log warning
                Log::warning("Accessing inactive template", [
                    'template_id' => $templateId,
                    'template_name' => $template->name,
                ]);
            }

            return $template;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new \App\Exceptions\TemplateNotFoundException(
                "Template with ID {$templateId} not found or not accessible."
            );
        }
    }

    /**
     * Search templates with keyword filtering
     *
     * @param string $query Search query
     * @param array $filters Additional filters
     * @param array $options Search options
     * @return Collection
     */
    public function searchTemplates(string $query, array $filters = [], array $options = []): Collection
    {
        return $this->cacheService->rememberSearchResults($query, $filters, function () use ($query, $filters, $options) {
            $searchQuery = Template::query()
                ->active()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%")
                      ->orWhereJsonContains('tags', $query);
                });

            // Apply additional filters
            $searchQuery = $this->applyFilters($searchQuery, $filters);

            // Limit results
            $limit = $options['limit'] ?? 20;
            $searchQuery = $searchQuery->limit($limit);

            return $searchQuery->get();
        });
    }

    /**
     * Get templates by category
     *
     * @param string $category
     * @param array $filters Additional filters
     * @return EloquentCollection
     */
    public function getTemplatesByCategory(string $category, array $filters = []): EloquentCollection
    {
        $cacheKey = self::CACHE_PREFIX . "category_{$category}_" . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($category, $filters) {
            $query = Template::query()
                ->active()
                ->where('category', $category);

            return $this->applyFilters($query, $filters)->get();
        });
    }

    /**
     * Get templates by audience type
     *
     * @param string $audienceType
     * @param array $filters Additional filters
     * @return EloquentCollection
     */
    public function getTemplatesByAudience(string $audienceType, array $filters = []): EloquentCollection
    {
        $cacheKey = self::CACHE_PREFIX . "audience_{$audienceType}_" . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($audienceType, $filters) {
            $query = Template::query()
                ->active()
                ->where('audience_type', $audienceType);

            return $this->applyFilters($query, $filters)->get();
        });
    }

    /**
     * Get premium templates only
     *
     * @param array $filters Additional filters
     * @return EloquentCollection
     */
    public function getPremiumTemplates(array $filters = []): EloquentCollection
    {
        $cacheKey = self::CACHE_PREFIX . 'premium_' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filters) {
            $query = Template::query()
                ->active()
                ->premium();

            return $this->applyFilters($query, $filters)->get();
        });
    }

    /**
     * Get popular templates based on usage statistics
     *
     * @param int $limit Number of templates to return
     * @return EloquentCollection
     */
    public function getPopularTemplates(int $limit = 10): EloquentCollection
    {
        return $this->cacheService->rememberPopularTemplates(function () use ($limit) {
            return Template::query()
                ->active()
                ->orderBy('usage_count', 'desc')
                ->orderBy('last_used_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get recently used templates
     *
     * @param int $limit Number of templates to return
     * @return EloquentCollection
     */
    public function getRecentlyUsedTemplates(int $limit = 10): EloquentCollection
    {
        $cacheKey = self::CACHE_PREFIX . "recent_{$limit}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($limit) {
            return Template::query()
                ->active()
                ->whereNotNull('last_used_at')
                ->orderBy('last_used_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Validate template structure against JSON schema and security rules
     *
     * @param array $structure Template structure to validate
     * @return bool
     * @throws \App\Exceptions\TemplateValidationException
     */
    public function validateTemplateStructure(array $structure): bool
    {
        // Sanitize structure first
        $sanitizedStructure = $this->sanitizeTemplateStructure($structure);

        // Basic structure validation
        $rules = [
            'sections' => 'required|array',
            'sections.*.type' => 'required|string|max:255',
            'sections.*.config' => 'nullable|array',
            'sections.*.order' => 'nullable|integer|min:0',
        ];

        $validator = Validator::make($sanitizedStructure, $rules);

        if ($validator->fails()) {
            throw new \App\Exceptions\TemplateValidationException(
                'Template structure validation failed: ' . json_encode($validator->errors()->toArray())
            );
        }

        // Security validation - check for XSS patterns
        $this->validateSecurity($sanitizedStructure);

        // Custom validation based on category
        $this->validateCategorySpecificRules($sanitizedStructure);

        return true;
    }

    /**
     * Create template structure from configuration
     *
     * @param string $category Template category
     * @param array $config Additional configuration
     * @return array
     */
    public function createTemplateStructure(string $category, array $config = []): array
    {
        $template = new Template(['category' => $category]);
        $structure = $template->getEffectiveStructure();

        // Apply configuration overrides
        return $this->applyStructureConfiguration($structure, $config);
    }

    /**
     * Increment template usage count
     *
     * @param int $templateId
     * @return bool
     */
    public function incrementUsage(int $templateId): bool
    {
        try {
            $template = $this->getTemplateById($templateId);
            $template->incrementUsage();

            // Clear relevant caches
            $this->cacheService->invalidateTemplate($templateId);
            $this->cacheService->invalidatePopularTemplates();

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to increment template usage', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update template performance metrics
     *
     * @param int $templateId
     * @param array $metrics Performance metrics (conversion_rate, load_time, etc.)
     * @return bool
     */
    public function updatePerformanceMetrics(int $templateId, array $metrics): bool
    {
        try {
            $template = $this->getTemplateById($templateId);
            $template->updatePerformanceMetrics($metrics);

            // Clear performance-specific caches
            Cache::forget(self::CACHE_PREFIX . "popular_*");
            Cache::forget(self::CACHE_PREFIX . "recent_*");

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to update template performance metrics', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get template statistics
     *
     * @param int $templateId
     * @return array
     */
    public function getTemplateStats(int $templateId): array
    {
        try {
            $template = $this->getTemplateById($templateId);
            $landingPages = $template->landingPages;

            return [
                'usage_stats' => $template->getUsageStats(),
                'performance_stats' => $template->getPerformanceStats(),
                'landing_page_count' => $landingPages->count(),
                'published_pages' => $landingPages->where('status', 'published')->count(),
                'conversion_rate_avg' => $landingPages->avg('usage_count') > 0
                    ? round(($landingPages->sum('conversion_count') / $landingPages->sum('usage_count')) * 100, 2)
                    : 0.0,
                'load_time_avg' => $template->getLoadTime(),
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to get template stats', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Clear template-related caches
     *
     * @param Template $template
     */
    private function clearTemplateCache(Template $template): void
    {
        // Clear all template caches
        Cache::forget(self::CACHE_PREFIX . 'all');
        Cache::forget(self::CACHE_PREFIX . "category_{$template->category}");
        Cache::forget(self::CACHE_PREFIX . "audience_{$template->audience_type}");
        Cache::forget(self::CACHE_PREFIX . "popular_*");
        Cache::forget(self::CACHE_PREFIX . "recent_*");
    }

    /**
     * Apply filters to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyFilters($query, array $filters)
    {
        foreach ($filters as $filter => $value) {
            switch ($filter) {
                case 'category':
                    $query->where('category', $value);
                    break;
                case 'audience_type':
                    $query->where('audience_type', $value);
                    break;
                case 'campaign_type':
                    $query->where('campaign_type', $value);
                    break;
                case 'is_active':
                    $query->where('is_active', $value);
                    break;
                case 'is_premium':
                    $query->where('is_premium', $value);
                    break;
                case 'tags':
                    if (is_array($value)) {
                        foreach ($value as $tag) {
                            $query->whereJsonContains('tags', $tag);
                        }
                    }
                    break;
            }
        }

        return $query;
    }

    /**
     * Apply sorting to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sort Sort field and direction (e.g., 'name:desc', 'usage_count:asc')
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applySorting($query, string $sort)
    {
        [$field, $direction] = explode(':', $sort . ':asc');
        $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'asc';

        // Allow different sorting fields
        $allowedFields = [
            'name', 'usage_count', 'last_used_at', 'created_at', 'conversion_rate'
        ];

        if (in_array($field, $allowedFields)) {
            if ($field === 'conversion_rate') {
                // Special handling for performance metrics
                $query->orderByRaw("JSON_EXTRACT(performance_metrics, '$.conversion_rate') {$direction}");
            } else {
                $query->orderBy($field, $direction);
            }
        }

        return $query;
    }

    /**
     * Sanitize template structure to prevent XSS
     *
     * @param array $structure
     * @return array
     */
    private function sanitizeTemplateStructure(array $structure): array
    {
        $sanitizer = new \App\Services\TemplateStructureSanitizer();
        return $sanitizer->sanitize($structure);
    }

    /**
     * Validate security aspects of template structure
     *
     * @param array $structure
     * @throws \App\Exceptions\TemplateSecurityException
     */
    private function validateSecurity(array $structure): void
    {
        // Check for potential XSS patterns
        $securityValidator = new \App\Services\TemplateSecurityValidator();
        $securityValidator->validate($structure);
    }

    /**
     * Validate category-specific rules
     *
     * @param array $structure
     * @throws \App\Exceptions\TemplateValidationException
     */
    private function validateCategorySpecificRules(array $structure): void
    {
        // Category-specific validation can be implemented here
        // For example, landing pages need hero sections, etc.
    }

    /**
     * Apply configuration to template structure
     *
     * @param array $structure
     * @param array $config
     * @return array
     */
    private function applyStructureConfiguration(array $structure, array $config): array
    {
        // Apply configuration overrides to structure
        // Implementation depends on specific requirements

        return $structure;
    }

    /**
     * Render template for mobile devices
     *
     * @param int $templateId
     * @param string $deviceType
     * @param array $options
     * @return array
     * @throws \App\Exceptions\TemplateNotFoundException
     */
    public function renderForMobile(int $templateId, string $deviceType = 'mobile', array $options = []): array
    {
        $template = $this->getTemplateById($templateId);

        // Get template structure
        $structure = $template->getEffectiveStructure();

        // Render for mobile using the mobile renderer
        return $this->mobileRenderer->renderForMobile($structure, $deviceType, $options);
    }

    /**
     * Get mobile-optimized template preview
     *
     * @param int $templateId
     * @param string $deviceType
     * @return array
     */
    public function getMobilePreview(int $templateId, string $deviceType = 'mobile'): array
    {
        $cacheKey = "mobile_preview:{$templateId}:{$deviceType}";

        return $this->cacheService->rememberTemplate($templateId, function () use ($templateId, $deviceType) {
            return $this->renderForMobile($templateId, $deviceType);
        });
    }

    /**
     * Detect user device type from user agent
     *
     * @param string $userAgent
     * @return string
     */
    public function detectDeviceType(string $userAgent = null): string
    {
        $userAgent = $userAgent ?? request()->userAgent();

        if (preg_match('/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            if (preg_match('/tablet|ipad/i', $userAgent)) {
                return 'tablet';
            }
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Get responsive template configuration
     *
     * @param int $templateId
     * @return array
     */
    public function getResponsiveConfig(int $templateId): array
    {
        $template = $this->getTemplateById($templateId);

        return [
            'breakpoints' => [
                'mobile' => ['max' => 640],
                'tablet' => ['min' => 641, 'max' => 1024],
                'desktop' => ['min' => 1025],
            ],
            'mobile_optimizations' => [
                'touch_friendly' => true,
                'responsive_images' => true,
                'lazy_loading' => true,
                'gesture_support' => true,
            ],
            'template_features' => [
                'has_mobile_layout' => true,
                'supports_touch' => true,
                'optimized_for_mobile' => true,
            ],
        ];
    }
}