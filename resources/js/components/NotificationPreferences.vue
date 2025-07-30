<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Notification Preferences</h2>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600"
        >
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-8">
        <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
        <p class="text-gray-500 mt-2">Loading preferences...</p>
      </div>

      <!-- Preferences Form -->
      <form v-else @submit.prevent="savePreferences">
        <!-- Global Settings -->
        <div class="mb-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Global Settings</h3>
          
          <div class="space-y-4">
            <!-- Email Notifications -->
            <div class="flex items-center justify-between">
              <div>
                <label class="text-sm font-medium text-gray-700">Email Notifications</label>
                <p class="text-xs text-gray-500">Receive notifications via email</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input
                  v-model="preferences.email_enabled"
                  type="checkbox"
                  class="sr-only peer"
                >
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>

            <!-- Push Notifications -->
            <div class="flex items-center justify-between">
              <div>
                <label class="text-sm font-medium text-gray-700">Push Notifications</label>
                <p class="text-xs text-gray-500">Receive browser push notifications</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input
                  v-model="preferences.push_enabled"
                  type="checkbox"
                  class="sr-only peer"
                >
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>

            <!-- Email Frequency -->
            <div v-if="preferences.email_enabled">
              <label class="block text-sm font-medium text-gray-700 mb-2">Email Frequency</label>
              <select
                v-model="preferences.email_frequency"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="immediate">Immediate</option>
                <option value="daily">Daily digest</option>
                <option value="weekly">Weekly digest</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Notification Types -->
        <div class="mb-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Notification Types</h3>
          
          <div class="space-y-4">
            <!-- Post Reactions -->
            <div class="border border-gray-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h4 class="text-sm font-medium text-gray-900">Post Reactions</h4>
                  <p class="text-xs text-gray-500">When someone reacts to your posts</p>
                </div>
                <i class="fas fa-thumbs-up text-blue-500"></i>
              </div>
              
              <div class="grid grid-cols-3 gap-4 text-xs">
                <label class="flex items-center">
                  <input
                    v-model="preferences.types.post_reaction.database"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  >
                  <span class="ml-2">In-app</span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="preferences.types.post_reaction.email"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    :disabled="!preferences.email_enabled"
                  >
                  <span class="ml-2">Email</span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="preferences.types.post_reaction.push"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    :disabled="!preferences.push_enabled"
                  >
                  <span class="ml-2">Push</span>
                </label>
              </div>
            </div>

            <!-- Post Comments -->
            <div class="border border-gray-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h4 class="text-sm font-medium text-gray-900">Post Comments</h4>
                  <p class="text-xs text-gray-500">When someone comments on your posts</p>
                </div>
                <i class="fas fa-comment text-green-500"></i>
              </div>
              
              <div class="grid grid-cols-3 gap-4 text-xs">
                <label class="flex items-center">
                  <input
                    v-model="preferences.types.post_comment.database"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  >
                  <span class="ml-2">In-app</span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="preferences.types.post_comment.email"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    :disabled="!preferences.email_enabled"
                  >
                  <span class="ml-2">Email</span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="preferences.types.post_comment.push"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    :disabled="!preferences.push_enabled"
                  >
                  <span class="ml-2">Push</span>
                </label>
              </div>
            </div>

            <!-- Mentions -->
            <div class="border border-gray-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h4 class="text-sm font-medium text-gray-900">Mentions</h4>
                  <p class="text-xs text-gray-500">When someone mentions you in posts or comments</p>
                </div>
                <i class="fas fa-at text-purple-500"></i>
              </div>
              
              <div class="grid grid-cols-3 gap-4 text-xs">
                <label class="flex items-center">
                  <input
                    v-model="preferences.types.post_mention.database"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  >
                  <span class="ml-2">In-app</span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="preferences.types.post_mention.email"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    :disabled="!preferences.email_enabled"
                  >
                  <span class="ml-2">Email</span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="preferences.types.post_mention.push"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    :disabled="!preferences.push_enabled"
                  >
                  <span class="ml-2">Push</span>
                </label>
              </div>
            </div>

            <!-- Connection Requests -->
            <div class="border border-gray-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h4 class="text-sm font-medium text-gray-900">Connection Requests</h4>
                  <p class="text-xs text-gray-500">When someone wants to connect with you</p>
                </div>
                <i class="fas fa-user-plus text-orange-500"></i>
              </div>
              
              <div class="grid grid-cols-3 gap-4 text-xs">
                <label class="flex items-center">
                  <input
                    v-model="preferences.types.connection_request.database"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  >
                  <span class="ml-2">In-app</span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="preferences.types.connection_request.email"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    :disabled="!preferences.email_enabled"
                  >
                  <span class="ml-2">Email</span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="preferences.types.connection_request.push"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    :disabled="!preferences.push_enabled"
                  >
                  <span class="ml-2">Push</span>
                </label>
              </div>
            </div>

            <!-- Connection Accepted -->
            <div class="border border-gray-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h4 class="text-sm font-medium text-gray-900">Connection Accepted</h4>
                  <p class="text-xs text-gray-500">When someone accepts your connection request</p>
                </div>
                <i class="fas fa-user-check text-green-500"></i>
              </div>
              
              <div class="grid grid-cols-3 gap-4 text-xs">
                <label class="flex items-center">
                  <input
                    v-model="preferences.types.connection_accepted.database"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  >
                  <span class="ml-2">In-app</span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="preferences.types.connection_accepted.email"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    :disabled="!preferences.email_enabled"
                  >
                  <span class="ml-2">Email</span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="preferences.types.connection_accepted.push"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    :disabled="!preferences.push_enabled"
                  >
                  <span class="ml-2">Push</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-gray-600 hover:text-gray-800"
            :disabled="saving"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50"
          >
            <i v-if="saving" class="fas fa-spinner fa-spin mr-2"></i>
            Save Preferences
          </button>
        </div>
      </form>

      <!-- Error Message -->
      <div v-if="error" class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
        {{ error }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const emit = defineEmits(['close', 'updated'])

const loading = ref(true)
const saving = ref(false)
const error = ref('')

const preferences = ref({
  email_enabled: true,
  push_enabled: true,
  email_frequency: 'immediate',
  types: {
    post_reaction: { email: true, push: true, database: true },
    post_comment: { email: true, push: true, database: true },
    post_mention: { email: true, push: true, database: true },
    connection_request: { email: true, push: true, database: true },
    connection_accepted: { email: true, push: true, database: true }
  }
})

onMounted(() => {
  loadPreferences()
})

const loadPreferences = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await fetch('/api/notifications/preferences')
    const data = await response.json()

    if (data.success) {
      preferences.value = { ...preferences.value, ...data.preferences }
    } else {
      error.value = 'Failed to load preferences'
    }
  } catch (err) {
    error.value = 'Network error while loading preferences'
    console.error('Error loading preferences:', err)
  } finally {
    loading.value = false
  }
}

const savePreferences = async () => {
  saving.value = true
  error.value = ''

  try {
    const response = await fetch('/api/notifications/preferences', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(preferences.value)
    })

    const data = await response.json()

    if (data.success) {
      emit('updated')
    } else {
      error.value = data.message || 'Failed to save preferences'
    }
  } catch (err) {
    error.value = 'Network error while saving preferences'
    console.error('Error saving preferences:', err)
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
/* Custom toggle switch styles are handled by Tailwind classes */
</style>