<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTemplateRequest;
use App\Http\Requests\Api\UpdateTemplateRequest;
use App\Http\Requests\Api\ExportTemplateRequest;
use App\Http\Requests\Api\ImportTemplateRequest;
use App\Http\Resources\TemplateResource;
use App\Models\Template;
use App\Services\TemplateService;
use App\Services\TemplatePreviewService;
use App\Services\TemplateAnalyticsService;
use App\Services\TemplateImportExportService;
use App\Services\VariantService;
use App\Services\ResponsiveTemplateRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;

class TemplateController extends Controller
{
    public function __construct(
        private TemplateService $templateService,
        private TemplatePreviewService $previewService,
        private TemplateAnalyticsService $analyticsService,
        private TemplateImportExportService $importExportService,
        private VariantService $variantService,
        private ResponsiveTemplateRenderer $responsiveRenderer
    ) {}

    /**
     * Display a listing of templates
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $templates = $this->templateService->getAllTemplates([
            'category' => $request->category,
            'audience_type' => $request->audience_type,
            'campaign_type' => $request->campaign_type,
            'is_active' => $request->is_active ? filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN) : null,
            'is_premium' => $request->is_premium ? filter_var($request->is_premium, FILTER_VALIDATE_BOOLEAN) : null,
            'search' => $request->search,
            'tags' => $request->tags ? explode(',', $request->tags) : null,
        ], [
            'sort_by' => $request->sort_by ?? 'name',
            'paginate' => true,
            'per_page' => $request->per_page ?? 15,
        ]);

        return response()->json([
            'templates' => TemplateResource::collection($templates->items()),
            'pagination' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
                'from' => $templates->firstItem(),
                'to' => $templates->lastItem(),
            ],
            'meta' => [
                'total_count' => Template::active()->count(),
                'categories' => Template::CATEGORIES,
                'audience_types' => Template::AUDIENCE_TYPES,
                'campaign_types' => Template::CAMPAIGN_TYPES,
            ]
        ]);
    }

    /**
     * Display the specified template
     *
     * @param Template $template
     * @return JsonResponse
     */
    public function show(Template $template): JsonResponse
    {
        $this->authorize('view', $template);

        $stats = $this->templateService->getTemplateStats($template->id);

        return response()->json([
            'template' => new TemplateResource($template),
            'stats' => $stats,
            'usage_stats' => $template->getUsageStats(),
            'performance_stats' => $template->getPerformanceStats(),
        ]);
    }

