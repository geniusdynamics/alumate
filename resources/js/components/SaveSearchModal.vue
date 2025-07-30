<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h2 class="modal-title">Save Search</h2>
        <button @click="$emit('close')" class="close-button">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form @submit.prevent="saveSearch" class="modal-body">
        <!-- Search Name -->
        <div class="form-group">
          <label for="searchName" class="form-label">Search Name</label>
          <input
            id="searchName"
            v-model="searchName"
            type="text"
            class="form-input"
            placeholder="Enter a name for this search"
            required
          />
          <p class="form-help">Give your search a descriptive name to easily find it later.</p>
        </div>

        <!-- Search Summary -->
        <div class="search-summary">
          <h3 class="summary-title">Search Details</h3>
          <div class="summary-content">
            <div v-if="query" class="summary-item">
              <strong>Query:</strong> "{{ query }}"
            </div>
            <div v-if="hasFilters" class="summary-item">
              <strong>Filters:</strong> {{ filterSummary }}
            </div>
          </div>
        </div>

        <!-- Alert Settings -->
        <div class="form-group">
          <div class="checkbox-group">
            <label class="checkbox-label">
              <input
                v-model="createAlert"
                type="checkbox"
                class="checkbox-input"
              />
              <span class="checkbox-text">Create email alert for new results</span>
            </label>
          </div>
          <p class="form-help">Get notified when new alumni match your search criteria.</p>
        </div>

        <!-- Alert Frequency -->
        <div v-if="createAlert" class="form-group">
          <label for="alertFrequency" class="form-label">Alert Frequency</label>
          <select
            id="alertFrequency"
            v-model="alertFrequency"
            class="form-select"
          >
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
          </select>
          <p class="form-help">How often would you like to receive email notifications?</p>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
          <button
            type="button"
            @click="$emit('close')"
            class="button secondary"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="!searchName.trim() || saving"
            class="button primary"
          >
            <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
            </svg>
            {{ saving ? 'Saving...' : 'Save Search' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

// Props
const props = defineProps({
  query: {
    type: String,
    default: ''
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['save', 'close'])

// Reactive data
const searchName = ref('')
const createAlert = ref(false)
const alertFrequency = ref('daily')
const saving = ref(false)

// Computed
const hasFilters = computed(() => {
  return Object.values(props.filters).some(value => {
    if (Array.isArray(value)) return value.length > 0
    if (typeof value === 'object' && value !== null) {
      return Object.values(value).some(v => v !== null && v !== '')
    }
    return value !== '' && value !== null
  })
})

const filterSummary = computed(() => {
  const summaryParts = []
  
  if (props.filters.graduation_year?.min || props.filters.graduation_year?.max) {
    const min = props.filters.graduation_year.min || 'Any'
    const max = props.filters.graduation_year.max || 'Any'
    summaryParts.push(`Graduated: ${min} - ${max}`)
  }
  
  if (props.filters.location) {
    summaryParts.push(`Location: ${props.filters.location}`)
  }
  
  if (props.filters.industry?.length > 0) {
    summaryParts.push(`Industry: ${props.filters.industry.join(', ')}`)
  }
  
  if (props.filters.company) {
    summaryParts.push(`Company: ${props.filters.company}`)
  }
  
  if (props.filters.school) {
    summaryParts.push(`School: ${props.filters.school}`)
  }
  
  if (props.filters.skills?.length > 0) {
    summaryParts.push(`Skills: ${props.filters.skills.join(', ')}`)
  }
  
  return summaryParts.join(' • ')
})

// Methods
const generateDefaultName = () => {
  const parts = []
  
  if (props.query) {
    parts.push(`"${props.query}"`)
  }
  
  if (props.filters.location) {
    parts.push(`in ${props.filters.location}`)
  }
  
  if (props.filters.industry?.length > 0) {
    const industry = props.filters.industry.length === 1 
      ? props.filters.industry[0] 
      : `${props.filters.industry.length} industries`
    parts.push(`in ${industry}`)
  }
  
  if (props.filters.graduation_year?.min || props.filters.graduation_year?.max) {
    if (props.filters.graduation_year.min === props.filters.graduation_year.max) {
      parts.push(`graduated ${props.filters.graduation_year.min}`)
    } else {
      const min = props.filters.graduation_year.min || 'before'
      const max = props.filters.graduation_year.max || 'after'
      parts.push(`graduated ${min}-${max}`)
    }
  }
  
  return parts.join(' ') || 'Alumni Search'
}

const saveSearch = async () => {
  if (!searchName.value.trim()) {
    return
  }

  saving.value = true

  try {
    const searchData = {
      name: searchName.value.trim(),
      create_alert: createAlert.value,
      alert_frequency: alertFrequency.value
    }

    emit('save', searchData)
  } catch (error) {
    console.error('Error saving search:', error)
  } finally {
    saving.value = false
  }
}

// Initialize with default name
onMounted(() => {
  searchName.value = generateDefaultName()
})
</script>

<style scoped>
.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}

.modal-content {
  @apply bg-white rounded-lg shadow-xl max-w-lg w-full mx-4;
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
  @apply p-6 space-y-6;
}

.form-group {
  @apply space-y-2;
}

.form-label {
  @apply block text-sm font-medium text-gray-700;
}

.form-input,
.form-select {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}

.form-help {
  @apply text-sm text-gray-600;
}

.search-summary {
  @apply p-4 bg-gray-50 rounded-lg;
}

.summary-title {
  @apply text-sm font-medium text-gray-900 mb-2;
}

.summary-content {
  @apply space-y-1;
}

.summary-item {
  @apply text-sm text-gray-700;
}

.checkbox-group {
  @apply flex items-start gap-3;
}

.checkbox-label {
  @apply flex items-start gap-2 cursor-pointer;
}

.checkbox-input {
  @apply mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500;
}

.checkbox-text {
  @apply text-sm text-gray-700;
}

.form-actions {
  @apply flex justify-end gap-3 pt-4 border-t border-gray-200;
}

.button {
  @apply flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
}

.button.primary {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.button.secondary {
  @apply border border-gray-300 text-gray-700 hover:bg-gray-50;
}
</style>