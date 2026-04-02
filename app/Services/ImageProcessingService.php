<?php

// ABOUTME: Image processing service for resizing, generating thumbnails, WebP conversion,
// ABOUTME: and optimization with support for multiple storage disks

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageProcessingService extends BaseService
{
    /**
     * Image size configurations
     */
    protected array $sizes = [
        'thumbnail' => [
            'width' => 150,
            'height' => 150,
            'fit' => 'crop', // crop, contain, fill
            'quality' => 85,
        ],
        'small' => [
            'width' => 400,
            'height' => 300,
            'fit' => 'contain',
            'quality' => 85,
        ],
        'medium' => [
            'width' => 800,
            'height' => 600,
            'fit' => 'contain',
            'quality' => 90,
        ],
        'large' => [
            'width' => 1600,
            'height' => 1200,
            'fit' => 'contain',
            'quality' => 90,
        ],
    ];

    /**
     * Output formats to generate
     */
    protected array $outputFormats = ['webp', 'original'];

    /**
     * Create a new image instance from path
     */
    protected function makeImage(string $path, string $disk): \Intervention\Image\Image
    {
        $fullPath = Storage::disk($disk)->path($path);

        return Image::read($fullPath);
    }

    /**
     * Resize an image to specific dimensions
     *
     * @throws Exception
     */
    public function resize(
        string $sourcePath,
        string $targetPath,
        int $width,
        int $height,
        string $fit = 'contain',
        int $quality = 90,
        ?string $sourceDisk = null,
        ?string $targetDisk = null
    ): bool {
        $sourceDisk = $sourceDisk ?? config('filesystems.default');
        $targetDisk = $targetDisk ?? $sourceDisk;

        try {
            $image = $this->makeImage($sourcePath, $sourceDisk);

            // Apply fit strategy
            match ($fit) {
                'crop' => $image->cover($width, $height),
                'fill' => $image->scaleFill($width, $height),
                default => $image->scaleDown($width, $height),
            };

            // Encode and save
            $encoded = $image->encodeByExtension(
                extension: pathinfo($targetPath, PATHINFO_EXTENSION) ?: 'jpg',
                quality: $quality
            );

            Storage::disk($targetDisk)->put($targetPath, $encoded);

            return true;
        } catch (Exception $e) {
            report($e);

            return false;
        }
    }

    /**
     * Generate thumbnails in multiple sizes
     *
     * @return array<string, string> Array of size => URL mappings
     */
    public function generateThumbnails(
        string $sourcePath,
        ?string $disk = null,
        ?array $sizes = null
    ): array {
        $disk = $disk ?? config('filesystems.default');
        $sizes = $sizes ?? array_keys($this->sizes);
        $thumbnails = [];

        $baseName = pathinfo($sourcePath, PATHINFO_FILENAME);
        $directory = dirname($sourcePath);

        foreach ($sizes as $size) {
            if (! isset($this->sizes[$size])) {
                continue;
            }

            $config = $this->sizes[$size];

            // Generate WebP version
            $webpFilename = "{$baseName}_{$size}.webp";
            $webpPath = "{$directory}/{$webpFilename}";

            if ($this->convertToWebp($sourcePath, $webpPath, $config['quality'], $disk)) {
                $thumbnails[$size] = Storage::disk($disk)->url($webpPath);
            }
        }

        return $thumbnails;
    }

    /**
     * Convert image to WebP format
     */
    public function convertToWebp(
        string $sourcePath,
        string $targetPath,
        int $quality = 85,
        ?string $disk = null
    ): bool {
        $disk = $disk ?? config('filesystems.default');

        try {
            $image = $this->makeImage($sourcePath, $disk);

            // Resize if too large (max 2048px)
            $width = $image->width();
            $height = $image->height();
            $maxDimension = 2048;

            if ($width > $maxDimension || $height > $maxDimension) {
                $image->scaleDown($maxDimension, $maxDimension);
            }

            // Encode as WebP
            $encoded = $image->encodeByExtension('webp', quality: $quality);

            Storage::disk($disk)->put($targetPath, $encoded);

            return true;
        } catch (Exception $e) {
            report($e);

            return false;
        }
    }

    /**
     * Optimize an image (resize if too large, convert to WebP)
     */
    public function optimize(
        string $path,
        ?string $disk = null,
        int $maxWidth = 2048,
        int $maxHeight = 2048,
        int $quality = 85
    ): bool {
        $disk = $disk ?? config('filesystems.default');

        try {
            $image = $this->makeImage($path, $disk);

            // Resize if dimensions exceed maximum
            $width = $image->width();
            $height = $image->height();

            if ($width > $maxWidth || $height > $maxHeight) {
                $image->scaleDown($maxWidth, $maxHeight);
            }

            // Re-encode with optimization
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            $encoded = match ($extension) {
                'webp' => $image->encodeByExtension('webp', quality: $quality),
                'png' => $image->encodeByExtension('png'),
                'gif' => $image->encodeByExtension('gif'),
                default => $image->encodeByExtension('jpg', quality: $quality),
            };

            Storage::disk($disk)->put($path, $encoded);

            return true;
        } catch (Exception $e) {
            report($e);

            return false;
        }
    }

    /**
     * Create a cropped square avatar
     */
    public function createAvatar(
        string $sourcePath,
        string $targetPath,
        int $size = 200,
        ?string $disk = null
    ): bool {
        $disk = $disk ?? config('filesystems.default');

        try {
            $image = $this->makeImage($sourcePath, $disk);

            // Create square crop from center
            $image->cover($size, $size);

            // Encode as WebP for optimal size
            $encoded = $image->encodeByExtension('webp', quality: 85);

            Storage::disk($disk)->put($targetPath, $encoded);

            return true;
        } catch (Exception $e) {
            report($e);

            return false;
        }
    }

    /**
     * Add watermark to image
     */
    public function addWatermark(
        string $sourcePath,
        string $watermarkPath,
        string $targetPath,
        string $position = 'bottom-right',
        int $opacity = 50,
        ?string $disk = null
    ): bool {
        $disk = $disk ?? config('filesystems.default');

        try {
            $image = $this->makeImage($sourcePath, $disk);
            $watermark = Image::read(Storage::disk($disk)->path($watermarkPath));

            // Scale watermark to 20% of image width
            $watermarkWidth = (int) ($image->width() * 0.2);
            $watermark->scaleDown($watermarkWidth);

            // Apply opacity
            $watermark->reduceColors(255, opacity: $opacity);

            // Calculate position
            $positionCoords = $this->calculateWatermarkPosition(
                $image->width(),
                $image->height(),
                $watermark->width(),
                $watermark->height(),
                $position
            );

            // Place watermark
            $image->place($watermark, $positionCoords['x'], $positionCoords['y']);

            // Save
            $encoded = $image->encodeByExtension(
                pathinfo($targetPath, PATHINFO_EXTENSION) ?: 'jpg'
            );

            Storage::disk($disk)->put($targetPath, $encoded);

            return true;
        } catch (Exception $e) {
            report($e);

            return false;
        }
    }

    /**
     * Calculate watermark position coordinates
     */
    protected function calculateWatermarkPosition(
        int $imageWidth,
        int $imageHeight,
        int $watermarkWidth,
        int $watermarkHeight,
        string $position
    ): array {
        $padding = 20;

        return match ($position) {
            'top-left' => ['x' => $padding, 'y' => $padding],
            'top-right' => ['x' => $imageWidth - $watermarkWidth - $padding, 'y' => $padding],
            'bottom-left' => ['x' => $padding, 'y' => $imageHeight - $watermarkHeight - $padding],
            'center' => [
                'x' => (int) (($imageWidth - $watermarkWidth) / 2),
                'y' => (int) (($imageHeight - $watermarkHeight) / 2),
            ],
            default => [
                'x' => $imageWidth - $watermarkWidth - $padding,
                'y' => $imageHeight - $watermarkHeight - $padding,
            ], // bottom-right
        };
    }

    /**
     * Get image dimensions
     */
    public function getDimensions(string $path, ?string $disk = null): ?array
    {
        $disk = $disk ?? config('filesystems.default');

        try {
            $image = $this->makeImage($path, $disk);

            return [
                'width' => $image->width(),
                'height' => $image->height(),
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get image file size
     */
    public function getFileSize(string $path, ?string $disk = null): int
    {
        $disk = $disk ?? config('filesystems.default');

        return Storage::disk($disk)->size($path);
    }

    /**
     * Generate responsive image srcset
     */
    public function generateSrcSet(
        string $sourcePath,
        ?string $disk = null,
        array $widths = [400, 800, 1200, 1600]
    ): array {
        $disk = $disk ?? config('filesystems.default');
        $srcSet = [];

        $baseName = pathinfo($sourcePath, PATHINFO_FILENAME);
        $directory = dirname($sourcePath);
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);

        foreach ($widths as $width) {
            $resizedFilename = "{$baseName}_{$width}w.{$extension}";
            $resizedPath = "{$directory}/{$resizedFilename}";

            if ($this->resize(
                $sourcePath,
                $resizedPath,
                $width,
                9999, // Auto height
                'contain',
                85,
                $disk,
                $disk
            )) {
                $srcSet[$width] = Storage::disk($disk)->url($resizedPath);
            }
        }

        return $srcSet;
    }

    /**
     * Batch process multiple images
     */
    public function batchProcess(
        array $paths,
        string $operation,
        array $options = [],
        ?string $disk = null
    ): array {
        $results = [];

        foreach ($paths as $path) {
            $results[$path] = match ($operation) {
                'optimize' => $this->optimize($path, $disk, ...$options),
                'thumbnails' => $this->generateThumbnails($path, $disk, $options['sizes'] ?? null),
                'webp' => $this->convertToWebp(
                    $path,
                    $options['target_path'] ?? str_replace(
                        pathinfo($path, PATHINFO_EXTENSION),
                        'webp',
                        $path
                    ),
                    $options['quality'] ?? 85,
                    $disk
                ),
                default => false,
            };
        }

        return $results;
    }

    /**
     * Update size configuration
     */
    public function setSizeConfig(string $size, array $config): void
    {
        $this->sizes[$size] = array_merge($this->sizes[$size] ?? [], $config);
    }

    /**
     * Get all size configurations
     */
    public function getSizeConfigs(): array
    {
        return $this->sizes;
    }

    /**
     * Add custom size configuration
     */
    public function addSizeConfig(string $name, int $width, int $height, string $fit = 'contain', int $quality = 85): void
    {
        $this->sizes[$name] = [
            'width' => $width,
            'height' => $height,
            'fit' => $fit,
            'quality' => $quality,
        ];
    }
}
