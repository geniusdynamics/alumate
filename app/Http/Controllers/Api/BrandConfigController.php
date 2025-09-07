<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LandingPageResource;
use App\Models\LandingPage;
use App\Models\BrandConfig;
use App\Services\LandingPageService;
use App\Services\BrandCustomizerService;
use App\Services\MediaUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

/**
 * Brand Configuration Controller
 *
 * Handles brand management specifically for landing pages,
 * integrating brand assets with landing page customization
 */
class BrandConfigController extends Controller
{
    public function __construct(
        private LandingPageService $landingPageService,
        private BrandCustomizerService $brandService,
        private MediaUploadService $uploadService
    ) {}

    /**
     * Display a listing of brand configurations for the tenant
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = optional(Auth::user())->tenant_id ?? 1;

        $configs = BrandConfig::where('tenant_id', $tenantId)
            ->when($request->is_active, fn($q) => $q->active())
            ->when($request->is_default, fn($q) => $q->default())
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
            )
            ->with(['creator', 'updater'])
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'brand_configs' => $configs->items(),
            'pagination' => [
                'current_page' => $configs->currentPage(),
                'last_page' => $configs->lastPage(),
                'per_page' => $configs->perPage(),
                'total' => $configs->total(),
            ],
            'meta' => [
                'total_active' => BrandConfig::active()->where('tenant_id', $tenantId)->count(),
                'total_default' => BrandConfig::default()->where('tenant_id', $tenantId)->count(),
            ]
        ]);
    }

    /**
     * Display the specified brand configuration
     *
     * @param BrandConfig $brandConfig
     * @return JsonResponse
     */
    public function show(BrandConfig $brandConfig): JsonResponse
    {
        $this->authorize('view', $brandConfig);

        return response()->json([
            'brand_config' => $brandConfig->load(['creator', 'updater']),
            'effective_config' => $brandConfig->getEffectiveConfig(),
            'usage_stats' => []
        ]);
    }

    /**
     * Store a newly created brand configuration
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->validateBrandConfig($request->all());

        $tenantId = auth()->user()->tenant_id;

        $brandConfig = BrandConfig::create(array_merge($request->validated(), [
            'tenant_id' => $tenantId,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));

        return response()->json([
            'brand_config' => $brandConfig->load(['creator', 'updater']),
            'message' => 'Brand configuration created successfully'
        ], 201);
    }

    /**
     * Update the specified brand configuration
     *
     * @param Request $request
     * @param BrandConfig $brandConfig
     * @return JsonResponse
     */
    public function update(Request $request, BrandConfig $brandConfig): JsonResponse
    {
        $this->authorize('update', $brandConfig);

        $this->validateBrandConfigUpdate($request->all(), $brandConfig);

        $brandConfig->update(array_merge($request->validated(), [
            'updated_by' => auth()->id(),
        ]));

        // Clear any cached configurations
        Cache::tags(['brand-configs'])->flush();

        return response()->json([
            'brand_config' => $brandConfig->fresh(),
            'message' => 'Brand configuration updated successfully'
        ]);
    }

    /**
     * Remove the specified brand configuration
     *
     * @param BrandConfig $brandConfig
     * @return JsonResponse
     */
    public function destroy(BrandConfig $brandConfig): JsonResponse
    {
        $this->authorize('delete', $brandConfig);

        // Check if brand config is in use
        if ($this->brandConfigInUse($brandConfig)) {
            return response()->json([
                'message' => 'Cannot delete brand configuration that is currently in use by landing pages'
            ], 422);
        }

        $brandConfig->delete();

        return response()->json([
            'message' => 'Brand configuration deleted successfully'
        ]);
    }

