<?php

// ABOUTME: Production file storage service with multi-tenant support, S3/Spaces integration,
// ABOUTME: image optimization, virus scanning, and quota enforcement

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessImageUpload;
use App\Jobs\ScanFileForVirus;
use App\Models\StoredFile;
use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStorageService extends BaseService
{
    protected ImageProcessingService $imageProcessor;

    protected array $config;

    // File size limits (in bytes)
    protected array $fileSizeLimits = [
        'image' => 20 * 1024 * 1024,      // 20MB
        'video' => 500 * 1024 * 1024,     // 500MB
        'document' => 50 * 1024 * 1024,   // 50MB
        'default' => 100 * 1024 * 1024,   // 100MB
    ];

    // Allowed MIME types by category
    protected array $allowedMimeTypes = [
        'image' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/bmp',
            'image/tiff',
        ],
        'video' => [
            'video/mp4',
            'video/mpeg',
            'video/quicktime',
            'video/webm',
            'video/avi',
            'video/x-msvideo',
            'video/x-matroska',
        ],
        'document' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'application/rtf',
            'application/zip',
        ],
    ];

    public function __construct(
        TenantContextService $tenantContext,
        ImageProcessingService $imageProcessor
    ) {
        parent::__construct($tenantContext);
        $this->imageProcessor = $imageProcessor;
        $this->config = config('filesystems');
    }

    /**
     * Upload a file to storage
     *
     * @throws Exception
     */
    public function upload(
        UploadedFile $file,
        User $user,
        ?string $collection = null,
        string $visibility = StoredFile::VISIBILITY_PRIVATE,
        ?string $disk = null,
        array $options = []
    ): StoredFile {
        $this->ensureTenantContext();

        // Validate file
        $validation = $this->validateFile($file);
        if (! $validation['valid']) {
            throw new Exception($validation['error']);
        }

        // Check quota
        if (! $this->checkQuota($user, $file->getSize())) {
            throw new Exception('Storage quota exceeded. Please upgrade your plan or delete some files.');
        }

        $disk = $disk ?? $this->getDefaultDisk();
        $fileType = $this->getFileType($file->getMimeType());
        $fileName = $this->generateUniqueFilename($file);
        $path = $this->generateStoragePath($user, $collection, $fileName);

        // Store the file
        $stored = Storage::disk($disk)->putFileAs(
            dirname($path),
            $file,
            basename($path),
            $visibility === StoredFile::VISIBILITY_PUBLIC ? 'public' : 'private'
        );

        if (! $stored) {
            throw new Exception('Failed to store file');
        }

        // Create database record
        $storedFile = StoredFile::create([
            'user_id' => $user->id,
            'tenant_id' => $this->getCurrentTenantId(),
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'visibility' => $visibility,
            'cdn_url' => $visibility === StoredFile::VISIBILITY_PUBLIC
                ? $this->generateCdnUrl($path, $disk)
                : null,
            'thumbnails' => null,
            'metadata' => $this->extractMetadata($file),
            'virus_scan_status' => config('filesystems.virus_scanning.enabled', true)
                ? StoredFile::SCAN_PENDING
                : StoredFile::SCAN_CLEAN,
            'storage_disk' => $disk,
            'collection' => $collection,
        ]);

        // Process images asynchronously
        if ($fileType === 'image' && ($options['process_image'] ?? true)) {
            ProcessImageUpload::dispatch($storedFile);
        }

        // Queue virus scan
        if (config('filesystems.virus_scanning.enabled', true)) {
            ScanFileForVirus::dispatch($storedFile);
        }

        $this->logActivity('file_uploaded', "File uploaded: {$storedFile->filename}", [
            'file_id' => $storedFile->id,
            'size' => $storedFile->size,
            'collection' => $collection,
        ]);

        return $storedFile;
    }

    /**
     * Handle chunked file upload
     *
     * @throws Exception
     */
    public function uploadChunked(
        UploadedFile $chunk,
        string $uploadId,
        int $chunkIndex,
        int $totalChunks,
        User $user,
        ?string $collection = null,
        string $visibility = StoredFile::VISIBILITY_PRIVATE,
        ?string $disk = null
    ): array {
        $this->ensureTenantContext();

        $tempPath = "chunks/{$uploadId}";
        $chunkPath = "{$tempPath}/chunk_{$chunkIndex}";

        // Store chunk
        Storage::disk('local')->putFileAs($tempPath, $chunk, "chunk_{$chunkIndex}");

        // Check if all chunks are uploaded
        $uploadedChunks = count(Storage::disk('local')->files($tempPath));

        if ($uploadedChunks < $totalChunks) {
            return [
                'complete' => false,
                'uploaded_chunks' => $uploadedChunks,
                'total_chunks' => $totalChunks,
            ];
        }

        // Merge chunks
        $finalPath = $this->mergeChunks($tempPath, $uploadId);

        // Create UploadedFile from merged file
        $mergedFile = new UploadedFile(
            $finalPath,
            $uploadId,
            mime_content_type($finalPath),
            null,
            true
        );

        // Upload merged file
        $storedFile = $this->upload($mergedFile, $user, $collection, $visibility, $disk);

        // Clean up chunks
        Storage::disk('local')->deleteDirectory($tempPath);

        return [
            'complete' => true,
            'file' => $storedFile,
        ];
    }

    /**
     * Delete a file
     */
    public function delete(StoredFile $storedFile): bool
    {
        $this->ensureTenantContext();

        // Check permissions
        if (! $this->canDeleteFile($storedFile)) {
            throw new Exception('Unauthorized to delete this file');
        }

        try {
            // Delete from storage
            $storedFile->deleteFromStorage();

            // Delete from database
            $storedFile->delete();

            $this->logActivity('file_deleted', "File deleted: {$storedFile->filename}", [
                'file_id' => $storedFile->id,
            ]);

            return true;
        } catch (Exception $e) {
            $this->handleServiceError($e, 'delete_file', ['file_id' => $storedFile->id]);

            return false;
        }
    }

    /**
     * Get file URL
     */
    public function getUrl(StoredFile $storedFile, ?string $variant = null): string
    {
        return $storedFile->getUrl($variant);
    }

    /**
     * Generate signed URL for private files
     */
    public function generateSignedUrl(
        StoredFile $storedFile,
        ?int $expiresInMinutes = null,
        array $additionalParams = []
    ): string {
        if ($storedFile->visibility === StoredFile::VISIBILITY_PUBLIC) {
            return $storedFile->getUrl();
        }

        $expiresIn = $expiresInMinutes ?? config('filesystems.signed_url_expiration', 15);

        return Storage::disk($storedFile->storage_disk)
            ->temporaryUrl(
                $storedFile->path,
                now()->addMinutes($expiresIn),
                $additionalParams
            );
    }

    /**
     * Generate thumbnails for an image
     */
    public function generateThumbnails(StoredFile $storedFile): array
    {
        if (! $storedFile->isImage()) {
            return [];
        }

        try {
            $thumbnails = $this->imageProcessor->generateThumbnails(
                $storedFile->path,
                $storedFile->storage_disk
            );

            // Update stored file with thumbnail URLs
            $storedFile->updateThumbnails($thumbnails);

            return $thumbnails;
        } catch (Exception $e) {
            $this->logActivity('thumbnail_generation_failed', "Failed to generate thumbnails: {$e->getMessage()}", [
                'file_id' => $storedFile->id,
            ], 'error');

            return [];
        }
    }

    /**
     * Optimize an image
     */
    public function optimizeImage(StoredFile $storedFile): void
    {
        if (! $storedFile->isImage()) {
            return;
        }

        try {
            $this->imageProcessor->optimize(
                $storedFile->path,
                $storedFile->storage_disk
            );

            // Update file size after optimization
            $newSize = Storage::disk($storedFile->storage_disk)->size($storedFile->path);
            $storedFile->update(['size' => $newSize]);
        } catch (Exception $e) {
            $this->logActivity('image_optimization_failed', "Failed to optimize image: {$e->getMessage()}", [
                'file_id' => $storedFile->id,
            ], 'error');
        }
    }

    /**
     * Scan file for viruses
     */
    public function scanForVirus(StoredFile $storedFile): array
    {
        if (! config('filesystems.virus_scanning.enabled', true)) {
            $storedFile->markAsScanned(StoredFile::SCAN_CLEAN);

            return ['status' => 'clean', 'message' => 'Virus scanning disabled'];
        }

        try {
            $scanner = config('filesystems.virus_scanning.driver', 'clamav');
            $result = $this->performVirusScan($storedFile, $scanner);

            $storedFile->markAsScanned($result['status']);

            if ($result['status'] === StoredFile::SCAN_INFECTED) {
                // Log security event
                $this->logActivity('virus_detected', "Virus detected in file: {$storedFile->filename}", [
                    'file_id' => $storedFile->id,
                    'user_id' => $storedFile->user_id,
                    'details' => $result['details'] ?? null,
                ], 'error');

                // Optionally quarantine or delete the file
                if (config('filesystems.virus_scanning.auto_delete_infected', false)) {
                    $this->delete($storedFile);
                }
            }

            return $result;
        } catch (Exception $e) {
            $this->logActivity('virus_scan_failed', "Virus scan failed: {$e->getMessage()}", [
                'file_id' => $storedFile->id,
            ], 'error');

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Check user storage quota
     */
    public function checkQuota(User $user, int $additionalBytes = 0): bool
    {
        $quotaLimit = $this->getUserQuota($user);

        if ($quotaLimit === -1) {
            return true; // Unlimited
        }

        $usedStorage = $this->getUsedStorage($user);
        $newTotal = $usedStorage + $additionalBytes;

        return $newTotal <= $quotaLimit;
    }

    /**
     * Get user quota in bytes
     */
    public function getUserQuota(User $user): int
    {
        // Check for tenant-specific quota first
        $tenantQuota = $this->getTenantQuota();
        if ($tenantQuota !== null) {
            return $tenantQuota;
        }

        // Check user subscription plan
        $planQuota = $user->getPreference('storage_quota');
        if ($planQuota) {
            return (int) $planQuota;
        }

        // Default quota from config
        return config('filesystems.default_quota_bytes', 5 * 1024 * 1024 * 1024); // 5GB default
    }

    /**
     * Get tenant storage quota
     */
    public function getTenantQuota(): ?int
    {
        $tenantId = $this->getCurrentTenantId();
        if (! $tenantId) {
            return null;
        }

        return $this->getTenantConfig('storage_quota_bytes');
    }

    /**
     * Get used storage for a user in bytes
     */
    public function getUsedStorage(User $user): int
    {
        return StoredFile::forUser($user->id)
            ->whereNull('deleted_at')
            ->sum('size') ?? 0;
    }

    /**
     * Get storage usage statistics
     */
    public function getStorageStats(User $user): array
    {
        $used = $this->getUsedStorage($user);
        $quota = $this->getUserQuota($user);

        return [
            'used_bytes' => $used,
            'used_formatted' => $this->formatBytes($used),
            'quota_bytes' => $quota,
            'quota_formatted' => $quota === -1 ? 'Unlimited' : $this->formatBytes($quota),
            'remaining_bytes' => $quota === -1 ? -1 : max(0, $quota - $used),
            'remaining_formatted' => $quota === -1 ? 'Unlimited' : $this->formatBytes(max(0, $quota - $used)),
            'usage_percentage' => $quota === -1 ? 0 : round(($used / $quota) * 100, 2),
            'file_count' => StoredFile::forUser($user->id)->count(),
        ];
    }

    /**
     * Copy file to another location
     */
    public function copy(StoredFile $storedFile, ?User $toUser = null, ?string $newCollection = null): StoredFile
    {
        $this->ensureTenantContext();

        $user = $toUser ?? $storedFile->user;
        $collection = $newCollection ?? $storedFile->collection;

        $newPath = $this->generateStoragePath($user, $collection, basename($storedFile->path));

        Storage::disk($storedFile->storage_disk)->copy($storedFile->path, $newPath);

        return StoredFile::create([
            'user_id' => $user->id,
            'tenant_id' => $this->getCurrentTenantId(),
            'path' => $newPath,
            'filename' => $storedFile->filename,
            'mime_type' => $storedFile->mime_type,
            'size' => $storedFile->size,
            'visibility' => $storedFile->visibility,
            'cdn_url' => $storedFile->cdn_url,
            'thumbnails' => $storedFile->thumbnails,
            'metadata' => $storedFile->metadata,
            'virus_scan_status' => $storedFile->virus_scan_status,
            'storage_disk' => $storedFile->storage_disk,
            'collection' => $collection,
        ]);
    }

    /**
     * Move file to another collection
     */
    public function move(StoredFile $storedFile, string $newCollection): StoredFile
    {
        $this->ensureTenantContext();

        if (! $this->canDeleteFile($storedFile)) {
            throw new Exception('Unauthorized to move this file');
        }

        $newPath = $this->generateStoragePath(
            $storedFile->user,
            $newCollection,
            basename($storedFile->path)
        );

        Storage::disk($storedFile->storage_disk)->move($storedFile->path, $newPath);

        $storedFile->update([
            'path' => $newPath,
            'collection' => $newCollection,
            'cdn_url' => $storedFile->visibility === StoredFile::VISIBILITY_PUBLIC
                ? $this->generateCdnUrl($newPath, $storedFile->storage_disk)
                : null,
        ]);

        return $storedFile;
    }

    /**
     * Validate file before upload
     */
    protected function validateFile(UploadedFile $file): array
    {
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        $fileType = $this->getFileType($mimeType);

        // Check MIME type
        if (! $this->isAllowedMimeType($mimeType)) {
            return [
                'valid' => false,
                'error' => 'File type not allowed. Allowed types: '.$this->getAllowedTypesList(),
            ];
        }

        // Check file size
        $maxSize = $this->fileSizeLimits[$fileType] ?? $this->fileSizeLimits['default'];
        if ($size > $maxSize) {
            return [
                'valid' => false,
                'error' => "File too large. Maximum size for {$fileType} is ".$this->formatBytes($maxSize),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Get file type from MIME type
     */
    protected function getFileType(string $mimeType): string
    {
        foreach ($this->allowedMimeTypes as $type => $mimes) {
            if (in_array($mimeType, $mimes, true)) {
                return $type;
            }
        }

        return 'default';
    }

    /**
     * Check if MIME type is allowed
     */
    protected function isAllowedMimeType(string $mimeType): bool
    {
        foreach ($this->allowedMimeTypes as $mimes) {
            if (in_array($mimeType, $mimes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get list of allowed file types for error messages
     */
    protected function getAllowedTypesList(): string
    {
        return 'Images (JPEG, PNG, GIF, WebP), Videos (MP4, WebM), Documents (PDF, DOC, XLS)';
    }

    /**
     * Generate unique filename
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();

        return Str::uuid().'.'.strtolower($extension);
    }

    /**
     * Generate storage path
     */
    protected function generateStoragePath(User $user, ?string $collection, string $filename): string
    {
        $tenantId = $this->getCurrentTenantId() ?? 'global';
        $collection = $collection ?? 'general';
        $datePath = now()->format('Y/m/d');

        return "tenants/{$tenantId}/{$collection}/{$user->id}/{$datePath}/{$filename}";
    }

    /**
     * Get default storage disk
     */
    protected function getDefaultDisk(): string
    {
        return config('filesystems.default_storage_disk', 's3');
    }

    /**
     * Generate CDN URL for public files
     */
    protected function generateCdnUrl(string $path, string $disk): ?string
    {
        $cdnBase = config("filesystems.disks.{$disk}.cdn_url");

        if (! $cdnBase) {
            return Storage::disk($disk)->url($path);
        }

        return rtrim($cdnBase, '/').'/'.ltrim($path, '/');
    }

    /**
     * Extract metadata from file
     */
    protected function extractMetadata(UploadedFile $file): array
    {
        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
        ];

        // Add image-specific metadata
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $imageSize = getimagesize($file->getPathname());
            if ($imageSize) {
                $metadata['width'] = $imageSize[0];
                $metadata['height'] = $imageSize[1];
            }
        }

        return $metadata;
    }

    /**
     * Merge chunked upload parts
     *
     * @throws Exception
     */
    protected function mergeChunks(string $tempPath, string $uploadId): string
    {
        $chunks = Storage::disk('local')->files($tempPath);
        sort($chunks); // Ensure correct order

        $mergedPath = storage_path("app/temp/{$uploadId}_merged");

        // Ensure temp directory exists
        if (! is_dir(dirname($mergedPath))) {
            mkdir(dirname($mergedPath), 0755, true);
        }

        $out = fopen($mergedPath, 'wb');
        if (! $out) {
            throw new Exception('Failed to create merged file');
        }

        foreach ($chunks as $chunk) {
            $in = fopen(Storage::disk('local')->path($chunk), 'rb');
            if ($in) {
                stream_copy_to_stream($in, $out);
                fclose($in);
            }
        }

        fclose($out);

        return $mergedPath;
    }

    /**
     * Check if user can delete file
     */
    protected function canDeleteFile(StoredFile $storedFile): bool
    {
        $currentUser = auth()->user();

        if (! $currentUser) {
            return false;
        }

        // User can delete their own files
        if ($storedFile->user_id === $currentUser->id) {
            return true;
        }

        // Super admins can delete any file
        if ($currentUser->is_super_admin) {
            return true;
        }

        // Tenant admins can delete files in their tenant
        if ($currentUser->hasRoleInCurrentTenant(User::ROLE_TENANT_ADMIN)) {
            return $storedFile->tenant_id === $this->getCurrentTenantId();
        }

        return false;
    }

    /**
     * Perform virus scan
     */
    protected function performVirusScan(StoredFile $storedFile, string $scanner): array
    {
        $filePath = Storage::disk($storedFile->storage_disk)->path($storedFile->path);

        switch ($scanner) {
            case 'clamav':
                return $this->scanWithClamAv($filePath);

            case 'mock':
                // For testing - always returns clean
                return ['status' => StoredFile::SCAN_CLEAN, 'message' => 'Mock scan passed'];

            default:
                throw new Exception("Unknown virus scanner: {$scanner}");
        }
    }

    /**
     * Scan with ClamAV
     */
    protected function scanWithClamAv(string $filePath): array
    {
        $socket = config('filesystems.virus_scanning.clamav_socket', '/var/run/clamav/clamd.ctl');

        if (! file_exists($socket)) {
            throw new Exception('ClamAV socket not found');
        }

        $clamd = stream_socket_client("unix://{$socket}", $errno, $errstr, 30);
        if (! $clamd) {
            throw new Exception("ClamAV connection failed: {$errstr}");
        }

        fwrite($clamd, "SCAN {$filePath}\n");
        $response = fgets($clamd);
        fclose($clamd);

        if (str_contains($response, 'FOUND')) {
            return [
                'status' => StoredFile::SCAN_INFECTED,
                'message' => 'Virus detected',
                'details' => $response,
            ];
        }

        return [
            'status' => StoredFile::SCAN_CLEAN,
            'message' => 'No virus detected',
        ];
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }
}
