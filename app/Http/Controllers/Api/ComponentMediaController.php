<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ComponentMediaController extends Controller
{
    public function __construct(
        private MediaUploadService $mediaUploadService
    ) {}

    /**
     * Upload media files for component usage
     */
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1',
            'files.*' => 'file|max:10240', // 10MB max
            'component_id' => 'nullable|exists:components,id',
            'media_type' => 'nullable|in:image,video,document,avatar,background',
            'optimize' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $files = $request->file('files');
            $user = Auth::user();
            
            $uploadedFiles = $this->mediaUploadService->uploadMedia($files, $user);
            
            // Add component-specific metadata
            foreach ($uploadedFiles as &$file) {
                $file['component_id'] = $request->component_id;
                $file['media_type'] = $request->media_type ?? 'image';
                $file['uploaded_at'] = now()->toISOString();
            }
            
            return response()->json([
                'message' => 'Files uploaded successfully',
                'files' => $uploadedFiles
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'File upload failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process media for specific component usage
     */
    public function process(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_url' => 'required|url',
            'component_id' => 'required|exists:components,id',
            'processing_options' => 'nullable|array',
            'processing_options.resize' => 'nullable|array',
            'processing_options.resize.width' => 'nullable|integer|min:1|max:4000',
            'processing_options.resize.height' => 'nullable|integer|min:1|max:4000',
            'processing_options.quality' => 'nullable|integer|min:1|max:100',
            'processing_options.format' => 'nullable|in:jpg,png,webp,gif'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fileUrl = $request->file_url;
            $componentId = $request->component_id;
            $options = $request->processing_options ?? [];
            
            // Process the media file according to options
            $processedFile = $this->processMediaFile($fileUrl, $options);
            
            return response()->json([
                'message' => 'Media processed successfully',
                'file' => $processedFile,
                'component_id' => $componentId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Media processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get media library for a tenant
     */
    public function library(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'type' => 'nullable|in:image,video,document',
            'component_id' => 'nullable|exists:components,id',
            'sort_by' => 'nullable|in:created_at,name,size',
            'sort_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tenantId = Auth::user()->tenant_id;
            $search = $request->search;
            $type = $request->type;
            $componentId = $request->component_id;
            $sortBy = $request->sort_by ?? 'created_at';
            $sortDirection = $request->sort_direction ?? 'desc';
            $perPage = $request->per_page ?? 20;
            
            // Get media files from storage
            $mediaFiles = $this->getMediaLibrary($tenantId, $search, $type, $componentId, $sortBy, $sortDirection, $perPage);
            
            return response()->json([
                'media' => $mediaFiles,
                'pagination' => [
                    'current_page' => $mediaFiles->currentPage(),
                    'last_page' => $mediaFiles->lastPage(),
                    'per_page' => $mediaFiles->perPage(),
                    'total' => $mediaFiles->total(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve media library',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete media files
     */
    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_urls' => 'required|array|min:1',
            'file_urls.*' => 'url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fileUrls = $request->file_urls;
            
            // Delete media files
            $this->deleteMediaFiles($fileUrls);
            
            return response()->json([
                'message' => 'Media files deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete media files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get media file information
     */
    public function info(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_url' => 'required|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fileUrl = $request->file_url;
            
            // Get file information
            $fileInfo = $this->getFileInfo($fileUrl);
            
            return response()->json([
                'file' => $fileInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve file information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize media files for web usage
     */
    public function optimize(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_urls' => 'required|array|min:1',
            'file_urls.*' => 'url',
            'quality' => 'nullable|integer|min:1|max:100',
            'format' => 'nullable|in:webp,jpg,png',
            'resize' => 'nullable|array',
            'resize.width' => 'nullable|integer|min:1|max:4000',
            'resize.height' => 'nullable|integer|min:1|max:4000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fileUrls = $request->file_urls;
            $options = [
                'quality' => $request->quality ?? 80,
                'format' => $request->get('format', 'webp'),
                'resize' => $request->resize
            ];
            
            // Optimize media files
            $optimizedFiles = $this->optimizeMediaFiles($fileUrls, $options);
            
            return response()->json([
                'message' => 'Media files optimized successfully',
                'files' => $optimizedFiles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to optimize media files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate thumbnails for media files
     */
    public function thumbnails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_url' => 'required|url',
            'sizes' => 'required|array|min:1',
            'sizes.*' => 'array',
            'sizes.*.width' => 'required|integer|min:1|max:1000',
            'sizes.*.height' => 'required|integer|min:1|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fileUrl = $request->file_url;
            $sizes = $request->sizes;
            
            // Generate thumbnails
            $thumbnails = $this->generateThumbnails($fileUrl, $sizes);
            
            return response()->json([
                'message' => 'Thumbnails generated successfully',
                'thumbnails' => $thumbnails
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate thumbnails',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process media file according to options
     */
    private function processMediaFile(string $fileUrl, array $options): array
    {
        // This would contain the actual media processing logic
        // For now, we'll return a placeholder response
        return [
            'original_url' => $fileUrl,
            'processed_url' => $fileUrl,
            'options_applied' => $options,
            'processed_at' => now()->toISOString()
        ];
    }

    /**
     * Get media library files
     */
    private function getMediaLibrary(int $tenantId, ?string $search, ?string $type, ?int $componentId, string $sortBy, string $sortDirection, int $perPage)
    {
        // This would contain the actual logic to retrieve media files
        // For now, we'll return an empty collection with pagination structure
        return collect([])->paginate($perPage);
    }

    /**
     * Delete media files
     */
    private function deleteMediaFiles(array $fileUrls): void
    {
        // This would contain the actual logic to delete media files
        foreach ($fileUrls as $url) {
            $path = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH));
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Get file information
     */
    private function getFileInfo(string $fileUrl): array
    {
        // This would contain the actual logic to get file information
        return [
            'url' => $fileUrl,
            'size' => 0,
            'type' => 'unknown',
            'dimensions' => null,
            'created_at' => now()->toISOString()
        ];
    }

    /**
     * Optimize media files
     */
    private function optimizeMediaFiles(array $fileUrls, array $options): array
    {
        // This would contain the actual logic to optimize media files
        $optimizedFiles = [];
        
        foreach ($fileUrls as $url) {
            $optimizedFiles[] = [
                'original_url' => $url,
                'optimized_url' => $url,
                'options_applied' => $options,
                'saved_bytes' => 0,
                'optimized_at' => now()->toISOString()
            ];
        }
        
        return $optimizedFiles;
    }

    /**
     * Generate thumbnails
     */
    private function generateThumbnails(string $fileUrl, array $sizes): array
    {
        // This would contain the actual logic to generate thumbnails
        $thumbnails = [];
        
        foreach ($sizes as $size) {
            $thumbnails[] = [
                'url' => $fileUrl,
                'width' => $size['width'],
                'height' => $size['height'],
                'generated_at' => now()->toISOString()
            ];
        }
        
        return $thumbnails;
    }
}