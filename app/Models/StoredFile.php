<?php

// ABOUTME: StoredFile model for production file storage infrastructure with multi-tenancy support
// ABOUTME: Manages file metadata, URLs, thumbnails, and relationships for uploaded files

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class StoredFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'path',
        'filename',
        'mime_type',
        'size',
        'visibility',
        'cdn_url',
        'thumbnails',
        'metadata',
        'virus_scan_status',
        'scanned_at',
        'storage_disk',
        'collection',
    ];

    protected $casts = [
        'size' => 'integer',
        'thumbnails' => 'array',
        'metadata' => 'array',
        'scanned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'url',
        'formatted_size',
        'is_image',
        'is_video',
        'is_document',
    ];

    // Virus scan status constants
    const SCAN_PENDING = 'pending';

    const SCAN_CLEAN = 'clean';

    const SCAN_INFECTED = 'infected';

    // Visibility constants
    const VISIBILITY_PUBLIC = 'public';

    const VISIBILITY_PRIVATE = 'private';

    // Storage disk constants
    const DISK_S3 = 's3';

    const DISK_SPACES = 'spaces';

    const DISK_LOCAL = 'local';

    // Image MIME types
    protected array $imageMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/bmp',
        'image/tiff',
    ];

    // Video MIME types
    protected array $videoMimeTypes = [
        'video/mp4',
        'video/mpeg',
        'video/quicktime',
        'video/webm',
        'video/avi',
        'video/x-msvideo',
    ];

    // Document MIME types
    protected array $documentMimeTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
    ];

    /**
     * Get the user who uploaded the file
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tenant associated with the file
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the file URL accessor
     */
    public function getUrlAttribute(): string
    {
        return $this->getUrl();
    }

    /**
     * Get the file URL
     */
    public function getUrl(?string $variant = null): string
    {
        // If CDN URL is available and file is public, use it
        if ($this->visibility === self::VISIBILITY_PUBLIC && $this->cdn_url) {
            if ($variant && isset($this->thumbnails[$variant])) {
                return $this->thumbnails[$variant];
            }

            return $this->cdn_url;
        }

        // If requesting a thumbnail variant
        if ($variant && isset($this->thumbnails[$variant])) {
            return $this->thumbnails[$variant];
        }

        // Generate temporary URL for private files
        if ($this->visibility === self::VISIBILITY_PRIVATE) {
            return Storage::disk($this->storage_disk)
                ->temporaryUrl($this->path, now()->addMinutes(15));
        }

        // Return public URL
        return Storage::disk($this->storage_disk)->url($this->path);
    }

    /**
     * Get formatted file size accessor
     */
    public function getFormattedSizeAttribute(): string
    {
        return $this->getFormattedSize();
    }

    /**
     * Get human-readable file size
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        } else {
            return $bytes.' B';
        }
    }

    /**
     * Check if file is an image accessor
     */
    public function getIsImageAttribute(): bool
    {
        return $this->isImage();
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return in_array($this->mime_type, $this->imageMimeTypes, true);
    }

    /**
     * Check if file is a video accessor
     */
    public function getIsVideoAttribute(): bool
    {
        return $this->isVideo();
    }

    /**
     * Check if file is a video
     */
    public function isVideo(): bool
    {
        return in_array($this->mime_type, $this->videoMimeTypes, true);
    }

    /**
     * Check if file is a document accessor
     */
    public function getIsDocumentAttribute(): bool
    {
        return $this->isDocument();
    }

    /**
     * Check if file is a document
     */
    public function isDocument(): bool
    {
        return in_array($this->mime_type, $this->documentMimeTypes, true);
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnail(string $size = 'medium'): ?string
    {
        if (! $this->isImage()) {
            return null;
        }

        if (isset($this->thumbnails[$size])) {
            return $this->thumbnails[$size];
        }

        // Fallback to original URL if thumbnail doesn't exist
        return $this->getUrl();
    }

    /**
     * Delete file from storage
     */
    public function deleteFromStorage(): bool
    {
        try {
            // Delete thumbnails first
            if ($this->thumbnails) {
                foreach ($this->thumbnails as $thumbnailUrl) {
                    $thumbnailPath = $this->extractPathFromUrl($thumbnailUrl);
                    if ($thumbnailPath) {
                        Storage::disk($this->storage_disk)->delete($thumbnailPath);
                    }
                }
            }

            // Delete main file
            Storage::disk($this->storage_disk)->delete($this->path);

            return true;
        } catch (\Exception $e) {
            report($e);

            return false;
        }
    }

    /**
     * Mark file as virus scanned
     */
    public function markAsScanned(string $status): void
    {
        $this->update([
            'virus_scan_status' => $status,
            'scanned_at' => now(),
        ]);
    }

    /**
     * Check if file is clean
     */
    public function isClean(): bool
    {
        return $this->virus_scan_status === self::SCAN_CLEAN;
    }

    /**
     * Check if file is infected
     */
    public function isInfected(): bool
    {
        return $this->virus_scan_status === self::SCAN_INFECTED;
    }

    /**
     * Check if file is pending scan
     */
    public function isPendingScan(): bool
    {
        return $this->virus_scan_status === self::SCAN_PENDING;
    }

    /**
     * Update thumbnails array
     */
    public function updateThumbnails(array $thumbnails): void
    {
        $this->update(['thumbnails' => $thumbnails]);
    }

    /**
     * Update metadata
     */
    public function updateMetadata(array $metadata): void
    {
        $currentMetadata = $this->metadata ?? [];
        $this->update(['metadata' => array_merge($currentMetadata, $metadata)]);
    }

    /**
     * Get file extension
     */
    public function getExtension(): string
    {
        return pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    /**
     * Get file name without extension
     */
    public function getBaseName(): string
    {
        return pathinfo($this->filename, PATHINFO_FILENAME);
    }

    /**
     * Scope for images
     */
    public function scopeImages($query)
    {
        return $query->whereIn('mime_type', $this->imageMimeTypes);
    }

    /**
     * Scope for videos
     */
    public function scopeVideos($query)
    {
        return $query->whereIn('mime_type', $this->videoMimeTypes);
    }

    /**
     * Scope for documents
     */
    public function scopeDocuments($query)
    {
        return $query->whereIn('mime_type', $this->documentMimeTypes);
    }

    /**
     * Scope by collection
     */
    public function scopeCollection($query, string $collection)
    {
        return $query->where('collection', $collection);
    }

    /**
     * Scope by visibility
     */
    public function scopeVisibility($query, string $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    /**
     * Scope for public files
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    /**
     * Scope for private files
     */
    public function scopePrivate($query)
    {
        return $query->where('visibility', self::VISIBILITY_PRIVATE);
    }

    /**
     * Scope by scan status
     */
    public function scopeScanStatus($query, string $status)
    {
        return $query->where('virus_scan_status', $status);
    }

    /**
     * Scope for clean files only
     */
    public function scopeClean($query)
    {
        return $query->where('virus_scan_status', self::SCAN_CLEAN);
    }

    /**
     * Scope for tenant
     */
    public function scopeForTenant($query, ?int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope for user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Extract path from URL
     */
    private function extractPathFromUrl(string $url): ?string
    {
        $parsedUrl = parse_url($url);
        if (! isset($parsedUrl['path'])) {
            return null;
        }

        // Remove any leading path components to get the storage path
        $path = $parsedUrl['path'];

        // Try to extract the path after the bucket/storage prefix
        // This is a simplified version - may need adjustment based on actual URL structure
        $parts = explode('/', $path);
        $storageIndex = array_search('storage', $parts);

        if ($storageIndex !== false) {
            return implode('/', array_slice($parts, $storageIndex + 1));
        }

        return ltrim($path, '/');
    }
}
