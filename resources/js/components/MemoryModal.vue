<template>
  <div class="modal-overlay" @click="closeModal">
    <div class="modal-container" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">Share a Memory</h3>
        <button @click="closeModal" class="close-button">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Memory Title</label>
          <input
            v-model="memoryData.title"
            type="text"
            class="form-input"
            placeholder="Give your memory a title"
          />
        </div>

        <div class="form-group">
          <label class="form-label">Share Your Story</label>
          <textarea
            v-model="memoryData.description"
            class="form-textarea"
            placeholder="What happened? Who was there? How did it make you feel?"
            rows="4"
          ></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">When did this happen?</label>
          <input
            v-model="memoryData.date"
            type="date"
            class="form-input"
          />
        </div>

        <div class="form-group">
          <label class="form-label">Add Photos (Optional)</label>
          <input
            type="file"
            ref="fileInput"
            @change="handleFileChange"
            accept="image/*"
            multiple
            class="form-input"
          />
          <p class="text-sm text-gray-500 mt-1">You can upload multiple photos</p>
        </div>

        <div v-if="previews.length > 0" class="photo-previews">
          <h4 class="text-sm font-medium text-gray-700 mb-2">Photo Previews</h4>
          <div class="grid grid-cols-3 gap-2">
            <div
              v-for="(preview, index) in previews"
              :key="index"
              class="relative"
            >
              <img :src="preview" class="w-full h-20 object-cover rounded" />
              <button
                @click="removePhoto(index)"
                class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1"
              >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button @click="closeModal" class="btn btn-secondary">
          Cancel
        </button>
        <button
          @click="submitMemory"
          class="btn btn-primary"
          :disabled="!canSubmit || submitting"
        >
          <svg v-if="submitting" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ submitting ? 'Sharing...' : 'Share Memory' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const emit = defineEmits(['close', 'submit']);

const submitting = ref(false);
const fileInput = ref(null);
const previews = ref([]);

const memoryData = ref({
  title: '',
  description: '',
  date: new Date().toISOString().split('T')[0],
  photos: []
});

const canSubmit = computed(() => {
  return memoryData.value.title.trim() &&
         memoryData.value.description.trim();
});

const closeModal = () => {
  emit('close');
};

const handleFileChange = (event) => {
  const files = Array.from(event.target.files);
  memoryData.value.photos = files;

  // Create previews
  previews.value = [];
  files.forEach(file => {
    const reader = new FileReader();
    reader.onload = (e) => {
      previews.value.push(e.target.result);
    };
    reader.readAsDataURL(file);
  });
};

const removePhoto = (index) => {
  memoryData.value.photos.splice(index, 1);
  previews.value.splice(index, 1);

  // Update file input
  if (fileInput.value) {
    const dt = new DataTransfer();
    memoryData.value.photos.forEach(file => dt.items.add(file));
    fileInput.value.files = dt.files;
  }
};

const submitMemory = async () => {
  if (!canSubmit.value) return;

  submitting.value = true;
  try {
    // Here you would typically upload photos and save the memory
    await new Promise(resolve => setTimeout(resolve, 1000)); // Simulate API call

    emit('submit', {
      ...memoryData.value,
      photos: previews.value
    });

    closeModal();
  } catch (error) {
    console.error('Error sharing memory:', error);
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped>
.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}

.modal-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto;
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
  @apply p-6 space-y-6;
}

.form-group {
  @apply space-y-2;
}

.form-label {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-300;
}

.form-input {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.form-textarea {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.modal-footer {
  @apply flex items-center justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-700;
}

.btn {
  @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md;
  @apply focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors;
}

.btn-primary {
  @apply text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply text-gray-700 bg-white border-gray-300 hover:bg-gray-50 focus:ring-blue-500;
  @apply dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600;
}

.photo-previews {
  @apply mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg;
}
</style>