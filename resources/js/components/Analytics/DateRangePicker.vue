<template>
  <div class="date-range-picker">
    <div class="picker-container">
      <div class="date-input-group">
        <label class="date-label">From</label>
        <input
          v-model="localStartDate"
          type="date"
          class="date-input"
          @change="handleStartDateChange"
        />
      </div>
      
      <div class="date-separator">
        <Icon name="arrow-right" class="w-4 h-4 text-gray-400" />
      </div>
      
      <div class="date-input-group">
        <label class="date-label">To</label>
        <input
          v-model="localEndDate"
          type="date"
          class="date-input"
          @change="handleEndDateChange"
        />
      </div>
    </div>
    
    <div class="quick-select">
      <button
        v-for="preset in datePresets"
        :key="preset.key"
        @click="selectPreset(preset)"
        class="preset-button"
        :class="{ 'active': activePreset === preset.key }"
      >
        {{ preset.label }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Icon from '@/Components/Icon.vue'

interface Props {
  startDate?: string
  endDate?: string
}

interface Emits {
  (e: 'update:startDate', value: string): void
  (e: 'update:endDate', value: string): void
  (e: 'change', value: { startDate: string; endDate: string }): void
}

const props = withDefaults(defineProps<Props>(), {
  startDate: () => new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  endDate: () => new Date().toISOString().split('T')[0],
})

const emit = defineEmits<Emits>()

const localStartDate = ref(props.startDate)
const localEndDate = ref(props.endDate)
const activePreset = ref('30d')

const datePresets = [
  {
    key: '7d',
    label: 'Last 7 days',
    getDates: () => ({
      start: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000),
      end: new Date(),
    }),
  },
  {
    key: '30d',
    label: 'Last 30 days',
    getDates: () => ({
      start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000),
      end: new Date(),
    }),
  },
  {
    key: '90d',
    label: 'Last 90 days',
    getDates: () => ({
      start: new Date(Date.now() - 90 * 24 * 60 * 60 * 1000),
      end: new Date(),
    }),
  },
  {
    key: '1y',
    label: 'Last year',
    getDates: () => ({
      start: new Date(Date.now() - 365 * 24 * 60 * 60 * 1000),
      end: new Date(),
    }),
  },
  {
    key: 'ytd',
    label: 'Year to date',
    getDates: () => ({
      start: new Date(new Date().getFullYear(), 0, 1),
      end: new Date(),
    }),
  },
]

const formatDate = (date: Date): string => {
  return date.toISOString().split('T')[0]
}

const handleStartDateChange = () => {
  emit('update:startDate', localStartDate.value)
  emitChange()
  activePreset.value = ''
}

const handleEndDateChange = () => {
  emit('update:endDate', localEndDate.value)
  emitChange()
  activePreset.value = ''
}

const selectPreset = (preset: any) => {
  const dates = preset.getDates()
  localStartDate.value = formatDate(dates.start)
  localEndDate.value = formatDate(dates.end)
  activePreset.value = preset.key
  
  emit('update:startDate', localStartDate.value)
  emit('update:endDate', localEndDate.value)
  emitChange()
}

const emitChange = () => {
  emit('change', {
    startDate: localStartDate.value,
    endDate: localEndDate.value,
  })
}

// Watch for prop changes
watch(() => props.startDate, (newValue) => {
  localStartDate.value = newValue
})

watch(() => props.endDate, (newValue) => {
  localEndDate.value = newValue
})
</script>

<style scoped>
.date-range-picker {
  @apply bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg p-4;
  @apply shadow-sm space-y-4;
}

.picker-container {
  @apply flex items-center space-x-4;
}

.date-input-group {
  @apply flex flex-col space-y-1;
}

.date-label {
  @apply text-xs font-medium text-gray-600 dark:text-gray-400;
}

.date-input {
  @apply px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
  @apply text-sm;
}

.date-separator {
  @apply flex items-center mt-5;
}

.quick-select {
  @apply flex flex-wrap gap-2;
}

.preset-button {
  @apply px-3 py-1.5 text-xs font-medium rounded-md border;
  @apply border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300;
  @apply bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600;
  @apply focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
  @apply transition-colors;
}

.preset-button.active {
  @apply bg-blue-600 text-white border-blue-600;
  @apply hover:bg-blue-700;
}

@media (max-width: 640px) {
  .picker-container {
    @apply flex-col space-x-0 space-y-4;
  }
  
  .date-separator {
    @apply rotate-90 mt-0;
  }
  
  .quick-select {
    @apply grid grid-cols-2 gap-2;
  }
}
</style>