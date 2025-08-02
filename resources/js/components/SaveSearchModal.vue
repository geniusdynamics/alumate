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
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}

.modal-content {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  max-width: 32rem;
  width: 100%;
  margin-left: 1rem;
  margin-right: 1rem;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-title {
  font-size: 1.25rem;
  line-height: 1.75rem;
  font-weight: 600;
  color: #111827;
}

.close-button {
  color: #9ca3af;
}

.close-button:hover {
  color: #4b5563;
}

.modal-body {
  padding: 1.5rem;
}

.modal-body > * + * {
  margin-top: 1.5rem;
}

.form-group > * + * {
  margin-top: 0.5rem;
}

.form-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.form-input,
.form-select {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  ring: 2px solid #3b82f6;
  border-color: transparent;
}

.form-help {
  font-size: 0.875rem;
  color: #4b5563;
}

.search-summary {
  padding: 1rem;
  background-color: #f9fafb;
  border-radius: 0.5rem;
}

.summary-title {
  font-size: 0.875rem;
  font-weight: 500;
  color: #111827;
  margin-bottom: 0.5rem;
}

.summary-content > * + * {
  margin-top: 0.25rem;
}

.summary-item {
  font-size: 0.875rem;
  color: #374151;
}

.checkbox-group {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}

.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  cursor: pointer;
}

.checkbox-input {
  margin-top: 0.125rem;
  border-radius: 0.25rem;
  border-color: #d1d5db;
  color: #2563eb;
}

.checkbox-input:focus {
  ring: 2px solid #3b82f6;
}

.checkbox-text {
  font-size: 0.875rem;
  color: #374151;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-weight: 500;
  transition: colors 150ms;
}

.button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.button.primary {
  background-color: #2563eb;
  color: white;
}

.button.primary:hover:not(:disabled) {
  background-color: #1d4ed8;
}

.button.secondary {
  border: 1px solid #d1d5db;
  color: #374151;
}

.button.secondary:hover:not(:disabled) {
  background-color: #f9fafb;
}
</style>