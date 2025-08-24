<template>
  <div class="modal-overlay" @click="closeModal">
    <div class="modal-container" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">Upload Photos</h3>
        <button @click="closeModal" class="close-button">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="modal-body">
        <div class="upload-area">
          <input
            type="file"
            ref="fileInput"
            @change="handleFileChange"
            accept="image/*"
            multiple
            class="file-input"
          />

          <div v-if="previews.length === 0" class="empty-state">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="text-gray-500 mt-2">Click to select photos or drag and drop</p>
            <p class="text-sm text-gray-400">Supports multiple images (JPEG, PNG)</p>
          </div>

          <div v-if="previews.length > 0" class="photo-grid">
            <div
              v-for="(preview, index) in previews"
              :key="index"
              class="photo-item"
            >
              <img :src="preview.url" :alt="preview.name" class="photo-preview" />
              <div class="photo-info">
                <p class="text-xs text-gray-600 truncate">{{ preview.name }}</p>
                <p class="text-xs text-gray-400">{{ formatFileSize(preview.size) }}</p>
              </div>
              <button
                @click="removePhoto(index)"
                class="remove-button"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <div v-if="previews.length > 0" class="upload-actions">
          <button
            @click="uploadPhotos"
            class="btn btn-primary"
            :disabled="uploading"
          >
            <svg v-if="uploading" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
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

  files.forEach(file => {
    const reader = new FileReader();
    reader.onload = (e) => {
      previews.value.push({
        url: e.target.result,
        name: file.name,
        size: file.size,
        file: file
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
    await new Promise(resolve => setTimeout(resolve, 2000)); // Simulate upload

    emit('upload', previews.value.map(p => p.file));
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
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}

.modal-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto;
}

.modal-header {
  @apply flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700;
}

.modal-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.close-button {
  @apply text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors;
}

.modal-body {
  @apply p-6;
}

.upload-area {
  @apply border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center;
  @apply hover:border-gray-400 dark:hover:border-gray-500 transition-colors;
}

.file-input {
  @apply absolute inset-0 w-full h-full opacity-0 cursor-pointer;
}

.empty-state {
  @apply pointer-events-none;
}

.photo-grid {
  @apply grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4;
}

.photo-item {
  @apply relative;
}

.photo-preview {
  @apply w-full h-32 object-cover rounded-lg;
}

.photo-info {
  @apply absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white p-2 rounded-b-lg;
}

.remove-button {
  @apply absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors;
}

.upload-actions {
  @apply mt-6 flex justify-center;
}

.btn {
  @apply inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-md;
  @apply focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors;
}

.btn-primary {
  @apply text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>