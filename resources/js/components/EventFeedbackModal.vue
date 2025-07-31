<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Event Feedback</h2>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <form @submit.prevent="submitFeedback" class="space-y-6">
        <!-- Overall Rating -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Overall Rating *
          </label>
          <div class="flex space-x-2">
            <button
              v-for="rating in 5"
              :key="rating"
              type="button"
              @click="form.overall_rating = rating"
              class="p-1"
            >
              <svg
                class="w-8 h-8"
                :class="rating <= form.overall_rating ? 'text-yellow-400' : 'text-gray-300'"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Detailed Ratings -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Content Quality</label>
            <div class="flex space-x-1">
              <button
                v-for="rating in 5"
                :key="rating"
                type="button"
                @click="form.content_rating = rating"
                class="p-1"
              >
                <svg
                  class="w-6 h-6"
                  :class="rating <= (form.content_rating || 0) ? 'text-yellow-400' : 'text-gray-300'"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              </button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Organization</label>
            <div class="flex space-x-1">
              <button
                v-for="rating in 5"
                :key="rating"
                type="button"
                @click="form.organization_rating = rating"
                class="p-1"
              >
                <svg
                  class="w-6 h-6"
                  :class="rating <= (form.organization_rating || 0) ? 'text-yellow-400' : 'text-gray-300'"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              </button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Networking Opportunities</label>
            <div class="flex space-x-1">
              <button
                v-for="rating in 5"
                :key="rating"
                type="button"
                @click="form.networking_rating = rating"
                class="p-1"
              >
                <svg
                  class="w-6 h-6"
                  :class="rating <= (form.networking_rating || 0) ? 'text-yellow-400' : 'text-gray-300'"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              </button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Venue/Platform</label>
            <div class="flex space-x-1">
              <button
                v-for="rating in 5"
                :key="rating"
                type="button"
                @click="form.venue_rating = rating"
                class="p-1"
              >
                <svg
                  class="w-6 h-6"
                  :class="rating <= (form.venue_rating || 0) ? 'text-yellow-400' : 'text-gray-300'"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Written Feedback -->
        <div>
          <label for="feedback_text" class="block text-sm font-medium text-gray-700 mb-2">
            Additional Comments
          </label>
          <textarea
            id="feedback_text"
            v-model="form.feedback_text"
            rows="4"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Share your thoughts about the event..."
          ></textarea>
        </div>

        <!-- Recommendation Questions -->
        <div class="space-y-4">
          <div class="flex items-center">
            <input
              id="would_recommend"
              v-model="form.would_recommend"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            >
            <label for="would_recommend" class="ml-2 block text-sm text-gray-900">
              I would recommend this event to other alumni
            </label>
          </div>

          <div class="flex items-center">
            <input
              id="would_attend_again"
              v-model="form.would_attend_again"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            >
            <label for="would_attend_again" class="ml-2 block text-sm text-gray-900">
              I would attend similar events in the future
            </label>
          </div>

          <div class="flex items-center">
            <input
              id="is_anonymous"
              v-model="form.is_anonymous"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            >
            <label for="is_anonymous" class="ml-2 block text-sm text-gray-900">
              Submit feedback anonymously
            </label>
          </div>
        </div>

        <!-- Improvement Suggestions -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Suggestions for Improvement
          </label>
          <div class="space-y-2">
            <div v-for="(suggestion, index) in form.improvement_suggestions" :key="index" class="flex items-center space-x-2">
              <input
                v-model="form.improvement_suggestions[index]"
                type="text"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Enter a suggestion..."
              >
              <button
                type="button"
                @click="removeSuggestion(index)"
                class="text-red-600 hover:text-red-800"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
              </button>
            </div>
            <button
              type="button"
              @click="addSuggestion"
              class="text-blue-600 hover:text-blue-800 text-sm"
            >
              + Add suggestion
            </button>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="!form.overall_rating || loading"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ loading ? 'Submitting...' : 'Submit Feedback' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import axios from 'axios'

interface Props {
  show: boolean
  event: any
}

interface Emits {
  (e: 'close'): void
  (e: 'submitted'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const loading = ref(false)

const form = reactive({
  overall_rating: 0,
  content_rating: 0,
  organization_rating: 0,
  networking_rating: 0,
  venue_rating: 0,
  feedback_text: '',
  would_recommend: false,
  would_attend_again: false,
  is_anonymous: false,
  improvement_suggestions: ['']
})

const addSuggestion = () => {
  form.improvement_suggestions.push('')
}

const removeSuggestion = (index: number) => {
  form.improvement_suggestions.splice(index, 1)
}

const submitFeedback = async () => {
  if (!form.overall_rating) return

  loading.value = true

  try {
    // Filter out empty suggestions
    const suggestions = form.improvement_suggestions.filter(s => s.trim() !== '')
    
    await axios.post(`/api/events/${props.event.id}/feedback`, {
      ...form,
      improvement_suggestions: suggestions.length > 0 ? suggestions : null
    })

    emit('submitted')
    emit('close')
    
    // Reset form
    Object.assign(form, {
      overall_rating: 0,
      content_rating: 0,
      organization_rating: 0,
      networking_rating: 0,
      venue_rating: 0,
      feedback_text: '',
      would_recommend: false,
      would_attend_again: false,
      is_anonymous: false,
      improvement_suggestions: ['']
    })
  } catch (error) {
    console.error('Failed to submit feedback:', error)
    alert('Failed to submit feedback. Please try again.')
  } finally {
    loading.value = false
  }
}

// Reset form when modal is closed
watch(() => props.show, (newShow) => {
  if (!newShow) {
    Object.assign(form, {
      overall_rating: 0,
      content_rating: 0,
      organization_rating: 0,
      networking_rating: 0,
      venue_rating: 0,
      feedback_text: '',
      would_recommend: false,
      would_attend_again: false,
      is_anonymous: false,
      improvement_suggestions: ['']
    })
  }
})
</script>