<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Start New Conversation</h2>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="modal-body">
        <!-- Conversation Type Tabs -->
        <div class="conversation-type-tabs">
          <button
            v-for="type in conversationTypes"
            :key="type.key"
            @click="activeType = type.key"
            :class="[
              'tab-button',
              activeType === type.key ? 'tab-active' : 'tab-inactive'
            ]"
          >
            <component :is="type.icon" class="w-5 h-5" />
            {{ type.label }}
          </button>
        </div>

        <!-- Direct Message Form -->
        <div v-if="activeType === 'direct'" class="conversation-form">
          <div class="form-group">
            <label class="form-label">Send message to:</label>
            <div class="user-search">
              <input
                v-model="userSearchQuery"
                @input="searchUsers"
                type="text"
                placeholder="Search for alumni..."
                class="form-input"
              />
              <div v-if="searchingUsers" class="search-loading">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500"></div>
              </div>
            </div>

            <!-- User Search Results -->
            <div v-if="userSearchResults.length > 0" class="user-results">
              <div
                v-for="user in userSearchResults"
                :key="user.id"
                @click="selectUser(user)"
                :class="[
                  'user-result-item',
                  selectedUser?.id === user.id ? 'selected' : ''
                ]"
              >
                <img
                  :src="user.avatar_url || '/default-avatar.png'"
                  :alt="user.name"
                  class="w-10 h-10 rounded-full"
                />
                <div class="user-info">
                  <h4 class="user-name">{{ user.name }}</h4>
                  <p class="user-details">
                    {{ user.current_position }} {{ user.current_company ? `at ${user.current_company}` : '' }}
                  </p>
                  <p class="user-education">{{ user.education_summary }}</p>
                </div>
              </div>
            </div>

            <!-- Selected User -->
            <div v-if="selectedUser" class="selected-user">
              <div class="selected-user-item">
                <img
                  :src="selectedUser.avatar_url || '/default-avatar.png'"
                  :alt="selectedUser.name"
                  class="w-8 h-8 rounded-full"
                />
                <span class="selected-user-name">{{ selectedUser.name }}</span>
                <button
                  @click="selectedUser = null"
                  class="remove-user-button"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Initial message (optional):</label>
            <textarea
              v-model="initialMessage"
              placeholder="Type your message..."
              rows="3"
              class="form-textarea"
            ></textarea>
          </div>
        </div>

        <!-- Group Conversation Form -->
        <div v-else-if="activeType === 'group'" class="conversation-form">
          <div class="form-group">
            <label class="form-label">Group name:</label>
            <input
              v-model="groupTitle"
              type="text"
              placeholder="Enter group name..."
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label class="form-label">Description (optional):</label>
            <textarea
              v-model="groupDescription"
              placeholder="Describe the purpose of this group..."
              rows="2"
              class="form-textarea"
            ></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Add participants:</label>
            <div class="user-search">
              <input
                v-model="userSearchQuery"
                @input="searchUsers"
                type="text"
                placeholder="Search for alumni to add..."
                class="form-input"
              />
              <div v-if="searchingUsers" class="search-loading">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500"></div>
              </div>
            </div>

            <!-- User Search Results -->
            <div v-if="userSearchResults.length > 0" class="user-results">
              <div
                v-for="user in userSearchResults"
                :key="user.id"
                @click="toggleUserSelection(user)"
                :class="[
                  'user-result-item',
                  selectedUsers.some(u => u.id === user.id) ? 'selected' : ''
                ]"
              >
                <img
                  :src="user.avatar_url || '/default-avatar.png'"
                  :alt="user.name"
                  class="w-10 h-10 rounded-full"
                />
                <div class="user-info">
                  <h4 class="user-name">{{ user.name }}</h4>
                  <p class="user-details">
                    {{ user.current_position }} {{ user.current_company ? `at ${user.current_company}` : '' }}
                  </p>
                </div>
                <div class="user-selection-indicator">
                  <svg
                    v-if="selectedUsers.some(u => u.id === user.id)"
                    class="w-5 h-5 text-blue-500"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- Selected Users -->
            <div v-if="selectedUsers.length > 0" class="selected-users">
              <h4 class="selected-users-title">Selected participants ({{ selectedUsers.length }}):</h4>
              <div class="selected-users-list">
                <div
                  v-for="user in selectedUsers"
                  :key="user.id"
                  class="selected-user-item"
                >
                  <img
                    :src="user.avatar_url || '/default-avatar.png'"
                    :alt="user.name"
                    class="w-6 h-6 rounded-full"
                  />
                  <span class="selected-user-name">{{ user.name }}</span>
                  <button
                    @click="removeUserFromSelection(user)"
                    class="remove-user-button"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Circle Conversation Form -->
        <div v-else-if="activeType === 'circle'" class="conversation-form">
          <div class="form-group">
            <label class="form-label">Select circle:</label>
            <select v-model="selectedCircleId" class="form-select">
              <option value="">Choose a circle...</option>
              <option
                v-for="circle in userCircles"
                :key="circle.id"
                :value="circle.id"
              >
                {{ circle.name }} ({{ circle.member_count }} members)
              </option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Conversation title (optional):</label>
            <input
              v-model="circleTitle"
              type="text"
              placeholder="Enter conversation title..."
              class="form-input"
            />
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button
          @click="$emit('close')"
          class="btn btn-secondary"
        >
          Cancel
        </button>
        <button
          @click="createConversation"
          :disabled="!canCreate || creating"
          class="btn btn-primary"
        >
          <span v-if="creating">Creating...</span>
          <span v-else>Start Conversation</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useMessagingStore } from '@/stores/messaging'
import axios from 'axios'

// Icons (you can replace with your preferred icon library)
const UserIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>`
}

const UsersIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" /></svg>`
}

const CircleIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
}

const emit = defineEmits(['close', 'conversation-created'])

const messagingStore = useMessagingStore()

// State
const activeType = ref('direct')
const creating = ref(false)

// Direct message state
const selectedUser = ref(null)
const initialMessage = ref('')

// Group conversation state
const groupTitle = ref('')
const groupDescription = ref('')
const selectedUsers = ref([])

// Circle conversation state
const selectedCircleId = ref('')
const circleTitle = ref('')

// User search state
const userSearchQuery = ref('')
const userSearchResults = ref([])
const searchingUsers = ref(false)
const userCircles = ref([])

const conversationTypes = [
  { key: 'direct', label: 'Direct Message', icon: UserIcon },
  { key: 'group', label: 'Group Chat', icon: UsersIcon },
  { key: 'circle', label: 'Circle Chat', icon: CircleIcon }
]

const canCreate = computed(() => {
  if (creating.value) return false

  switch (activeType.value) {
    case 'direct':
      return selectedUser.value !== null
    case 'group':
      return groupTitle.value.trim().length > 0 && selectedUsers.value.length > 0
    case 'circle':
      return selectedCircleId.value !== ''
    default:
      return false
  }
})

// Methods
const searchUsers = async () => {
  if (userSearchQuery.value.trim().length < 2) {
    userSearchResults.value = []
    return
  }

  searchingUsers.value = true
  try {
    const response = await axios.get('/api/alumni/search', {
      params: { q: userSearchQuery.value, limit: 10 }
    })
    userSearchResults.value = response.data.data || []
  } catch (error) {
    console.error('Failed to search users:', error)
    userSearchResults.value = []
  } finally {
    searchingUsers.value = false
  }
}

const selectUser = (user) => {
  selectedUser.value = user
  userSearchQuery.value = ''
  userSearchResults.value = []
}

const toggleUserSelection = (user) => {
  const index = selectedUsers.value.findIndex(u => u.id === user.id)
  if (index === -1) {
    selectedUsers.value.push(user)
  } else {
    selectedUsers.value.splice(index, 1)
  }
}

const removeUserFromSelection = (user) => {
  const index = selectedUsers.value.findIndex(u => u.id === user.id)
  if (index !== -1) {
    selectedUsers.value.splice(index, 1)
  }
}

const loadUserCircles = async () => {
  try {
    const response = await axios.get('/api/user/circles')
    userCircles.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load user circles:', error)
    userCircles.value = []
  }
}

const createConversation = async () => {
  if (!canCreate.value) return

  creating.value = true
  try {
    let conversation

    switch (activeType.value) {
      case 'direct':
        conversation = await messagingStore.createDirectConversation(selectedUser.value.id)
        
        // Send initial message if provided
        if (initialMessage.value.trim()) {
          await messagingStore.sendMessage({
            conversation_id: conversation.id,
            content: initialMessage.value.trim()
          })
        }
        break

      case 'group':
        conversation = await messagingStore.createGroupConversation(
          selectedUsers.value.map(u => u.id),
          groupTitle.value.trim(),
          groupDescription.value.trim() || null
        )
        break

      case 'circle':
        conversation = await messagingStore.createCircleConversation(
          selectedCircleId.value,
          circleTitle.value.trim() || null
        )
        break
    }

    emit('conversation-created', conversation)
  } catch (error) {
    console.error('Failed to create conversation:', error)
    // You might want to show an error message to the user
  } finally {
    creating.value = false
  }
}

onMounted(() => {
  loadUserCircles()
})
</script>

<style scoped>
.modal-overlay {
  @apply fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4;
}

.modal-content {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden;
}

.modal-header {
  @apply flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700;
}

.modal-body {
  @apply p-6 overflow-y-auto max-h-96;
}

.modal-footer {
  @apply flex items-center justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-700;
}

.conversation-type-tabs {
  @apply flex space-x-1 mb-6 bg-gray-100 dark:bg-gray-700 rounded-lg p-1;
}

.tab-button {
  @apply flex items-center space-x-2 px-4 py-2 rounded-md text-sm font-medium transition-colors;
}

.tab-active {
  @apply bg-white dark:bg-gray-600 text-blue-600 dark:text-blue-400 shadow-sm;
}

.tab-inactive {
  @apply text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200;
}

.conversation-form {
  @apply space-y-4;
}

.form-group {
  @apply space-y-2;
}

.form-label {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-300;
}

.form-input {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white;
}

.form-textarea {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white;
}

.form-select {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white;
}

.user-search {
  @apply relative;
}

.search-loading {
  @apply absolute right-3 top-1/2 transform -translate-y-1/2;
}

.user-results {
  @apply mt-2 max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg;
}

.user-result-item {
  @apply flex items-center space-x-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-b-0;
}

.user-result-item.selected {
  @apply bg-blue-50 dark:bg-blue-900/20;
}

.user-info {
  @apply flex-1 min-w-0;
}

.user-name {
  @apply font-medium text-gray-900 dark:text-white;
}

.user-details {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.user-education {
  @apply text-xs text-gray-500 dark:text-gray-500;
}

.user-selection-indicator {
  @apply flex-shrink-0;
}

.selected-user {
  @apply mt-2;
}

.selected-users {
  @apply mt-4;
}

.selected-users-title {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300 mb-2;
}

.selected-users-list {
  @apply space-y-2;
}

.selected-user-item {
  @apply flex items-center space-x-2 px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg;
}

.selected-user-name {
  @apply flex-1 text-sm font-medium text-gray-900 dark:text-white;
}

.remove-user-button {
  @apply text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1;
}

.btn {
  @apply inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors;
}

.btn-primary {
  @apply text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply text-gray-700 bg-white border-gray-300 hover:bg-gray-50 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700;
}
</style>