    /**
     * Upload logo for brand configuration
     *
     * @param Request $request
     * @param BrandConfig $brandConfig
     * @return JsonResponse
     */
    public function uploadLogo(Request $request, BrandConfig $brandConfig): JsonResponse
    {
        $this->authorize('update', $brandConfig);

        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = $brandConfig->tenant_id;
        $uploadedFile = $this->brandService->uploadLogo($request->file('logo'), $tenantId);

        // Update brand config with logo URL
        $brandConfig->update([
            'logo_url' => $uploadedFile->url,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'logo' => $uploadedFile,
            'message' => 'Logo uploaded successfully'
        ]);
    }

    /**
     * Upload favicon for brand configuration
     *
     * @param Request $request
     * @param BrandConfig $brandConfig
     * @return JsonResponse
     */
    public function uploadFavicon(Request $request, BrandConfig $brandConfig): JsonResponse
    {
        $this->authorize('update', $brandConfig);

        $validator = Validator::make($request->all(), [
            'favicon' => 'required|image|mimes:ico,png,jpg,gif|max:1024'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = $brandConfig->tenant_id;
        $faviconFile = $this->brandService->uploadLogo($request->file('favicon'), $tenantId);

        // Set as favicon type
        $faviconFile->update(['type' => 'favicon']);

        $brandConfig->update([
            'favicon_url' => $faviconFile->url,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'favicon' => $faviconFile,
            'message' => 'Favicon uploaded successfully'
        ]);
    }

    /**
     * Upload custom asset for brand configuration
     *
     * @param Request $request
     * @param BrandConfig $brandConfig
     * @return JsonResponse
     */
    public function uploadCustomAsset(Request $request, BrandConfig $brandConfig): JsonResponse
    {
        $this->authorize('update', $brandConfig);

        $validator = Validator::make($request->all(), [
            'asset' => 'required|file|mimes:css,js,woff,woff2,ttf,otf,png,jpg,jpeg,gif,webp|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('asset');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = "brand-assets/{$brandConfig->tenant_id}/custom/" . $filename;

        $storedPath = $file->storeAs("brand-assets/{$brandConfig->tenant_id}/custom", $filename, 'public');

        return response()->json([
            'asset_url' => Storage::url($storedPath),
            'filename' => $filename,
            'mime_type' => $file->getMimeType(),
            'message' => 'Custom asset uploaded successfully'
        ]);
    }

    /**
     * Apply brand configuration to landing page template
     *
     * @param Request $request
     * @param BrandConfig $brandConfig
     * @param int $templateId
     * @return JsonResponse
     */
    public function applyToTemplate(Request $request, BrandConfig $brandConfig, int $templateId): JsonResponse
    {
        $this->authorize('view', $brandConfig);

        $request->validate([
            'customizations' => 'nullable|array'
        ]);

        try {
            $landingPage = $this->landingPageService->createFromTemplate($templateId, array_merge(
                $request->customizations ?? [],
                ['brand_config' => $brandConfig->getEffectiveConfig()]
            ));

            return response()->json([
                'landing_page' => new LandingPageResource($landingPage),
                'message' => 'Brand configuration applied to landing page successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to apply brand configuration',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Generate brand preview
     *
     * @param Request $request
     * @param BrandConfig $brandConfig
     * @return JsonResponse
     */
    public function preview(Request $request, BrandConfig $brandConfig): JsonResponse
    {
        $this->authorize('view', $brandConfig);

        $request->validate([
            'template_id' => 'nullable|exists:templates,id',
            'config' => 'nullable|array'
        ]);

        $effectiveConfig = $brandConfig->getEffectiveConfig();

        if ($request->config) {
            $effectiveConfig = array_merge($effectiveConfig, $request->config);
        }

        return response()->json([
            'brand_config' => $brandConfig,
            'effective_config' => $effectiveConfig,
            'preview_data' => [
                'css_variables' => $this->generateCssVariables($effectiveConfig),
                'preview_elements' => $this->generatePreviewElements($effectiveConfig),
            ]
        ]);
    }

    /**
     * Export brand configuration
     *
     * @param Request $request
     * @param BrandConfig $brandConfig
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request, BrandConfig $brandConfig): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('view', $brandConfig);

        $exportPath = $this->brandService->exportAssets(
            $brandConfig->toArray(),
            [],
            $request->format ?? 'json',
            $brandConfig->tenant_id
        );

        return response()->download($exportPath)->deleteFileAfterSend();
    }

    /**
     * Import brand configuration
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'config_file' => 'required|file|mimes:json',
        ]);

        $file = $request->file('config_file');
        $configData = json_decode(file_get_contents($file->getPathname()), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['message' => 'Invalid JSON file'], 422);
        }

        if (!isset($configData['brand_config'])) {
            return response()->json(['message' => 'Invalid brand configuration format'], 422);
        }

        $tenantId = optional(Auth::user())->tenant_id ?? 1;

        $brandConfig = BrandConfig::create(array_merge($configData['brand_config'], [
            'tenant_id' => $tenantId,
            'created_by' => optional(Auth::id()),
            'updated_by' => optional(Auth::id()),
        ]));

        return response()->json([
            'brand_config' => $brandConfig,
            'message' => 'Brand configuration imported successfully'
        ], 201);
    }

    /**
     * Validate brand configuration data
     *
     * @param array $data
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateBrandConfig(array $data): void
    {
        $rules = BrandConfig::getValidationRules();
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    /**
     * Validate brand configuration update data
     *
     * @param array $data
     * @param BrandConfig $brandConfig
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateBrandConfigUpdate(array $data, BrandConfig $brandConfig): void
    {
        $rules = array_merge([
            'name' => 'sometimes|required|string|max:255|unique:brand_configs,name,' . $brandConfig->id
        ], array_diff_key(BrandConfig::getValidationRules(), ['tenant_id' => '']));

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    /**
     * Check if brand configuration is currently in use by landing pages
     *
     * @param BrandConfig $brandConfig
     * @return bool
     */
    private function brandConfigInUse(BrandConfig $brandConfig): bool
    {
        return LandingPage::where('brand_config', 'like', '%' . $brandConfig->name . '%')
            ->orWhereJsonContains('brand_config', $brandConfig->id)
            ->exists();
    }

    /**
     * Generate CSS variables from brand configuration
     *
     * @param array $config
     * @return string
     */
    private function generateCssVariables(array $config): string
    {
        $css = ":root {\n";

        if (isset($config['colors'])) {
            foreach ($config['colors'] as $key => $value) {
                $css .= "  --brand-color-{$key}: {$value};\n";
            }
        }

        if (isset($config['typography'])) {
            $css .= "  --brand-font-family: {$config['typography']['font_family']};\n";
            if (isset($config['typography']['heading_font_family'])) {
                $css .= "  --brand-heading-font-family: {$config['typography']['heading_font_family']};\n";
            }
        }

        if (isset($config['spacing'])) {
            foreach ($config['spacing'] as $key => $value) {
                $css .= "  --brand-spacing-{$key}: {$value};\n";
            }
        }

        $css .= "}\n\n";

        // Add custom CSS if present
        if (isset($config['custom_css'])) {
            $css .= $config['custom_css'];
        }

        return $css;
    }

    /**
     * Generate preview elements for brand configuration
     *
     * @param array $config
     * @return array
     */
    private function generatePreviewElements(array $config): array
    {
        return [
            'hero' => [
                'background' => $config['colors']['primary'] ?? '#007bff',
                'logo' => $config['assets']['logo_url'] ?? null,
                'typography' => [
                    'font_family' => $config['typography']['font_family'] ?? 'Inter, sans-serif'
                ]
            ],
            'buttons' => [
                'primary' => [
                    'background' => $config['colors']['primary'] ?? '#007bff',
                    'color' => '#ffffff'
                ],
                'secondary' => [
                    'background' => $config['colors']['secondary'] ?? '#6c757d',
                    'color' => '#ffffff'
                ]
            ],
            'assets' => $config['assets'] ?? []
        ];
    }
}