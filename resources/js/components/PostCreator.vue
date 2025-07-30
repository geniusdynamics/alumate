<template>
  <div class="post-creator bg-white rounded-lg shadow-sm border p-6">
    <form @submit.prevent="submitPost">
      <!-- User Avatar and Basic Info -->
      <div class="flex items-start space-x-3 mb-4">
        <img 
          :src="user.avatar_url || '/default-avatar.png'" 
          :alt="user.name"
          class="w-10 h-10 rounded-full"
        >
        <div class="flex-1">
          <h3 class="font-medium text-gray-900">{{ user.name }}</h3>
          <div class="flex items-center space-x-2 mt-1">
            <select 
              v-model="form.visibility" 
              class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="public">🌍 Public</option>
              <option value="circles">👥 My Circles</option>
              <option value="groups">🏢 Selected Groups</option>
              <option value="specific">🎯 Specific Audience</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Rich Text Editor -->
      <div class="mb-4">
        <div 
          ref="editor"
          contenteditable="true"
          @input="updateContent"
          @paste="handlePaste"
          class="min-h-[120px] p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
          :class="{ 'border-red-300': errors.content }"
          placeholder="What's on your mind?"
        ></div>
        <div class="flex justify-between items-center mt-2">
          <div class="text-sm text-gray-500">
            {{ contentLength }}/5000 characters
          </div>
          <div class="flex space-x-2">
            <button 
              type="button" 
              @click="formatText('bold')"
              class="p-1 text-gray-600 hover:text-gray-800"
              title="Bold"
            >
              <strong>B</strong>
            </button>
            <button 
              type="button" 
              @click="formatText('italic')"
              class="p-1 text-gray-600 hover:text-gray-800"
              title="Italic"
            >
              <em>I</em>
            </button>
            <button 
              type="button" 
              @click="insertLink"
              class="p-1 text-gray-600 hover:text-gray-800"
              title="Add Link"
            >
              🔗
            </button>
          </div>
        </div>
        <div v-if="errors.content" class="text-red-500 text-sm mt-1">
          {{ errors.content }}
        </div>
      </div>

      <!-- Media Upload Area -->
      <div class="mb-4">
        <div 
          @drop="handleDrop"
          @dragover.prevent
          @dragenter.prevent
          class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors"
          :class="{ 'border-blue-500 bg-blue-50': isDragging }"
        >
          <input 
            ref="fileInput"
            type="file"
            multiple
            accept="image/*,video/*,.pdf,.doc,.docx"
            @change="handleFileSelect"
            class="hidden"
          >
          <div v-if="!uploadedMedia.length">
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
              <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <p class="mt-2 text-sm text-gray-600">
              <button 
                type="button" 
                @click="$refs.fileInput.click()"
                class="font-medium text-blue-600 hover:text-blue-500"
              >
                Upload files
              </button>
              or drag and drop
            </p>
            <p class="text-xs text-gray-500">PNG, JPG, GIF, MP4, PDF up to 100MB</p>
          </div>
        </div>

        <!-- Media Preview -->
        <div v-if="uploadedMedia.length" class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4">
          <div 
            v-for="(media, index) in uploadedMedia" 
            :key="index"
            class="relative group"
          >
            <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
              <img 
                v-if="media.type === 'image'"
                :src="media.urls.medium || media.urls.original"
                :alt="media.original_name"
                class="w-full h-full object-cover"
              >
              <div 
                v-else-if="media.type === 'video'"
                class="w-full h-full flex items-center justify-center bg-gray-200"
              >
                <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2 6a2 2 0 012-2h6l2 2h6a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM5 8a1 1 0 000 2v3a1 1 0 001 1h3a1 1 0 001-1V9a1 1 0 100-2H6a1 1 0 00-1 1z"/>
                </svg>
              </div>
              <div 
                v-else
                class="w-full h-full flex items-center justify-center bg-gray-200"
              >
                <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                </svg>
              </div>
            </div>
            <button 
              type="button"
              @click="removeMedia(index)"
              class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm hover:bg-red-600 opacity-0 group-hover:opacity-100 transition-opacity"
            >
              ×
            </button>
            <p class="text-xs text-gray-500 mt-1 truncate">{{ media.original_name }}</p>
          </div>
        </div>
      </div>

      <!-- Audience Selection -->
      <div v-if="form.visibility === 'groups' || form.visibility === 'specific'" class="mb-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Select Audience</h4>
        
        <!-- Groups Selection -->
        <div v-if="form.visibility === 'groups' || form.visibility === 'specific'" class="mb-3">
          <label class="text-sm text-gray-600 mb-1 block">Groups</label>
          <div class="max-h-32 overflow-y-auto border border-gray-300 rounded-md p-2">
            <label 
              v-for="group in userGroups" 
              :key="group.id"
              class="flex items-center space-x-2 py-1 hover:bg-gray-50 rounded px-2"
            >
              <input 
                type="checkbox" 
                :value="group.id"
                v-model="form.group_ids"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              >
              <span class="text-sm">{{ group.name }}</span>
            </label>
          </div>
        </div>

        <!-- Circles Selection -->
        <div v-if="form.visibility === 'specific'" class="mb-3">
          <label class="text-sm text-gray-600 mb-1 block">Circles</label>
          <div class="max-h-32 overflow-y-auto border border-gray-300 rounded-md p-2">
            <label 
              v-for="circle in userCircles" 
              :key="circle.id"
              class="flex items-center space-x-2 py-1 hover:bg-gray-50 rounded px-2"
            >
              <input 
                type="checkbox" 
                :value="circle.id"
                v-model="form.circle_ids"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              >
              <span class="text-sm">{{ circle.name }}</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Post Type Selection -->
      <div class="mb-4">
        <label class="text-sm text-gray-600 mb-1 block">Post Type</label>
        <select 
          v-model="form.post_type"
          class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="text">📝 Text Post</option>
          <option value="media">📸 Media Post</option>
          <option value="career_update">💼 Career Update</option>
          <option value="achievement">🏆 Achievement</option>
          <option value="event">📅 Event</option>
        </select>
      </div>

      <!-- Scheduling -->
      <div class="mb-4">
        <label class="flex items-center space-x-2">
          <input 
            type="checkbox" 
            v-model="isScheduled"
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
          >
          <span class="text-sm text-gray-700">Schedule this post</span>
        </label>
        
        <div v-if="isScheduled" class="mt-2">
          <input 
            type="datetime-local"
            v-model="form.scheduled_for"
            :min="minScheduleTime"
            class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
          >
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-between items-center pt-4 border-t border-gray-200">
        <div class="flex space-x-2">
          <button 
            type="button"
            @click="saveDraft"
            :disabled="isSubmitting"
            class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 disabled:opacity-50"
          >
            Save Draft
          </button>
          <button 
            type="button"
            @click="$refs.fileInput.click()"
            class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
          >
            📎 Add Media
          </button>
        </div>
        
        <div class="flex space-x-2">
          <button 
            type="button"
            @click="resetForm"
            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800"
          >
            Cancel
          </button>
          <button 
            type="submit"
            :disabled="isSubmitting || !canSubmit"
            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ isSubmitting ? 'Publishing...' : (isScheduled ? 'Schedule Post' : 'Post') }}
          </button>
        </div>
      </div>
    </form>

    <!-- Draft saved notification -->
    <div 
      v-if="draftSaved"
      class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg"
    >
      Draft saved!
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, nextTick } from 'vue'
import { usePage } from '@inertiajs/vue3'

