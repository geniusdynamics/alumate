<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class MediaUploadService
{
    protected array $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    protected array $allowedVideoTypes = ['mp4', 'mov', 'avi', 'wmv', 'webm'];

    protected array $allowedDocumentTypes = ['pdf', 'doc', 'docx', 'txt'];

    protected int $maxImageSize = 10 * 1024 * 1024; // 10MB

    protected int $maxVideoSize = 100 * 1024 * 1024; // 100MB

    protected int $maxDocumentSize = 25 * 1024 * 1024; // 25MB

    protected array $imageDimensions = [
        'thumbnail' => [300, 300],
        'medium' => [800, 600],
        'large' => [1200, 900],
    ];

    public function uploadMedia(array $files, $user): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $validation = $this->validateFile($file);
            if (! $validation['valid']) {
                throw new \InvalidArgumentException($validation['error']);
            }

            $uploadedFile = $this->processAndStoreFile($file, $user);
            $uploadedFiles[] = $uploadedFile;
        }

        return $uploadedFiles;
    }

    protected function validateFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $size = $file->getSize();

        // Check file type and size
        if (in_array($extension, $this->allowedImageTypes)) {
            if ($size > $this->maxImageSize) {
                return ['valid' => false, 'error' => 'Image file too large. Maximum size is 10MB.'];
            }

            // Validate image dimensions
            $imageSize = getimagesize($file->getPathname());
            if (! $imageSize) {
                return ['valid' => false, 'error' => 'Invalid image file.'];
            }

            if ($imageSize[0] > 4000 || $imageSize[1] > 4000) {
                return ['valid' => false, 'error' => 'Image dimensions too large. Maximum is 4000x4000 pixels.'];
            }

        } elseif (in_array($extension, $this->allowedVideoTypes)) {
            if ($size > $this->maxVideoSize) {
                return ['valid' => false, 'error' => 'Video file too large. Maximum size is 100MB.'];
            }

        } elseif (in_array($extension, $this->allowedDocumentTypes)) {
            if ($size > $this->maxDocumentSize) {
                return ['valid' => false, 'error' => 'Document file too large. Maximum size is 25MB.'];
            }

        } else {
            return ['valid' => false, 'error' => 'File type not allowed.'];
        }

        return ['valid' => true];
    }

    protected function processAndStoreFile(UploadedFile $file, $user): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = $this->generateFilename($file);
        $path = "media/posts/{$user->id}/".date('Y/m');

        $fileData = [
            'original_name' => $file->getClientOriginalName(),
            'filename' => $filename,
            'extension' => $extension,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'type' => $this->getFileType($extension),
            'urls' => [],
        ];

        if (in_array($extension, $this->allowedImageTypes)) {
            $fileData['urls'] = $this->processImage($file, $path, $filename);
        } else {
            // Store original file for videos and documents
            $storedPath = $file->storeAs($path, $filename, 'public');
            $fileData['urls']['original'] = Storage::url($storedPath);
        }

        return $fileData;
    }

    protected function processImage(UploadedFile $file, string $path, string $filename): array
    {
        $urls = [];
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        // Store original
        $originalPath = $file->storeAs($path, $filename, 'public');
        $urls['original'] = Storage::url($originalPath);

        // Create thumbnails
        foreach ($this->imageDimensions as $size => $dimensions) {
            $resizedFilename = "{$baseName}_{$size}.{$extension}";
            $resizedPath = "{$path}/{$resizedFilename}";

            $image = Image::make($file->getPathname())
                ->resize($dimensions[0], $dimensions[1], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

            Storage::disk('public')->put($resizedPath, $image->encode());
            $urls[$size] = Storage::url($resizedPath);
        }

        return $urls;
    }

    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();

        return Str::uuid().'.'.$extension;
    }

    protected function getFileType(string $extension): string
    {
        if (in_array($extension, $this->allowedImageTypes)) {
            return 'image';
        } elseif (in_array($extension, $this->allowedVideoTypes)) {
            return 'video';
        } elseif (in_array($extension, $this->allowedDocumentTypes)) {
            return 'document';
        }

        return 'unknown';
    }

    public function deleteMedia(array $mediaUrls): void
    {
        foreach ($mediaUrls as $mediaData) {
            if (isset($mediaData['urls']) && is_array($mediaData['urls'])) {
                foreach ($mediaData['urls'] as $url) {
                    $path = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH));
                    Storage::disk('public')->delete($path);
                }
            }
        }
    }
}
