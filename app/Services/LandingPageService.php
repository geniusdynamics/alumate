<?php

// ABOUTME: LandingPageService manages landing page lifecycle including creation, publishing, and analytics
// ABOUTME: Handles template-based creation, branding, form submissions, and performance tracking

namespace App\Services;

use App\Models\LandingPage;
use App\Models\LandingPageSubmission;
use App\Models\Lead;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LandingPageService extends BaseService
{
    private LeadManagementService $leadService;

    private const CACHE_PREFIX = 'landing_pages_';

    private const CACHE_DURATION = 300; // 5 minutes

    public function __construct(LeadManagementService $leadService)
    {
        $this->leadService = $leadService;
    }

    /**
     * Create a new landing page
     */
    public function createLandingPage(array $data): LandingPage
    {
        $validated = $this->validateLandingPageData($data);

        $page = LandingPage::create([
            'template_id' => $validated['template_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'audience_type' => $validated['audience_type'] ?? $validated['target_audience'] ?? 'individual',
            'campaign_type' => $validated['campaign_type'] ?? 'onboarding',
            'category' => $validated['category'] ?? 'individual',
            'config' => $validated['config'] ?? ['sections' => []],
            'brand_config' => $validated['brand_config'] ?? [],
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'seo_keywords' => $validated['seo_keywords'] ?? [],
            'custom_css' => $validated['custom_css'] ?? null,
            'custom_js' => $validated['custom_js'] ?? null,
        ]);

        Log::info('Landing page created', [
            'page_id' => $page->id,
            'name' => $page->name,
        ]);

        return $page;
    }

    /**
     * Create a landing page from a template
     */
    public function createFromTemplate(int $templateId, array $customizations = []): LandingPage
    {
        $template = Template::find($templateId);

        if (! $template) {
            throw new \App\Exceptions\TemplateNotFoundException(
                "Template with ID {$templateId} not found."
            );
        }

        $templateStructure = $template->structure ?? [];
        $templateConfig = $template->default_config ?? [];

        $data = array_merge([
            'template_id' => $templateId,
            'name' => $customizations['name'] ?? $template->name.' - Copy',
            'description' => $customizations['description'] ?? $template->description,
            'audience_type' => $customizations['audience_type'] ?? $template->audience_type,
            'campaign_type' => $customizations['campaign_type'] ?? $template->campaign_type,
            'category' => $customizations['category'] ?? 'individual',
            'config' => $this->applyCustomizations($templateConfig, $customizations['config'] ?? []),
        ], $customizations);

        return $this->createLandingPage($data);
    }

    /**
     * Update a landing page
     */
    public function updateLandingPage(int $pageId, array $data): LandingPage
    {
        $page = LandingPage::findOrFail($pageId);

        $validated = $this->validateLandingPageData($data, $pageId);

        $page->update($validated);

        // Invalidate cache
        Cache::forget(self::CACHE_PREFIX.'page_'.$pageId);
        Cache::forget(self::CACHE_PREFIX.'metrics_'.$pageId);

        Log::info('Landing page updated', ['page_id' => $pageId]);

        return $page->fresh();
    }

    /**
     * Delete a landing page
     */
    public function deleteLandingPage(int $pageId): bool
    {
        $page = LandingPage::findOrFail($pageId);

        // Invalidate cache
        Cache::forget(self::CACHE_PREFIX.'page_'.$pageId);
        Cache::forget(self::CACHE_PREFIX.'metrics_'.$pageId);

        $result = $page->delete();

        Log::info('Landing page deleted', ['page_id' => $pageId]);

        return $result;
    }

    /**
     * Duplicate a landing page
     */
    public function duplicate(int $pageId, array $overrides = []): LandingPage
    {
        $page = LandingPage::findOrFail($pageId);

        $newData = array_merge(
            $page->only([
                'template_id', 'description', 'config', 'brand_config',
                'audience_type', 'campaign_type', 'category',
                'seo_title', 'seo_description', 'seo_keywords',
                'custom_css', 'custom_js',
            ]),
            ['name' => $overrides['name'] ?? $page->name.' - Copy'],
            $overrides
        );

        return $this->createLandingPage($newData);
    }

    /**
     * Archive a landing page
     */
    public function archive(int $pageId): bool
    {
        $page = LandingPage::findOrFail($pageId);
        $page->archive();

        Cache::forget(self::CACHE_PREFIX.'page_'.$pageId);
        Cache::forget(self::CACHE_PREFIX.'metrics_'.$pageId);

        Log::info('Landing page archived', ['page_id' => $pageId]);

        return true;
    }

    /**
     * Publish a landing page
     */
    public function publishPage(int $pageId, array $options = []): bool
    {
        $page = LandingPage::findOrFail($pageId);

        // Validation
        if (empty($page->name)) {
            throw new \InvalidArgumentException('Landing page must have a name before publishing');
        }

        if ($page->isPublished()) {
            throw new \InvalidArgumentException('Landing page is already published');
        }

        $publishAt = $options['publish_at'] ?? now();

        $page->update([
            'status' => 'published',
            'published_at' => $publishAt,
            'version' => $page->version + 1,
            'public_url' => $this->generatePublicUrl($page),
            'preview_url' => $this->generatePreviewUrl($page),
        ]);

        Cache::forget(self::CACHE_PREFIX.'page_'.$pageId);

        Log::info('Landing page published', ['page_id' => $pageId]);

        return true;
    }

    /**
     * Unpublish a landing page
     */
    public function unpublishPage(int $pageId): bool
    {
        $page = LandingPage::findOrFail($pageId);
        $page->unpublish();

        Cache::forget(self::CACHE_PREFIX.'page_'.$pageId);

        Log::info('Landing page unpublished', ['page_id' => $pageId]);

        return true;
    }

    /**
     * Apply branding to a landing page
     */
    public function applyBranding(int $pageId, array $brandConfig, bool $validate = true): array
    {
        $page = LandingPage::findOrFail($pageId);

        if ($validate) {
            $this->validateBrandConfig($brandConfig);
        }

        $currentConfig = $page->config ?? [];
        $currentConfig['brand'] = $brandConfig;

        $page->update([
            'config' => $currentConfig,
            'brand_config' => array_merge($page->brand_config ?? [], $brandConfig),
        ]);

        Cache::forget(self::CACHE_PREFIX.'page_'.$pageId);

        return $currentConfig;
    }

    /**
     * Customize landing page content
     */
    public function customizeContent(int $pageId, array $customizations, bool $validate = true): array
    {
        $page = LandingPage::findOrFail($pageId);

        $currentConfig = $page->config ?? [];
        $updatedConfig = $this->applyCustomizations($currentConfig, $customizations);

        $updateData = ['config' => $updatedConfig];

        if (isset($customizations['seo_title'])) {
            $updateData['seo_title'] = $customizations['seo_title'];
        }
        if (isset($customizations['seo_description'])) {
            $updateData['seo_description'] = $customizations['seo_description'];
        }
        if (isset($customizations['seo_keywords'])) {
            $updateData['seo_keywords'] = $customizations['seo_keywords'];
        }

        $page->update($updateData);

        Cache::forget(self::CACHE_PREFIX.'page_'.$pageId);

        return $updatedConfig;
    }

    /**
     * Get performance metrics for a landing page
     */
    public function getPerformanceMetrics(int $pageId): array
    {
        return Cache::remember(self::CACHE_PREFIX.'metrics_'.$pageId, self::CACHE_DURATION, function () use ($pageId) {
            $page = LandingPage::find($pageId);

            if (! $page) {
                return [];
            }

            $analytics = $page->analytics;
            $submissions = $page->submissions;

            $pageViews = $analytics->where('event_type', 'page_view')->count();
            $uniqueVisitors = $analytics->where('event_type', 'page_view')
                ->pluck('session_id')
                ->unique()
                ->count();
            $conversionCount = $submissions->count();
            $conversionRate = $pageViews > 0
                ? round(($conversionCount / $pageViews) * 100, 2)
                : 0;

            $deviceBreakdown = $analytics->where('event_type', 'page_view')
                ->groupBy('device_type')
                ->map(fn ($group) => $group->count())
                ->toArray();

            return [
                'page_views' => $pageViews,
                'unique_visitors' => $uniqueVisitors,
                'conversion_count' => $conversionCount,
                'conversion_rate' => $conversionRate,
                'bounce_rate' => $this->calculateBounceRate($analytics),
                'device_breakdown' => $deviceBreakdown,
                'usage_count' => $page->usage_count,
            ];
        });
    }

    /**
     * Handle form submission on a landing page
     */
    public function handleFormSubmission(LandingPage $page, array $formData, Request $request): LandingPageSubmission
    {
        $submission = LandingPageSubmission::create([
            'landing_page_id' => $page->id,
            'form_name' => $formData['form_name'] ?? 'default',
            'form_data' => $formData,
            'utm_data' => $this->extractUtmData($request),
            'session_data' => [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->headers->get('referer'),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->headers->get('referer'),
            'status' => 'pending',
        ]);

        // Try to create a lead
        try {
            $leadData = [
                'first_name' => $formData['first_name'] ?? '',
                'last_name' => $formData['last_name'] ?? '',
                'email' => $formData['email'] ?? '',
                'phone' => $formData['phone'] ?? null,
                'company' => $formData['company'] ?? null,
                'job_title' => $formData['job_title'] ?? null,
                'lead_type' => $this->mapAudienceToLeadType($page->audience_type ?? 'individual'),
                'source' => 'landing_page',
                'utm_data' => $this->extractUtmData($request),
                'form_data' => $formData,
            ];

            $lead = $this->leadService->createLead($leadData);
            $submission->update(['lead_id' => $lead->id, 'status' => 'processed']);

            // Increment conversion count
            $page->incrementConversion();
        } catch (\Exception $e) {
            Log::warning('Lead creation failed for landing page submission', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);
            $submission->update(['status' => 'failed']);
        }

        // Increment usage count
        $page->incrementUsage();

        // Invalidate metrics cache
        Cache::forget(self::CACHE_PREFIX.'metrics_'.$page->id);

        return $submission;
    }

    /**
     * Generate public URL for a landing page
     */
    public function generatePublicUrl(LandingPage $page): string
    {
        $baseUrl = config('app.url', 'http://localhost:8080');

        return "{$baseUrl}/pages/{$page->slug}";
    }

    /**
     * Generate preview URL for a landing page
     */
    public function generatePreviewUrl(LandingPage $page): string
    {
        $baseUrl = config('app.url', 'http://localhost:8080');

        return "{$baseUrl}/pages/preview/{$page->id}";
    }

    /**
     * Get analytics data for a landing page
     */
    public function getAnalytics(LandingPage $page): array
    {
        return $page->getPerformanceStats();
    }

    /**
     * Increment usage count for a landing page
     */
    public function incrementUsage(LandingPage $page): void
    {
        $page->incrementUsage();
        Cache::forget(self::CACHE_PREFIX.'metrics_'.$page->id);
    }

    /**
     * Increment conversion count for a landing page
     */
    public function incrementConversion(LandingPage $page): void
    {
        $page->incrementConversion();
        Cache::forget(self::CACHE_PREFIX.'metrics_'.$page->id);
    }

    /**
     * Validate landing page data
     */
    private function validateLandingPageData(array $data, ?int $ignoreId = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'template_id' => 'nullable|exists:templates,id',
            'description' => 'nullable|string|max:1000',
            'audience_type' => 'nullable|in:individual,institution,employer',
            'campaign_type' => 'nullable|in:onboarding,event_promotion,networking,career_services,recruiting,donation,leadership,marketing',
            'category' => 'nullable|in:individual,institution,employer',
            'config' => 'nullable|array',
            'brand_config' => 'nullable|array',
            'seo_title' => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
            'seo_keywords' => 'nullable|array',
            'custom_css' => 'nullable|string',
            'custom_js' => 'nullable|string',
            'target_audience' => 'nullable|in:individual,institution,employer',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \InvalidArgumentException(implode(', ', $validator->errors()->all()));
        }

        return $validator->validated();
    }

    /**
     * Validate brand configuration
     */
    private function validateBrandConfig(array $brandConfig): void
    {
        $allowedKeys = ['colors', 'fonts', 'logos', 'spacing', 'typography', 'brand'];

        foreach (array_keys($brandConfig) as $key) {
            if (! in_array($key, $allowedKeys) && $key !== 'brand') {
                throw new \InvalidArgumentException("Invalid brand config key: {$key}");
            }
        }
    }

    /**
     * Apply customizations to base config
     */
    private function applyCustomizations(array $baseConfig, array $customizations): array
    {
        $result = $baseConfig;

        foreach ($customizations as $key => $value) {
            if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                $result[$key] = array_merge($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Extract UTM data from request
     */
    private function extractUtmData(Request $request): array
    {
        return [
            'utm_source' => $request->query('utm_source'),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'utm_term' => $request->query('utm_term'),
            'utm_content' => $request->query('utm_content'),
        ];
    }

    /**
     * Map audience type to lead type
     */
    private function mapAudienceToLeadType(string $audience): string
    {
        return match ($audience) {
            'institution' => 'institutional',
            'employer' => 'enterprise',
            default => 'individual',
        };
    }

    /**
     * Calculate bounce rate from analytics
     */
    private function calculateBounceRate($analytics): float
    {
        $totalSessions = $analytics->where('event_type', 'page_view')
            ->pluck('session_id')
            ->unique()
            ->count();

        if ($totalSessions === 0) {
            return 0;
        }

        $bouncedSessions = $analytics->groupBy('session_id')
            ->filter(fn ($events) => $events->count() === 1)
            ->count();

        return round(($bouncedSessions / $totalSessions) * 100, 2);
    }
}
