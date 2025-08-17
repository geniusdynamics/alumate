<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div 
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
        aria-hidden="true"
        @click="$emit('close')"
      ></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form @submit.prevent="handleSubmit">
          <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="w-full">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4" id="modal-title">
                  Upload Photo
                </h3>
                
                <!-- Photo Upload Area -->
                <div class="mb-4">
                  <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                    <input
                      ref="fileInput"
                      type="file"
                      accept="image/*"
                      multiple
                      @change="handleFileUpload"
                      class="hidden"
                    >
                    
                    <div v-if="uploadedFiles.length === 0">
                      <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                      </svg>
                      <div class="mt-4">
                        <p class="text-sm text-gray-600">Click to upload or drag and drop</p>
                        <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF up to 10MB</p>
                      </div>
                      <button
                        type="button"
                        @click="$refs.fileInput.click()"
                        class="mt-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
                      >
                        Choose Files
                      </button>
                    </div>
                    
                    <!-- Preview uploaded files -->
                    <div v-else class="space-y-2">
                      <div v-for="(file, index) in uploadedFiles" :key="index" class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <div class="flex items-center space-x-2">
                          <img v-if="file.preview" :src="file.preview" class="w-10 h-10 object-cover rounded" />
                          <span class="text-sm text-gray-700">{{ file.name }}</span>
                        </div>
                        <button
                          type="button"
                          @click="removeFile(index)"
                          class="text-red-500 hover:text-red-700"
                        >
                          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                          </svg>
                        </button>
                      </div>
                      <button
                        type="button"
                        @click="$refs.fileInput.click()"
                        class="text-blue-600 hover:text-blue-700 text-sm"
                      >
                        Add more photos
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Title -->
                <div class="mb-4">
                  <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Title (optional)
                  </label>
                  <input
                    id="title"
                    v-model="form.title"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    placeholder="Give your photos a title..."
                  >
                </div>

                <!-- Description -->
                <div class="mb-4">
                  <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Description (optional)
                  </label>
                  <textarea
                    id="description"
                    v-model="form.description"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    placeholder="Share the story behind these photos..."
                  ></textarea>
                </div>
              </div>
            </div>
          </div>
          
          <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="submit"
              :disabled="uploadedFiles.length === 0 || loading"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Uploading...
              </span>
              <span v-else>Upload Photos</span>
            </button>
            <button
              type="button"
              @click="$emit('close')"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:hover:bg-gray-700"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'

interface Props {
  eventId: number
}

interface FileWithPreview extends File {
  preview?: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  uploaded: [photos: any[]]
}>()

const loading = ref(false)
const uploadedFiles = ref<FileWithPreview[]>([])
const fileInput = ref<HTMLInputElement>()

const form = reactive({
  title: '',
  description: ''
})

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || [])
  
  for (const file of files) {
    // Check file size (10MB limit)
    if (file.size > 10 * 1024 * 1024) {
      alert(`File ${file.name} is too large. Maximum size is 10MB.`)
      continue
    }
    
    // Check file type
    if (!file.type.startsWith('image/')) {
      alert(`File ${file.name} is not a valid image.`)
      continue
    }
    
    // Create preview
    const fileWithPreview = file as FileWithPreview
    const reader = new FileReader()
    reader.onload = (e) => {
      fileWithPreview.preview = e.target?.result as string
    }
    reader.readAsDataURL(file)
    
    uploadedFiles.value.push(fileWithPreview)
  }
  
  // Clear the input
  if (target) {
    target.value = ''
  }
}

const removeFile = (index: number) => {
  uploadedFiles.value.splice(index, 1)
}

const handleSubmit = async () => {
  if (uploadedFiles.value.length === 0) return
  
  loading.value = true
  
  try {
    const formData = new FormData()
    formData.append('event_id', props.eventId.toString())
    formData.append('title', form.title)
    formData.append('description', form.description)
    
    uploadedFiles.value.forEach((file, index) => {
      formData.append(`photos[${index}]`, file)
    })
    
    const response = await fetch(`/api/reunions/${props.eventId}/photos`, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      }
    })
    
    if (!response.ok) {
      throw new Error('Upload failed')
    }
    
    const data = await response.json()
    emit('uploaded', data.photos || [])
    emit('close')
    
    // Reset form
    form.title = ''
    form.description = ''
    uploadedFiles.value = []
  } catch (error) {
    console.error('Error uploading photos:', error)
    alert('Failed to upload photos. Please try again.')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* Additional styles if needed */
</style>