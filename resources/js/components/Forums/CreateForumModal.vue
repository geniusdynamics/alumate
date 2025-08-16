<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form @submit.prevent="createForum">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                  Create New Forum
                </h3>

                <div class="space-y-4">
                  <!-- Forum Name -->
                  <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                      Forum Name *
                    </label>
                    <input
                      id="name"
                      v-model="form.name"
                      type="text"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Enter forum name"
                    />
                  </div>

                  <!-- Description -->
                  <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">
                      Description
                    </label>
                    <textarea
                      id="description"
                      v-model="form.description"
                      rows="3"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Describe what this forum is about"
                    ></textarea>
                  </div>

                  <!-- Color -->
                  <div>
                    <label for="color" class="block text-sm font-medium text-gray-700">
                      Theme Color
                    </label>
                    <div class="mt-1 flex items-center space-x-3">
                      <input
                        id="color"
                        v-model="form.color"
                        type="color"
                        class="h-10 w-16 border border-gray-300 rounded-md"
                      />
                      <input
                        v-model="form.color"
                        type="text"
                        class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="#3B82F6"
                      />
                    </div>
                  </div>

                  <!-- Icon -->
                  <div>
                    <label for="icon" class="block text-sm font-medium text-gray-700">
                      Icon (emoji or text)
                    </label>
                    <input
                      id="icon"
                      v-model="form.icon"
                      type="text"
                      maxlength="2"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                      placeholder="💬"
                    />
                  </div>

                  <!-- Visibility -->
                  <div>
                    <label for="visibility" class="block text-sm font-medium text-gray-700">
                      Visibility
                    </label>
                    <select
                      id="visibility"
                      v-model="form.visibility"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    >
                      <option value="public">Public - Anyone can view and participate</option>
                      <option value="group_only">Group Only - Only group members can access</option>
                      <option value="private">Private - Restricted access</option>
                    </select>
                  </div>

                  <!-- Group Selection (if group_only) -->
                  <div v-if="form.visibility === 'group_only'">
                    <label for="group_id" class="block text-sm font-medium text-gray-700">
                      Associated Group
                    </label>
                    <select
                      id="group_id"
                      v-model="form.group_id"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    >
                      <option value="">Select a group</option>
                      <option v-for="group in availableGroups" :key="group.id" :value="group.id">
                        {{ group.name }}
                      </option>
                    </select>
                  </div>

                  <!-- Moderation Settings -->
                  <div class="space-y-3">
                    <h4 class="text-sm font-medium text-gray-700">Moderation Settings</h4>
                    
                    <div class="flex items-center">
                      <input
                        id="requires_approval"
                        v-model="form.requires_approval"
                        type="checkbox"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                      />
                      <label for="requires_approval" class="ml-2 block text-sm text-gray-700">
                        Require approval for new posts
                      </label>
                    </div>

                    <div class="flex items-center">
                      <input
                        id="allow_anonymous"
                        v-model="form.allow_anonymous"
                        type="checkbox"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                      />
                      <label for="allow_anonymous" class="ml-2 block text-sm text-gray-700">
                        Allow anonymous posts
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="submit"
              :disabled="loading || !form.name.trim()"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading" class="inline-flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Creating...
              </span>
              <span v-else>Create Forum</span>
            </button>
            <button
              type="button"
              @click="$emit('close')"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

// Emits
const emit = defineEmits(['close', 'created'])

// Reactive data
const loading = ref(false)
const availableGroups = ref([])
const form = ref({
  name: '',
  description: '',
  color: '#3B82F6',
  icon: '💬',
  visibility: 'public',
  group_id: '',
  requires_approval: false,
  allow_anonymous: false,
})

// Methods
const createForum = async () => {
  try {
    loading.value = true
    
    const response = await fetch('/api/forums', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify({
        ...form.value,
        group_id: form.value.group_id || null,
      }),
    })

    const data = await response.json()

    if (data.success) {
      emit('created', data.data)
    } else {
      alert('Error creating forum: ' + (data.message || 'Unknown error'))
    }
  } catch (error) {
    console.error('Error creating forum:', error)
    alert('Error creating forum. Please try again.')
  } finally {
    loading.value = false
  }
}

const loadAvailableGroups = async () => {
  try {
    const response = await fetch('/api/groups')
    const data = await response.json()
    
    if (data.success) {
      availableGroups.value = data.data
    }
  } catch (error) {
    console.error('Error loading groups:', error)
  }
}

// Lifecycle
onMounted(() => {
  loadAvailableGroups()
})
</script>