export default {
  name: 'PostCreator',
  props: {
    userGroups: {
      type: Array,
      default: () => []
    },
    userCircles: {
      type: Array,
      default: () => []
    },
    editPost: {
      type: Object,
      default: null
    },
    draftData: {
      type: Object,
      default: null
    }
  },
  emits: ['post-created', 'post-updated', 'draft-saved'],
  setup(props, { emit }) {
    const { props: pageProps } = usePage()
    const user = computed(() => pageProps.auth.user)
    
    const editor = ref(null)
    const fileInput = ref(null)
    const isDragging = ref(false)
    const isSubmitting = ref(false)
    const isScheduled = ref(false)
    const draftSaved = ref(false)
    const uploadedMedia = ref([])
    
    const form = ref({
      content: '',
      post_type: 'text',
      visibility: 'public',
      circle_ids: [],
      group_ids: [],
      scheduled_for: '',
      metadata: {}
    })
    
    const errors = ref({})
    
    const contentLength = computed(() => form.value.content.length)
    const canSubmit = computed(() => {
      return (form.value.content.trim().length > 0 || uploadedMedia.value.length > 0) && !isSubmitting.value
    })
    
    const minScheduleTime = computed(() => {
      const now = new Date()
      now.setMinutes(now.getMinutes() + 5) // Minimum 5 minutes from now
      return now.toISOString().slice(0, 16)
    })
    
    onMounted(() => {
      if (props.editPost) {
        loadPostData(props.editPost)
      } else if (props.draftData) {
        loadDraftData(props.draftData)
      }
      
      // Auto-save draft every 30 seconds
      setInterval(() => {
        if (form.value.content.trim() || uploadedMedia.value.length) {
          saveDraft(true) // Silent save
        }
      }, 30000)
    })
    
    const updateContent = () => {
      form.value.content = editor.value.innerText || ''
    }
    
    const formatText = (command) => {
      document.execCommand(command, false, null)
      editor.value.focus()
    }
    
    const insertLink = () => {
      const url = prompt('Enter URL:')
      if (url) {
        document.execCommand('createLink', false, url)
        editor.value.focus()
      }
    }
    
    const handlePaste = (event) => {
      // Handle pasted images
      const items = event.clipboardData.items
      for (let item of items) {
        if (item.type.indexOf('image') !== -1) {
          const file = item.getAsFile()
          if (file) {
            uploadFiles([file])
          }
        }
      }
    }
    
    const handleDrop = (event) => {
      event.preventDefault()
      isDragging.value = false
      const files = Array.from(event.dataTransfer.files)
      uploadFiles(files)
    }
    
    const handleFileSelect = (event) => {
      const files = Array.from(event.target.files)
      uploadFiles(files)
    }
    
    const uploadFiles = async (files) => {
      if (!files.length) return
      
      const formData = new FormData()
      files.forEach(file => {
        formData.append('files[]', file)
      })
      
      try {
        const response = await fetch('/api/posts/media', {
          method: 'POST',
          body: formData,
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Authorization': `Bearer ${pageProps.auth.token}`
          }
        })
        
        const result = await response.json()
        
        if (result.success) {
          uploadedMedia.value.push(...result.data.media)
        } else {
          alert('Failed to upload media: ' + result.message)
        }
      } catch (error) {
        console.error('Upload error:', error)
        alert('Failed to upload media')
      }
    }
    
    const removeMedia = (index) => {
      uploadedMedia.value.splice(index, 1)
    }
    
    const submitPost = async () => {
      if (!canSubmit.value) return
      
      isSubmitting.value = true
      errors.value = {}
      
      try {
        const postData = {
          ...form.value,
          media_urls: uploadedMedia.value,
          scheduled_for: isScheduled.value ? form.value.scheduled_for : null
        }
        
        const url = props.editPost ? `/api/posts/${props.editPost.id}` : '/api/posts'
        const method = props.editPost ? 'PUT' : 'POST'
        
        const response = await fetch(url, {
          method,
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Authorization': `Bearer ${pageProps.auth.token}`
          },
          body: JSON.stringify(postData)
        })
        
        const result = await response.json()
        
        if (result.success) {
          if (props.editPost) {
            emit('post-updated', result.data.post)
          } else {
            emit('post-created', result.data)
          }
          resetForm()
        } else {
          if (result.errors) {
            errors.value = result.errors
          } else {
            alert('Failed to create post: ' + result.message)
          }
        }
      } catch (error) {
        console.error('Submit error:', error)
        alert('Failed to create post')
      } finally {
        isSubmitting.value = false
      }
    }
    
    const saveDraft = async (silent = false) => {
      if (!form.value.content.trim() && !uploadedMedia.value.length) return
      
      try {
        const draftData = {
          ...form.value,
          media_urls: uploadedMedia.value
        }
        
        const response = await fetch('/api/posts/drafts', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Authorization': `Bearer ${pageProps.auth.token}`
          },
          body: JSON.stringify(draftData)
        })
        
        const result = await response.json()
        
        if (result.success) {
          if (!silent) {
            draftSaved.value = true
            setTimeout(() => {
              draftSaved.value = false
            }, 3000)
          }
          emit('draft-saved', result.data)
        }
      } catch (error) {
        console.error('Draft save error:', error)
      }
    }
    
    const resetForm = () => {
      form.value = {
        content: '',
        post_type: 'text',
        visibility: 'public',
        circle_ids: [],
        group_ids: [],
        scheduled_for: '',
        metadata: {}
      }
      uploadedMedia.value = []
      isScheduled.value = false
      errors.value = {}
      
      if (editor.value) {
        editor.value.innerHTML = ''
      }
    }
    
    const loadPostData = (post) => {
      form.value = {
        content: post.content,
        post_type: post.post_type,
        visibility: post.visibility,
        circle_ids: post.circle_ids || [],
        group_ids: post.group_ids || [],
        scheduled_for: '',
        metadata: post.metadata || {}
      }
      uploadedMedia.value = post.media_urls || []
      
      nextTick(() => {
        if (editor.value) {
          editor.value.innerText = post.content
        }
      })
    }
    
    const loadDraftData = (draft) => {
      form.value = {
        content: draft.content,
        post_type: draft.post_type,
        visibility: draft.visibility,
        circle_ids: draft.circle_ids || [],
        group_ids: draft.group_ids || [],
        scheduled_for: draft.scheduled_for || '',
        metadata: draft.metadata || {}
      }
      uploadedMedia.value = draft.media_urls || []
      isScheduled.value = !!draft.scheduled_for
      
      nextTick(() => {
        if (editor.value) {
          editor.value.innerText = draft.content
        }
      })
    }
    
    return {
      user,
      editor,
      fileInput,
      isDragging,
      isSubmitting,
      isScheduled,
      draftSaved,
      uploadedMedia,
      form,
      errors,
      contentLength,
      canSubmit,
      minScheduleTime,
      updateContent,
      formatText,
      insertLink,
      handlePaste,
      handleDrop,
      handleFileSelect,
      removeMedia,
      submitPost,
      saveDraft,
      resetForm
    }
  }
}
</script>

<style scoped>
.post-creator [contenteditable]:empty:before {
  content: attr(placeholder);
  color: #9CA3AF;
  pointer-events: none;
}

.post-creator [contenteditable]:focus {
  outline: none;
}
</style>