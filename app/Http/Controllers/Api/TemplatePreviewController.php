<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateResource;
use App\Models\Template;
use App\Models\LandingPage;
use App\Services\TemplatePreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * Template Preview Controller
 *
 * Provides real-time preview API endpoints for template customization and rendering.
 * Handles responsive preview modes and brand application.
 */
class TemplatePreviewController extends Controller
{
    public function __construct(
        private TemplatePreviewService $previewService
    ) {}

    /**
     * Generate template preview with brand application
     *
     * POST /api/templates/{id}/preview
     *
     * @param Request $request
     * @param Template $template
     * @return JsonResponse
     */
    public function preview(Request $request, Template $template): JsonResponse
    {
        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'config' => 'nullable|array',
            'device_mode' => 'nullable|string|in:desktop,tablet,mobile',
            'force_refresh' => 'boolean',
            'include_responsive' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $config = $request->get('config', []);
        $deviceMode = $request->get('device_mode', 'desktop');
        $forceRefresh = $request->boolean('force_refresh', false);
        $includeResponsive = $request->boolean('include_responsive', true);

        try {
            // Add refresh option if force refresh requested
            $options = [
                'device_mode' => $deviceMode,
                'include_responsive' => $includeResponsive,
            ];

            if ($forceRefresh) {
                // Clear cache before generating
                $this->previewService->clearTemplateCache($template->id);
            }

            // Generate preview data
            $preview = $this->previewService->generateTemplatePreview($template->id, $config, $options);

            $response = [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'preview' => $preview,
                'device_mode' => $deviceMode,
                'generated_at' => now()->toISOString(),
                'cache_used' => !$forceRefresh,
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Preview generation failed',
                'error' => $e->getMessage(),
                'template_id' => $template->id,
            ], 500);
        }
    }

    /**
     * Render template HTML for specific device mode
     *
     * GET /api/templates/{id}/render/{device_mode}
     *
     * @param Request $request
     * @param Template $template
     * @param string $deviceMode
     * @return JsonResponse
     */
    public function render(Request $request, Template $template, string $deviceMode): JsonResponse
    {
        // Validate device mode parameter
        if (!in_array($deviceMode, ['desktop', 'tablet', 'mobile'])) {
            return response()->json([
                'message' => 'Invalid device mode. Must be one of: desktop, tablet, mobile',
            ], 422);
        }

        $config = $request->get('config', []);

        try {
            $preview = $this->previewService->generateTemplatePreview($template->id, $config, [
                'device_mode' => $deviceMode
            ]);

            return response()->json([
                'template_id' => $template->id,
                'device_mode' => $deviceMode,
                'html' => $preview['compiled_html'] ?? '',
                'css' => $preview['responsive_styles'] ?? '',
                'viewport' => $preview['viewport'] ?? [],
                'rendered_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Template rendering failed',
                'error' => $e->getMessage(),
                'template_id' => $template->id,
                'device_mode' => $deviceMode,
            ], 500);
        }
    }

    /**
     * Get responsive preview for all device modes
     *
     * GET /api/templates/{id}/responsive-preview
     *
     * @param Request $request
     * @param Template $template
     * @return JsonResponse
     */
    public function responsivePreview(Request $request, Template $template): JsonResponse
    {
        $config = $request->get('config', []);

        try {
            $responsivePreview = $this->previewService->generateMultiDevicePreview($template->id, $config);

            return response()->json([
                'template_id' => $template->id,
                'template_name' => $template->name,
                'responsive_preview' => $responsivePreview,
                'device_modes' => array_keys($responsivePreview['devices'] ?? []),
                'generated_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Responsive preview generation failed',
                'error' => $e->getMessage(),
                'template_id' => $template->id,
            ], 500);
        }
    }

    /**
     * Get preview configuration options
     *
     * GET /api/templates/preview-options
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function previewOptions(Request $request): JsonResponse
    {
        try {
            $options = $this->previewService->getPreviewOptions();

            return response()->json([
                'preview_options' => $options,
                'server_time' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Preview options retrieval failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate landing page preview
     *
     * POST /api/landing-pages/{id}/preview
     *
     * @param Request $request
     * @param LandingPage $landingPage
     * @return JsonResponse
     */
    public function landingPagePreview(Request $request, LandingPage $landingPage): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_mode' => 'nullable|string|in:desktop,tablet,mobile',
            'force_refresh' => 'boolean',
            'include_responsive' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $deviceMode = $request->get('device_mode', 'desktop');
        $forceRefresh = $request->boolean('force_refresh', false);
        $includeResponsive = $request->boolean('include_responsive', true);

        try {
            // Add refresh option if force refresh requested
            $options = [
                'device_mode' => $deviceMode,
                'include_responsive' => $includeResponsive,
            ];

            if ($forceRefresh) {
                // Clear cache before generating
                $this->previewService->clearTemplateCache($landingPage->template_id);
            }

            // Generate preview data
            $preview = $this->previewService->generateLandingPagePreview($landingPage->id, $options);

            $response = [
                'landing_page_id' => $landingPage->id,
                'landing_page_name' => $landingPage->name,
                'template_id' => $landingPage->template_id,
                'preview' => $preview,
                'device_mode' => $deviceMode,
                'generated_at' => now()->toISOString(),
                'cache_used' => !$forceRefresh,
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Landing page preview generation failed',
                'error' => $e->getMessage(),
                'landing_page_id' => $landingPage->id,
            ], 500);
        }
    }

    /**
     * Clear preview cache for specific template
     *
     * POST /api/templates/{id}/clear-cache
     *
     * @param Template $template
     * @return JsonResponse
     */
    public function clearCache(Template $template): JsonResponse
    {
        try {
            $result = $this->previewService->clearTemplateCache($template->id);

            return response()->json([
                'message' => $result ? 'Preview cache cleared successfully' : 'Cache clearing failed',
                'template_id' => $template->id,
                'success' => $result,
                'cleared_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Cache clearing failed',
                'error' => $e->getMessage(),
                'template_id' => $template->id,
            ], 500);
        }
    }
}