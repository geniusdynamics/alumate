<template>
  <div class="saved-searches">
    <div class="saved-searches-header">
      <h3 class="saved-searches-title">Saved Searches</h3>
      <button
        @click="showCreateModal = true"
        class="create-search-btn"
        v-if="!showCreateModal"
      >
        <PlusIcon class="w-4 h-4" />
        Save Current Search
      </button>
    </div>

    <!-- Create/Edit Search Modal -->
    <div v-if="showCreateModal" class="create-search-modal">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">
            {{ editingSearch ? 'Edit Saved Search' : 'Save Current Search' }}
          </h4>
          <button
            @click="closeModal"
            class="modal-close-btn"
            aria-label="Close modal"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
        
        <form @submit.prevent="saveSearch" class="modal-form">
          <div class="form-group">
            <label for="search-name" class="form-label">Search Name</label>
            <input
              id="search-name"
              v-model="searchForm.name"
              type="text"
              required
              class="form-input"
              placeholder="Enter a name for this search..."
            />
          </div>
          
          <div class="form-group">
            <label for="search-query" class="form-label">Search Query</label>
            <input
              id="search-query"
              v-model="searchForm.query"
              type="text"
              required
              class="form-input"
              placeholder="Enter search terms..."
            />
          </div>
          
          <div class="form-group">
            <label class="form-label">Notification Settings</label>
            <div class="notification-options">
              <label class="checkbox-item">
                <input
                  v-model="searchForm.email_alerts"
                  type="checkbox"
                  class="checkbox-input"
                />
                <span class="checkbox-label">Email alerts for new results</span>
              </label>
              
              <div v-if="searchForm.email_alerts" class="alert-frequency">
                <label for="alert-frequency" class="form-label">Alert Frequency</label>
                <select
                  id="alert-frequency"
                  v-model="searchForm.alert_frequency"
                  class="form-select"
                >
                  <option value="immediate">Immediate</option>
                  <option value="daily">Daily</option>
                  <option value="weekly">Weekly</option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="form-actions">
            <button
              type="button"
              @click="closeModal"
              class="cancel-btn"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="save-btn"
              :disabled="isSaving"
            >
              <LoadingSpinner v-if="isSaving" class="w-4 h-4" />
              {{ editingSearch ? 'Update Search' : 'Save Search' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Saved Searches List -->
    <div v-if="savedSearches.length > 0" class="saved-searches-list">
      <div
        v-for="search in savedSearches"
        :key="search.id"
        class="saved-search-item"
      >
        <div class="search-info">
          <div class="search-header">
            <h4 class="search-name">{{ search.name }}</h4>
            <div class="search-meta">
              <span class="search-date">
                Saved {{ formatDate(search.created_at) }}
              </span>
              <span v-if="search.email_alerts" class="alert-indicator">
                <BellIcon class="w-4 h-4" />
                {{ search.alert_frequency }}
              </span>
            </div>
          </div>
          
          <div class="search-query">
            <span class="query-text">{{ search.query }}</span>
            <div v-if="search.filters && hasActiveFilters(search.filters)" class="search-filters-summary">
              <span class="filters-label">Filters:</span>
              <span class="filters-text">{{ getFiltersSummary(search.filters) }}</span>
            </div>
          </div>
          
          <div class="search-stats">
            <span class="last-run">
              Last run: {{ search.last_run_at ? formatDate(search.last_run_at) : 'Never' }}
            </span>
            <span v-if="search.last_result_count !== null" class="result-count">
              {{ search.last_result_count }} results
            </span>
          </div>
        </div>
        
        <div class="search-actions">
          <button
            @click="runSearch(search)"
            class="action-btn run-btn"
            :disabled="isRunning === search.id"
            :title="'Run search: ' + search.name"
          >
            <PlayIcon v-if="isRunning !== search.id" class="w-4 h-4" />
            <LoadingSpinner v-else class="w-4 h-4" />
          </button>
          
          <button
            @click="editSearch(search)"
            class="action-btn edit-btn"
            :title="'Edit search: ' + search.name"
          >
            <PencilIcon class="w-4 h-4" />
          </button>
          
          <button
            @click="toggleAlerts(search)"
            class="action-btn alert-btn"
            :class="{ active: search.email_alerts }"
            :title="search.email_alerts ? 'Disable alerts' : 'Enable alerts'"
          >
            <BellIcon class="w-4 h-4" />
          </button>
          
          <button
            @click="deleteSearch(search)"
            class="action-btn delete-btn"
            :title="'Delete search: ' + search.name"
          >
            <TrashIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!isLoading" class="empty-state">
      <div class="empty-icon">
        <BookmarkIcon class="w-12 h-12 text-gray-400" />
      </div>
      <h3 class="empty-title">No saved searches</h3>
      <p class="empty-message">
        Save your searches to quickly access them later and get notified of new results.
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-state">
      <LoadingSpinner class="w-8 h-8" />
      <p>Loading saved searches...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useToast } from '@/composables/useToast'
import {
  PlusIcon,
  XMarkIcon,
  BellIcon,
  PlayIcon,
  PencilIcon,
  TrashIcon,
  BookmarkIcon
} from '@heroicons/vue/24/outline'
import LoadingSpinner from './LoadingSpinner.vue'

interface SavedSearch {
  id: number
  name: string
  query: string
  filters: Record<string, any>
  email_alerts: boolean
  alert_frequency: 'immediate' | 'daily' | 'weekly'
  created_at: string
  updated_at: string
  last_run_at: string | null
  last_result_count: number | null
}

interface SearchForm {
  name: string
  query: string
  filters: Record<string, any>
  email_alerts: boolean
  alert_frequency: 'immediate' | 'daily' | 'weekly'
}

const props = defineProps<{
  currentQuery?: string
  currentFilters?: Record<string, any>
}>()

const emit = defineEmits<{
  'search-selected': [search: SavedSearch]
  'search-run': [query: string, filters: Record<string, any>]
}>()

// Reactive state
const savedSearches = ref<SavedSearch[]>([])
const isLoading = ref(false)
const isSaving = ref(false)
const isRunning = ref<number | null>(null)
const showCreateModal = ref(false)
const editingSearch = ref<SavedSearch | null>(null)

const searchForm = reactive<SearchForm>({
  name: '',
  query: props.currentQuery || '',
  filters: props.currentFilters || {},
  email_alerts: false,
  alert_frequency: 'daily'
})

// Toast composable
const { showToast } = useToast()

// Computed properties
const hasActiveFilters = (filters: Record<string, any>): boolean => {
  return Object.values(filters).some(value => {
    if (Array.isArray(value)) return value.length > 0
    if (typeof value === 'object' && value !== null) {
      return Object.values(value).some(v => v !== '' && v !== null)
    }
    return value !== '' && value !== null
  })
}

const getFiltersSummary = (filters: Record<string, any>): string => {
  const summaryParts = []
  
  if (filters.location) summaryParts.push(`Location: ${filters.location}`)
  if (filters.graduation_year) summaryParts.push(`Year: ${filters.graduation_year}`)
  if (filters.industry?.length) summaryParts.push(`Industries: ${filters.industry.length}`)
  if (filters.skills?.length) summaryParts.push(`Skills: ${filters.skills.length}`)
  
  return summaryParts.join(', ')
}

// Methods
const loadSavedSearches = async () => {
  isLoading.value = true
  
  try {
    const response = await fetch('/api/saved-searches')
    if (response.ok) {
      const data = await response.json()
      savedSearches.value = data.searches || []
    } else {
      throw new Error('Failed to load saved searches')
    }
  } catch (error) {
    console.error('Failed to load saved searches:', error)
    showToast('Failed to load saved searches', 'error')
  } finally {
    isLoading.value = false
  }
}

const saveSearch = async () => {
  isSaving.value = true
  
  try {
    const url = editingSearch.value 
      ? `/api/saved-searches/${editingSearch.value.id}`
      : '/api/saved-searches'
    
    const method = editingSearch.value ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(searchForm)
    })

    if (response.ok) {
      const data = await response.json()
      
      if (editingSearch.value) {
        const index = savedSearches.value.findIndex(s => s.id === editingSearch.value!.id)
        if (index > -1) {
          savedSearches.value[index] = data.search
        }
        showToast('Search updated successfully!', 'success')
      } else {
        savedSearches.value.unshift(data.search)
        showToast('Search saved successfully!', 'success')
      }
      
      closeModal()
    } else {
      throw new Error('Failed to save search')
    }
  } catch (error) {
    console.error('Failed to save search:', error)
    showToast('Failed to save search. Please try again.', 'error')
  } finally {
    isSaving.value = false
  }
}

