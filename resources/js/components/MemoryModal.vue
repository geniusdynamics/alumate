<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-900">Share a Memory</h2>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <form @submit.prevent="createMemory" class="space-y-4">
        <!-- Type Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Memory Type *</label>
          <div class="grid grid-cols-3 gap-2">
            <button
              v-for="type in memoryTypes"
              :key="type.value"
              type="button"
              @click="form.type = type.value"
              class="p-3 border rounded-lg text-center transition-colors"
              :class="form.type === type.value 
                ? 'border-purple-500 bg-purple-50 text-purple-700' 
                : 'border-gray-300 hover:border-gray-400'"
            >
              <div class="text-lg mb-1">{{ type.icon }}</div>
              <div class="text-sm font-medium">{{ type.label }}</div>
            </button>
          </div>
        </div>

        <!-- Title -->
        <div>
          <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
            Title *
          </label>
          <input
            id="title"
            v-model="form.title"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
            placeholder="Give your memory a title..."
          >
        </div>

        <!-- Description -->
        <div>
          <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
            Description *
          </label>
          <textarea
            id="description"
            v-model="form.description"
            rows="4"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
            placeholder="Share your memory in detail..."
          ></textarea>
        </div>

        <!-- Memory Date -->
        <div>
          <label for="memory_date" class="block text-sm font-medium text-gray-700 mb-2">
            When did this happen?
          </label>
          <input
            id="memory_date"
            v-model="form.memory_date"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
          >
        </div>

        <!-- Media Upload -->
        <div v-if="['photo', 'video'].includes(form.type)">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ form.type === 'photo' ? 'Photos' : 'Video' }}
          </label>
          
          <!-- File Input -->
          <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
            <input
              ref="fileInput"
              type="file"
              :accept="form.type === 'photo' ? 'image/*' : 'video/*'"
              :multiple="form.type === 'photo'"
              @change="handleFileUpload"
              class="hidden"
            >
            
            <div v-if="uploadedFiles.length === 0">
              <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              <div class="mt-4">
                <button
                  type="button"
                  @click="$refs.fileInput.click()"
                  class="text-purple-600 hover:text-purple-500"
                >
                  Upload {{ form.type === 'photo' ? 'photos' : 'video' }}
                </button>
                <p class="text-sm text-gray-500 mt-1">
                  {{ form.type === 'photo' ? 'PNG, JPG, GIF up to 10MB each' : 'MP4, MOV up to 50MB' }}
                </p>
              </div>
            </div>

            <!-- Preview uploaded files -->
            <div v-else class="space-y-2">
              <div
                v-for="(file, index) in uploadedFiles"
                :key="index"
                class="flex items-center justify-between p-2 bg-gray-50 rounded"
              >
                <span class="text-sm text-gray-700">{{ file.name }}</span>
                <button
                  type="button"
                  @click="removeFile(index)"
                  class="text-red-600 hover:text-red-800"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </div>
              <button
                type="button"
                @click="$refs.fileInput.click()"
                class="text-sm text-purple-600 hover:text-purple-500"
              >
                Add more {{ form.type === 'photo' ? 'photos' : 'files' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Tagged Users -->
        <div>
          <label for="tagged_users" class="block text-sm font-medium text-gray-700 mb-2">
            Tag People
          </label>
          <input
            id="tagged_users"
            v-model="form.tagged_users"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
            placeholder="Tag people who were part of this memory (comma separated)"
          >
          <p class="text-xs text-gray-500 mt-1">Separate multiple names with commas</p>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-3 pt-4">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="!canSubmit || loading"
            class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ loading ? 'Sharing...' : 'Share Memory' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import axios from 'axios'

interface Props {
  show: boolean
  reunion?: any
}

interface Emits {
  (e: 'close'): void
  (e: 'created'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const loading = ref(false)
const uploadedFiles = ref([])
const fileInput = ref(null)

const memoryTypes = [
  { value: 'text', label: 'Story', icon: '📝' },
  { value: 'photo', label: 'Photo', icon: '📸' },
  { value: 'video', label: 'Video', icon: '🎥' }
]

const form = reactive({
  type: 'text',
  title: '',
  description: '',
  memory_date: '',
  tagged_users: '',
  media_urls: []
})

const canSubmit = computed(() => {
  return form.type && form.title && form.description
})

const handleFileUpload = async (event: Event) => {
  const files = Array.from((event.target as HTMLInputElement).files || [])
  
  for (const file of files) {
    // Validate file size
    const maxSize = form.type === 'photo' ? 10 * 1024 * 1024 : 50 * 1024 * 1024 // 10MB for photos, 50MB for videos
    if (file.size > maxSize) {
      alert(`File ${file.name} is too large. Maximum size is ${form.type === 'photo' ? '10MB' : '50MB'}.`)
      continue
    }

    uploadedFiles.value.push(file)
  }
}

const removeFile = (index: number) => {
  uploadedFiles.value.splice(index, 1)
}

const uploadFiles = async (): Promise<string[]> => {
  if (uploadedFiles.value.length === 0) return []

  const uploadPromises = uploadedFiles.value.map(async (file) => {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('type', form.type)
    
    // This would typically upload to a file storage service
    // For now, we'll simulate with a placeholder URL
    return `https://example.com/uploads/${Date.now()}-${file.name}`
  })

  return Promise.all(uploadPromises)
}

const createMemory = async () => {
  if (!canSubmit.value) return

  loading.value = true

  try {
    // Upload files first if any
    const mediaUrls = await uploadFiles()

    const memoryData = {
      type: form.type,
      title: form.title,
      description: form.description,
      memory_date: form.memory_date || null,
      tagged_users: form.tagged_users ? form.tagged_users.split(',').map(name => name.trim()).filter(name => name) : [],
      media_urls: mediaUrls.length > 0 ? mediaUrls : null
    }

    // Use reunion ID if available, otherwise use a generic endpoint
    const endpoint = props.reunion 
      ? `/api/reunions/${props.reunion.id}/memories`
      : '/api/memories'

    await axios.post(endpoint, memoryData)

    emit('created')
    emit('close')
    resetForm()
  } catch (error) {
    console.error('Failed to create memory:', error)
    alert('Failed to share memory. Please try again.')
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  Object.assign(form, {
    type: 'text',
    title: '',
    description: '',
    memory_date: '',
    tagged_users: '',
    media_urls: []
  })
  uploadedFiles.value = []
}

// Reset form when modal is closed
watch(() => props.show, (newShow) => {
  if (!newShow) {
    resetForm()
  }
})
</script>