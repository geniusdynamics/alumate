<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
      <div class="p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold text-gray-900">Share Your Success Story</h2>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitStory" class="space-y-6">
          <!-- Basic Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Story Title *
              </label>
              <input
                v-model="form.title"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Give your story a compelling title"
              />
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Summary *
              </label>
              <textarea
                v-model="form.summary"
                required
                rows="3"
                maxlength="500"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Write a brief summary of your achievement (max 500 characters)"
              ></textarea>
              <p class="text-sm text-gray-500 mt-1">{{ form.summary.length }}/500 characters</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Achievement Type *
              </label>
              <select
                v-model="form.achievement_type"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Select achievement type</option>
                <option v-for="type in achievementTypes" :key="type" :value="type">
                  {{ formatAchievementType(type) }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Industry
              </label>
              <select
                v-model="form.industry"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Select industry</option>
                <option v-for="industry in industries" :key="industry" :value="industry">
                  {{ industry }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Current Role
              </label>
              <input
                v-model="form.current_role"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Your current job title"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Current Company
              </label>
              <input
                v-model="form.current_company"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Your current company"
              />
            </div>
          </div>

          <!-- Story Content -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Your Story *
            </label>
            <textarea
              v-model="form.content"
              required
              rows="8"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Tell your story in detail. What challenges did you face? How did you overcome them? What impact did you make?"
            ></textarea>
          </div>

          <!-- Media Upload -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Featured Image
            </label>
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
              <input
                ref="featuredImageInput"
                type="file"
                accept="image/*"
                @change="handleFeaturedImageUpload"
                class="hidden"
              />
              <div v-if="featuredImagePreview" class="mb-4">
                <img :src="featuredImagePreview" alt="Preview" class="max-w-xs h-32 object-cover rounded-md" />
                <button
                  @click="removeFeaturedImage"
                  type="button"
                  class="mt-2 text-sm text-red-600 hover:text-red-800"
                >
                  Remove image
                </button>
              </div>
              <div v-else class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                  <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <div class="mt-4">
                  <button
                    @click="$refs.featuredImageInput.click()"
                    type="button"
                    class="text-blue-600 hover:text-blue-500"
                  >
                    Upload a featured image
                  </button>
                  <p class="text-sm text-gray-500">PNG, JPG, GIF up to 2MB</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Additional Media -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Additional Media (Optional)
            </label>
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
              <input
                ref="mediaFilesInput"
                type="file"
                multiple
                accept="image/*,video/*,.pdf"
                @change="handleMediaFilesUpload"
                class="hidden"
              />
              <div v-if="mediaFilePreviews.length > 0" class="mb-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div
                    v-for="(preview, index) in mediaFilePreviews"
                    :key="index"
                    class="relative"
                  >
                    <img
                      v-if="preview.type === 'image'"
                      :src="preview.url"
                      :alt="`Preview ${index + 1}`"
                      class="w-full h-20 object-cover rounded-md"
                    />
                    <div
                      v-else
                      class="w-full h-20 bg-gray-100 rounded-md flex items-center justify-center"
                    >
                      <span class="text-sm text-gray-600">{{ preview.name }}</span>
                    </div>
                    <button
                      @click="removeMediaFile(index)"
                      type="button"
                      class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs"
                    >
                      ×
                    </button>
                  </div>
                </div>
              </div>
              <div class="text-center">
                <button
                  @click="$refs.mediaFilesInput.click()"
                  type="button"
                  class="text-blue-600 hover:text-blue-500"
                >
                  Upload additional media
                </button>
                <p class="text-sm text-gray-500">Images, videos, or documents up to 10MB each</p>
              </div>
            </div>
          </div>

          <!-- Tags -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Tags
            </label>
            <div class="flex flex-wrap gap-2 mb-2">
              <span
                v-for="tag in form.tags"
                :key="tag"
                class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800"
              >
                #{{ tag }}
                <button
                  @click="removeTag(tag)"
                  type="button"
                  class="ml-2 text-blue-600 hover:text-blue-800"
                >
                  ×
                </button>
              </span>
            </div>
            <input
              v-model="newTag"
              type="text"
              @keydown.enter.prevent="addTag"
              @keydown.comma.prevent="addTag"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Add tags (press Enter or comma to add)"
            />
          </div>

          <!-- Settings -->
          <div class="space-y-4">
            <div class="flex items-center">
              <input
                v-model="form.allow_social_sharing"
                type="checkbox"
                id="allow_sharing"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="allow_sharing" class="ml-2 block text-sm text-gray-900">
                Allow social media sharing
              </label>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Status
              </label>
              <select
                v-model="form.status"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="draft">Save as Draft</option>
                <option value="published">Publish Now</option>
              </select>
            </div>
          </div>

          <!-- Submit Buttons -->
          <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
            <button
              @click="$emit('close')"
              type="button"
              class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50"
            >
              {{ submitting ? 'Saving...' : (form.status === 'published' ? 'Publish Story' : 'Save Draft') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'

interface MediaPreview {
  type: 'image' | 'file'
  url: string
  name: string
  file: File
}

const submitting = ref(false)
const featuredImagePreview = ref<string | null>(null)
const mediaFilePreviews = ref<MediaPreview[]>([])
const newTag = ref('')

const form = reactive({
  title: '',
  summary: '',
  content: '',
  achievement_type: '',
  industry: '',
  current_role: '',
  current_company: '',
  tags: [] as string[],
  allow_social_sharing: true,
  status: 'draft',
  featured_image_file: null as File | null,
  media_files: [] as File[]
})

const industries = [
  'Technology', 'Healthcare', 'Finance', 'Education', 'Marketing',
  'Engineering', 'Consulting', 'Non-profit', 'Government', 'Media'
]

const achievementTypes = [
  'promotion', 'award', 'startup', 'publication', 'patent',
  'leadership', 'community_service', 'innovation', 'research', 'entrepreneurship'
]

defineEmits<{
  close: []
  created: [story: any]
}>()

const handleFeaturedImageUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    form.featured_image_file = file
    const reader = new FileReader()
    reader.onload = (e) => {
      featuredImagePreview.value = e.target?.result as string
    }
    reader.readAsDataURL(file)
  }
}

const removeFeaturedImage = () => {
  form.featured_image_file = null
  featuredImagePreview.value = null
}

const handleMediaFilesUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || [])
  
  files.forEach(file => {
    form.media_files.push(file)
    
    const preview: MediaPreview = {
      type: file.type.startsWith('image/') ? 'image' : 'file',
      url: '',
      name: file.name,
      file
    }
    
    if (preview.type === 'image') {
      const reader = new FileReader()
      reader.onload = (e) => {
        preview.url = e.target?.result as string
      }
      reader.readAsDataURL(file)
    }
    
    mediaFilePreviews.value.push(preview)
  })
}