    /**
     * Store a newly created template
     *
     * @param StoreTemplateRequest $request
     * @return JsonResponse
     */
    public function store(StoreTemplateRequest $request): JsonResponse
    {
        // Validate template structure if provided
        if ($request->has('structure')) {
            $this->templateService->validateTemplateStructure($request->structure);
        }

        $templateData = array_merge($request->validated(), [
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $template = Template::create($templateData);

        return response()->json([
            'template' => new TemplateResource($template),
            'message' => 'Template created successfully',
        ], 201);
    }

    /**
     * Update the specified template
     *
     * @param UpdateTemplateRequest $request
     * @param Template $template
     * @return JsonResponse
     */
    public function update(UpdateTemplateRequest $request, Template $template): JsonResponse
    {
        $this->authorize('update', $template);

        // Validate template structure if being updated
        if ($request->has('structure')) {
            $this->templateService->validateTemplateStructure($request->structure);
        }

        $updatedData = array_merge($request->validated(), [
            'updated_by' => Auth::id(),
        ]);

        $template->update($updatedData);

        return response()->json([
            'template' => new TemplateResource($template),
            'message' => 'Template updated successfully',
        ]);
    }

    /**
     * Remove the specified template
     *
     * @param Template $template
     * @return JsonResponse
     */
    public function destroy(Template $template): JsonResponse
    {
        $this->authorize('delete', $template);

        // Check if template has active landing pages
        if ($template->landingPages()->where('status', 'published')->exists()) {
            return response()->json([
                'message' => 'Cannot delete template with published landing pages. Unpublish or delete pages first.',
            ], 422);
        }

        $template->delete();

        return response()->json([
            'message' => 'Template deleted successfully',
        ]);
    }

    /**
     * Search templates with keyword filtering
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'limit' => 'integer|min:1|max:50',
            'category' => 'nullable|string|in:' . implode(',', Template::CATEGORIES),
            'audience_type' => 'nullable|string|in:' . implode(',', Template::AUDIENCE_TYPES),
            'campaign_type' => 'nullable|string|in:' . implode(',', Template::CAMPAIGN_TYPES),
        ]);

        $templates = $this->templateService->searchTemplates(
            $request->q,
            array_filter([
                'category' => $request->category,
                'audience_type' => $request->audience_type,
                'campaign_type' => $request->campaign_type,
            ]),
            ['limit' => $request->limit ?? 20]
        );

        return response()->json([
            'query' => $request->q,
            'results' => TemplateResource::collection($templates),
            'count' => $templates->count(),
        ]);
    }

    /**
     * Get templates by category
     *
     * @param string $category
     * @param Request $request
     * @return JsonResponse
     */
    public function categories(Request $request): JsonResponse
    {
        $templatesByCategory = [];

        foreach (Template::CATEGORIES as $category) {
            $templatesByCategory[$category] = $this->templateService->getTemplatesByCategory($category, [
                'is_active' => true,
            ]);
        }

        return response()->json([
            'categories' => Template::CATEGORIES,
            'templates_by_category' => $templatesByCategory,
        ]);
    }

    /**
     * Get templates grouped by audience
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function byAudience(Request $request): JsonResponse
    {
        $templatesByAudience = [];

        foreach (Template::AUDIENCE_TYPES as $audienceType) {
            $templates = $this->templateService->getTemplatesByAudience($audienceType, [
                'is_active' => true,
            ]);

            if ($templates->count() > 0) {
                $templatesByAudience[$audienceType] = TemplateResource::collection($templates);
            }
        }

        return response()->json([
            'audience_types' => Template::AUDIENCE_TYPES,
            'templates_by_audience' => $templatesByAudience,
        ]);
    }

    /**
     * Get popular templates
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = min((int) $request->limit ?? 10, 50);
        $templates = $this->templateService->getPopularTemplates($limit);

        return response()->json([
            'templates' => TemplateResource::collection($templates),
            'count' => $templates->count(),
        ]);
    }

    /**
     * Get recently used templates
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = min((int) $request->limit ?? 10, 50);
        $templates = $this->templateService->getRecentlyUsedTemplates($limit);

        return response()->json([
            'templates' => TemplateResource::collection($templates),
            'count' => $templates->count(),
        ]);
    }

    /**
     * Get premium templates only
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function premium(Request $request): JsonResponse
    {
        $templates = $this->templateService->getPremiumTemplates([
            'category' => $request->category,
            'audience_type' => $request->audience_type,
        ]);

        return response()->json([
            'templates' => TemplateResource::collection($templates),
            'count' => $templates->count(),
        ]);
    }

    /**
     * Generate comprehensive template preview with real-time compilation
     */
    public function generatePreview(Template $template, Request $request): JsonResponse
    {
        $this->authorize('view', $template);

        $request->validate([
            'custom_config' => 'nullable|array',
            'cache_enabled' => 'boolean',
            'device_mode' => ['nullable', Rule::in(['desktop', 'tablet', 'mobile'])],
        ]);

        $customConfig = $request->custom_config ?? [];
        $deviceMode = $request->device_mode ?? 'desktop';
        $forceRefresh = $request->cache_enabled === false;

        try {
            // Check if we're forcing refresh and template has changed recently
            if ($forceRefresh && $template->updated_at->diffInMinutes() < 5) {
                Cache::forget("template_preview_template_{$template->id}_" . tenant()?->id . "_*");
            }

            $preview = $this->previewService->generateTemplatePreview(
                $template->id,
                $customConfig,
                ['device_mode' => $deviceMode, 'force_refresh' => $forceRefresh]
            );

            return response()->json([
                'template' => new TemplateResource($template),
                'preview' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Preview generation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Render template for specific viewport
     */
    public function render(Template $template, Request $request, string $viewport): JsonResponse
    {
        $this->authorize('view', $template);

        if (!in_array($viewport, ['desktop', 'tablet', 'mobile'])) {
            return response()->json([
                'message' => 'Invalid viewport. Must be one of: desktop, tablet, mobile'
            ], 422);
        }

        $customConfig = $request->get('custom_config', []);

        try {
            $preview = $this->previewService->generateTemplatePreview(
                $template->id,
                $customConfig,
                ['device_mode' => $viewport]
            );

            return response()->json([
                'template_id' => $template->id,
                'viewport' => $viewport,
                'html' => $preview['compiled_html'],
                'css' => $preview['compiled_css'],
                'responsive_styles' => $preview['responsive_styles'],
                'render_timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Template rendering failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate responsive preview for all device modes
     */
    public function responsivePreview(Template $template, Request $request): JsonResponse
    {
        $this->authorize('view', $template);

        $customConfig = $request->get('custom_config', []);

        try {
            $multiDevicePreview = $this->previewService->generateMultiDevicePreview(
                $template->id,
                $customConfig
            );

            $responsivePreview = [];
            foreach ($multiDevicePreview['devices'] as $device => $deviceData) {
                $responsivePreview[$device] = [
                    'viewport' => $device,
                    'width' => $deviceData['dimensions']['width'],
                    'height' => $deviceData['dimensions']['height'],
                    'html' => $deviceData['preview']['compiled_html'],
                    'css' => $deviceData['preview']['compiled_css'],
                    'breakpoints' => $deviceData['breakpoints'],
                    'media_queries' => $deviceData['media_queries'],
                    'config' => $deviceData['preview']['config']
                ];
            }

            return response()->json([
                'template_id' => $template->id,
                'template_name' => $template->name,
                'responsive_preview' => $responsivePreview,
                'viewports' => array_keys($responsivePreview),
                'generated_at' => $multiDevicePreview['generated_at'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Responsive preview generation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get preview assets and dependencies
     */
    public function previewAssets(Template $template, Request $request): JsonResponse
    {
        $this->authorize('view', $template);

        $customConfig = $request->get('custom_config', []);
        $deviceMode = $request->get('device_mode', 'desktop');

        try {
            $preview = $this->previewService->generateTemplatePreview(
                $template->id,
                $customConfig,
                ['device_mode' => $deviceMode]
            );

            // Get brand information
            $brandConfig = $customConfig['brand_config'] ?? [];
            $hasColors = !empty($brandConfig['colors']);
            $hasFonts = !empty($brandConfig['fonts']);
            $hasLogos = !empty($brandConfig['logos']);

            $assets = [
                'styles' => [
                    'main_css' => $preview['compiled_css'],
                    'responsive_css' => $preview['responsive_styles'],
                    'brand_css' => $this->compileBrandCss($brandConfig),
                ],
                'scripts' => [
                    'preview_js' => $preview['compiled_js'] ?? '',
                    'responsive_js' => $this->generateResponsiveJs($deviceMode),
                ],
                'fonts' => $brandConfig['fonts'] ?? [],
                'images' => [
                    'template_images' => $this->getTemplateImages($template),
                    'brand_images' => $this->getBrandImages($brandConfig),
                ],
                'metadata' => [
                    'has_brand' => $hasColors || $hasFonts || $hasLogos,
                    'colors_count' => count($brandConfig['colors'] ?? []),
                    'fonts_count' => count($brandConfig['fonts'] ?? []),
                    'logos_count' => count($brandConfig['logos'] ?? []),
                    'viewport_options' => $this->previewService->getPreviewOptions()['device_modes'],
                ]
            ];

            return response()->json([
                'template_id' => $template->id,
                'assets' => $assets,
                'cache_duration' => $this->getCacheDuration($preview),
                'expires_at' => now()->addSeconds($this->previewService::CACHE_DURATION)->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Assets compilation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply brand configuration to template preview
     */
    public function applyBrand(Template $template, Request $request): JsonResponse
    {
        $this->authorize('view', $template);

        $request->validate([
            'custom_config' => 'nullable|array',
            'brand_overrides' => 'nullable|array',
            'force_refresh' => 'boolean'
        ]);

        $customConfig = $request->custom_config ?? [];
        $brandOverrides = $request->brand_overrides ?? [];

        // Merge brand overrides into config
        if (!empty($brandOverrides)) {
            $customConfig = array_merge($customConfig, [
                'brand_config' => array_merge($customConfig['brand_config'] ?? [], $brandOverrides)
            ]);
        }

        try {
            $preview = $this->previewService->generateTemplatePreview(
                $template->id,
                $customConfig,
                ['force_refresh' => $request->force_refresh ?? false]
            );

            return response()->json([
                'template_id' => $template->id,
                'branded_structure' => $preview['compiled_html'],
                'overwrites_applied' => !empty($brandOverrides),
                'applied_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Brand application failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear preview cache for template
     */
    public function clearCache(Template $template, Request $request): JsonResponse
    {
        $this->authorize('update', $template);

        try {
            $success = $this->previewService->clearTemplateCache($template->id);

            return response()->json([
                'message' => 'Preview cache cleared successfully',
                'template_id' => $template->id,
                'cleared_at' => now()->toISOString(),
                'success' => $success,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Cache clearing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get global preview options and configuration
     */
    public static function getPreviewOptions(): JsonResponse
    {
        $options = [
            'viewports' => [
                [
                    'key' => 'desktop',
                    'name' => 'Desktop',
                    'width' => 1920,
                    'height' => 1080,
                ],
                [
                    'key' => 'tablet',
                    'name' => 'Tablet',
                    'width' => 768,
                    'height' => 1024,
                ],
                [
                    'key' => 'mobile',
                    'name' => 'Mobile',
                    'width' => 375,
                    'height' => 667,
                ],
            ],
            'cache_settings' => [
                'preview_duration' => TemplatePreviewService::CACHE_DURATION,
                'default_expiry_minutes' => floor(TemplatePreviewService::CACHE_DURATION / 60),
            ],
            'supported_features' => [
                'real_time_previews' => true,
                'responsive_design' => true,
                'brand_application' => true,
                'viewport_switching' => true,
                'cache_management' => true,
            ],
            'asset_types' => [
                'stylesheets',
                'javascript',
                'fonts',
                'images',
            ]
        ];

        return response()->json($options);
    }

    /**
     * Duplicate an existing template
     *
     * @param Template $template
     * @param Request $request
     * @return JsonResponse
     */
    public function duplicate(Template $template, Request $request): JsonResponse
    {
        $this->authorize('view', $template);

        $request->validate([
            'name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $modifications = array_filter([
            'name' => $request->name,
            'is_active' => $request->is_active,
            'slug' => null, // Auto-generate new slug
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $duplicateTemplate = Template::create(array_merge($template->toArray(), $modifications, [
            'usage_count' => 0,
            'last_used_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return response()->json([
            'template' => new TemplateResource($duplicateTemplate),
            'message' => 'Template duplicated successfully',
        ], 201);
    }

    /**
     * Activate a template
     *
     * @param Template $template
     * @return JsonResponse
     */
    public function activate(Template $template): JsonResponse
    {
        $this->authorize('update', $template);

        $template->update([
            'is_active' => true,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'template' => new TemplateResource($template),
            'message' => 'Template activated successfully',
        ]);
    }

    /**
     * Deactivate a template
     *
     * @param Template $template
     * @return JsonResponse
     */
    public function deactivate(Template $template): JsonResponse
    {
        $this->authorize('update', $template);

        if ($template->landingPages()->where('status', 'published')->exists()) {
            return response()->json([
                'message' => 'Cannot deactivate template with published landing pages.',
            ], 422);
        }

        $template->update([
            'is_active' => false,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'template' => new TemplateResource($template),
            'message' => 'Template deactivated successfully',
        ]);
    }

    /**
     * Get template usage statistics
     *
     * @param Template $template
     * @return JsonResponse
     */
    public function stats(Template $template): JsonResponse
    {
        $this->authorize('view', $template);

        $stats = $this->templateService->getTemplateStats($template->id);

        return response()->json([
            'template_id' => $template->id,
            'stats' => $stats,
            'usage_stats' => $template->getUsageStats(),
            'performance_stats' => $template->getPerformanceStats(),
        ]);
    }

    /**
     * Compile brand-specific CSS
     */
    private function compileBrandCss(array $brandConfig): string
    {
        $css = '';

        if (!empty($brandConfig['colors'])) {
            foreach ($brandConfig['colors'] as $color) {
                if (!empty($color['name']) && !empty($color['value'])) {
                    $cssVar = '--brand-' . strtolower(str_replace(' ', '-', $color['name']));
                    $css .= "{$cssVar}: {$color['value']};";
                }
            }
        }

        if (!empty($brandConfig['fonts'])) {
            foreach ($brandConfig['fonts'] as $font) {
                if (!empty($font['family'])) {
                    $css .= "font-family: {$font['family']};";
                }
            }
        }

        return $css;
    }

    /**
     * Generate responsive JavaScript
     */
    private function generateResponsiveJs(string $deviceMode): string
    {
        return "
            // Responsive preview JavaScript
            document.addEventListener('DOMContentLoaded', function() {
                const viewport = '{$deviceMode}';
                console.log('Preview loaded for ' + viewport + ' viewport');

                // Add viewport class to body
                document.body.classList.add('preview-' + viewport);

                // Handle responsive interactions
                window.addEventListener('resize', function() {
                    console.log('Viewport size: ' + window.innerWidth + 'x' + window.innerHeight);
                });
            });
        ";
    }

    /**
     * Get template images
     */
    private function getTemplateImages(Template $template): array
    {
        // Extract images from template structure
        $images = [];
        $structure = $template->getEffectiveStructure();

        if (isset($structure['sections'])) {
            foreach ($structure['sections'] as $section) {
                if (!empty($section['config']['image'])) {
                    $images[] = $section['config']['image'];
                }
                if (!empty($section['config']['background_image'])) {
                    $images[] = $section['config']['background_image'];
                }
            }
        }

        return array_unique($images);
    }

    /**
     * Get brand images
     */
    private function getBrandImages(array $brandConfig): array
    {
        $images = [];

        if (!empty($brandConfig['logos'])) {
            foreach ($brandConfig['logos'] as $logo) {
                if (!empty($logo['url'])) {
                    $images[] = $logo['url'];
                }
            }
        }

        return $images;
    }

    /**
     * Get cache duration information
     */
    private function getCacheDuration(array $preview): array
    {
        return [
            'ttl_seconds' => $this->previewService::CACHE_DURATION,
            'expires_at' => now()->addSeconds($this->previewService::CACHE_DURATION)->toISOString(),
            'cache_hash' => $preview['cache_hash'] ?? null,
            'generated_at' => $preview['generated_at'] ?? now()->toISOString(),
        ];
    }

    /**
     * Track analytics events
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function trackEvent(Request $request): JsonResponse
    {
        $request->validate([
            'event_type' => 'required|string|in:' . implode(',', \App\Models\TemplateAnalyticsEvent::EVENT_TYPES),
            'template_id' => 'required|exists:templates,id',
            'landing_page_id' => 'nullable|exists:landing_pages,id',
            'event_data' => 'nullable|array',
            'session_id' => 'nullable|string|max:255',
            'conversion_value' => 'nullable|numeric|min:0|max:999999.99',
            'referrer_url' => 'nullable|url|max:2000',
            'user_agent' => 'nullable|string|max:1000',
            'timestamp' => 'nullable|date',
        ]);

        $eventData = array_merge($request->only([
            'event_type', 'template_id', 'landing_page_id', 'event_data',
            'session_id', 'conversion_value', 'referrer_url', 'user_agent', 'timestamp'
        ]), [
            'ip_address' => $request->ip(),
        ]);

        $event = $this->analyticsService->trackEvent($eventData);

        if (!$event) {
            return response()->json([
                'message' => 'Failed to track analytics event',
            ], 500);
        }

        return response()->json([
            'event' => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'template_id' => $event->template_id,
                'landing_page_id' => $event->landing_page_id,
                'timestamp' => $event->timestamp,
                'created_at' => $event->created_at->toISOString(),
            ],
            'message' => 'Analytics event tracked successfully',
        ]);
    }

    /**
     * Track multiple analytics events in batch
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function trackEvents(Request $request): JsonResponse
    {
        $request->validate([
            'events' => 'required|array',
            'events.*.event_type' => 'required|string|in:' . implode(',', \App\Models\TemplateAnalyticsEvent::EVENT_TYPES),
            'events.*.template_id' => 'required|exists:templates,id',
            'events.*.landing_page_id' => 'nullable|exists:landing_pages,id',
            'events.*.event_data' => 'nullable|array',
            'events.*.session_id' => 'nullable|string|max:255',
            'events.*.conversion_value' => 'nullable|numeric|min:0|max:999999.99',
            'events.*.referrer_url' => 'nullable|url|max:2000',
            'events.*.user_agent' => 'nullable|string|max:1000',
            'events.*.timestamp' => 'nullable|date',
        ]);

        $eventsData = [];
        foreach ($request->events as $eventData) {
            $eventsData[] = array_merge($eventData, [
                'ip_address' => $request->ip(),
            ]);
        }

        $results = $this->analyticsService->trackEvents($eventsData);

        return response()->json([
            'results' => $results,
            'total_events' => count($results),
            'successful_events' => count(array_filter($results, fn($result) => $result['success'])),
            'message' => 'Batch analytics events processed',
        ]);
    }

    /**
     * Get template analytics statistics
     *
     * @param Template $template
     * @param Request $request
     * @return JsonResponse
     */
    public function analytics(Template $template, Request $request): JsonResponse
    {
        $this->authorize('view', $template);

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'event_types' => 'nullable|array',
            'event_types.*' => 'string|in:' . implode(',', \App\Models\TemplateAnalyticsEvent::EVENT_TYPES),
            'metrics' => 'nullable|array',
            'metrics.*' => 'string|in:basic,conversion,engagement,device',
        ]);

        $options = array_filter([
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'event_types' => $request->event_types,
        ]);

        $analytics = $this->analyticsService->getTemplateAnalytics(
            $template->id,
            $options
        );

        return response()->json([
            'template_id' => $template->id,
            'template_name' => $template->name,
            'analytics' => $analytics,
            'generated_at' => now()->toISOString(),
            'options' => $options,
        ]);
    }

    /**
     * Get comprehensive analytics report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function analyticsReport(Request $request): JsonResponse
    {
        $request->validate([
            'templates' => 'nullable|array',
            'templates.*' => 'exists:templates,id',
            'landing_pages' => 'nullable|array',
            'landing_pages.*' => 'exists:landing_pages,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'event_types' => 'nullable|array',
            'event_types.*' => 'string|in:' . implode(',', \App\Models\TemplateAnalyticsEvent::EVENT_TYPES),
        ]);

        $options = array_filter([
            'templates' => $request->templates,
            'landing_pages' => $request->landing_pages,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'event_types' => $request->event_types,
        ]);

        $report = $this->analyticsService->getAnalyticsReport($options);

        return response()->json($report);
    }

    /**
     * Get analytics tracking code for template
     *
     * @param Template $template
     * @param Request $request
     * @return JsonResponse
     */
    public function trackingCode(Template $template, Request $request): JsonResponse
    {
        $this->authorize('view', $template);

        $request->validate([
            'landing_page_id' => 'nullable|exists:landing_pages,id',
        ]);

        $trackingCode = $this->analyticsService->generateTrackingCode(
            $template->id,
            $request->landing_page_id
        );

        return response()->json([
            'template_id' => $template->id,
            'landing_page_id' => $request->landing_page_id,
            'tracking_code' => $trackingCode,
            'generated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Clear analytics cache for template
     *
     * @param Template $template
     * @return JsonResponse
     */
    public function clearAnalyticsCache(Template $template): JsonResponse
    {
        $this->authorize('update', $template);

        try {
            $this->analyticsService->clearTemplateCache($template->id);

            return response()->json([
                'message' => 'Analytics cache cleared successfully',
                'template_id' => $template->id,
                'cleared_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Analytics cache clearing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analytics configuration and options
     *
     * @return JsonResponse
     */
    public static function analyticsOptions(): JsonResponse
    {
        return response()->json([
            'event_types' => \App\Models\TemplateAnalyticsEvent::EVENT_TYPES,
            'device_types' => \App\Models\TemplateAnalyticsEvent::DEVICE_TYPES,
            'browser_types' => \App\Models\TemplateAnalyticsEvent::BROWSER_TYPES,
            'metrics' => [
                'basic' => [
                    'total_events',
                    'page_views',
                    'unique_sessions',
                    'unique_users',
                    'events_today',
                    'events_last_week',
                    'events_last_month',
                ],
                'conversion' => [
                    'conversion_count',
                    'total_conversion_value',
                    'conversion_rate',
                    'conversion_funnel',
                ],
                'engagement' => [
                    'click_events',
                    'form_submissions',
                    'cta_clicks',
                    'average_scroll_depth',
                    'average_time_on_page',
                    'exit_rate',
                ],
                'device' => [
                    'device_breakdown',
                    'browser_breakdown',
                ],
            ],
            'features' => [
                'real_time_tracking' => true,
                'batch_tracking' => true,
                'conversion_tracking' => true,
                'session_tracking' => true,
                'device_detection' => true,
                'geo_tracking' => true,
                'custom_events' => true,
                'tracking_code_generation' => true,
                'analytics_reporting' => true,
                'performance_metrics' => true,
            ],
            'version' => '1.0.0',
        ]);
    }

    /**
     * Get variants for a template
     *
     * @param Template $template
     * @param Request $request
     * @return JsonResponse
     */
    public function getVariants(Template $template, Request $request): JsonResponse
    {
        $this->authorize('view', $template);

        $variants = $this->variantService->getTemplateVariants($template->id, [
            'is_active' => $request->boolean('is_active', true),
            'is_control' => $request->boolean('is_control') ?? null,
        ], [
            'paginate' => true,
            'per_page' => $request->per_page ?? 15,
        ]);

        return response()->json([
            'variants' => $variants->items(),
            'pagination' => [
                'current_page' => $variants->currentPage(),
                'last_page' => $variants->lastPage(),
                'per_page' => $variants->perPage(),
                'total' => $variants->total(),
            ],
        ]);
    }

    /**
     * Create a new A/B test for a template
     *
     * @param Template $template
     * @param Request $request
     * @return JsonResponse
     */
    public function createAbTest(Template $template, Request $request): JsonResponse
    {
        $this->authorize('update', $template);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'goals' => 'required|array',
            'traffic_allocation' => 'integer|min:0|max:100',
            'distribution_method' => 'string|in:even,manual,weighted',
            'confidence_threshold' => 'numeric|min:80|max:99.99',
            'minimum_sample_size' => 'nullable|integer|min:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $testData = array_merge($request->only([
            'name', 'description', 'goals', 'traffic_allocation',
            'distribution_method', 'confidence_threshold', 'minimum_sample_size',
            'start_date', 'end_date'
        ]), [
            'created_by' => \Illuminate\Support\Facades\Auth::id(),
            'updated_by' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        $test = $this->variantService->createAbTest($testData, $template);

        return response()->json([
            'test' => $test,
            'message' => 'A/B test created successfully',
        ], 201);
    }

    /**
     * Add a variant to an A/B test
     *
     * @param Template $template
     * @param \App\Models\TemplateAbTest $test
     * @param Request $request
     * @return JsonResponse
     */
    public function addVariant(Template $template, \App\Models\TemplateAbTest $test, Request $request): JsonResponse
    {
        $this->authorize('update', $template);

        $request->validate([
            'variant_name' => 'required|string|max:255',
            'custom_structure' => 'nullable|array',
            'custom_config' => 'nullable|array',
        ]);

        $variantData = array_merge($request->only([
            'variant_name', 'custom_structure', 'custom_config',
        ]), [
            'template_id' => $template->id,
            'created_by' => \Illuminate\Support\Facades\Auth::id(),
            'updated_by' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        $variant = $this->variantService->addVariantToTest($test, $variantData);

        return response()->json([
            'variant' => $variant,
            'message' => 'Variant added to test successfully',
        ], 201);
    }

    /**
     * Start an A/B test
     *
     * @param \App\Models\TemplateAbTest $test
     * @return JsonResponse
     */
    public function startAbTest(\App\Models\TemplateAbTest $test): JsonResponse
    {
        if (!$test->variants->where('template_id', $test->template->id)->isNotEmpty()) {
            return response()->json([
                'message' => 'Cannot start test without variants',
            ], 422);
        }

        $success = $test->start();

        if (!$success) {
            return response()->json([
                'message' => 'Failed to start A/B test',
            ], 500);
        }

        return response()->json([
            'test' => $test->fresh(),
            'message' => 'A/B test started successfully',
        ]);
    }

    /**
     * Stop an A/B test
     *
     * @param \App\Models\TemplateAbTest $test
     * @return JsonResponse
     */
    public function stopAbTest(\App\Models\TemplateAbTest $test): JsonResponse
    {
        $results = $this->variantService->analyzeTestResults($test);

        $success = $test->complete();

        if (!$success) {
            return response()->json([
                'message' => 'Failed to complete A/B test',
            ], 500);
        }

        return response()->json([
            'test' => $test->fresh(),
            'results' => $results,
            'message' => 'A/B test completed successfully',
        ]);
    }

    /**
     * Get A/B test results
     *
     * @param \App\Models\TemplateAbTest $test
     * @return JsonResponse
     */
    public function getAbTestResults(\App\Models\TemplateAbTest $test): JsonResponse
    {
        $results = $this->variantService->analyzeTestResults($test);

        return response()->json([
            'test' => $test,
            'results' => $results,
            'generated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Record a conversion event
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recordConversion(Request $request): JsonResponse
    {
        $request->validate([
            'variant_id' => 'required|exists:template_variants,id',
            'conversion_value' => 'nullable|numeric|min:0',
        ]);

        $success = $this->variantService->recordConversion(
            $request->variant_id,
            ['conversion_value' => $request->conversion_value]
        );

        if (!$success) {
            return response()->json([
                'message' => 'Failed to record conversion',
            ], 500);
        }

        return response()->json([
            'message' => 'Conversion recorded successfully',
            'variant_id' => $request->variant_id,
        ]);
    }

    /**
     * Get split for user (determine which variant to show)
     *
     * @param Template $template
     * @param Request $request
     * @return JsonResponse
     */
    public function getVariantForUser(Template $template, Request $request): JsonResponse
    {
        $this->authorize('view', $template);

        $request->validate([
            'user_identifier' => 'required|string|max:255',
        ]);

        $activeTest = $this->variantService->getActiveTestForTemplate($template->id);

        if (!$activeTest) {
            // Return the original template if no active A/B test
            return response()->json([
                'variant_type' => 'original',
                'template' => new TemplateResource($template),
                'variant' => null,
            ]);
        }

        $selectedVariant = $this->variantService->splitTraffic($activeTest, $request->user_identifier);

        if (!$selectedVariant) {
            return response()->json([
                'variant_type' => 'original',
                'template' => new TemplateResource($template),
                'variant' => null,
            ]);
        }

        $effectiveConfig = $selectedVariant->getEffectiveConfig();
        $effectiveStructure = $selectedVariant->getEffectiveStructure();

        return response()->json([
            'variant_type' => 'variant',
            'template_id' => $template->id,
            'variant_id' => $selectedVariant->id,
            'variant_name' => $selectedVariant->variant_name,
            'effective_config' => $effectiveConfig,
            'effective_structure' => $effectiveStructure,
            'test_id' => $activeTest->id,
            'is_control' => $selectedVariant->is_control,
        ]);
    }
}