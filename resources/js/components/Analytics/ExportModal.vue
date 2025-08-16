<template>
  <div class="modal-overlay" @click="closeModal">
    <div class="modal-container" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">Export Analytics Data</h3>
        <button @click="closeModal" class="close-button">
          <Icon name="x" class="w-5 h-5" />
        </button>
      </div>
      
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Data Type</label>
          <select v-model="exportConfig.data_type" class="form-select">
            <option value="engagement_metrics">Engagement Metrics</option>
            <option value="alumni_activity">Alumni Activity</option>
            <option value="community_health">Community Health</option>
            <option value="platform_usage">Platform Usage</option>
          </select>
        </div>
        
        <div class="form-group">
          <label class="form-label">Export Format</label>
          <div class="format-options">
            <label class="format-option">
              <input
                v-model="exportConfig.format"
                type="radio"
                value="csv"
                class="format-radio"
              />
              <div class="format-content">
                <Icon name="file-text" class="w-5 h-5" />
                <span>CSV</span>
              </div>
            </label>
            
            <label class="format-option">
              <input
                v-model="exportConfig.format"
                type="radio"
                value="json"
                class="format-radio"
              />
              <div class="format-content">
                <Icon name="code" class="w-5 h-5" />
                <span>JSON</span>
              </div>
            </label>
            
            <label class="format-option">
              <input
                v-model="exportConfig.format"
                type="radio"
                value="xlsx"
                class="format-radio"
              />
              <div class="format-content">
                <Icon name="file-spreadsheet" class="w-5 h-5" />
                <span>Excel</span>
              </div>
            </label>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button @click="closeModal" class="btn btn-secondary">
          Cancel
        </button>
        <button @click="handleExport" class="btn btn-primary" :disabled="exporting">
          <Icon v-if="exporting" name="loader" class="w-4 h-4 animate-spin" />
          {{ exporting ? 'Exporting...' : 'Export Data' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import Icon from '@/Components/Icon.vue'

const emit = defineEmits<{
  close: []
  export: [config: any]
}>()

const exporting = ref(false)

const exportConfig = reactive({
  data_type: 'engagement_metrics',
  format: 'csv',
})

const closeModal = () => {
  emit('close')
}

const handleExport = async () => {
  exporting.value = true
  try {
    await emit('export', exportConfig)
  } finally {
    exporting.value = false
  }
}
</script><style 
scoped>
.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}

.modal-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4;
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
  @apply p-6 space-y-4;
}

.form-group {
  @apply space-y-2;
}

.form-label {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-300;
}

.form-select {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.format-options {
  @apply grid grid-cols-3 gap-3;
}

.format-option {
  @apply relative cursor-pointer;
}

.format-radio {
  @apply sr-only;
}

.format-content {
  @apply flex flex-col items-center p-4 border-2 border-gray-200 dark:border-gray-600 rounded-lg;
  @apply hover:border-blue-300 dark:hover:border-blue-500 transition-colors;
}

.format-option input:checked + .format-content {
  @apply border-blue-500 bg-blue-50 dark:bg-blue-900/20;
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