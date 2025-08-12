<template>
    <BaseModal :show="true" @close="$emit('close')" max-width="2xl">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Create Post
                </h3>
                <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
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
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 resize-none"
                        required
                    ></textarea>
                </div>
                
                <!-- Post Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Post Type
                    </label>
                    <select
                        v-model="form.post_type"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Visibility
                    </label>
                    <select
                        v-model="form.visibility"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                        <option value="circles">My Circles</option>
                        <option value="public">Public</option>
                        <option value="connections">Connections Only</option>
                    </select>
                </div>
                
                <!-- Media Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Add Media (Optional)
                    </label>
                    <input
                        ref="fileInput"
                        type="file"
                        multiple
                        accept="image/*,video/*"
                        @change="handleFileUpload"
                        class="hidden"
                    />
                    <button
                        type="button"
                        @click="$refs.fileInput.click()"
                        class="flex items-center space-x-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <PhotoIcon class="h-5 w-5 text-gray-400" />
                        <span class="text-sm text-gray-600 dark:text-gray-300">Add Photos/Videos</span>
                    </button>
                </div>
                
                <!-- Media Preview -->
                <div v-if="form.media_files.length > 0" class="grid grid-cols-2 gap-2">
                    <div
                        v-for="(file, index) in form.media_files"
                        :key="index"
                        class="relative"
                    >
                        <img
                            v-if="file.type.startsWith('image/')"
                            :src="file.preview"
                            :alt="`Preview ${index + 1}`"
                            class="w-full h-24 object-cover rounded-lg"
                        />
                        <div
                            v-else
                            class="w-full h-24 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center"
                        >
                            <VideoCameraIcon class="h-8 w-8 text-gray-400" />
                        </div>
                        <button
                            type="button"
                            @click="removeFile(index)"
                            class="absolute -top-2 -right-2 h-6 w-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600"
                        >
                            <XMarkIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ form.content.length }}/500 characters
                    </div>
                    <div class="flex space-x-3">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="!form.content.trim() || posting"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors flex items-center space-x-2"
                        >
                            <div v-if="posting" class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                            <span>{{ posting ? 'Posting...' : 'Post' }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </BaseModal>
</template>

<script setup>
import { ref, reactive } from 'vue'
import BaseModal from '@/Components/ui/BaseModal.vue'
import {
    XMarkIcon,
    PhotoIcon,
    VideoCameraIcon
} from '@heroicons/vue/24/outline'

const emit = defineEmits(['close', 'posted'])

const posting = ref(false)
const fileInput = ref(null)

const form = reactive({
    content: '',
    post_type: 'general',
    visibility: 'circles',
    media_files: []
})

const handleFileUpload = (event) => {
    const files = Array.from(event.target.files)
    
    files.forEach(file => {
        if (file.size > 10 * 1024 * 1024) { // 10MB limit
            alert('File size must be less than 10MB')
            return
        }
        
        const fileObj = {
            file,
            type: file.type,
            preview: null
        }
        
        if (file.type.startsWith('image/')) {
            const reader = new FileReader()
            reader.onload = (e) => {
                fileObj.preview = e.target.result
            }
            reader.readAsDataURL(file)
        }
        
        form.media_files.push(fileObj)
    })
    
    // Clear the input
    event.target.value = ''
}

const removeFile = (index) => {
    form.media_files.splice(index, 1)
}

const createPost = async () => {
    try {
        posting.value = true
        
        const formData = new FormData()
        formData.append('content', form.content)
        formData.append('post_type', form.post_type)
        formData.append('visibility', form.visibility)
        
        form.media_files.forEach((fileObj, index) => {
            formData.append(`media_files[${index}]`, fileObj.file)
        })
        
        const response = await fetch('/api/posts', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        })
        
        if (response.ok) {
            emit('posted')
        } else {
            throw new Error('Failed to create post')
        }
    } catch (error) {
        console.error('Failed to create post:', error)
        alert('Failed to create post. Please try again.')
    } finally {
        posting.value = false
    }
}
</script>