const runSearch = async (search: SavedSearch) => {
  isRunning.value = search.id
  
  try {
    // Update last run time
    const response = await fetch(`/api/saved-searches/${search.id}/run`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })

    if (response.ok) {
      const data = await response.json()
      
      // Update the search in our list
      const index = savedSearches.value.findIndex(s => s.id === search.id)
      if (index > -1) {
        savedSearches.value[index] = { ...search, ...data.search }
      }
      
      // Emit the search event
      emit('search-run', search.query, search.filters)
      emit('search-selected', search)
    } else {
      throw new Error('Failed to run search')
    }
  } catch (error) {
    console.error('Failed to run search:', error)
    showToast('Failed to run search. Please try again.', 'error')
  } finally {
    isRunning.value = null
  }
}

const editSearch = (search: SavedSearch) => {
  editingSearch.value = search
  Object.assign(searchForm, {
    name: search.name,
    query: search.query,
    filters: search.filters,
    email_alerts: search.email_alerts,
    alert_frequency: search.alert_frequency
  })
  showCreateModal.value = true
}

const deleteSearch = async (search: SavedSearch) => {
  if (!confirm(`Are you sure you want to delete "${search.name}"?`)) {
    return
  }
  
  try {
    const response = await fetch(`/api/saved-searches/${search.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })

    if (response.ok) {
      savedSearches.value = savedSearches.value.filter(s => s.id !== search.id)
      showToast('Search deleted successfully!', 'success')
    } else {
      throw new Error('Failed to delete search')
    }
  } catch (error) {
    console.error('Failed to delete search:', error)
    showToast('Failed to delete search. Please try again.', 'error')
  }
}

const toggleAlerts = async (search: SavedSearch) => {
  try {
    const response = await fetch(`/api/saved-searches/${search.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        ...search,
        email_alerts: !search.email_alerts
      })
    })

    if (response.ok) {
      const data = await response.json()
      const index = savedSearches.value.findIndex(s => s.id === search.id)
      if (index > -1) {
        savedSearches.value[index] = data.search
      }
      
      const action = data.search.email_alerts ? 'enabled' : 'disabled'
      showToast(`Alerts ${action} for "${search.name}"`, 'success')
    } else {
      throw new Error('Failed to toggle alerts')
    }
  } catch (error) {
    console.error('Failed to toggle alerts:', error)
    showToast('Failed to update alert settings. Please try again.', 'error')
  }
}

