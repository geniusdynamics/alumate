<template>
  <div class="feature-availability" :class="availabilityClass">
    <div class="availability-indicator">
      <component :is="availabilityIcon" class="w-5 h-5" />
    </div>
    <span class="availability-text">{{ availabilityText }}</span>
    <div v-if="showTooltip" class="availability-tooltip">
      {{ tooltipText }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { 
  CheckIcon, 
  XMarkIcon, 
  MinusIcon,
  StarIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

type AvailabilityLevel = 'full' | 'enhanced' | 'basic' | 'limited' | 'none' | 'standard' | 'priority'

interface Props {
  level: AvailabilityLevel
  showTooltip?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showTooltip: false
})

// Computed properties
const availabilityConfig = computed(() => {
  const configs = {
    full: {
      text: 'Full Access',
      icon: CheckIcon,
      class: 'full-access',
      tooltip: 'Complete access to all features and functionality'
    },
    enhanced: {
      text: 'Enhanced',
      icon: StarIcon,
      class: 'enhanced-access',
      tooltip: 'Advanced features with additional institutional benefits'
    },
    basic: {
      text: 'Basic',
      icon: CheckIcon,
      class: 'basic-access',
      tooltip: 'Core functionality with essential features'
    },
    limited: {
      text: 'Limited',
      icon: ExclamationTriangleIcon,
      class: 'limited-access',
      tooltip: 'Restricted access with basic functionality only'
    },
    none: {
      text: 'Not Available',
      icon: XMarkIcon,
      class: 'no-access',
      tooltip: 'Feature not available for this plan'
    },
    standard: {
      text: 'Standard',
      icon: CheckIcon,
      class: 'standard-access',
      tooltip: 'Standard support during business hours'
    },
    priority: {
      text: 'Priority',
      icon: StarIcon,
      class: 'priority-access',
      tooltip: '24/7 priority support with dedicated account manager'
    }
  }
  
  return configs[props.level] || configs.none
})

const availabilityClass = computed(() => availabilityConfig.value.class)
const availabilityIcon = computed(() => availabilityConfig.value.icon)
const availabilityText = computed(() => availabilityConfig.value.text)
const tooltipText = computed(() => availabilityConfig.value.tooltip)
</script>

<style scoped>
.feature-availability {
  @apply flex items-center space-x-2 relative;
}

.availability-indicator {
  @apply flex-shrink-0;
}

.availability-text {
  @apply text-sm font-medium;
}

.availability-tooltip {
  @apply absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg whitespace-nowrap opacity-0 pointer-events-none transition-opacity z-10;
}

.feature-availability:hover .availability-tooltip {
  @apply opacity-100;
}

/* Full Access */
.full-access .availability-indicator {
  @apply text-green-600;
}

.full-access .availability-text {
  @apply text-green-700;
}

.full-access {
  @apply bg-green-50 px-3 py-1 rounded-full;
}

/* Enhanced Access */
.enhanced-access .availability-indicator {
  @apply text-blue-600;
}

.enhanced-access .availability-text {
  @apply text-blue-700;
}

.enhanced-access {
  @apply bg-blue-50 px-3 py-1 rounded-full;
}

/* Basic Access */
.basic-access .availability-indicator {
  @apply text-emerald-600;
}

.basic-access .availability-text {
  @apply text-emerald-700;
}

.basic-access {
  @apply bg-emerald-50 px-3 py-1 rounded-full;
}

/* Limited Access */
.limited-access .availability-indicator {
  @apply text-yellow-600;
}

.limited-access .availability-text {
  @apply text-yellow-700;
}

.limited-access {
  @apply bg-yellow-50 px-3 py-1 rounded-full;
}

/* No Access */
.no-access .availability-indicator {
  @apply text-red-600;
}

.no-access .availability-text {
  @apply text-red-700;
}

.no-access {
  @apply bg-red-50 px-3 py-1 rounded-full;
}

/* Standard Access */
.standard-access .availability-indicator {
  @apply text-gray-600;
}

.standard-access .availability-text {
  @apply text-gray-700;
}

.standard-access {
  @apply bg-gray-50 px-3 py-1 rounded-full;
}

/* Priority Access */
.priority-access .availability-indicator {
  @apply text-purple-600;
}

.priority-access .availability-text {
  @apply text-purple-700;
}

.priority-access {
  @apply bg-purple-50 px-3 py-1 rounded-full;
}

/* Tooltip arrow */
.availability-tooltip::after {
  content: '';
  @apply absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900;
}
</style>