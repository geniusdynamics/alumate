<?php
// ABOUTME: Background job for processing image uploads including thumbnail generation,
// ABOUTME: WebP conversion, and optimization

declare(strict_types=1);

namespace App\Jobs;

use App\Models\StoredFile;
use App\Services\ImageProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessImageUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        protected StoredFile $storedFile
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(ImageProcessingService $imageProcessor): void
    {
        try {
            Log::info('Starting image processing', [
                'file_id' => $this->storedFile->id,
                'filename' => $this->storedFile->filename,
            ]);

            // Verify file is an image
            if (!$this->storedFile->isImage()) {
                Log::info('Skipping image processing - not an image', [
                    'file_id' => $this->storedFile->id,
                    'mime_type' => $this->storedFile->mime_type,
                ]);
                return;
            }

            // Generate thumbnails
            $this->generateThumbnails($imageProcessor);

            // Optimize original image
            $this->optimizeImage($imageProcessor);

            // Update file size after optimization
            $this->updateFileSize();

            Log::info('Image processing completed successfully', [
                'file_id' => $this->storedFile->id,
            ]);
        } catch (Exception $e) {
            Log::error('Image processing failed', [
                'file_id' => $this->storedFile->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate thumbnails for the image
     */
    protected function generateThumbnails(ImageProcessingService $imageProcessor): void
    {
        try {
            Log::debug('Generating thumbnails', [
                'file_id' => $this->storedFile->id,
            ]);

            $thumbnails = $imageProcessor->generateThumbnails(
                $this->storedFile->path,
                $this->storedFile->storage_disk
            );

            if (!empty($thumbnails)) {
                $this->storedFile->updateThumbnails($thumbnails);

                Log::debug('Thumbnails generated', [
                    'file_id' => $this->storedFile->id,
                    'thumbnails' => array_keys($thumbnails),
                ]);
            }
        } catch (Exception $e) {
            Log::warning('Thumbnail generation failed', [
                'file_id' => $this->storedFile->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - allow processing to continue without thumbnails
        }
    }

    /**
     * Optimize the original image
     */
    protected function optimizeImage(ImageProcessingService $imageProcessor): void
    {
        try {
            Log::debug('Optimizing image', [
                'file_id' => $this->storedFile->id,
                'original_size' => $this->storedFile->size,
            ]);

            $imageProcessor->optimize(
                $this->storedFile->path,
                $this->storedFile->storage_disk,
                maxWidth: 2048,
                maxHeight: 2048,
                quality: 85
            );

            Log::debug('Image optimized', [
                'file_id' => $this->storedFile->id,
            ]);
        } catch (Exception $e) {
            Log::warning('Image optimization failed', [
                'file_id' => $this->storedFile->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - allow processing to continue without optimization
        }
    }

    /**
     * Update file size after processing
     */
    protected function updateFileSize(): void
    {
        try {
            $newSize = \Illuminate\Support\Facades\Storage::disk($this->storedFile->storage_disk)
                ->size($this->storedFile->path);

            $this->storedFile->update(['size' => $newSize]);

            Log::debug('File size updated', [
                'file_id' => $this->storedFile->id,
                'old_size' => $this->storedFile->size,
                'new_size' => $newSize,
            ]);
        } catch (Exception $e) {
            Log::warning('Failed to update file size', [
                'file_id' => $this->storedFile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('ProcessImageUpload job failed permanently', [
            'file_id' => $this->storedFile->id,
            'filename' => $this->storedFile->filename,
            'error' => $exception->getMessage(),
        ]);

        // Update file status to indicate processing failed
        try {
            $this->storedFile->updateMetadata([
                'processing_error' => $exception->getMessage(),
                'processing_failed_at' => now()->toIso8601String(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update file metadata after processing failure', [
                'file_id' => $this->storedFile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }
}