const closeModal = () => {
  showCreateModal.value = false
  editingSearch.value = null
  Object.assign(searchForm, {
    name: '',
    query: props.currentQuery || '',
    filters: props.currentFilters || {},
    email_alerts: false,
    alert_frequency: 'daily'
  })
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60)
  
  if (diffInHours < 24) {
    return `${Math.floor(diffInHours)} hours ago`
  } else if (diffInHours < 24 * 7) {
    return `${Math.floor(diffInHours / 24)} days ago`
  } else {
    return date.toLocaleDateString()
  }
}

// Lifecycle
onMounted(() => {
  loadSavedSearches()
})
</script>

<style scoped>
.saved-searches {
  @apply space-y-6;
}

.saved-searches-header {
  @apply flex items-center justify-between;
}

.saved-searches-title {
  @apply text-lg font-semibold text-gray-900;
}

.create-search-btn {
  @apply flex items-center space-x-2 px-4 py-2;
  @apply bg-blue-600 text-white rounded-md hover:bg-blue-700;
  @apply transition-colors;
}

.create-search-modal {
  @apply fixed inset-0 z-50 flex items-center justify-center;
  @apply bg-black bg-opacity-50;
}

.modal-content {
  @apply bg-white rounded-lg shadow-xl max-w-md w-full mx-4;
  @apply max-h-screen overflow-y-auto;
}