const removeMediaFile = (index: number) => {
  form.media_files.splice(index, 1)
  mediaFilePreviews.value.splice(index, 1)
}

const addTag = () => {
  const tag = newTag.value.trim().replace(/^#/, '')
  if (tag && !form.tags.includes(tag)) {
    form.tags.push(tag)
    newTag.value = ''
  }
}

const removeTag = (tag: string) => {
  const index = form.tags.indexOf(tag)
  if (index > -1) {
    form.tags.splice(index, 1)
  }
}

const submitStory = async () => {
  submitting.value = true
  
  try {
    const formData = new FormData()
    
    // Add text fields
    Object.keys(form).forEach(key => {
      if (key === 'featured_image_file' || key === 'media_files') return
      
      const value = form[key]
      if (Array.isArray(value)) {
        formData.append(key, JSON.stringify(value))
      } else if (value !== null && value !== '') {
        formData.append(key, value.toString())
      }
    })
    
    // Add files
    if (form.featured_image_file) {
      formData.append('featured_image_file', form.featured_image_file)
    }
    
    form.media_files.forEach((file, index) => {
      formData.append(`media_files[${index}]`, file)
    })
    
    const response = await fetch('/api/success-stories', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      },
      body: formData
    })
    
    const data = await response.json()
    
    if (data.success) {
      emit('created', data.data)
    } else {
      alert('Error creating story: ' + (data.message || 'Unknown error'))
    }
  } catch (error) {
    console.error('Error submitting story:', error)
    alert('Error creating story. Please try again.')
  } finally {
    submitting.value = false
  }
}

const formatAchievementType = (type: string) => {
  return type.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

const emit = defineEmits<{
  close: []
  created: [story: any]
}>()
</script>