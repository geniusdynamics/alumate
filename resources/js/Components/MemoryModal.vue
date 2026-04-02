<template>
    <div class="modal-overlay" @click="closeModal">
        <div class="modal-container" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Share a Memory</h3>
                <button @click="closeModal" class="close-button">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Memory Title</label>
                    <input v-model="memoryData.title" type="text" class="form-input" placeholder="Give your memory a title" />
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
                    <input v-model="memoryData.date" type="date" class="form-input" />
                </div>

                <div class="form-group">
                    <label class="form-label">Add Photos (Optional)</label>
                    <input type="file" ref="fileInput" @change="handleFileChange" accept="image/*" multiple class="form-input" />
                    <p class="mt-1 text-sm text-gray-500">You can upload multiple photos</p>
                </div>

                <div v-if="previews.length > 0" class="photo-previews">
                    <h4 class="mb-2 text-sm font-medium text-gray-700">Photo Previews</h4>
                    <div class="grid grid-cols-3 gap-2">
                        <div v-for="(preview, index) in previews" :key="index" class="relative">
                            <img :src="preview" class="h-20 w-full rounded object-cover" />
                            <button @click="removePhoto(index)" class="absolute right-1 top-1 rounded-full bg-red-500 p-1 text-white">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button @click="closeModal" class="btn btn-secondary">Cancel</button>
                <button @click="submitMemory" class="btn btn-primary" :disabled="!canSubmit || submitting">
                    <svg v-if="submitting" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    {{ submitting ? 'Sharing...' : 'Share Memory' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

const emit = defineEmits(['close', 'submit']);

const submitting = ref(false);
const fileInput = ref(null);
const previews = ref([]);

const memoryData = ref({
    title: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
    photos: [],
});

const canSubmit = computed(() => {
    return memoryData.value.title.trim() && memoryData.value.description.trim();
});

const closeModal = () => {
    emit('close');
};

const handleFileChange = (event) => {
    const files = Array.from(event.target.files);
    memoryData.value.photos = files;

    // Create previews
    previews.value = [];
    files.forEach((file) => {
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
        memoryData.value.photos.forEach((file) => dt.items.add(file));
        fileInput.value.files = dt.files;
    }
};

const submitMemory = async () => {
    if (!canSubmit.value) return;

    submitting.value = true;
    try {
        // Here you would typically upload photos and save the memory
        await new Promise((resolve) => setTimeout(resolve, 1000)); // Simulate API call

        emit('submit', {
            ...memoryData.value,
            photos: previews.value,
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
    @apply fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50;
}

.modal-container {
    @apply mx-4 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800;
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
    @apply space-y-6 p-6;
}

.form-group {
    @apply space-y-2;
}

.form-label {
    @apply block text-sm font-medium text-gray-700 dark:text-gray-300;
}

.form-input {
    @apply w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-600;
    @apply bg-white text-gray-900 dark:bg-gray-700 dark:text-white;
    @apply focus:border-blue-500 focus:ring-2 focus:ring-blue-500;
}

.form-textarea {
    @apply w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-600;
    @apply bg-white text-gray-900 dark:bg-gray-700 dark:text-white;
    @apply focus:border-blue-500 focus:ring-2 focus:ring-blue-500;
}

.modal-footer {
    @apply flex items-center justify-end space-x-3 border-t border-gray-200 p-6 dark:border-gray-700;
}

.btn {
    @apply inline-flex items-center rounded-md border border-transparent px-4 py-2 text-sm font-medium;
    @apply transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.btn-primary {
    @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
    @apply disabled:cursor-not-allowed disabled:opacity-50;
}

.btn-secondary {
    @apply border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-blue-500;
    @apply dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600;
}

.photo-previews {
    @apply mt-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-700;
}
</style>

