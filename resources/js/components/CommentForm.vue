<template>
  <div class="comment-form">
    <div class="flex space-x-3">
      <img
        :src="user.avatar_url || '/default-avatar.png'"
        :alt="user.name"
        class="w-8 h-8 rounded-full flex-shrink-0"
      >
      <div class="flex-1">
        <div class="relative">
          <textarea
            ref="textareaRef"
            v-model="content"
            @input="handleInput"
            @keydown="handleKeydown"
            :placeholder="placeholder"
            class="w-full p-3 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-300': error }"
            rows="2"
            :disabled="loading"
          ></textarea>
          
          <!-- Mention Suggestions -->
          <div
            v-if="showMentionSuggestions && mentionSuggestions.length > 0"
            class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-40 overflow-y-auto"
          >
            <div
              v-for="(user, index) in mentionSuggestions"
              :key="user.id"
              @click="selectMention(user)"
              :class="[
                'px-4 py-2 cursor-pointer flex items-center space-x-2',
                index === selectedSuggestionIndex ? 'bg-blue-50' : 'hover:bg-gray-50'
              ]"
            >
              <img
                :src="user.avatar_url || '/default-avatar.png'"
                :alt="user.name"
                class="w-6 h-6 rounded-full"
              >
              <div>
                <div class="font-medium text-sm">{{ user.name }}</div>
                <div class="text-xs text-gray-500">@{{ user.username }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="text-red-500 text-sm mt-1">
          {{ error }}
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center mt-2">
          <div class="text-xs text-gray-500">
            {{ content.length }}/{{ maxLength }}
          </div>
          <div class="flex space-x-2">
            <button
              v-if="parentId || content.trim()"
              @click="cancel"
              class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800"
              :disabled="loading"
            >
              Cancel
            </button>
            <button
              @click="submit"
              :disabled="!canSubmit || loading"
              class="px-4 py-1 bg-blue-500 text-white text-sm rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <i v-if="loading" class="fas fa-spinner fa-spin mr-1"></i>
              {{ parentId ? 'Reply' : 'Comment' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  postId: {
    type: Number,
    required: true
  },
  parentId: {
    type: Number,
    default: null
  },
  placeholder: {
    type: String,
    default: 'Write a comment...'
  },
  autoFocus: {
    type: Boolean,
    default: false
  },
  maxLength: {
    type: Number,
    default: 2000
  }
})

const emit = defineEmits(['submitted', 'cancelled'])

const page = usePage()
const user = computed(() => page.props.auth.user)

const textareaRef = ref(null)
const content = ref('')
const loading = ref(false)
const error = ref('')

// Mention system
const showMentionSuggestions = ref(false)
const mentionSuggestions = ref([])
const selectedSuggestionIndex = ref(0)
const mentionQuery = ref('')
const mentionStartPos = ref(0)
const loadingMentions = ref(false)

const canSubmit = computed(() => {
  return content.value.trim().length > 0 && 
         content.value.length <= props.maxLength && 
         !loading.value
})

onMounted(() => {
  if (props.autoFocus) {
    nextTick(() => {
      textareaRef.value?.focus()
    })
  }
})

const handleInput = async (event) => {
  const textarea = event.target
  const cursorPos = textarea.selectionStart
  const textBeforeCursor = content.value.substring(0, cursorPos)
  
  // Check for mention trigger
  const mentionMatch = textBeforeCursor.match(/@(\w*)$/)
  
  if (mentionMatch) {
    mentionQuery.value = mentionMatch[1]
    mentionStartPos.value = cursorPos - mentionMatch[0].length
    
    if (mentionQuery.value.length >= 1) {
      await searchMentions(mentionQuery.value)
    } else {
      showMentionSuggestions.value = false
    }
  } else {
    showMentionSuggestions.value = false
  }
  
  // Auto-resize textarea
  textarea.style.height = 'auto'
  textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px'
}

const handleKeydown = (event) => {
  if (showMentionSuggestions.value && mentionSuggestions.value.length > 0) {
    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault()
        selectedSuggestionIndex.value = Math.min(
          selectedSuggestionIndex.value + 1,
          mentionSuggestions.value.length - 1
        )
        break
      case 'ArrowUp':
        event.preventDefault()
        selectedSuggestionIndex.value = Math.max(selectedSuggestionIndex.value - 1, 0)
        break
      case 'Enter':
        if (!event.shiftKey) {
          event.preventDefault()
          selectMention(mentionSuggestions.value[selectedSuggestionIndex.value])
        }
        break
      case 'Escape':
        event.preventDefault()
        showMentionSuggestions.value = false
        break
    }
  } else if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    submit()
  }
}

const searchMentions = async (query) => {
  if (loadingMentions.value) return
  
  loadingMentions.value = true
  
  try {
    const response = await fetch(`/api/posts/mentions/search?query=${encodeURIComponent(query)}`)
    const data = await response.json()
    
    if (data.success) {
      mentionSuggestions.value = data.users
      selectedSuggestionIndex.value = 0
      showMentionSuggestions.value = data.users.length > 0
    }
  } catch (error) {
    console.error('Error searching mentions:', error)
  } finally {
    loadingMentions.value = false
  }
}

const selectMention = (user) => {
  const beforeMention = content.value.substring(0, mentionStartPos.value)
  const afterCursor = content.value.substring(textareaRef.value.selectionStart)
  
  content.value = beforeMention + `@${user.username} ` + afterCursor
  showMentionSuggestions.value = false
  
  // Set cursor position after the mention
  nextTick(() => {
    const newCursorPos = mentionStartPos.value + user.username.length + 2
    textareaRef.value.setSelectionRange(newCursorPos, newCursorPos)
    textareaRef.value.focus()
  })
}

const submit = async () => {
  if (!canSubmit.value) return
  
  loading.value = true
  error.value = ''
  
  try {
    const response = await fetch(`/api/posts/${props.postId}/comment`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        content: content.value.trim(),
        parent_id: props.parentId
      })
    })
    
    const data = await response.json()
    
    if (data.success) {
      emit('submitted', data.comment)
      content.value = ''
      textareaRef.value.style.height = 'auto'
    } else {
      error.value = data.message || 'Failed to post comment'
    }
  } catch (err) {
    error.value = 'Network error. Please try again.'
    console.error('Error submitting comment:', err)
  } finally {
    loading.value = false
  }
}

const cancel = () => {
  content.value = ''
  error.value = ''
  showMentionSuggestions.value = false
  textareaRef.value.style.height = 'auto'
  emit('cancelled')
}

// Expose methods for parent component
defineExpose({
  focus: () => textareaRef.value?.focus(),
  clear: () => {
    content.value = ''
    error.value = ''
  }
})
</script>

<style scoped>
textarea {
  min-height: 2.5rem;
  max-height: 7.5rem;
}

.mention-suggestion {
  @apply transition-colors duration-150;
}
</style>