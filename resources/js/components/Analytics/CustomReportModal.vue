<template>
  <div class="modal-overlay" @click="closeModal">
    <div class="modal-container" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">Generate Custom Report</h3>
        <button @click="closeModal" class="close-button">
          <Icon name="x" class="w-5 h-5" />
        </button>
      </div>
      
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Report Name</label>
          <input
            v-model="reportConfig.name"
            type="text"
            class="form-input"
            placeholder="Enter report name"
          />
        </div>
        
        <div class="form-group">
          <label class="form-label">Select Metrics</label>
          <div class="metrics-grid">
            <label
              v-for="metric in availableMetrics"
              :key="metric.key"
              class="metric-option"
            >
              <input
                v-model="reportConfig.metrics"
                type="checkbox"
                :value="metric.key"
                class="metric-checkbox"
              />
              <div class="metric-content">
                <span class="metric-name">{{ metric.label }}</span>
                <span class="metric-description">{{ metric.description }}</span>
              </div>
            </label>
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Date Range</label>
          <div class="date-range">
            <input
              v-model="reportConfig.start_date"
              type="date"
              class="form-input"
            />
            <span class="date-separator">to</span>
            <input
              v-model="reportConfig.end_date"
              type="date"
              class="form-input"
            />
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Filters (Optional)</label>
          <div class="filters-grid">
            <select v-model="reportConfig.graduation_year" class="form-select">
              <option value="">All Years</option>
              <option v-for="year in graduationYears" :key="year" :value="year">
                {{ year }}
              </option>
            </select>
            
            <input
              v-model="reportConfig.location"
              type="text"
              class="form-input"
              placeholder="Location filter"
            />
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button @click="closeModal" class="btn btn-secondary">
          Cancel
        </button>
        <button
          @click="generateReport"
          class="btn btn-primary"
          :disabled="!canGenerate || generating"
        >
          <Icon v-if="generating" name="loader" class="w-4 h-4 animate-spin" />
          {{ generating ? 'Generating...' : 'Generate Report' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed } from 'vue'
import Icon from '@/Components/Icon.vue'

const emit = defineEmits<{
  close: []
  generate: [config: any]
}>()

const generating = ref(false)

const reportConfig = reactive({
  name: '',
  metrics: [] as string[],
  start_date: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
  graduation_year: '',
  location: '',
})

const availableMetrics = [
  {
    key: 'engagement_rate',
    label: 'Engagement Rate',
    description: 'Overall platform engagement percentage'
  },
  {
    key: 'active_users',
    label: 'Active Users',
    description: 'Number of users active in the period'
  },
  {
    key: 'new_users',
    label: 'New Users',
    description: 'New user registrations'
  },
  {
    key: 'posts_created',
    label: 'Posts Created',
    description: 'Total posts created by users'
  },
  {
    key: 'connections_made',
    label: 'Connections Made',
    description: 'New connections between alumni'
  },
  {
    key: 'events_attended',
    label: 'Events Attended',
    description: 'Event attendance statistics'
  },
]

const graduationYears = computed(() => {
  const currentYear = new Date().getFullYear()
  const years = []
  for (let year = currentYear; year >= currentYear - 50; year--) {
    years.push(year)
  }
  return years
})

const canGenerate = computed(() => {
  return reportConfig.name.trim() && reportConfig.metrics.length > 0
})

const closeModal = () => {
  emit('close')
}

const generateReport = async () => {
  if (!canGenerate.value) return
  
  generating.value = true
  try {
    await emit('generate', { ...reportConfig })
  } finally {
    generating.value = false
  }
}
</script>

<style scoped>
.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}

.modal-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto;
}

.modal-header {
  @apply flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700;
}

.modal-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.close-button {
  @apply text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors;
}

.modal-body {
  @apply p-6 space-y-6;
}

.form-group {
  @apply space-y-2;
}

.form-label {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-300;
}

.form-input {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.form-select {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.metrics-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-3;
}

.metric-option {
  @apply relative cursor-pointer;
}

.metric-checkbox {
  @apply sr-only;
}

.metric-content {
  @apply flex flex-col p-3 border-2 border-gray-200 dark:border-gray-600 rounded-lg;
  @apply hover:border-blue-300 dark:hover:border-blue-500 transition-colors;
}

.metric-option input:checked + .metric-content {
  @apply border-blue-500 bg-blue-50 dark:bg-blue-900/20;
}

.metric-name {
  @apply font-medium text-gray-900 dark:text-white;
}

.metric-description {
  @apply text-sm text-gray-600 dark:text-gray-400 mt-1;
}

.date-range {
  @apply flex items-center space-x-3;
}

.date-separator {
  @apply text-gray-500 dark:text-gray-400;
}

.filters-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-3;
}

.modal-footer {
  @apply flex items-center justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-700;
}

.btn {
  @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md;
  @apply focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors;
}

.btn-primary {
  @apply text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply text-gray-700 bg-white border-gray-300 hover:bg-gray-50 focus:ring-blue-500;
  @apply dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600;
}
</style>