.modal-header {
  @apply flex items-center justify-between p-6 border-b border-gray-200;
}

.modal-title {
  @apply text-lg font-semibold text-gray-900;
}

.modal-close-btn {
  @apply text-gray-400 hover:text-gray-600;
}

.modal-form {
  @apply p-6 space-y-4;
}

.form-group {
  @apply space-y-2;
}

.form-label {
  @apply block text-sm font-medium text-gray-700;
}

.form-input {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}

.form-select {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}

.notification-options {
  @apply space-y-3;
}

.checkbox-item {
  @apply flex items-center space-x-2;
}

.checkbox-input {
  @apply rounded border-gray-300 text-blue-600;
  @apply focus:ring-blue-500 focus:ring-2;
}

.checkbox-label {
  @apply text-sm text-gray-700;
}

.alert-frequency {
  @apply ml-6 space-y-2;
}

.form-actions {
  @apply flex items-center justify-end space-x-3 pt-4;
}

.cancel-btn {
  @apply px-4 py-2 text-gray-700 border border-gray-300 rounded-md;
  @apply hover:bg-gray-50 transition-colors;
}

.save-btn {
  @apply flex items-center space-x-2 px-4 py-2;
  @apply bg-blue-600 text-white rounded-md hover:bg-blue-700;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.saved-searches-list {
  @apply space-y-4;
}

.saved-search-item {
  @apply bg-white border border-gray-200 rounded-lg p-4;
  @apply flex items-start justify-between;
  @apply hover:shadow-md transition-shadow;
}

.search-info {
  @apply flex-1 space-y-2;
}

.search-header {
  @apply flex items-start justify-between;
}

.search-name {
  @apply font-medium text-gray-900;
}

.search-meta {
  @apply flex items-center space-x-2 text-sm text-gray-500;
}

.alert-indicator {
  @apply flex items-center space-x-1 text-blue-600;
}

.search-query {
  @apply space-y-1;
}

.query-text {
  @apply text-sm text-gray-700 font-mono bg-gray-100 px-2 py-1 rounded;
}

.search-filters-summary {
  @apply text-xs text-gray-500;
}

.filters-label {
  @apply font-medium;
}

.search-stats {
  @apply flex items-center space-x-4 text-xs text-gray-500;
}

.search-actions {
  @apply flex items-center space-x-2 ml-4;
}

.action-btn {
  @apply p-2 text-gray-400 hover:text-gray-600 rounded;
  @apply transition-colors;
}

.run-btn:hover {
  @apply text-green-600;
}

.edit-btn:hover {
  @apply text-blue-600;
}

.alert-btn:hover {
  @apply text-yellow-600;
}

.alert-btn.active {
  @apply text-yellow-600;
}

.delete-btn:hover {
  @apply text-red-600;
}

.empty-state {
  @apply text-center py-12;
}

.empty-icon {
  @apply flex justify-center mb-4;
}

.empty-title {
  @apply text-lg font-medium text-gray-900 mb-2;
}

.empty-message {
  @apply text-gray-600;
}

.loading-state {
  @apply flex flex-col items-center justify-center py-12;
  @apply text-gray-600;
}
</style>