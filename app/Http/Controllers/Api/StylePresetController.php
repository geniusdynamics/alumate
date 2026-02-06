<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StylePreset;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class StylePresetController extends Controller
{
    /**
     * Get all style presets for the current tenant
     */
    public function index(): JsonResponse
    {
        $presets = StylePreset::where('tenant_id', tenant('id'))
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json($presets);
    }

    /**
     * Store a new style preset
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'category' => 'required|string|max:100',
            'styles' => 'required|array',
            'tailwind_classes' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $preset = StylePreset::create([
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category,
            'styles' => $request->styles,
            'tailwind_classes' => $request->tailwind_classes ?? [],
            'tenant_id' => tenant('id'),
            'created_by' => auth()->id()
        ]);

        return response()->json($preset, 201);
    }

    /**
     * Get a specific style preset
     */
    public function show(StylePreset $stylePreset): JsonResponse
    {
        // Ensure the preset belongs to the current tenant
        if ($stylePreset->tenant_id !== tenant('id')) {
            return response()->json(['message' => 'Style preset not found'], 404);
        }

        return response()->json($stylePreset);
    }

    /**
     * Update a style preset
     */
    public function update(Request $request, StylePreset $stylePreset): JsonResponse
    {
        // Ensure the preset belongs to the current tenant
        if ($stylePreset->tenant_id !== tenant('id')) {
            return response()->json(['message' => 'Style preset not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:500',
            'category' => 'sometimes|required|string|max:100',
            'styles' => 'sometimes|required|array',
            'tailwind_classes' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $stylePreset->update($request->only([
            'name',
            'description',
            'category',
            'styles',
            'tailwind_classes'
        ]));

        return response()->json($stylePreset);
    }

    /**
     * Delete a style preset
     */
    public function destroy(StylePreset $stylePreset): JsonResponse
    {
        // Ensure the preset belongs to the current tenant
        if ($stylePreset->tenant_id !== tenant('id')) {
            return response()->json(['message' => 'Style preset not found'], 404);
        }

        $stylePreset->delete();

        return response()->json(['message' => 'Style preset deleted successfully']);
    }

    /**
     * Get style presets by category
     */
    public function byCategory(string $category): JsonResponse
    {
        $presets = StylePreset::where('tenant_id', tenant('id'))
            ->where('category', $category)
            ->orderBy('name')
            ->get();

        return response()->json($presets);
    }

    /**
     * Duplicate a style preset
     */
    public function duplicate(StylePreset $stylePreset): JsonResponse
    {
        // Ensure the preset belongs to the current tenant
        if ($stylePreset->tenant_id !== tenant('id')) {
            return response()->json(['message' => 'Style preset not found'], 404);
        }

        $duplicatedPreset = StylePreset::create([
            'name' => $stylePreset->name . ' (Copy)',
            'description' => $stylePreset->description,
            'category' => $stylePreset->category,
            'styles' => $stylePreset->styles,
            'tailwind_classes' => $stylePreset->tailwind_classes,
            'tenant_id' => tenant('id'),
            'created_by' => auth()->id()
        ]);

        return response()->json($duplicatedPreset, 201);
    }

    /**
     * Get style preset categories
     */
    public function categories(): JsonResponse
    {
        $categories = StylePreset::where('tenant_id', tenant('id'))
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        return response()->json($categories);
    }

    /**
     * Bulk store style presets (for importing)
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'presets' => 'required|array',
            'presets.*.name' => 'required|string|max:255',
            'presets.*.description' => 'nullable|string|max:500',
            'presets.*.category' => 'required|string|max:100',
            'presets.*.styles' => 'required|array',
            'presets.*.tailwind_classes' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $createdPresets = [];
        
        foreach ($request->presets as $presetData) {
            $preset = StylePreset::create([
                'name' => $presetData['name'],
                'description' => $presetData['description'] ?? null,
                'category' => $presetData['category'],
                'styles' => $presetData['styles'],
                'tailwind_classes' => $presetData['tailwind_classes'] ?? [],
                'tenant_id' => tenant('id'),
                'created_by' => auth()->id()
            ]);
            
            $createdPresets[] = $preset;
        }

        return response()->json([
            'message' => 'Style presets imported successfully',
            'presets' => $createdPresets
        ], 201);
    }

    /**
     * Export style presets
     */
    public function export(): JsonResponse
    {
        $presets = StylePreset::where('tenant_id', tenant('id'))
            ->select(['name', 'description', 'category', 'styles', 'tailwind_classes'])
            ->get();

        return response()->json([
            'presets' => $presets,
            'exported_at' => now()->toISOString(),
            'tenant_id' => tenant('id')
        ]);
    }
}