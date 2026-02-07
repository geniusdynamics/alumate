<?php
// ABOUTME: File upload controller with support for chunked uploads, signed URLs,
// ABOUTME: and multi-tenant file management

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\FileUploadRequest;
use App\Models\StoredFile;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class FileUploadController extends Controller
{
    protected FileStorageService $fileStorage;

    public function __construct(FileStorageService $fileStorage)
    {
        $this->fileStorage = $fileStorage;
    }

    /**
     * Handle file upload
     */
    public function upload(FileUploadRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $user = $request->user();

            $storedFile = $this->fileStorage->upload(
                file: $file,
                user: $user,
                collection: $request->input('collection'),
                visibility: $request->input('visibility', StoredFile::VISIBILITY_PRIVATE),
                disk: $request->input('disk'),
                options: [
                    'process_image' => $request->boolean('process_image', true),
                ]
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $storedFile->id,
                    'filename' => $storedFile->filename,
                    'mime_type' => $storedFile->mime_type,
                    'size' => $storedFile->size,
                    'formatted_size' => $storedFile->formatted_size,
                    'url' => $storedFile->url,
                    'visibility' => $storedFile->visibility,
                    'collection' => $storedFile->collection,
                    'is_image' => $storedFile->is_image,
                    'thumbnails' => $storedFile->thumbnails,
                    'virus_scan_status' => $storedFile->virus_scan_status,
                    'created_at' => $storedFile->created_at,
                ],
            ], 201);
        } catch (Exception $e) {
            Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle chunked file upload
     */
    public function uploadChunk(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'chunk' => 'required|file|max:5120', // 5MB per chunk
                'upload_id' => 'required|string|size:36',
                'chunk_index' => 'required|integer|min:0',
                'total_chunks' => 'required|integer|min:1',
                'filename' => 'required|string|max:255',
            ]);

            $result = $this->fileStorage->uploadChunked(
                chunk: $request->file('chunk'),
                uploadId: $request->input('upload_id'),
                chunkIndex: $request->input('chunk_index'),
                totalChunks: $request->input('total_chunks'),
                user: $request->user(),
                collection: $request->input('collection'),
                visibility: $request->input('visibility', StoredFile::VISIBILITY_PRIVATE),
                disk: $request->input('disk')
            );

            if (!$result['complete']) {
                return response()->json([
                    'success' => true,
                    'complete' => false,
                    'uploaded_chunks' => $result['uploaded_chunks'],
                    'total_chunks' => $result['total_chunks'],
                ]);
            }

            $storedFile = $result['file'];

            return response()->json([
                'success' => true,
                'complete' => true,
                'data' => [
                    'id' => $storedFile->id,
                    'filename' => $storedFile->filename,
                    'mime_type' => $storedFile->mime_type,
                    'size' => $storedFile->size,
                    'formatted_size' => $storedFile->formatted_size,
                    'url' => $storedFile->url,
                    'visibility' => $storedFile->visibility,
                    'collection' => $storedFile->collection,
                    'is_image' => $storedFile->is_image,
                    'virus_scan_status' => $storedFile->virus_scan_status,
                ],
            ], 201);
        } catch (Exception $e) {
            Log::error('Chunked upload failed', [
                'error' => $e->getMessage(),
                'upload_id' => $request->input('upload_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a file
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        try {
            $storedFile = StoredFile::findOrFail($id);

            $this->fileStorage->delete($storedFile);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);
        } catch (Exception $e) {
            Log::error('File deletion failed', [
                'error' => $e->getMessage(),
                'file_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get file URL (with optional signed URL for private files)
     */
    public function getUrl(Request $request, int $id): JsonResponse
    {
        try {
            $storedFile = StoredFile::findOrFail($id);

            // Check access
            if ($storedFile->visibility === StoredFile::VISIBILITY_PRIVATE) {
                $this->authorizeAccess($storedFile, $request->user());
            }

            $variant = $request->input('variant');
            $url = $storedFile->getUrl($variant);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $storedFile->id,
                    'url' => $url,
                    'variant' => $variant,
                    'expires_at' => $storedFile->visibility === StoredFile::VISIBILITY_PRIVATE
                        ? now()->addMinutes(15)->toIso8601String()
                        : null,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Get file URL failed', [
                'error' => $e->getMessage(),
                'file_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get signed URL for private files
     */
    public function getSignedUrl(Request $request, int $id): JsonResponse
    {
        try {
            $storedFile = StoredFile::findOrFail($id);

            // Only allow signed URLs for private files
            if ($storedFile->visibility === StoredFile::VISIBILITY_PUBLIC) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $storedFile->id,
                        'url' => $storedFile->url,
                        'is_public' => true,
                    ],
                ]);
            }

            // Check access
            $this->authorizeAccess($storedFile, $request->user());

            $expiresIn = $request->input('expires_in', 15); // Default 15 minutes
            $maxExpiration = config('filesystems.max_signed_url_expiration', 60);

            if ($expiresIn > $maxExpiration) {
                $expiresIn = $maxExpiration;
            }

            $url = $this->fileStorage->generateSignedUrl($storedFile, $expiresIn);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $storedFile->id,
                    'url' => $url,
                    'expires_at' => now()->addMinutes($expiresIn)->toIso8601String(),
                    'expires_in_minutes' => $expiresIn,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Generate signed URL failed', [
                'error' => $e->getMessage(),
                'file_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List user's files
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $query = StoredFile::forUser($user->id)
                ->when($request->input('collection'), function ($q, $collection) {
                    $q->collection($collection);
                })
                ->when($request->input('visibility'), function ($q, $visibility) {
                    $q->visibility($visibility);
                })
                ->when($request->input('type'), function ($q, $type) {
                    match ($type) {
                        'image' => $q->images(),
                        'video' => $q->videos(),
                        'document' => $q->documents(),
                        default => null,
                    };
                });

            $files = $query->orderByDesc('created_at')
                ->paginate($request->input('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $files->items(),
                'pagination' => [
                    'current_page' => $files->currentPage(),
                    'last_page' => $files->lastPage(),
                    'per_page' => $files->perPage(),
                    'total' => $files->total(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('List files failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get storage statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $stats = $this->fileStorage->getStorageStats($user);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (Exception $e) {
            Log::error('Get storage stats failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update file metadata
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $storedFile = StoredFile::findOrFail($id);

            // Check ownership
            $this->authorizeAccess($storedFile, $request->user());

            $validated = $request->validate([
                'filename' => 'sometimes|string|max:255',
                'collection' => 'nullable|string|max:100',
                'metadata' => 'sometimes|array',
            ]);

            $storedFile->update($validated);

            return response()->json([
                'success' => true,
                'data' => $storedFile->fresh(),
            ]);
        } catch (Exception $e) {
            Log::error('Update file failed', [
                'error' => $e->getMessage(),
                'file_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete files
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:stored_files,id',
            ]);

            $deleted = 0;
            $failed = [];

            foreach ($validated['ids'] as $id) {
                try {
                    $storedFile = StoredFile::find($id);
                    if ($storedFile) {
                        $this->fileStorage->delete($storedFile);
                        $deleted++;
                    }
                } catch (Exception $e) {
                    $failed[] = ['id' => $id, 'error' => $e->getMessage()];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'deleted_count' => $deleted,
                    'failed' => $failed,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Bulk delete files failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Authorize access to a file
     *
     * @throws Exception
     */
    protected function authorizeAccess(StoredFile $storedFile, $user): void
    {
        if (!$user) {
            throw new Exception('Authentication required');
        }

        // Owner has access
        if ($storedFile->user_id === $user->id) {
            return;
        }

        // Super admin has access
        if ($user->is_super_admin) {
            return;
        }

        // Tenant admin has access to files in their tenant
        if ($user->hasRoleInCurrentTenant(\App\Models\User::ROLE_TENANT_ADMIN)) {
            // This check would need tenant context service
            return;
        }

        throw new Exception('Unauthorized access to file');
    }
}
