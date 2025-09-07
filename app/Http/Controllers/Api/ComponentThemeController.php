<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComponentThemeRequest;
use App\Http\Resources\ComponentThemeResource;
use App\Models\ComponentTheme;
use App\Services\ComponentThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ComponentThemeController extends Controller
{
    public function __construct(
        private ComponentThemeService $themeService
    ) {}

    /**
     * Display a listing of themes
     */
    public function index(Request $request): JsonResponse
    {
        $themes = $this->themeService->getThemesForTenant(Auth::user()->tenant_id, [
            'search' => $request->search,
            'is_default' => $request->is_default,
            'include_inactive' => $request->include_inactive,
            'sort_by' => $request->sort_by,
            'sort_direction' => $request->sort_direction,
            'created_after' => $request->created_after,
            'created_before' => $request->created_before,
        ], $request->per_page ?? 15);

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
     * Store a newly created theme
     */
    public function store(ComponentThemeRequest $request): JsonResponse
    {
        $theme = $this->themeService->createTheme($request->validated(), Auth::user()->tenant_id);

        return response()->json([
            'theme' => new ComponentThemeResource($theme),
            'message' => 'Theme created successfully'
        ], 201);
    }

    /**
     * Display the specified theme
     */
    public function show(ComponentTheme $theme): JsonResponse
    {
        $this->authorize('view', $theme);

        return response()->json([
            'theme' => new ComponentThemeResource($theme)
        ]);
    }

    /**
     * Update the specified theme
     */
    public function update(ComponentThemeRequest $request, ComponentTheme $theme): JsonResponse
    {
        $this->authorize('update', $theme);

        $theme = $this->themeService->updateTheme($theme, $request->validated());

        // Clear related caches
        Cache::forget("theme_{$theme->id}");
        Cache::forget("theme_css_{$theme->id}");
        Cache::forget("theme_components_{$theme->id}");

        return response()->json([
            'theme' => new ComponentThemeResource($theme),
            'message' => 'Theme updated successfully'
        ]);
    }

    /**
     * Remove the specified theme
     */
    public function destroy(ComponentTheme $theme): JsonResponse
    {
        $this->authorize('delete', $theme);

        // Check if theme can be deleted (not default theme and not in use)
        if ($theme->is_default) {
            return response()->json([
                'message' => 'Cannot delete default theme. Set another theme as default first.'
            ], 422);
        }

        if ($theme->components()->exists()) {
            return response()->json([
                'message' => 'Cannot delete theme with associated components. Remove associations first.'
            ], 422);
        }

        $this->themeService->deleteTheme($theme);

        // Clear related caches
        Cache::forget("theme_{$theme->id}");
        Cache::forget("theme_css_{$theme->id}");
        Cache::forget("theme_components_{$theme->id}");

        return response()->json(['message' => 'Theme deleted successfully']);
    }

    /**
     * Duplicate an existing theme
     */
    public function duplicate(ComponentTheme $theme, Request $request): JsonResponse
    {
        $this->authorize('view', $theme);

        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $newTheme = $this->themeService->duplicateTheme($theme, $request->name);

        return response()->json([
            'theme' => new ComponentThemeResource($newTheme),
            'message' => 'Theme duplicated successfully'
        ], 201);
    }

    /**
     * Apply theme to components
     */
    public function apply(Request $request, ComponentTheme $theme): JsonResponse
    {
        $this->authorize('update', $theme);

        $request->validate([
            'component_ids' => 'required|array',
            'component_ids.*' => 'exists:components,id'
        ]);

        $results = $this->themeService->applyTheme($theme, $request->component_ids);

        // Clear related caches
        foreach ($request->component_ids as $componentId) {
            Cache::forget("component_{$componentId}");
        }

        return response()->json([
            'message' => 'Theme applied successfully',
            'applied_count' => $results['applied_count'],
            'skipped_count' => $results['skipped_count']
        ]);
    }

    /**
     * Get theme preview with compiled CSS
     */
    public function preview(ComponentTheme $theme, Request $request): JsonResponse
    {
        $this->authorize('view', $theme);

        $componentIds = $request->get('component_ids', []);
        $previewData = $this->themeService->generatePreview($theme, $componentIds);

        // Cache the preview for performance
        $cacheKey = "theme_preview_{$theme->id}_" . md5(serialize($componentIds));
        $cachedPreview = Cache::get($cacheKey);

        if ($cachedPreview) {
            return response()->json($cachedPreview);
        }

        Cache::put($cacheKey, $previewData, now()->addHours(1));

        return response()->json($previewData);
    }

    /**
     * Generate CSS compilation for theme
     */
    public function compile(ComponentTheme $theme): JsonResponse
    {
        $this->authorize('view', $theme);

        $css = $this->themeService->compileCss($theme);

        return response()->json([
            'css' => $css,
            'theme_id' => $theme->id,
            'compiled_at' => now()->toISOString()
        ]);
    }

    /**
     * Validate theme configuration
     */
    public function validateConfig(ComponentTheme $theme, Request $request): JsonResponse
    {
        $this->authorize('view', $theme);

        $request->validate([
            'config' => 'required|array'
        ]);

        $validationResult = $this->themeService->validateThemeConfig($request->config);

        return response()->json([
            'valid' => $validationResult['valid'],
            'errors' => $validationResult['errors'] ?? [],
            'warnings' => $validationResult['warnings'] ?? [],
            'message' => $validationResult['valid']
                ? 'Theme configuration is valid'
                : 'Theme configuration has issues'
        ], $validationResult['valid'] ? 200 : 422);
    }

    /**
     * Get theme inheritance chain
     */
    public function inheritance(ComponentTheme $theme): JsonResponse
    {
        $this->authorize('view', $theme);

        $inheritanceChain = $theme->getInheritanceChain();

        return response()->json([
            'theme' => $theme->only(['id', 'name', 'slug', 'is_default']),
            'inheritance_chain' => $inheritanceChain,
            'merged_config' => $theme->getMergedConfig()
        ]);
    }

    /**
     * Override theme settings
     */
    public function override(ComponentTheme $theme, Request $request): JsonResponse
    {
        $this->authorize('update', $theme);

        $request->validate([
            'overrides' => 'required|array',
            'overrides.*' => 'array'
        ]);

        $originalConfig = $theme->config;
        $theme->config = array_merge($theme->config, $request->overrides);
        $theme->save();

        // Create backup of original config
        $backupPath = "themes/backups/override_{$theme->id}_" . now()->format('Y_m_d_H_i_s');
        Storage::put($backupPath . '.json', json_encode([
            'theme_id' => $theme->id,
            'original_config' => $originalConfig,
            'overrides' => $request->overrides,
            'backed_up_at' => now()->toISOString()
        ]));

        // Clear caches
        Cache::forget("theme_{$theme->id}");
        Cache::forget("theme_css_{$theme->id}");

        return response()->json([
            'theme' => new ComponentThemeResource($theme),
            'message' => 'Theme overrides applied successfully',
            'backup_path' => $backupPath
        ]);
    }

    /**
     * Set theme as default
     */
    public function setDefault(ComponentTheme $theme): JsonResponse
    {
        $this->authorize('update', $theme);

        $this->themeService->setAsDefault($theme);

        return response()->json([
            'theme' => new ComponentThemeResource($theme),
            'message' => 'Theme set as default successfully'
        ]);
    }

    /**
     * Create backup of theme
     */
    public function backup(ComponentTheme $theme): JsonResponse
    {
        $this->authorize('view', $theme);

        $backupPath = $this->themeService->backupTheme($theme);

        return response()->json([
            'backup_path' => $backupPath,
            'message' => 'Theme backup created successfully'
        ]);
    }

    /**
     * Restore theme from backup
     */
    public function restore(Request $request): JsonResponse
    {
        $request->validate([
            'backup_path' => 'required|string|regex:/^themes\/backups\//'
        ]);

        if (!Storage::exists($request->backup_path . '.json')) {
            return response()->json([
                'message' => 'Backup file not found'
            ], 404);
        }

        $backupData = json_decode(Storage::get($request->backup_path . '.json'), true);

        // Validate backup belongs to current tenant
        if (!isset($backupData['theme_id'])) {
            $themeId = $backupData['theme_id']; // Adjust based on backup structure
            $theme = ComponentTheme::forTenant(Auth::user()->tenant_id)->find($themeId);
        } else {
            $theme = ComponentTheme::forTenant(Auth::user()->tenant_id)->find($backupData['theme_id']);
        }

        if (!$theme) {
            return response()->json([
                'message' => 'Theme not found or access denied'
            ], 404);
        }

        $this->authorize('update', $theme);

        $theme->config = $backupData['original_config'];
        $theme->save();

        // Clear caches
        Cache::forget("theme_{$theme->id}");
        Cache::forget("theme_css_{$theme->id}");

        return response()->json([
            'theme' => new ComponentThemeResource($theme),
            'message' => 'Theme restored from backup successfully'
        ]);
    }

    /**
     * Get theme usage statistics
     */
    public function usage(ComponentTheme $theme): JsonResponse
    {
        $this->authorize('view', $theme);

        $stats = $this->themeService->getThemeUsageStats($theme);

        return response()->json([
            'stats' => $stats,
            'theme' => $theme->only(['id', 'name', 'slug'])
        ]);
    }

    /**
     * Bulk operations on themes
     */
    public function bulk(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|string|in:delete,set_default,export',
            'theme_ids' => 'required|array|min:1',
            'theme_ids.*' => 'exists:component_themes,id'
        ]);

        $themes = ComponentTheme::forTenant(Auth::user()->tenant_id)
            ->whereIn('id', $request->theme_ids)
            ->get();

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        if ($request->action === 'export') {
            $exportData = $this->themeService->bulkExportThemes($themes);
            return response()->json([
                'themes' => $exportData,
                'count' => $themes->count(),
                'exported_at' => now()->toISOString()
            ]);
        }

        foreach ($themes as $theme) {
            try {
                switch ($request->action) {
                    case 'delete':
                        if (!$theme->is_default && !$theme->components()->exists()) {
                            $this->themeService->deleteTheme($theme);
                            $results[] = ['id' => $theme->id, 'status' => 'deleted'];
                            $successCount++;
                        } else {
                            $results[] = ['id' => $theme->id, 'status' => 'skipped'];
                            $errorCount++;
                        }
                        break;

                    case 'set_default':
                        // Can only have one default theme, so only set first theme as default
                        if ($successCount === 0) {
                            $this->themeService->setAsDefault($theme);
                            $results[] = ['id' => $theme->id, 'status' => 'set_default'];
                            $successCount++;
                        } else {
                            $results[] = ['id' => $theme->id, 'status' => 'skipped', 'reason' => 'Already set default'];
                        }
                        break;
                }
            } catch (\Exception $e) {
                $results[] = ['id' => $theme->id, 'status' => 'error', 'message' => $e->getMessage()];
                $errorCount++;
            }
        }

        return response()->json([
            'message' => "Bulk {$request->action} operation completed",
            'results' => $results,
            'summary' => [
                'success' => $successCount,
                'errors' => $errorCount,
                'total' => count($themes)
            ]
        ]);
    }

    /**
     * Export themes in various formats
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'string|in:json,tailwind,css',
            'theme_ids' => 'nullable|array',
            'theme_ids.*' => 'exists:component_themes,id'
        ]);

        $format = $request->get('format', 'json');
        $themeIds = $request->get('theme_ids', []);

        if (!empty($themeIds)) {
            $themes = ComponentTheme::forTenant(Auth::user()->tenant_id)
                ->whereIn('id', $themeIds)
                ->get();
        } else {
            $themes = ComponentTheme::forTenant(Auth::user()->tenant_id)->get();
        }

        $exportData = $this->themeService->exportThemes($themes, $format);

        return response()->json([
            'themes' => $exportData,
            'format' => $format,
            'exported_at' => now()->toISOString(),
            'count' => $themes->count()
        ]);
    }

    /**
     * Get cached theme data for performance
     */
    public function cached(ComponentTheme $theme): JsonResponse
    {
        $this->authorize('view', $theme);

        $cacheKey = "theme_{$theme->id}";

        $data = Cache::remember($cacheKey, now()->addHours(24), function () use ($theme) {
            return [
                'theme' => new ComponentThemeResource($theme),
                'css' => $theme->compileToCss(),
                'merged_config' => $theme->getMergedConfig(),
                'inheritance_chain' => $theme->getInheritanceChain(),
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

        Cache::forget("theme_{$theme->id}");
        Cache::forget("theme_css_{$theme->id}");
        Cache::forget("theme_preview_{$theme->id}");
        Cache::forget("theme_components_{$theme->id}");

        return response()->json(['message' => 'Theme cache cleared successfully']);
    }
}