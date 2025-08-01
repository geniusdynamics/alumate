<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold text-gray-900">Create Fundraising Campaign</h2>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <form @submit.prevent="createCampaign" class="space-y-6">
          <!-- Basic Information -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Campaign Title *
            </label>
            <input
              v-model="form.title"
              type="text"
              required
              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter campaign title"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Description *
            </label>
            <textarea
              v-model="form.description"
              required
              rows="3"
              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Brief description of your campaign"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Campaign Story
            </label>
            <textarea
              v-model="form.story"
              rows="4"
              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Tell the full story of your campaign"
            ></textarea>
          </div>

          <!-- Campaign Details -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Goal Amount ($) *
              </label>
              <input
                v-model.number="form.goal_amount"
                type="number"
                min="1"
                step="0.01"
                required
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="10000"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Campaign Type *
              </label>
              <select
                v-model="form.type"
                required
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Select type</option>
                <option value="general">General</option>
                <option value="scholarship">Scholarship</option>
                <option value="emergency">Emergency</option>
                <option value="project">Project</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Start Date *
              </label>
              <input
                v-model="form.start_date"
                type="date"
                required
                :min="today"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                End Date *
              </label>
              <input
                v-model="form.end_date"
                type="date"
                required
                :min="form.start_date || today"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <!-- Campaign Settings -->
          <div class="space-y-4">
            <h3 class="text-lg font-medium text-gray-900">Campaign Settings</h3>
            
            <div class="space-y-3">
              <label class="flex items-center">
                <input
                  v-model="form.allow_peer_fundraising"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="ml-2 text-sm text-gray-700">
                  Allow peer-to-peer fundraising
                </span>
              </label>

              <label class="flex items-center">
                <input
                  v-model="form.show_donor_names"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="ml-2 text-sm text-gray-700">
                  Show donor names publicly
                </span>
              </label>

              <label class="flex items-center">
                <input
                  v-model="form.allow_anonymous_donations"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="ml-2 text-sm text-gray-700">
                  Allow anonymous donations
                </span>
              </label>
            </div>
          </div>

          <!-- Thank You Message -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Thank You Message
            </label>
            <textarea
              v-model="form.thank_you_message"
              rows="3"
              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Message to show donors after they contribute"
            ></textarea>
          </div>

          <!-- Media URLs -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Campaign Images
            </label>
            <div class="space-y-2">
              <input
                v-for="(url, index) in form.media_urls"
                :key="index"
                v-model="form.media_urls[index]"
                type="url"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="https://example.com/image.jpg"
              />
              <button
                type="button"
                @click="addMediaUrl"
                class="text-blue-600 hover:text-blue-700 text-sm font-medium"
              >
                + Add Image URL
              </button>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end space-x-3 pt-6 border-t">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md font-medium"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium disabled:opacity-50"
            >
              {{ loading ? 'Creating...' : 'Create Campaign' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'

defineEmits<{
  close: []
  created: []
}>()

const loading = ref(false)

const form = reactive({
  title: '',
  description: '',
  story: '',
  goal_amount: null as number | null,
  type: '',
  start_date: '',
  end_date: '',
  allow_peer_fundraising: true,
  show_donor_names: true,
  allow_anonymous_donations: true,
  thank_you_message: '',
  media_urls: [''] as string[],
})

const today = computed(() => {
  return new Date().toISOString().split('T')[0]
})

function addMediaUrl() {
  form.media_urls.push('')
}

async function createCampaign() {
  loading.value = true
  
  try {
    const payload = {
      ...form,
      media_urls: form.media_urls.filter(url => url.trim() !== ''),
    }
    
    const response = await fetch('/api/fundraising-campaigns', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(payload),
    })
    
    if (response.ok) {
      emit('created')
    } else {
      const error = await response.json()
      console.error('Failed to create campaign:', error)
      alert('Failed to create campaign. Please try again.')
    }
  } catch (error) {
    console.error('Failed to create campaign:', error)
    alert('Failed to create campaign. Please try again.')
  } finally {
    loading.value = false
  }
}
</script>