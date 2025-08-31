<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BrandCustomizerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BrandCustomizerController extends Controller
{
    public function __construct(
        private BrandCustomizerService $brandCustomizerService
    ) {}

    /**
     * Get all brand customizer data
     */
    public function getData(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        
        $data = $this->brandCustomizerService->getBrandData($tenantId);
        
        return response()->json($data);
    }

    /**
     * Upload brand logos
     */
    public function uploadLogos(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'logos' => 'required|array|max:10',
            'logos.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = auth()->user()->tenant_id;
        $uploadedLogos = [];

        foreach ($request->file('logos') as $file) {
            $logo = $this->brandCustomizerService->uploadLogo($file, $tenantId);
            $uploadedLogos[] = $logo;
        }

        return response()->json($uploadedLogos);
    }

    /**
     * Set primary logo
     */
    public function setPrimaryLogo(string $logoId): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        
        $result = $this->brandCustomizerService->setPrimaryLogo($logoId, $tenantId);
        
        if (!$result) {
            return response()->json(['message' => 'Logo not found'], 404);
        }

        return response()->json(['message' => 'Primary logo updated successfully']);
    }

    /**
     * Optimize logo
     */
    public function optimizeLogo(string $logoId): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        
        $optimizedLogo = $this->brandCustomizerService->optimizeLogo($logoId, $tenantId);
        
        if (!$optimizedLogo) {
            return response()->json(['message' => 'Logo not found'], 404);
        }

        return response()->json($optimizedLogo);
    }

    /**
     * Delete logo
     */
    public function deleteLogo(string $logoId): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        
        $result = $this->brandCustomizerService->deleteLogo($logoId, $tenantId);
        
        if (!$result) {
            return response()->json(['message' => 'Logo not found'], 404);
        }

        return response()->json(['message' => 'Logo deleted successfully']);
    }

    /**
     * Create or update brand color
     */
    public function storeColor(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'value' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'type' => 'required|in:primary,secondary,accent,neutral,semantic',
            'usageGuidelines' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = auth()->user()->tenant_id;
        
        $color = $this->brandCustomizerService->createColor($request->validated(), $tenantId);
        
        return response()->json($color, 201);
    }

    /**
     * Update brand color
     */
    public function updateColor(Request $request, string $colorId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'value' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'type' => 'required|in:primary,secondary,accent,neutral,semantic',
            'usageGuidelines' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = auth()->user()->tenant_id;
        
        $color = $this->brandCustomizerService->updateColor($colorId, $request->validated(), $tenantId);
        
        if (!$color) {
            return response()->json(['message' => 'Color not found'], 404);
        }

        return response()->json($color);
    }

    /**
     * Delete brand color
     */
    public function deleteColor(string $colorId): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        
        $result = $this->brandCustomizerService->deleteColor($colorId, $tenantId);
        
        if (!$result) {
            return response()->json(['message' => 'Color not found'], 404);
        }

        return response()->json(['message' => 'Color deleted successfully']);
    }

    /**
     * Upload custom fonts
     */
    public function uploadFonts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fonts' => 'required|array|max:10',
            'fonts.*' => 'required|file|mimes:woff,woff2,ttf,otf|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = auth()->user()->tenant_id;
        
        $fontUrl = $this->brandCustomizerService->uploadFonts($request->file('fonts'), $tenantId);
        
        return response()->json(['fontUrl' => $fontUrl]);
    }

    /**
     * Create or update brand font
     */
    public function storeFont(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'family' => 'required|string|max:255',
            'type' => 'required|in:heading,body,display,monospace',
            'source' => 'required|in:google,adobe,custom,system',
            'url' => 'nullable|url',
            'weights' => 'required|array|min:1',
            'weights.*' => 'integer|min:100|max:900',
            'styles' => 'required|array|min:1',
            'styles.*' => 'string|in:normal,italic,oblique',
            'fallbacks' => 'required|array|min:1',
            'fallbacks.*' => 'string|max:255',
            'loadingStrategy' => 'required|in:preload,swap,lazy'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = auth()->user()->tenant_id;
        
        $font = $this->brandCustomizerService->createFont($request->validated(), $tenantId);
        
        return response()->json($font, 201);
    }

    /**
     * Update brand font
     */
    public function updateFont(Request $request, string $fontId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'family' => 'required|string|max:255',
            'type' => 'required|in:heading,body,display,monospace',
            'source' => 'required|in:google,adobe,custom,system',
            'url' => 'nullable|url',
            'weights' => 'required|array|min:1',
            'weights.*' => 'integer|min:100|max:900',
            'styles' => 'required|array|min:1',
            'styles.*' => 'string|in:normal,italic,oblique',
            'fallbacks' => 'required|array|min:1',
            'fallbacks.*' => 'string|max:255',
            'loadingStrategy' => 'required|in:preload,swap,lazy'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = auth()->user()->tenant_id;
        
        $font = $this->brandCustomizerService->updateFont($fontId, $request->validated(), $tenantId);
        
        if (!$font) {
            return response()->json(['message' => 'Font not found'], 404);
        }

        return response()->json($font);
    }

    /**
     * Set primary font
     */
    public function setPrimaryFont(string $fontId): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        
        $result = $this->brandCustomizerService->setPrimaryFont($fontId, $tenantId);
        
        if (!$result) {
            return response()->json(['message' => 'Font not found'], 404);
        }

        return response()->json(['message' => 'Primary font updated successfully']);
    }

    /**
     * Delete brand font
     */
    public function deleteFont(string $fontId): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        
        $result = $this->brandCustomizerService->deleteFont($fontId, $tenantId);
        
        if (!$result) {
            return response()->json(['message' => 'Font not found'], 404);
        }

        return response()->json(['message' => 'Font deleted successfully']);
    }

    /**
     * Create brand template
     */
    public function storeTemplate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'colorIds' => 'required|array|min:1',
            'colorIds.*' => 'string|exists:brand_colors,id',
            'primaryFont' => 'required|string|max:255',
            'secondaryFont' => 'nullable|string|max:255',
            'logoVariant' => 'nullable|string|exists:brand_logos,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'isDefault' => 'boolean',
            'autoApplyToExisting' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = auth()->user()->tenant_id;
        
        $template = $this->brandCustomizerService->createTemplate($request->validated(), $tenantId);
        
        return response()->json($template, 201);
    }

    /**
     * Update brand template
     */
    public function updateTemplate(Request $request, string $templateId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'colorIds' => 'required|array|min:1',
            'colorIds.*' => 'string|exists:brand_colors,id',
            'primaryFont' => 'required|string|max:255',
            'secondaryFont' => 'nullable|string|max:255',
            'logoVariant' => 'nullable|string|exists:brand_logos,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'isDefault' => 'boolean',
            'autoApplyToExisting' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = auth()->user()->tenant_id;
        
        $template = $this->brandCustomizerService->updateTemplate($templateId, $request->validated(), $tenantId);
        
        if (!$template) {
            return response()->json(['message' => 'Template not found'], 404);
        }

        return response()->json($template);
    }

    /**
     * Apply brand template
     */
    public function applyTemplate(string $templateId): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        
        $result = $this->brandCustomizerService->applyTemplate($templateId, $tenantId);
        
        if (!$result) {
            return response()->json(['message' => 'Template not found'], 404);
        }

        return response()->json($result);
    }

    /**
     * Duplicate brand template
     */
    public function duplicateTemplate(string $templateId): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        
        $newTemplate = $this->brandCustomizerService->duplicateTemplate($templateId, $tenantId);
        
        if (!$newTemplate) {
            return response()->json(['message' => 'Template not found'], 404);
        }

        return response()->json($newTemplate);
    }

    /**
     * Run brand consistency check
     */
    public function consistencyCheck(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'guidelines' => 'required|array',
            'assets' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = auth()->user()->tenant_id;
        
        $report = $this->brandCustomizerService->runConsistencyCheck(
            $request->input('guidelines'),
            $request->input('assets'),
            $tenantId
        );
        
        return response()->json($report);
    }

    /**
     * Auto-fix brand consistency issue
     */
    public function autoFixIssue(string $issueId): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        
        $result = $this->brandCustomizerService->autoFixIssue($issueId, $tenantId);
        
        if (!$result['success']) {
            return response()->json(['message' => 'Issue not found or cannot be auto-fixed'], 404);
        }

        return response()->json($result);
    }

    /**
     * Update brand guidelines
     */
    public function updateGuidelines(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enforceColorPalette' => 'boolean',
            'requireContrastCheck' => 'boolean',
            'minContrastRatio' => 'numeric|min:1|max:21',
            'enforceFontFamilies' => 'boolean',
            'enforceTypographyScale' => 'boolean',
            'maxHeadingSize' => 'integer|min:12|max:100',
            'maxBodySize' => 'integer|min:8|max:32',
            'enforceLogoPlacement' => 'boolean',
            'minLogoSize' => 'integer|min:16|max:200',
            'logoClearSpace' => 'numeric|min:0.5|max:5'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = auth()->user()->tenant_id;
        
        $guidelines = $this->brandCustomizerService->updateGuidelines($request->validated(), $tenantId);
        
        return response()->json($guidelines);
    }

    /**
     * Export brand assets
     */
    public function exportAssets(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'assets' => 'required|array',
            'guidelines' => 'required|array',
            'format' => 'required|in:zip,json,css'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = auth()->user()->tenant_id;
        
        $exportPath = $this->brandCustomizerService->exportAssets(
            $request->input('assets'),
            $request->input('guidelines'),
            $request->input('format'),
            $tenantId
        );
        
        return response()->download($exportPath)->deleteFileAfterSend();
    }
}
