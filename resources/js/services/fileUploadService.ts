import httpService from './httpService';

export interface UploadedFile {
    id: string;
    original_name: string;
    file_name: string;
    path: string;
    url: string;
    size: number;
    mime_type: string;
    uploaded_at: string;
}

export interface UploadResponse {
    success: boolean;
    data: UploadedFile | UploadedFile[];
    error?: string;
    message?: string;
}

export interface UploadProgress {
    loaded: number;
    total: number;
    percentage: number;
}

class FileUploadService {
    /**
     * Upload a single file
     */
    async uploadFile(
        file: File,
        onProgress?: (progress: UploadProgress) => void
    ): Promise<UploadResponse> {
        try {
            const formData = new FormData();
            formData.append('file', file);

            const response = await httpService.upload<UploadResponse>(
                '/api/upload',
                formData,
                onProgress ? (progressEvent) => {
                    const progress: UploadProgress = {
                        loaded: progressEvent.loaded,
                        total: progressEvent.total,
                        percentage: Math.round((progressEvent.loaded * 100) / progressEvent.total),
                    };
                    onProgress(progress);
                } : undefined
            );

            return response.data;
        } catch (error: any) {
            return {
                success: false,
                data: {} as UploadedFile,
                error: error.response?.data?.error || 'Upload failed',
                message: error.response?.data?.message || error.message,
            };
        }
    }

    /**
     * Upload multiple files
     */
    async uploadFiles(
        files: File[],
        onProgress?: (progress: UploadProgress) => void
    ): Promise<UploadResponse> {
        try {
            const formData = new FormData();
            files.forEach((file, index) => {
                formData.append(`files[${index}]`, file);
            });

            const response = await httpService.upload<UploadResponse>(
                '/api/upload/multiple',
                formData,
                onProgress ? (progressEvent) => {
                    const progress: UploadProgress = {
                        loaded: progressEvent.loaded,
                        total: progressEvent.total,
                        percentage: Math.round((progressEvent.loaded * 100) / progressEvent.total),
                    };
                    onProgress(progress);
                } : undefined
            );

            return response.data;
        } catch (error: any) {
            return {
                success: false,
                data: [],
                error: error.response?.data?.error || 'Upload failed',
                message: error.response?.data?.message || error.message,
            };
        }
    }

    /**
     * Delete a file
     */
    async deleteFile(filePath: string): Promise<{ success: boolean; message?: string; error?: string }> {
        try {
            const response = await httpService.delete('/api/upload', {
                data: { file_path: filePath },
            });

            return response.data;
        } catch (error: any) {
            return {
                success: false,
                error: error.response?.data?.error || 'Delete failed',
                message: error.response?.data?.message || error.message,
            };
        }
    }

    /**
     * Validate file before upload
     */
    validateFile(file: File, options: {
        maxSize?: number;
        allowedTypes?: string[];
        maxFiles?: number;
    } = {}): { valid: boolean; error?: string } {
        const { maxSize = 10 * 1024 * 1024, allowedTypes = [], maxFiles = 1 } = options;

        // Check file size
        if (file.size > maxSize) {
            return {
                valid: false,
                error: `File size exceeds ${this.formatFileSize(maxSize)}`,
            };
        }

        // Check file type
        if (allowedTypes.length > 0 && !allowedTypes.includes(file.type)) {
            return {
                valid: false,
                error: `File type "${file.type}" is not allowed`,
            };
        }

        return { valid: true };
    }

    /**
     * Format file size
     */
    formatFileSize(bytes: number): string {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

export const fileUploadService = new FileUploadService();
export default fileUploadService;
