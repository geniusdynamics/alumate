<?php
// ABOUTME: File upload request validation with support for file type, size,
// ABOUTME: and collection validation

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\StoredFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FileUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $maxSize = $this->getMaxFileSize();

        return [
            'file' => [
                'required',
                'file',
                'max:' . $maxSize,
                $this->getMimeTypeRule(),
            ],
            'collection' => [
                'nullable',
                'string',
                'max:100',
                Rule::in($this->getAllowedCollections()),
            ],
            'visibility' => [
                'nullable',
                'string',
                Rule::in([StoredFile::VISIBILITY_PUBLIC, StoredFile::VISIBILITY_PRIVATE]),
            ],
            'disk' => [
                'nullable',
                'string',
                Rule::in([StoredFile::DISK_S3, StoredFile::DISK_SPACES, StoredFile::DISK_LOCAL]),
            ],
            'process_image' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'A file is required for upload.',
            'file.file' => 'The uploaded item must be a valid file.',
            'file.max' => 'The file size exceeds the maximum allowed size of ' . $this->getMaxFileSizeInReadable() . '.',
            'file.mimetypes' => 'The file type is not allowed. Allowed types: images (JPEG, PNG, GIF, WebP), videos (MP4, WebM), documents (PDF, DOC, XLS).',
            'collection.in' => 'The specified collection is not valid.',
            'visibility.in' => 'Visibility must be either "public" or "private".',
            'disk.in' => 'The specified storage disk is not valid.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'file' => 'uploaded file',
            'collection' => 'file collection',
            'visibility' => 'file visibility',
            'disk' => 'storage disk',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default visibility if not provided
        if (!$this->has('visibility')) {
            $this->merge(['visibility' => StoredFile::VISIBILITY_PRIVATE]);
        }

        // Set default process_image if not provided
        if (!$this->has('process_image')) {
            $this->merge(['process_image' => true]);
        }
    }

    /**
     * Get the maximum file size in kilobytes
     */
    protected function getMaxFileSize(): int
    {
        // Get from user quota or config (in KB for validation rule)
        $maxBytes = config('filesystems.upload_max_size', 100 * 1024 * 1024); // Default 100MB
        return (int) ($maxBytes / 1024);
    }

    /**
     * Get max file size in human readable format
     */
    protected function getMaxFileSizeInReadable(): string
    {
        $maxBytes = config('filesystems.upload_max_size', 100 * 1024 * 1024);

        if ($maxBytes >= 1073741824) {
            return number_format($maxBytes / 1073741824, 2) . ' GB';
        } elseif ($maxBytes >= 1048576) {
            return number_format($maxBytes / 1048576, 2) . ' MB';
        } elseif ($maxBytes >= 1024) {
            return number_format($maxBytes / 1024, 2) . ' KB';
        }
        return $maxBytes . ' B';
    }

    /**
     * Get MIME type validation rule
     */
    protected function getMimeTypeRule(): string
    {
        $allowedTypes = [
            // Images
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/bmp',
            'image/tiff',
            // Videos
            'video/mp4',
            'video/mpeg',
            'video/quicktime',
            'video/webm',
            'video/avi',
            'video/x-msvideo',
            'video/x-matroska',
            // Documents
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
        ];

        return 'mimetypes:' . implode(',', $allowedTypes);
    }

    /**
     * Get allowed collections
     */
    protected function getAllowedCollections(): array
    {
        return [
            'avatars',
            'attachments',
            'gallery',
            'documents',
            'videos',
            'thumbnails',
            'logos',
            'banners',
            'exports',
            'imports',
            'general',
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Additional validation for specific collections
        $collection = $this->input('collection');
        $file = $this->file('file');

        if ($collection === 'avatars') {
            $this->validateAvatar($file);
        }

        if ($collection === 'logos') {
            $this->validateLogo($file);
        }
    }

    /**
     * Validate avatar-specific requirements
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateAvatar($file): void
    {
        $validator = validator(['file' => $file], [
            'file' => 'mimetypes:image/jpeg,image/png,image/gif,image/webp',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    /**
     * Validate logo-specific requirements
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogo($file): void
    {
        $validator = validator(['file' => $file], [
            'file' => 'mimetypes:image/jpeg,image/png,image/svg+xml,image/webp',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }
}
