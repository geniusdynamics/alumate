<template>
    <BaseModal :show="true" @close="$emit('close')" max-width="2xl">
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Create Post</h3>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>

            <form @submit.prevent="createPost" class="space-y-4">
                <!-- Post Content -->
                <div>
                    <textarea
                        v-model="form.content"
                        placeholder="What's on your mind?"
                        rows="4"
                        class="w-full resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder-gray-500 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                        required
                    ></textarea>
                </div>

                <!-- Post Type -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Post Type </label>
                    <select
                        v-model="form.post_type"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="general">General Update</option>
                        <option value="career">Career Update</option>
                        <option value="achievement">Achievement</option>
                        <option value="question">Question</option>
                        <option value="event">Event Announcement</option>
                    </select>
                </div>

                <!-- Visibility -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Visibility </label>
                    <select
                        v-model="form.visibility"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="circles">My Circles</option>
                        <option value="public">Public</option>
                        <option value="connections">Connections Only</option>
                    </select>
                </div>

                <!-- Media Upload -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Add Media (Optional) </label>
                    <input ref="fileInput" type="file" multiple accept="image/*,video/*" @change="handleFileUpload" class="hidden" />
                    <button
                        type="button"
                        @click="$refs.fileInput.click()"
                        class="flex items-center space-x-2 rounded-lg border border-gray-300 px-4 py-2 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
                    >
                        <PhotoIcon class="h-5 w-5 text-gray-400" />
                        <span class="text-sm text-gray-600 dark:text-gray-300">Add Photos/Videos</span>
                    </button>
                </div>

                <!-- Media Preview -->
                <div v-if="form.media_files.length > 0" class="grid grid-cols-2 gap-2">
                    <div v-for="(file, index) in form.media_files" :key="index" class="relative">
                        <img
                            v-if="file.type.startsWith('image/')"
                            :src="file.preview"
                            :alt="`Preview ${index + 1}`"
                            class="h-24 w-full rounded-lg object-cover"
                        />
                        <div v-else class="flex h-24 w-full items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700">
                            <VideoCameraIcon class="h-8 w-8 text-gray-400" />
                        </div>
                        <button
                            type="button"
                            @click="removeFile(index)"
                            class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white hover:bg-red-600"
                        >
                            <XMarkIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ form.content.length }}/500 characters</div>
                    <div class="flex space-x-3">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="!form.content.trim() || posting"
                            class="flex items-center space-x-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <div v-if="posting" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                            <span>{{ posting ? 'Posting...' : 'Post' }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </BaseModal>
</template>

<script setup>
import BaseModal from '@/Components/ui/BaseModal.vue';
import { PhotoIcon, VideoCameraIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { reactive, ref } from 'vue';

const emit = defineEmits(['close', 'posted']);

const posting = ref(false);
const fileInput = ref(null);

const form = reactive({
    content: '',
    post_type: 'general',
    visibility: 'circles',
    media_files: [],
});

const handleFileUpload = (event) => {
    const files = Array.from(event.target.files);

    files.forEach((file) => {
        if (file.size > 10 * 1024 * 1024) {
            // 10MB limit
            alert('File size must be less than 10MB');
            return;
        }

        const fileObj = {
            file,
            type: file.type,
            preview: null,
        };

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                fileObj.preview = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        form.media_files.push(fileObj);
    });

    // Clear the input
    event.target.value = '';
};

const removeFile = (index) => {
    form.media_files.splice(index, 1);
};

const createPost = async () => {
    try {
        posting.value = true;

        const formData = new FormData();
        formData.append('content', form.content);
        formData.append('post_type', form.post_type);
        formData.append('visibility', form.visibility);

        form.media_files.forEach((fileObj, index) => {
            formData.append(`media_files[${index}]`, fileObj.file);
        });

        const response = await fetch('/api/posts', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: formData,
        });

        if (response.ok) {
            emit('posted');
        } else {
            throw new Error('Failed to create post');
        }
    } catch (error) {
        console.error('Failed to create post:', error);
        alert('Failed to create post. Please try again.');
    } finally {
        posting.value = false;
    }
};
</script>











