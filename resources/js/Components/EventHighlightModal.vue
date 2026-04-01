<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white p-6">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900">Create Event Highlight</h2>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="createHighlight" class="space-y-4">
                <!-- Type Selection -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Type *</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            v-for="type in highlightTypes"
                            :key="type.value"
                            type="button"
                            @click="form.type = type.value"
                            class="rounded-lg border p-3 text-center transition-colors"
                            :class="form.type === type.value ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-300 hover:border-gray-400'"
                        >
                            <div class="mb-1 text-lg">{{ type.icon }}</div>
                            <div class="text-sm font-medium">{{ type.label }}</div>
                        </button>
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <label for="title" class="mb-2 block text-sm font-medium text-gray-700"> Title * </label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        required
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Give your highlight a title..."
                    />
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="mb-2 block text-sm font-medium text-gray-700"> Description </label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Tell us more about this highlight..."
                    ></textarea>
                </div>

                <!-- Media Upload -->
                <div v-if="['photo', 'video'].includes(form.type)">
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        {{ form.type === 'photo' ? 'Photos' : 'Video' }}
                    </label>

                    <!-- File Input -->
                    <div class="rounded-lg border-2 border-dashed border-gray-300 p-6 text-center">
                        <input
                            ref="fileInput"
                            type="file"
                            :accept="form.type === 'photo' ? 'image/*' : 'video/*'"
                            :multiple="form.type === 'photo'"
                            @change="handleFileUpload"
                            class="hidden"
                        />

                        <div v-if="uploadedFiles.length === 0">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path
                                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            <div class="mt-4">
                                <button type="button" @click="$refs.fileInput.click()" class="text-blue-600 hover:text-blue-500">
                                    Upload {{ form.type === 'photo' ? 'photos' : 'video' }}
                                </button>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ form.type === 'photo' ? 'PNG, JPG, GIF up to 10MB each' : 'MP4, MOV up to 50MB' }}
                                </p>
                            </div>
                        </div>

                        <!-- Preview uploaded files -->
                        <div v-else class="space-y-2">
                            <div v-for="(file, index) in uploadedFiles" :key="index" class="flex items-center justify-between rounded bg-gray-50 p-2">
                                <span class="text-sm text-gray-700">{{ file.name }}</span>
                                <button type="button" @click="removeFile(index)" class="text-red-600 hover:text-red-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <button type="button" @click="$refs.fileInput.click()" class="text-sm text-blue-600 hover:text-blue-500">
                                Add more {{ form.type === 'photo' ? 'photos' : 'files' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quote Content (for quote type) -->
                <div v-if="form.type === 'quote'">
                    <label for="quote_content" class="mb-2 block text-sm font-medium text-gray-700"> Quote * </label>
                    <textarea
                        id="quote_content"
                        v-model="form.quote_content"
                        rows="4"
                        required
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter the memorable quote..."
                    ></textarea>

                    <div class="mt-2">
                        <label for="quote_author" class="mb-1 block text-sm font-medium text-gray-700"> Attribution </label>
                        <input
                            id="quote_author"
                            v-model="form.quote_author"
                            type="text"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Who said this? (optional)"
                        />
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end space-x-3 pt-4">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="!canSubmit || loading"
                        class="rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ loading ? 'Creating...' : 'Create Highlight' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';

interface Props {
    show: boolean;
    event: any;
}

interface Emits {
    (e: 'close'): void;
    (e: 'created'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const loading = ref(false);
const uploadedFiles = ref([]);
const fileInput = ref(null);

const highlightTypes = [
    { value: 'photo', label: 'Photo', icon: '📸' },
    { value: 'video', label: 'Video', icon: '🎥' },
    { value: 'quote', label: 'Quote', icon: '💬' },
    { value: 'moment', label: 'Moment', icon: '✨' },
    { value: 'achievement', label: 'Achievement', icon: '🏆' },
];

const form = reactive({
    type: 'photo',
    title: '',
    description: '',
    quote_content: '',
    quote_author: '',
    media_urls: [],
});

const canSubmit = computed(() => {
    if (!form.type || !form.title) return false;

    if (form.type === 'quote' && !form.quote_content) return false;

    return true;
});

const handleFileUpload = async (event: Event) => {
    const files = Array.from((event.target as HTMLInputElement).files || []);

    for (const file of files) {
        // Validate file size
        const maxSize = form.type === 'photo' ? 10 * 1024 * 1024 : 50 * 1024 * 1024; // 10MB for photos, 50MB for videos
        if (file.size > maxSize) {
            // TODO-toast: alert(`File ${file.name} is too large. Maximum size is ${form.type === 'photo' ? '10MB' : '50MB'}.`);
            continue;
        }

        uploadedFiles.value.push(file);
    }
};

const removeFile = (index: number) => {
    uploadedFiles.value.splice(index, 1);
};

const uploadFiles = async (): Promise<string[]> => {
    if (uploadedFiles.value.length === 0) return [];

    const uploadPromises = uploadedFiles.value.map(async (file) => {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('type', form.type);

        // This would typically upload to a file storage service
        // For now, we'll simulate with a placeholder URL
        return `https://example.com/uploads/${Date.now()}-${file.name}`;
    });

    return Promise.all(uploadPromises);
};

const createHighlight = async () => {
    if (!canSubmit.value) return;

    loading.value = true;

    try {
        // Upload files first if any
        const mediaUrls = await uploadFiles();

        const highlightData = {
            type: form.type,
            title: form.title,
            description: form.description || null,
            media_urls: mediaUrls.length > 0 ? mediaUrls : null,
            metadata: {},
        };

        // Add type-specific data
        if (form.type === 'quote') {
            highlightData.metadata = {
                quote_content: form.quote_content,
                quote_author: form.quote_author || null,
            };
        }

        await axios.post(`/api/events/${props.event.id}/highlights`, highlightData);

        emit('created');
        emit('close');
        resetForm();
    } catch (error) {
        console.error('Failed to create highlight:', error);
        // TODO-toast: alert('Failed to create highlight. Please try again.');
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    Object.assign(form, {
        type: 'photo',
        title: '',
        description: '',
        quote_content: '',
        quote_author: '',
        media_urls: [],
    });
    uploadedFiles.value = [];
};

// Reset form when modal is closed
watch(
    () => props.show,
    (newShow) => {
        if (!newShow) {
            resetForm();
        }
    },
);
</script>
