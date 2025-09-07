<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComponentRequest;
use App\Http\Requests\UpdateComponentRequest;
use App\Http\Resources\ComponentResource;
use App\Models\Component;
use App\Services\ComponentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ComponentController extends Controller
{
    public function __construct(
        private ComponentService $componentService
    ) {}

    /**
     * Display a listing of components
     */
    public function index(Request $request): JsonResponse
    {
        $components = $this->componentService->search([
            'search' => $request->search,
            'category' => $request->category,
            'type' => $request->type,
            'is_active' => $request->is_active,
            'theme_id' => $request->theme_id,
            'version' => $request->version,
            'sort_by' => $request->sort_by,
            'sort_direction' => $request->sort_direction,
            'created_after' => $request->created_after,
            'created_before' => $request->created_before,
        ], Auth::user()->tenant_id, $request->per_page ?? 15);

        return response()->json([
            'components' => ComponentResource::collection($components->items()),
            'pagination' => [
                'current_page' => $components->currentPage(),
                'last_page' => $components->lastPage(),
                'per_page' => $components->perPage(),
                'total' => $components->total(),
            ]
        ]);
    }

    /**
     * Store a newly created component
     */
    public function store(StoreComponentRequest $request): JsonResponse
    {
        $component = $this->componentService->create($request->validated(), Auth::user()->tenant_id);

        return response()->json([
            'component' => new ComponentResource($component),
            'message' => 'Component created successfully'
        ], 201);
    }

    /**
     * Display the specified component
     */
    public function show(Component $component): JsonResponse
    {
        $this->authorize('view', $component);

        return response()->json([
            'component' => new ComponentResource($component)
        ]);
    }

    /**
     * Update the specified component
     */
    public function update(UpdateComponentRequest $request, Component $component): JsonResponse
    {
        $this->authorize('update', $component);

        $component = $this->componentService->update($component, $request->validated());

        // Clear related caches
        Cache::forget("component_{$component->id}");
        Cache::forget("component_preview_{$component->id}");

        return response()->json([
            'component' => new ComponentResource($component),
            'message' => 'Component updated successfully'
        ]);
    }

    /**
     * Remove the specified component
     */
    public function destroy(Component $component): JsonResponse
    {
        $this->authorize('delete', $component);

        // Check if component can be deleted
        if ($component->instances()->exists()) {
            return response()->json([
                'message' => 'Cannot delete component with existing instances. Delete instances first.'
            ], 422);
        }

        $this->componentService->delete($component);

        // Clear related caches
        Cache::forget("component_{$component->id}");
        Cache::forget("component_preview_{$component->id}");

        return response()->json(['message' => 'Component deleted successfully']);
    }

    /**
     * Duplicate an existing component
     */
    public function duplicate(Component $component, Request $request): JsonResponse
    {
        $this->authorize('view', $component);

        $modifications = $request->only(['name', 'slug', 'description', 'is_active']);
        $duplicatedComponent = $this->componentService->duplicate($component, $modifications);

        return response()->json([
            'component' => new ComponentResource($duplicatedComponent),
            'message' => 'Component duplicated successfully'
        ], 201);
    }

    /**
     * Create a new version of a component
     */
    public function createVersion(Component $component, Request $request): JsonResponse
    {
        $this->authorize('update', $component);

        $request->validate([
            'version' => 'required|string|regex:/^\d+\.\d+\.\d+$/',
            'changes' => 'nullable|array'
        ]);

        $newComponent = $this->componentService->createVersion(
            $component, 
            $request->version, 
            $request->changes ?? []
        );

        return response()->json([
            'component' => new ComponentResource($newComponent),
            'message' => 'Component version created successfully'
        ], 201);
    }

    /**
     * Activate a component
     */
    public function activate(Component $component): JsonResponse
    {
        $this->authorize('update', $component);

        $component = $this->componentService->activate($component);

        return response()->json([
            'component' => new ComponentResource($component),
            'message' => 'Component activated successfully'
        ]);
    }

    /**
     * Deactivate a component
     */
    public function deactivate(Component $component): JsonResponse
    {
        $this->authorize('update', $component);

        $component = $this->componentService->deactivate($component);

        return response()->json([
            'component' => new ComponentResource($component),
            'message' => 'Component deactivated successfully'
        ]);
    }

    /**
     * Get components by category
     */
    public function byCategory(string $category, Request $request): JsonResponse
    {
        $filters = [
            'is_active' => $request->is_active,
            'type' => $request->type
        ];

        $components = $this->componentService->getByCategory(
            $category, 
            Auth::user()->tenant_id, 
            $filters
        );

        return response()->json([
            'components' => ComponentResource::collection($components),
            'category' => $category
        ]);
    }

    /**
     * Get component preview data
     */
    public function preview(Component $component, Request $request): JsonResponse
    {
        $this->authorize('view', $component);

        $customConfig = $request->get('config', []);
        $previewData = $this->componentService->generatePreview($component, $customConfig);

        // Cache the preview for performance
        $cacheKey = "component_preview_{$component->id}_" . md5(serialize($customConfig));
        $cachedPreview = Cache::get($cacheKey);
        
        if ($cachedPreview) {
            return response()->json($cachedPreview);
        }

        Cache::put($cacheKey, $previewData, now()->addHours(1));

        return response()->json($previewData);
    }

    /**
     * Validate component configuration
     */
    public function validateConfig(Component $component, Request $request): JsonResponse
    {
        $this->authorize('view', $component);

        $request->validate([
            'config' => 'required|array'
        ]);

        try {
            // Create a temporary component instance for validation
            $tempComponent = clone $component;
            $tempComponent->config = $request->config;

            if ($tempComponent->validateConfig()) {
                return response()->json([
                    'valid' => true,
                    'message' => 'Configuration is valid'
                ]);
            } else {
                return response()->json([
                    'valid' => false,
                    'message' => 'Configuration is invalid',
                    'errors' => ['Configuration validation failed']
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Configuration validation failed',
                'errors' => [$e->getMessage()]
            ], 422);
        }
    }

    /**
     * Get component usage statistics
     */
    public function usage(Component $component): JsonResponse
    {
        $this->authorize('view', $component);

        $stats = $component->getUsageStats();
        $analytics = $this->componentService->getComponentStats(Auth::user()->tenant_id);

        return response()->json([
            'stats' => $stats,
            'analytics' => $analytics
        ]);
    }

    /**
     * Bulk operations on components
     */
    public function bulk(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|string|in:delete,activate,deactivate,duplicate',
            'component_ids' => 'required|array|min:1',
            'component_ids.*' => 'exists:components,id'
        ]);

        $components = Component::forTenant(Auth::user()->tenant_id)
            ->whereIn('id', $request->component_ids)
            ->get();

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($components as $component) {
            try {
                switch ($request->action) {
                    case 'delete':
                        if (!$component->instances()->exists()) {
                            $this->componentService->delete($component);
                            $results[] = ['id' => $component->id, 'status' => 'deleted'];
                            $successCount++;
                        } else {
                            $results[] = ['id' => $component->id, 'status' => 'skipped', 'reason' => 'Has instances'];
                            $errorCount++;
                        }
                        break;
                    case 'activate':
                        $this->componentService->activate($component);
                        $results[] = ['id' => $component->id, 'status' => 'activated'];
                        $successCount++;
                        break;
                    case 'deactivate':
                        $this->componentService->deactivate($component);
                        $results[] = ['id' => $component->id, 'status' => 'deactivated'];
                        $successCount++;
                        break;
                    case 'duplicate':
                        $duplicated = $this->componentService->duplicate($component);
                        $results[] = ['id' => $component->id, 'status' => 'duplicated', 'new_id' => $duplicated->id];
                        $successCount++;
                        break;
                }
            } catch (\Exception $e) {
                $results[] = ['id' => $component->id, 'status' => 'error', 'message' => $e->getMessage()];
                $errorCount++;
            }
        }

        return response()->json([
            'message' => "Bulk {$request->action} operation completed",
            'results' => $results,
            'summary' => [
                'success' => $successCount,
                'errors' => $errorCount,
                'total' => count($components)
            ]
        ]);
    }

    /**
     * Export components in various formats
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'component_ids' => 'nullable|array',
            'component_ids.*' => 'exists:components,id',
            'format' => 'string|in:json,grapejs,tailwind'
        ]);

        $format = $request->get('format', 'json');
        $componentIds = $request->get('component_ids', []);

        if (!empty($componentIds)) {
            $components = Component::forTenant(Auth::user()->tenant_id)
                ->whereIn('id', $componentIds)
                ->get();
        } else {
            $components = Component::forTenant(Auth::user()->tenant_id)->get();
        }

        switch ($format) {
            case 'grapejs':
                $data = $components->map(function ($component) {
                    return [
                        'id' => $component->id,
                        'name' => $component->name,
                        'category' => $component->category,
                        'type' => $component->type,
                        'config' => $component->config,
                        'grapejs_block' => [
                            'label' => $component->name,
                            'category' => $component->category,
                            'content' => "<div data-component-id='{$component->id}'></div>",
                            'attributes' => [
                                'data-component-id' => $component->id,
                                'data-component-category' => $component->category
                            ]
                        ]
                    ];
                });
                break;
            case 'tailwind':
                $data = $components->map(function ($component) {
                    return [
                        'id' => $component->id,
                        'name' => $component->name,
                        'category' => $component->category,
                        'tailwind_mappings' => $component->getTailwindMappings(),
                        'config' => $component->config
                    ];
                });
                break;
            default:
                $data = ComponentResource::collection($components);
        }

        return response()->json([
            'components' => $data,
            'format' => $format,
            'exported_at' => now()->toISOString(),
            'count' => $components->count()
        ]);
    }

    /**
     * Get cached component data for performance
     */
    public function cached(Component $component): JsonResponse
    {
        $this->authorize('view', $component);

        $cacheKey = "component_{$component->id}";
        
        $data = Cache::remember($cacheKey, now()->addHours(24), function () use ($component) {
            return [
                'component' => new ComponentResource($component),
                'preview_html' => $this->componentService->generatePreview($component)['preview_html'] ?? '',
                'responsive_variants' => $component->generateResponsiveVariants(),
                'accessibility_metadata' => $component->getAccessibilityMetadata(),
                'cached_at' => now()->toISOString()
            ];
        });

        return response()->json($data);
    }

    /**
     * Clear component cache
     */
    public function clearCache(Component $component): JsonResponse
    {
        $this->authorize('update', $component);

        Cache::forget("component_{$component->id}");
        Cache::forget("component_preview_{$component->id}");

        return response()->json(['message' => 'Component cache cleared successfully']);
    }
}