<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComponentThemeRequest;
use App\Http\Resources\ComponentThemeResource;
use App\Models\ComponentTheme;
use App\Services\GrapeJSThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ComponentThemeController extends Controller
{
    public function __construct(
        private GrapeJSThemeService $grapeJSThemeService
    ) {}

    /**
     * Display a listing of themes
     */
    public function index(Request $request): JsonResponse
    {
        $themes = ComponentTheme::forTenant(Auth::user()->tenant_id)
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->is_default !== null, function ($query) use ($request) {
                $query->where('is_default', $request->boolean('is_default'));
            })
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'themes' => ComponentThemeResource::collection($themes->items()),
            'pagination' => [
                'current_page' => $themes->currentPage(),
                'last_page' => $themes->lastPage(),
                'per_page' => $themes->perPage(),
                'total' => $themes->total(),
            ]
        ]);
    }

    /**
     * Get themes formatted for GrapeJS integration
     */
    public function grapeJSIndex(): JsonResponse
    {
        $themes = $this->grapeJSThemeService->getThemesForGrapeJS(Auth::user()->tenant_id);

        return response()->json([
            'themes' => $themes,
            'default_theme' => $themes->firstWhere('isDefault', true)
        ]);
    }

    /**
     * Store a newly created theme
     */
    public function store(ComponentThemeRequest $request): JsonResponse
    {
        $theme = ComponentTheme::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $request->name,
            'slug' => str($request->name)->slug(),
            'config' => $request->config,
            'is_default' => $request->boolean('is_default', false),
        ]);

        // If this is set as default, unset other default themes
        if ($theme->is_default) {
            ComponentTheme::forTenant(Auth::user()->tenant_id)
                ->where('id', '!=', $theme->id)
                ->update(['is_default' => false]);
        }

        // Clear theme cache
        $this->grapeJSThemeService->clearThemeCache($theme);

        return response()->json([
            'theme' => new ComponentThemeResource($theme),
            'grapejs_data' => $this->grapeJSThemeService->exportForGrapeJS($theme)
        ], 201);
    }

    /**
     * Display the specified theme
     */
    public function show(ComponentTheme $theme): JsonResponse
    {
        $this->authorize('view', $theme);

        return response()->json([
            'theme' => new ComponentThemeResource($theme),
            'grapejs_data' => $this->grapeJSThemeService->exportForGrapeJS($theme)
        ]);
    }

    /**
     * Update the specified theme
     */
    public function update(ComponentThemeRequest $request, ComponentTheme $theme): JsonResponse
    {
        $this->authorize('update', $theme);

        $theme->update([
            'name' => $request->name,
            'slug' => str($request->name)->slug(),
            'config' => $request->config,
            'is_default' => $request->boolean('is_default', false),
        ]);

        // If this is set as default, unset other default themes
        if ($theme->is_default) {
            ComponentTheme::forTenant(Auth::user()->tenant_id)
                ->where('id', '!=', $theme->id)
                ->update(['is_default' => false]);
        }

        // Clear theme cache
        $this->grapeJSThemeService->clearThemeCache($theme);

        return response()->json([
            'theme' => new ComponentThemeResource($theme),
            'grapejs_data' => $this->grapeJSThemeService->exportForGrapeJS($theme)
        ]);
    }

    /**
     * Remove the specified theme
     */
    public function destroy(ComponentTheme $theme): JsonResponse
    {
        $this->authorize('delete', $theme);

        // Prevent deletion of default theme if it's the only one
        if ($theme->is_default) {
            $otherThemes = ComponentTheme::forTenant(Auth::user()->tenant_id)
                ->where('id', '!=', $theme->id)
                ->exists();

            if (!$otherThemes) {
                return response()->json([
                    'message' => 'Cannot delete the only theme. Create another theme first.'
                ], 422);
            }

            // Set another theme as default
            ComponentTheme::forTenant(Auth::user()->tenant_id)
                ->where('id', '!=', $theme->id)
                ->first()
                ->update(['is_default' => true]);
        }

        // Clear theme cache
        $this->grapeJSThemeService->clearThemeCache($theme);

        $theme->delete();

        return response()->json(['message' => 'Theme deleted successfully']);
    }

    /**
     * Duplicate an existing theme
     */
    public function duplicate(ComponentTheme $theme): JsonResponse
    {
        $this->authorize('view', $theme);

        $duplicatedTheme = ComponentTheme::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $theme->name . ' (Copy)',
            'slug' => str($theme->name . ' (Copy)')->slug(),
            'config' => $theme->config,
            'is_default' => false,
        ]);

        return response()->json([
            'theme' => new ComponentThemeResource($duplicatedTheme),
            'grapejs_data' => $this->grapeJSThemeService->exportForGrapeJS($duplicatedTheme)
        ], 201);
    }

    /**
     * Apply theme to components
     */
    public function apply(Request $request, ComponentTheme $theme): JsonResponse
    {
        $this->authorize('update', $theme);

        $request->validate([
            'component_ids' => 'nullable|array',
            'component_ids.*' => 'exists:components,id'
        ]);

        $appliedCount = $theme->applyToComponents($request->component_ids ?? []);

        return response()->json([
            'message' => "Theme applied to {$appliedCount} components",
            'applied_count' => $appliedCount
        ]);
    }

    /**
     * Import theme from various sources
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'source' => 'required|string|in:json,file,url,grapejs',
            'config' => 'required|array',
            'grapejs_config' => 'nullable|array'
        ]);

        $config = $request->config;

        // If importing from GrapeJS, convert the configuration
        if ($request->source === 'grapejs' && $request->grapejs_config) {
            $config = $this->grapeJSThemeService->convertFromGrapeJSStyles($request->grapejs_config);
        }

        $theme = ComponentTheme::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $request->name,
            'slug' => str($request->name)->slug(),
            'config' => $config,
            'is_default' => false,
        ]);

        return response()->json([
            'theme' => new ComponentThemeResource($theme),
            'grapejs_data' => $this->grapeJSThemeService->exportForGrapeJS($theme)
        ], 201);
    }

    /**
     * Export theme in various formats
     */
    public function export(ComponentTheme $theme, Request $request): JsonResponse
    {
        $this->authorize('view', $theme);

        $format = $request->query('format', 'json');

        switch ($format) {
            case 'grapejs':
                $data = $this->grapeJSThemeService->exportForGrapeJS($theme);
                break;
            case 'css':
                $data = [
                    'css' => $theme->compileToCss(),
                    'variables' => $theme->generateCssVariables()
                ];
                break;
            case 'tailwind':
                $data = [
                    'mappings' => $this->grapeJSThemeService->generateTailwindMappings($theme),
                    'config' => $theme->config
                ];
                break;
            default:
                $data = [
                    'theme' => new ComponentThemeResource($theme),
                    'config' => $theme->config,
                    'exported_at' => now()->toISOString()
                ];
        }

        return response()->json($data);
    }

    /**
     * Get theme preview HTML
     */
    public function preview(ComponentTheme $theme): JsonResponse
    {
        $this->authorize('view', $theme);

        $previewHtml = $theme->generatePreviewHtml();
        $accessibilityIssues = $theme->checkAccessibility();
        $compatibilityIssues = $this->grapeJSThemeService->validateGrapeJSCompatibility($theme);

        return response()->json([
            'html' => $previewHtml,
            'accessibility_issues' => $accessibilityIssues,
            'compatibility_issues' => $compatibilityIssues,
            'css_variables' => $this->grapeJSThemeService->generateGrapeJSCssVariables($theme)
        ]);
    }

    /**
     * Validate theme configuration
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'config' => 'required|array'
        ]);

        try {
            // Create a temporary theme instance for validation
            $tempTheme = new ComponentTheme([
                'config' => $request->config
            ]);

            $tempTheme->validateConfig($request->config);
            $accessibilityIssues = $tempTheme->checkAccessibility();
            $compatibilityIssues = $this->grapeJSThemeService->validateGrapeJSCompatibility($tempTheme);

            return response()->json([
                'valid' => true,
                'accessibility_issues' => $accessibilityIssues,
                'compatibility_issues' => $compatibilityIssues,
                'css_variables' => $this->grapeJSThemeService->generateGrapeJSCssVariables($tempTheme)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'errors' => [$e->getMessage()]
            ], 422);
        }
    }

    /**
     * Get theme usage statistics
     */
    public function usage(ComponentTheme $theme): JsonResponse
    {
        $this->authorize('view', $theme);

        $componentCount = $theme->components()->count();
        $pageCount = $theme->components()
            ->with('instances')
            ->get()
            ->pluck('instances')
            ->flatten()
            ->pluck('page_id')
            ->unique()
            ->count();

        return response()->json([
            'component_count' => $componentCount,
            'page_count' => $pageCount,
            'is_default' => $theme->is_default,
            'last_used' => $theme->updated_at
        ]);
    }

    /**
     * Get cached theme data for performance
     */
    public function cached(ComponentTheme $theme): JsonResponse
    {
        $this->authorize('view', $theme);

        $cacheKey = "theme_data_{$theme->id}";
        
        $data = Cache::remember($cacheKey, now()->addHours(24), function () use ($theme) {
            return [
                'theme' => new ComponentThemeResource($theme),
                'grapejs_data' => $this->grapeJSThemeService->exportForGrapeJS($theme),
                'css_variables' => $this->grapeJSThemeService->generateGrapeJSCssVariables($theme),
                'tailwind_mappings' => $this->grapeJSThemeService->generateTailwindMappings($theme),
                'accessibility_check' => $theme->checkAccessibility(),
                'cached_at' => now()->toISOString()
            ];
        });

        return response()->json($data);
    }

    /**
     * Clear theme cache
     */
    public function clearCache(ComponentTheme $theme): JsonResponse
    {
        $this->authorize('update', $theme);

        $this->grapeJSThemeService->clearThemeCache($theme);
        Cache::forget("theme_data_{$theme->id}");

        return response()->json(['message' => 'Theme cache cleared successfully']);
    }

    /**
     * Bulk operations on themes
     */
    public function bulk(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|string|in:delete,export,apply',
            'theme_ids' => 'required|array|min:1',
            'theme_ids.*' => 'exists:component_themes,id'
        ]);

        $themes = ComponentTheme::forTenant(Auth::user()->tenant_id)
            ->whereIn('id', $request->theme_ids)
            ->get();

        $results = [];

        foreach ($themes as $theme) {
            switch ($request->action) {
                case 'delete':
                    if (!$theme->is_default || $themes->count() > 1) {
                        $this->grapeJSThemeService->clearThemeCache($theme);
                        $theme->delete();
                        $results[] = ['id' => $theme->id, 'status' => 'deleted'];
                    } else {
                        $results[] = ['id' => $theme->id, 'status' => 'skipped', 'reason' => 'Cannot delete default theme'];
                    }
                    break;
                case 'export':
                    $results[] = [
                        'id' => $theme->id,
                        'status' => 'exported',
                        'data' => $this->grapeJSThemeService->exportForGrapeJS($theme)
                    ];
                    break;
                case 'apply':
                    $appliedCount = $theme->applyToComponents();
                    $results[] = [
                        'id' => $theme->id,
                        'status' => 'applied',
                        'applied_count' => $appliedCount
                    ];
                    break;
            }
        }

        return response()->json([
            'message' => "Bulk {$request->action} completed",
            'results' => $results
        ]);
    }
}