<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h2 class="modal-title">Saved Searches</h2>
        <button @click="$emit('close')" class="close-button">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="modal-body">
        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner">
            <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
          <p>Loading saved searches...</p>
        </div>

        <!-- No Saved Searches -->
        <div v-else-if="savedSearches.length === 0" class="empty-state">
          <div class="empty-icon">
            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
            </svg>
          </div>
          <h3 class="empty-title">No saved searches</h3>
          <p class="empty-message">
            Save your searches to quickly access them later and get notified of new results.
          </p>
        </div>

        <!-- Saved Searches List -->
        <div v-else class="searches-list">
          <div
            v-for="search in savedSearches"
            :key="search.id"
            class="search-item"
          >
            <div class="search-content">
              <div class="search-header">
                <h3 class="search-name">{{ search.name }}</h3>
                <div class="search-meta">
                  <span class="search-date">
                    Saved {{ formatDate(search.created_at) }}
                  </span>
                  <span class="search-results">
                    {{ search.result_count }} results
                  </span>
                </div>
              </div>

              <div class="search-details">
                <div v-if="search.query" class="search-query">
                  <strong>Query:</strong> "{{ search.query }}"
                </div>
                <div v-if="search.filter_description" class="search-filters">
                  <strong>Filters:</strong> {{ search.filter_description }}
                </div>
              </div>

              <!-- Search Alerts -->
              <div v-if="search.alerts && search.alerts.length > 0" class="search-alerts">
                <div
                  v-for="alert in search.alerts"
                  :key="alert.id"
                  class="alert-item"
                >
                  <div class="alert-info">
                    <span class="alert-frequency">{{ formatFrequency(alert.frequency) }} alerts</span>
                    <span
                      class="alert-status"
                      :class="{ active: alert.is_active, inactive: !alert.is_active }"
                    >
                      {{ alert.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </div>
                  <div class="alert-actions">
                    <button
                      @click="toggleAlert(alert)"
                      class="alert-toggle"
                      :class="{ active: alert.is_active }"
                    >
                      {{ alert.is_active ? 'Disable' : 'Enable' }}
                    </button>
                    <select
                      v-model="alert.frequency"
                      @change="updateAlertFrequency(alert)"
                      class="frequency-select"
                    >
                      <option value="daily">Daily</option>
                      <option value="weekly">Weekly</option>
                      <option value="monthly">Monthly</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="search-actions">
              <button
                @click="loadSearch(search)"
                class="action-button primary"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Load Search
              </button>
              <button
                @click="deleteSearch(search)"
                class="action-button danger"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

// Emits
const emit = defineEmits(['load', 'close'])

// Reactive data
const savedSearches = ref([])
const loading = ref(true)

// Methods
const fetchSavedSearches = async () => {
  loading.value = true
  
  try {
    const response = await fetch('/api/search/saved', {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}`
      }
    })

    const data = await response.json()

    if (data.success) {
      savedSearches.value = data.data
    } else {
      console.error('Failed to fetch saved searches:', data.message)
    }
  } catch (error) {
    console.error('Error fetching saved searches:', error)
  } finally {
    loading.value = false
  }
}

const loadSearch = (search) => {
  emit('load', search)
}

const deleteSearch = async (search) => {
  if (!confirm(`Are you sure you want to delete the search "${search.name}"?`)) {
    return
  }

  try {
    const response = await fetch(`/api/search/saved/${search.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}`
      }
    })

    const data = await response.json()

    if (data.success) {
      savedSearches.value = savedSearches.value.filter(s => s.id !== search.id)
    } else {
      console.error('Failed to delete search:', data.message)
    }
  } catch (error) {
    console.error('Error deleting search:', error)
  }
}

const toggleAlert = async (alert) => {
  try {
    const response = await fetch(`/api/search/alerts/${alert.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}`
      },
      body: JSON.stringify({
        is_active: !alert.is_active
      })
    })

    const data = await response.json()

    if (data.success) {
      alert.is_active = !alert.is_active
    } else {
      console.error('Failed to toggle alert:', data.message)
    }
  } catch (error) {
    console.error('Error toggling alert:', error)
  }
}

const updateAlertFrequency = async (alert) => {
  try {
    const response = await fetch(`/api/search/alerts/${alert.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}`
      },
      body: JSON.stringify({
        frequency: alert.frequency
      })
    })

    const data = await response.json()

    if (!data.success) {
      console.error('Failed to update alert frequency:', data.message)
    }
  } catch (error) {
    console.error('Error updating alert frequency:', error)
  }
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatFrequency = (frequency) => {
  const frequencyMap = {
    daily: 'Daily',
    weekly: 'Weekly',
    monthly: 'Monthly'
  }
  return frequencyMap[frequency] || frequency
}

// Initialize
onMounted(() => {
  fetchSavedSearches()
})
</script>

<style scoped>
.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}

.modal-content {
  @apply bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden;
}

.modal-header {
  @apply flex justify-between items-center p-6 border-b border-gray-200;
}

.modal-title {
  @apply text-xl font-semibold text-gray-900;
}

.close-button {
  @apply text-gray-400 hover:text-gray-600;
}

.modal-body {
  @apply p-6 overflow-y-auto max-h-[calc(90vh-120px)];
}

.loading-state {
  @apply flex flex-col items-center justify-center py-12;
}

.loading-spinner {
  @apply mb-4;
}

.empty-state {
  @apply text-center py-12;
}

.empty-icon {
  @apply mb-4;
}

.empty-title {
  @apply text-xl font-semibold text-gray-900 mb-2;
}

.empty-message {
  @apply text-gray-600;
}

.searches-list {
  @apply space-y-4;
}

.search-item {
  @apply flex gap-4 p-4 border border-gray-200 rounded-lg;
}

.search-content {
  @apply flex-1;
}

.search-header {
  @apply mb-3;
}

.search-name {
  @apply text-lg font-semibold text-gray-900 mb-1;
}

.search-meta {
  @apply flex gap-4 text-sm text-gray-600;
}

.search-details {
  @apply space-y-1 text-sm text-gray-700 mb-3;
}

.search-alerts {
  @apply space-y-2;
}

.alert-item {
  @apply flex justify-between items-center p-3 bg-gray-50 rounded-lg;
}

.alert-info {
  @apply flex gap-3;
}

.alert-status.active {
  @apply text-green-600;
}

.alert-status.inactive {
  @apply text-red-600;
}

.alert-actions {
  @apply flex gap-2;
}

.alert-toggle {
  @apply px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100;
}

.alert-toggle.active {
  @apply bg-red-50 border-red-300 text-red-700;
}

.frequency-select {
  @apply px-2 py-1 text-sm border border-gray-300 rounded;
}

.search-actions {
  @apply flex flex-col gap-2;
}

.action-button {
  @apply flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors;
}

.action-button.primary {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.action-button.danger {
  @apply bg-red-600 text-white hover:bg-red-700;
}
</style>