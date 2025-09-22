<template>
    <div class="modal-overlay" @click="closeModal">
        <div class="modal-container" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Upload Photos</h3>
                <button @click="closeModal" class="close-button">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="modal-body">
                <div class="upload-area">
                    <input type="file" ref="fileInput" @change="handleFileChange" accept="image/*" multiple class="file-input" />

                    <div v-if="previews.length === 0" class="empty-state">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        <p class="mt-2 text-gray-500">Click to select photos or drag and drop</p>
                        <p class="text-sm text-gray-400">Supports multiple images (JPEG, PNG)</p>
                    </div>

                    <div v-if="previews.length > 0" class="photo-grid">
                        <div v-for="(preview, index) in previews" :key="index" class="photo-item">
                            <img :src="preview.url" :alt="preview.name" class="photo-preview" />
                            <div class="photo-info">
                                <p class="truncate text-xs text-gray-600">{{ preview.name }}</p>
                                <p class="text-xs text-gray-400">{{ formatFileSize(preview.size) }}</p>
                            </div>
                            <button @click="removePhoto(index)" class="remove-button">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="previews.length > 0" class="upload-actions">
                    <button @click="uploadPhotos" class="btn btn-primary" :disabled="uploading">
                        <svg v-if="uploading" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        {{ uploading ? 'Uploading...' : `Upload ${previews.length} Photo${previews.length > 1 ? 's' : ''}` }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const emit = defineEmits(['close', 'upload']);

const uploading = ref(false);
const fileInput = ref(null);
const previews = ref([]);

const closeModal = () => {
    emit('close');
};

const handleFileChange = (event) => {
    const files = Array.from(event.target.files);

    files.forEach((file) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            previews.value.push({
                url: e.target.result,
                name: file.name,
                size: file.size,
                file: file,
            });
        };
        reader.readAsDataURL(file);
    });
};

const removePhoto = (index) => {
    previews.value.splice(index, 1);
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const uploadPhotos = async () => {
    if (previews.value.length === 0) return;

    uploading.value = true;
    try {
        // Here you would typically upload the photos
        await new Promise((resolve) => setTimeout(resolve, 2000)); // Simulate upload

        emit(
            'upload',
            previews.value.map((p) => p.file),
        );
        closeModal();
    } catch (error) {
        console.error('Error uploading photos:', error);
    } finally {
        uploading.value = false;
    }
};
</script>

<style scoped>
.modal-overlay {
    @apply fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50;
}

.modal-container {
    @apply mx-4 max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800;
}

.modal-header {
    @apply flex items-center justify-between border-b border-gray-200 p-6 dark:border-gray-700;
}

.modal-title {
    @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.close-button {
    @apply text-gray-400 transition-colors hover:text-gray-600 dark:hover:text-gray-300;
}

.modal-body {
    @apply p-6;
}

.upload-area {
    @apply rounded-lg border-2 border-dashed border-gray-300 p-8 text-center dark:border-gray-600;
    @apply transition-colors hover:border-gray-400 dark:hover:border-gray-500;
}

.file-input {
    @apply absolute inset-0 h-full w-full cursor-pointer opacity-0;
}

.empty-state {
    @apply pointer-events-none;
}

.photo-grid {
    @apply grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4;
}

.photo-item {
    @apply relative;
}

.photo-preview {
    @apply h-32 w-full rounded-lg object-cover;
}

.photo-info {
    @apply absolute bottom-0 left-0 right-0 rounded-b-lg bg-black bg-opacity-50 p-2 text-white;
}

.remove-button {
    @apply absolute right-2 top-2 rounded-full bg-red-500 p-1 text-white transition-colors hover:bg-red-600;
}

.upload-actions {
    @apply mt-6 flex justify-center;
}

.btn {
    @apply inline-flex items-center rounded-md border border-transparent px-6 py-3 text-sm font-medium;
    @apply transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.btn-primary {
    @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
    @apply disabled:cursor-not-allowed disabled:opacity-50;
}
</style>
