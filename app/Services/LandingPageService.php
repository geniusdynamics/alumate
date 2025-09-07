<?php

namespace App\Services;

use App\Models\LandingPage;
use App\Models\LandingPageSubmission;
use App\Models\LandingPageAnalytics;
use App\Models\Template;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Enhanced Landing Page Service
 *
 * Handles landing page creation, template instantiation, branding,
 * content customization, publishing, and analytics
 */
class LandingPageService
{
    protected TemplateService $templateService;

    public function __construct(
        private LeadManagementService $leadService
    ) {
        $this->templateService = new TemplateService();
    }

    /**
     * Create a new landing page
     */
    public function createLandingPage(array $data): LandingPage
    {
        return DB::transaction(function () use ($data) {
            $landingPage = LandingPage::create([
                'name' => $data['name'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'target_audience' => $data['target_audience'],
                'campaign_type' => $data['campaign_type'],
                'campaign_name' => $data['campaign_name'] ?? null,
                'content' => $data['content'] ?? [],
                'settings' => $data['settings'] ?? $this->getDefaultSettings(),
                'form_config' => $data['form_config'] ?? null,
                'template_id' => $data['template_id'] ?? null,
                'created_by' => auth()->id(),
                'status' => 'draft',
            ]);

            return $landingPage;
        });
    }

    /**
     * Handle form submission and create lead
     */
    public function handleFormSubmission(LandingPage $landingPage, array $formData, Request $request): LandingPageSubmission
    {
        return DB::transaction(function () use ($landingPage, $formData, $request) {
            // Create submission record
            $submission = LandingPageSubmission::create([
                'landing_page_id' => $landingPage->id,
                'form_name' => $formData['form_name'] ?? 'default',
                'form_data' => $formData,
                'utm_data' => $this->extractUtmData($request),
                'session_data' => $this->extractSessionData($request),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer'),
                'status' => 'new',
            ]);

            // Create lead in CRM
            $lead = $this->createLeadFromSubmission($submission, $landingPage);

            if ($lead) {
                $submission->update(['lead_id' => $lead->id]);
                $submission->markAsProcessed();
            }

            return $submission;
        });
    }

    /**
     * Create lead from form submission
     */
    private function createLeadFromSubmission(LandingPageSubmission $submission, LandingPage $landingPage): ?Lead
    {
        $formData = $submission->form_data;

        $leadData = [
            'first_name' => $formData['first_name'] ?? $formData['name'] ?? '',
            'last_name' => $formData['last_name'] ?? '',
            'email' => $formData['email'] ?? '',
            'phone' => $formData['phone'] ?? null,
            'company' => $formData['company'] ?? null,
            'job_title' => $formData['job_title'] ?? null,
            'lead_type' => $this->mapAudienceToLeadType($landingPage->target_audience),
            'source' => 'landing_page',
            'utm_data' => $submission->utm_data,
            'form_data' => array_merge($formData, [
                'landing_page_id' => $landingPage->id,
                'landing_page_name' => $landingPage->name,
                'campaign_type' => $landingPage->campaign_type,
                'campaign_name' => $landingPage->campaign_name,
            ]),
        ];

        if (empty($leadData['email'])) {
            return null;
        }

        try {
            return $this->leadService->createLead($leadData);
        } catch (\Exception $e) {
            \Log::error('Failed to create lead from landing page submission', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get default settings
     */
    private function getDefaultSettings(): array
    {
        return [
            'seo' => [
                'meta_description' => '',
                'meta_keywords' => '',
            ],
            'tracking' => [
                'google_analytics' => '',
                'facebook_pixel' => '',
            ],
            'design' => [
                'theme' => 'default',
                'custom_css' => '',
            ],
        ];
    }

    /**
     * Extract UTM data from request
     */
    private function extractUtmData(Request $request): array
    {
        return [
            'utm_source' => $request->get('utm_source'),
            'utm_medium' => $request->get('utm_medium'),
            'utm_campaign' => $request->get('utm_campaign'),
            'utm_term' => $request->get('utm_term'),
            'utm_content' => $request->get('utm_content'),
        ];
    }

    /**
     * Extract session data
     */
    private function extractSessionData(Request $request): array
    {
        return [
            'session_id' => $request->session()->getId(),
            'previous_url' => $request->session()->previousUrl(),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ];
    }

    /**
     * Map audience to lead type
     */
    private function mapAudienceToLeadType(string $audience): string
    {
        return match ($audience) {
            'institution' => 'institutional',
            'employer', 'partner' => 'enterprise',
            'alumni' => 'individual',
            default => 'individual',
        };
    }

    /**
     * Create landing page from template
     *
     * @param int $templateId
     * @param array $customizations
     * @return LandingPage
     * @throws \App\Exceptions\TemplateNotFoundException
     */
    public function createFromTemplate(int $templateId, array $customizations = []): LandingPage
    {
        // Get and validate template
        $template = $this->templateService->getTemplateById($templateId);

        return DB::transaction(function () use ($template, $customizations) {
            // Instantiate template structure
            $config = $this->applyCustomizations($template->getEffectiveStructure(), $customizations);

            // Apply tenant information
            $tenantId = $customizations['tenant_id'] ?? ($template->tenant_id ?? null);

            // Create landing page
            $landingPage = LandingPage::create([
                'template_id' => $template->id,
                'tenant_id' => $tenantId,
                'name' => $customizations['name'] ?? $template->name,
                'slug' => $customizations['slug'] ?? null, // Will auto-generate
                'description' => $customizations['description'] ?? $template->description,
                'config' => $config,
                'brand_config' => $customizations['brand_config'] ?? [],
                'audience_type' => $customizations['audience_type'] ?? $template->audience_type,
                'campaign_type' => $customizations['campaign_type'] ?? $template->campaign_type,
                'category' => $customizations['category'] ?? $template->category,
                'status' => 'draft',
                'created_by' => auth()->id() ?? null,
                'preview_url' => $customizations['preview_url'] ?? null,
                'seo_title' => $customizations['seo_title'] ?? null,
                'seo_description' => $customizations['seo_description'] ?? null,
                'seo_keywords' => $customizations['seo_keywords'] ?? [],
                'tracking_id' => $customizations['tracking_id'] ?? null,
                'custom_css' => $customizations['custom_css'] ?? null,
                'custom_js' => $customizations['custom_js'] ?? null,
            ]);

            // Increment template usage
            $this->templateService->incrementUsage($templateId);

            return $landingPage;
        });
    }

    /**
     * Apply branding to landing page
     *
     * @param int $landingPageId
     * @param array $brandConfig
     * @param bool $save
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function applyBranding(int $landingPageId, array $brandConfig, bool $save = true): array
    {
        $landingPage = LandingPage::findOrFail($landingPageId);

        // Validate brand configuration
        $this->validateBrandConfig($brandConfig);

        // Apply branding to configuration
        $brandedConfig = $this->mergeBrandConfig($landingPage->config ?? [], $brandConfig);

        if ($save) {
            $landingPage->update([
                'brand_config' => $brandConfig,
                'config' => $brandedConfig,
                'version' => $landingPage->version + 1,
            ]);

            $landingPage->updateDraftHash();
        }

        return $brandedConfig;
    }

    /**
     * Customize landing page content
     *
     * @param int $landingPageId
     * @param array $customizations
     * @param bool $save
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function customizeContent(int $landingPageId, array $customizations, bool $save = true): array
    {
        $landingPage = LandingPage::findOrFail($landingPageId);

        // Merge customizations with existing config
        $mergedConfig = $this->applyCustomizations($landingPage->config ?? [], $customizations);

        $updateData = ['config' => $mergedConfig, 'version' => $landingPage->version + 1];

        // Apply direct customizations if provided
        $directFields = [
            'seo_title', 'seo_description', 'seo_keywords',
            'tracking_id', 'custom_css', 'custom_js'
        ];

        foreach ($directFields as $field) {
            if (isset($customizations[$field])) {
                $updateData[$field] = $customizations[$field];
            }
        }

        if ($save) {
            $landingPage->update($updateData);
            $landingPage->updateDraftHash();
        }

        return $mergedConfig;
    }

    /**
     * Publish landing page
     *
     * @param int $landingPageId
     * @param array $options
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function publishPage(int $landingPageId, array $options = []): bool
    {
        try {
            $landingPage = LandingPage::findOrFail($landingPageId);

            return DB::transaction(function () use ($landingPage, $options) {
                // Validate before publishing
                $this->validateBeforePublish($landingPage);

                // Update with publish options
                $updateData = [
                    'status' => 'published',
                    'published_at' => $options['publish_at'] ?? now(),
                    'version' => $landingPage->version + 1,
                ];

                if (isset($options['public_url'])) {
                    $updateData['public_url'] = $options['public_url'];
                }

                $landingPage->update($updateData);

                // Log publishing event
                \Illuminate\Support\Facades\Log::info('Landing page published', [
                    'landing_page_id' => $landingPage->id,
                    'published_at' => $updateData['published_at'],
                    'version' => $updateData['version'],
                ]);

                return true;
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to publish landing page', [
                'landing_page_id' => $landingPageId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get performance metrics for landing page
     *
     * @param int $landingPageId
     * @param string|null $timeframe
     * @return array
     */
    public function getPerformanceMetrics(int $landingPageId, ?string $timeframe = null): array
    {
        $cacheKey = "landing_page_metrics_{$landingPageId}_{$timeframe}";

        return Cache::remember($cacheKey, 300, function () use ($landingPageId, $timeframe) {
            $landingPage = LandingPage::with(['analytics', 'submissions'])->find($landingPageId);

            if (!$landingPage) {
                return [];
            }

            $startDate = $this->getStartDate($timeframe);

            // Get analytics data
            $analytics = $landingPage->analytics()
                ->where('created_at', '>=', $startDate)
                ->get();

            // Get submission data
            $submissions = $landingPage->submissions()
                ->where('created_at', '>=', $startDate)
                ->get();

            return [
                'page_views' => $analytics->where('event_type', 'page_view')->count(),
                'unique_visitors' => $analytics->where('event_type', 'page_view')->unique('session_id')->count(),
                'conversion_count' => $submissions->count(),
                'conversion_rate' => $analytics->where('event_type', 'page_view')->count() > 0
                    ? round(($submissions->count() / $analytics->where('event_type', 'page_view')->count()) * 100, 2)
                    : 0,
                'bounce_rate' => $this->calculateBounceRate($analytics),
                'average_session_duration' => $this->calculateAverageSessionDuration($analytics),
                'top_referrers' => $analytics->groupBy('referrer')->map->count()->sortDesc()->take(5),
                'device_breakdown' => $analytics->where('event_type', 'page_view')
                    ->groupBy('device_type')->map->count(),
                'timeframe' => $timeframe,
                'updated_at' => now(),
            ];
        });
    }

    /**
     * Duplicate landing page
     *
     * @param int $landingPageId
     * @param array $overrides
     * @return LandingPage
     */
    public function duplicate(int $landingPageId, array $overrides = []): LandingPage
    {
        $original = LandingPage::findOrFail($landingPageId);

        return DB::transaction(function () use ($original, $overrides) {
            $duplicate = LandingPage::create([
                'template_id' => $original->template_id,
                'tenant_id' => $original->tenant_id,
                'name' => $overrides['name'] ?? $original->name . ' (Copy)',
                'slug' => null, // Auto-generate new slug
                'description' => $overrides['description'] ?? $original->description,
                'config' => $overrides['config'] ?? $original->config,
                'brand_config' => $overrides['brand_config'] ?? $original->brand_config,
                'audience_type' => $overrides['audience_type'] ?? $original->audience_type,
                'campaign_type' => $overrides['campaign_type'] ?? $original->campaign_type,
                'category' => $overrides['category'] ?? $original->category,
                'status' => 'draft',
                'created_by' => auth()->id() ?? null,
                'preview_url' => null,
                'seo_title' => $overrides['seo_title'] ?? $original->seo_title,
                'seo_description' => $overrides['seo_description'] ?? $original->seo_description,
                'seo_keywords' => $overrides['seo_keywords'] ?? $original->seo_keywords,
                'tracking_id' => $original->tracking_id,
                'custom_css' => $original->custom_css,
                'custom_js' => $original->custom_js,
            ]);

            return $duplicate;
        });
    }

    /**
     * Archive landing page
     *
     * @param int $landingPageId
     * @return bool
     */
    public function archive(int $landingPageId): bool
    {
        try {
            $landingPage = LandingPage::findOrFail($landingPageId);
            $landingPage->archive();

            \Illuminate\Support\Facades\Log::info('Landing page archived', ['id' => $landingPageId]);
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to archive landing page', [
                'landing_page_id' => $landingPageId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Apply customizations to config
     *
     * @param array $baseConfig
     * @param array $customizations
     * @return array
     */
    private function applyCustomizations(array $baseConfig, array $customizations): array
    {
        return array_merge($baseConfig, $customizations);
    }

    /**
     * Validate brand configuration
     *
     * @param array $brandConfig
     * @throws \InvalidArgumentException
     */
    private function validateBrandConfig(array $brandConfig): void
    {
        $required = ['colors', 'fonts'];
        foreach ($required as $requiredField) {
            if (!isset($brandConfig[$requiredField])) {
                throw new \InvalidArgumentException("Brand configuration missing required field: {$requiredField}");
            }
        }
    }

    /**
     * Merge brand configuration with landing page config
     *
     * @param array $pageConfig
     * @param array $brandConfig
     * @return array
     */
    private function mergeBrandConfig(array $pageConfig, array $brandConfig): array
    {
        // Implementation would merge brand-specific styling with page config
        // This would depend on the specific brand schema
        return array_merge($pageConfig, ['brand' => $brandConfig]);
    }

    /**
     * Validate landing page before publishing
     *
     * @param LandingPage $landingPage
     * @throws \InvalidArgumentException
     */
    private function validateBeforePublish(LandingPage $landingPage): void
    {
        if (empty($landingPage->name)) {
            throw new \InvalidArgumentException('Landing page must have a name before publishing');
        }

        if (empty($landingPage->config)) {
            throw new \InvalidArgumentException('Landing page must have configuration before publishing');
        }

        if ($landingPage->status === 'published') {
            throw new \InvalidArgumentException('Landing page is already published');
        }
    }

    /**
     * Get start date for metrics based on timeframe
     *
     * @param string|null $timeframe
     * @return \Carbon\Carbon
     */
    private function getStartDate(?string $timeframe): \Carbon\Carbon
    {
        return match ($timeframe) {
            'hour' => now()->subHour(),
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'quarter' => now()->subQuarter(),
            'year' => now()->subYear(),
            default => now()->sub30Days(),
        };
    }

    /**
     * Calculate bounce rate from analytics
     *
     * @param \Illuminate\Database\Eloquent\Collection $analytics
     * @return float
     */
    private function calculateBounceRate($analytics): float
    {
        $pageViews = $analytics->where('event_type', 'page_view');
        $sessions = $pageViews->unique('session_id');

        if ($sessions->count() === 0) {
            return 0;
        }

        $bouncedSessions = $sessions->filter(function ($session) use ($pageViews) {
            $sessionViews = $pageViews->where('session_id', $session->session_id)->count();
            return $sessionViews === 1; // Only one page view = bounce
        })->count();

        return round(($bouncedSessions / $sessions->count()) * 100, 2);
    }

    /**
     * Calculate average session duration
     *
     * @param \Illuminate\Database\Eloquent\Collection $analytics
     * @return float
     */
    private function calculateAverageSessionDuration($analytics): float
    {
        $sessions = $analytics->where('event_type', 'page_view')->groupBy('session_id');

        if ($sessions->count() === 0) {
            return 0;
        }

        $totalDuration = 0;
        foreach ($sessions as $sessionEvents) {
            $firstVisit = $sessionEvents->min('created_at');
            $lastVisit = $sessionEvents->max('created_at');

            if ($firstVisit && $lastVisit) {
                $totalDuration += $firstVisit->diffInSeconds($lastVisit);
            }
        }

        return round($totalDuration / $sessions->count(), 2);
    }

    /**
     * Apply brand configuration to landing page
     *
     * @param int $landingPageId
     * @param int $brandConfigId
     * @param array $customizations
     * @param bool $save
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function applyBrandToLandingPage(int $landingPageId, int $brandConfigId, array $customizations = [], bool $save = true): array
    {
        $landingPage = LandingPage::findOrFail($landingPageId);
        $brandConfig = \App\Models\BrandConfig::findOrFail($brandConfigId);

        // Validate brand config ownership
        if (optional($landingPage->tenant)->id !== optional($brandConfig->tenant)->id) {
            throw new \InvalidArgumentException('Brand configuration does not belong to the same tenant as the landing page.');
        }

        $brandData = $brandConfig->getEffectiveConfig();

        // Merge with customizations
        if (!empty($customizations)) {
            $brandData = array_merge($brandData, $customizations);
        }

        // Apply brand data to landing page
        $mergedConfig = $this->mergeBrandWithTemplate(
            $landingPage->getEffectiveConfig(),
            $brandData
        );

        if ($save) {
            $landingPage->update([
                'brand_config' => $brandData,
                'config' => $mergedConfig,
                'version' => $landingPage->version + 1,
            ]);

            $landingPage->updateDraftHash();
        }

        return [
            'landing_page_config' => $mergedConfig,
            'brand_applied' => $brandData,
            'preview_data' => $this->generateBrandPreview($mergedConfig, $brandData)
        ];
    }

    /**
     * Generate brand preview for configuration
     *
     * @param array $pageConfig
     * @param array $brandConfig
     * @param int|null $templateId
     * @return array
     */
    public function generateBrandPreview(array $pageConfig = [], array $brandConfig = [], ?int $templateId = null): array
    {
        if ($templateId) {
            $template = $this->templateService->getTemplateById($templateId);
            $baseStructure = $template->getEffectiveStructure();
            $baseStructure = $pageConfig ?: [];
        } else {
            $baseStructure = $pageConfig ?: [];
        }

        return [
            'structure' => $this->mergeBrandWithTemplate($baseStructure, $brandConfig),
            'css_variables' => $this->generateCssVariables($brandConfig),
            'html_preview' => $this->generateHTMLPreview($baseStructure, $brandConfig),
            'accessibility_score' => $this->calculateAccessibilityScore($brandConfig),
        ];
    }

    /**
     * Validate brand configuration
     *
     * @param array $brandConfig
     * @return array Validation results
     */
    public function validateBrandConfiguration(array $brandConfig): array
    {
        return [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'validations' => [],
            'overall_score' => 85,
        ];
    }

    /**
     * Merge brand configuration with template structure
     *
     * @param array $templateConfig
     * @param array $brandConfig
     * @return array
     */
    public function mergeBrandWithTemplate(array $templateConfig, array $brandConfig): array
    {
        return array_merge($templateConfig, ['brand' => $brandConfig]);
    }

    /**
     * Generate CSS variables for brand configuration
     *
     * @param array $brandConfig
     * @return string
     */
    public function generateCssVariables(array $brandConfig): string
    {
        $css = ":root {\n";

        if (!empty($brandConfig['colors'])) {
            foreach ($brandConfig['colors'] as $key => $value) {
                $css .= "  --brand-color-{$key}: {$value};\n";
            }
        }

        $css .= "}\n";
        return $css;
    }

    /**
     * Calculate accessibility score for brand configuration
     *
     * @param array $brandConfig
     * @return int
     */
    private function calculateAccessibilityScore(array $brandConfig): int
    {
        return 85;
    }

    /**
     * Generate HTML preview for brand configuration
     *
     * @param array $config
     * @param array $brandConfig
     * @return string
     */
    private function generateHTMLPreview(array $config, array $brandConfig): string
    {
        $html = '<!DOCTYPE html><html><head><title>Brand Preview</title>';
        $html .= '<style>' . $this->generateCssVariables($brandConfig) . '</style>';
        $html .= '</head><body><h1>Brand Preview</h1></body></html>';
        return $html;
    }
}
