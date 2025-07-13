<template>
    <div class="file-uploader-container">
        <div 
            @dragover.prevent="isDragOver = true"
            @dragleave="isDragOver = false"
            @drop.prevent="handleDrop"
            :class="[
                'file-uploader',
                { 'drag-over': isDragOver },
                { 'disabled': disabled }
            ]"
        >
            <input 
                type="file" 
                @change="uploadFile" 
                :multiple="multiple" 
                :accept="accept"
                :disabled="disabled"
                class="hidden" 
                ref="fileInput" 
            />
            
            <div class="upload-area" @click="triggerFileSelect">
                <div class="upload-icon">
                    <svg class="w-12 h-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
                <div class="upload-text">
                    <p class="text-lg font-medium text-foreground">Drop files here or click to upload</p>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ acceptedFormats ? `Accepted formats: ${acceptedFormats}` : 'Any file type' }}
                        {{ maxSize ? ` • Max size: ${formatFileSize(maxSize)}` : '' }}
                    </p>
                </div>
            </div>
        </div>
        
        <!-- File List -->
        <div v-if="files.length > 0" class="mt-4 space-y-2">
            <div v-for="file in files" :key="file.id" class="file-item">
                <div class="flex items-center space-x-3">
                    <div class="file-icon">
                        <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-foreground truncate">{{ file.name }}</p>
                        <p class="text-xs text-muted-foreground">{{ formatFileSize(file.size) }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div v-if="file.uploading" class="flex items-center space-x-2">
                            <div class="w-32 bg-muted rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full transition-all duration-300" :style="{ width: file.progress + '%' }"></div>
                            </div>
                            <span class="text-xs text-muted-foreground">{{ file.progress }}%</span>
                        </div>
                        <div v-else-if="file.error" class="text-red-500 text-xs">{{ file.error }}</div>
                        <div v-else-if="file.uploaded" class="text-green-500 text-xs">✓ Uploaded</div>
                        <button 
                            @click="removeFile(file.id)"
                            class="text-red-500 hover:text-red-700 text-xs"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import LoadingButton from '@/components/loaders/LoadingButton.vue';
import { useToast } from '@/composables/useToast';
import Skeleton from '@/components/loaders/Skeleton.vue';
import { fileUploadService } from '@/services/fileUploadService';

interface Props {
    multiple?: boolean;
    accept?: string;
    maxSize?: number; // in bytes
    uploadUrl?: string;
    disabled?: boolean;
}

interface FileItem {
    id: string;
    name: string;
    size: number;
    file: File;
    uploading: boolean;
    uploaded: boolean;
    progress: number;
    error?: string;
}

const props = withDefaults(defineProps<Props>(), {
    multiple: false,
    accept: '',
    maxSize: 10485760, // 10MB
    uploadUrl: '/upload',
    disabled: false,
});

const emit = defineEmits<{
    'file-uploaded': [file: FileItem];
    'file-error': [file: FileItem, error: string];
    'files-selected': [files: FileItem[]];
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const files = ref<FileItem[]>([]);
const isDragOver = ref(false);

const acceptedFormats = computed(() => {
    if (!props.accept) return null;
    return props.accept.split(',').map(format => format.trim().replace('.', '')).join(', ');
});

const triggerFileSelect = () => {
    if (!props.disabled) {
        fileInput.value?.click();
    }
};

const handleDrop = (event: DragEvent) => {
    isDragOver.value = false;
    if (event.dataTransfer?.files && !props.disabled) {
        processFiles(event.dataTransfer.files);
    }
};

const uploadFile = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files && !props.disabled) {
        processFiles(input.files);
    }
};

const processFiles = (fileList: FileList) => {
    const newFiles: FileItem[] = [];
    
    for (const file of fileList) {
        const fileItem: FileItem = {
            id: Date.now() + Math.random().toString(36).substr(2, 9),
            name: file.name,
            size: file.size,
            file,
            uploading: false,
            uploaded: false,
            progress: 0,
        };
        
        // Validate file size
        if (props.maxSize && file.size > props.maxSize) {
            fileItem.error = `File size exceeds ${formatFileSize(props.maxSize)}`;
            emit('file-error', fileItem, fileItem.error);
        } else {
            newFiles.push(fileItem);
        }
    }
    
    if (!props.multiple) {
        files.value = newFiles;
    } else {
        files.value.push(...newFiles);
    }
    
    emit('files-selected', newFiles);
    
    // Auto-upload files
    newFiles.forEach(file => {
        if (!file.error) {
            uploadFileToServer(file);
        }
    });
};

const uploadFileToServer = async (fileItem: FileItem) => {
    fileItem.uploading = true;
    
    const formData = new FormData();
    formData.append('file', fileItem.file);
    
    try {
        const response = await fileUploadService.uploadFile(fileItem.file, (progress) => {
            fileItem.progress = progress.percentage;
        });

        if (response.success) {
            fileItem.uploaded = true;
            fileItem.uploading = false;
            emit('file-uploaded', fileItem);
        } else {
            fileItem.error = response.error || 'Upload failed';
            fileItem.uploading = false;
            emit('file-error', fileItem, fileItem.error);
        }
    } catch (error) {
        fileItem.error = 'Upload failed';
        fileItem.uploading = false;
        emit('file-error', fileItem, fileItem.error);
    }
};

const removeFile = (fileId: string) => {
    files.value = files.value.filter(file => file.id !== fileId);
};

const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
</script>

<style scoped>
.file-uploader {
    @apply border-2 border-dashed border-muted-foreground/25 rounded-lg p-8 text-center transition-all duration-200 cursor-pointer hover:border-muted-foreground/50;
}

.file-uploader.drag-over {
    @apply border-primary bg-primary/5;
}

.file-uploader.disabled {
    @apply opacity-50 cursor-not-allowed;
}

.file-item {
    @apply p-3 border border-border rounded-lg bg-card;
}

.upload-area {
    @apply space-y-4;
}

.upload-icon {
    @apply flex justify-center;
}

.upload-text {
    @apply text-center;
}
</style>

