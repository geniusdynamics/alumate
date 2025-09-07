<!-- ABOUTME: Alert icon component for displaying visual alert indicators with severity-based styling -->
<!-- ABOUTME: Provides consistent alert iconography across the monitoring dashboard with animation support -->
<template>
  <div 
    :class="[
      'alert-icon-container',
      `severity-${severity}`,
      `size-${size}`,
      {
        'animated': animated,
        'pulsing': pulsing,
        'clickable': clickable
      }
    ]"
    @click="handleClick"
    :title="tooltip"
  >
    <!-- Critical Alert Icon -->
    <svg 
      v-if="severity === 'critical'"
      class="alert-icon"
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
      />
    </svg>

    <!-- High Alert Icon -->
    <svg 
      v-else-if="severity === 'high'"
      class="alert-icon"
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
      />
    </svg>

    <!-- Medium Alert Icon -->
    <svg 
      v-else-if="severity === 'medium'"
      class="alert-icon"
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
      />
    </svg>

    <!-- Low Alert Icon -->
    <svg 
      v-else-if="severity === 'low'"
      class="alert-icon"
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
      />
    </svg>

    <!-- Success/Resolved Icon -->
    <svg 
      v-else-if="severity === 'success' || severity === 'resolved'"
      class="alert-icon"
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
      />
    </svg>

    <!-- Warning Icon -->
    <svg 
      v-else-if="severity === 'warning'"
      class="alert-icon"
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
      />
    </svg>

    <!-- Default/Info Icon -->
    <svg 
      v-else
      class="alert-icon"
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
      />
    </svg>

    <!-- Alert Count Badge -->
    <div v-if="count && count > 0" class="alert-count">
      {{ count > 99 ? '99+' : count }}
    </div>

    <!-- Status Indicator Dot -->
    <div v-if="showStatusDot" :class="['status-dot', statusDotColor]"></div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  severity?: 'critical' | 'high' | 'medium' | 'low' | 'warning' | 'success' | 'resolved' | 'info'
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  animated?: boolean
  pulsing?: boolean
  clickable?: boolean
  count?: number
  tooltip?: string
  showStatusDot?: boolean
  statusDotColor?: 'red' | 'yellow' | 'green' | 'blue' | 'gray'
}

interface Emits {
  'click': []
}

const props = withDefaults(defineProps<Props>(), {
  severity: 'info',
  size: 'md',
  animated: false,
  pulsing: false,
  clickable: false,
  count: 0,
  showStatusDot: false,
  statusDotColor: 'red'
})

const emit = defineEmits<Emits>()

// Computed
const tooltip = computed(() => {
  if (props.tooltip) return props.tooltip
  
  const severityLabels = {
    critical: 'Critical Alert',
    high: 'High Priority Alert',
    medium: 'Medium Priority Alert',
    low: 'Low Priority Alert',
    warning: 'Warning',
    success: 'Success',
    resolved: 'Resolved',
    info: 'Information'
  }
  
  return severityLabels[props.severity] || 'Alert'
})

// Methods
const handleClick = () => {
  if (props.clickable) {
    emit('click')
  }
}
</script>

<style scoped>
.alert-icon-container {
  @apply relative inline-flex items-center justify-center;
  @apply transition-all duration-200 ease-in-out;
}

.alert-icon {
  @apply transition-all duration-200 ease-in-out;
}

/* Size Variants */
.size-xs {
  @apply w-4 h-4;
}

.size-xs .alert-icon {
  @apply w-4 h-4;
}

.size-sm {
  @apply w-5 h-5;
}

.size-sm .alert-icon {
  @apply w-5 h-5;
}

.size-md {
  @apply w-6 h-6;
}

.size-md .alert-icon {
  @apply w-6 h-6;
}

.size-lg {
  @apply w-8 h-8;
}

.size-lg .alert-icon {
  @apply w-8 h-8;
}

.size-xl {
  @apply w-10 h-10;
}

.size-xl .alert-icon {
  @apply w-10 h-10;
}

/* Severity Colors */
.severity-critical {
  @apply text-red-600 dark:text-red-400;
}

.severity-high {
  @apply text-orange-600 dark:text-orange-400;
}

.severity-medium {
  @apply text-yellow-600 dark:text-yellow-400;
}

.severity-low {
  @apply text-blue-600 dark:text-blue-400;
}

.severity-warning {
  @apply text-yellow-600 dark:text-yellow-400;
}

.severity-success,
.severity-resolved {
  @apply text-green-600 dark:text-green-400;
}

.severity-info {
  @apply text-gray-600 dark:text-gray-400;
}

/* Interactive States */
.clickable {
  @apply cursor-pointer;
}

.clickable:hover {
  @apply scale-110;
}

.clickable:active {
  @apply scale-95;
}

/* Animations */
.animated {
  animation: bounce 2s infinite;
}

.pulsing {
  animation: pulse 2s infinite;
}

.severity-critical.pulsing {
  animation: pulse-critical 1s infinite;
}

.severity-high.pulsing {
  animation: pulse-high 1.5s infinite;
}

/* Alert Count Badge */
.alert-count {
  @apply absolute -top-2 -right-2;
  @apply bg-red-500 text-white text-xs font-bold;
  @apply rounded-full min-w-5 h-5 flex items-center justify-center;
  @apply px-1;
}

.size-xs .alert-count {
  @apply -top-1 -right-1 text-xs min-w-4 h-4;
}

.size-sm .alert-count {
  @apply -top-1.5 -right-1.5 text-xs min-w-4 h-4;
}

.size-lg .alert-count {
  @apply -top-2.5 -right-2.5 text-sm min-w-6 h-6;
}

.size-xl .alert-count {
  @apply -top-3 -right-3 text-sm min-w-6 h-6;
}

/* Status Dot */
.status-dot {
  @apply absolute -bottom-0.5 -right-0.5;
  @apply w-2 h-2 rounded-full border border-white dark:border-gray-800;
}

.status-dot.red {
  @apply bg-red-500;
}

.status-dot.yellow {
  @apply bg-yellow-500;
}

.status-dot.green {
  @apply bg-green-500;
}

.status-dot.blue {
  @apply bg-blue-500;
}

.status-dot.gray {
  @apply bg-gray-500;
}

.size-xs .status-dot {
  @apply w-1.5 h-1.5;
}

.size-lg .status-dot,
.size-xl .status-dot {
  @apply w-3 h-3;
}

/* Keyframe Animations */
@keyframes bounce {
  0%, 20%, 53%, 80%, 100% {
    transform: translate3d(0, 0, 0);
  }
  40%, 43% {
    transform: translate3d(0, -8px, 0);
  }
  70% {
    transform: translate3d(0, -4px, 0);
  }
  90% {
    transform: translate3d(0, -2px, 0);
  }
}

@keyframes pulse {
  0% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
  100% {
    opacity: 1;
  }
}

@keyframes pulse-critical {
  0% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.1);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes pulse-high {
  0% {
    opacity: 1;
  }
  50% {
    opacity: 0.6;
  }
  100% {
    opacity: 1;
  }
}

/* Focus States for Accessibility */
.clickable:focus {
  @apply outline-none ring-2 ring-offset-2;
}

.severity-critical.clickable:focus {
  @apply ring-red-500;
}

.severity-high.clickable:focus {
  @apply ring-orange-500;
}

.severity-medium.clickable:focus {
  @apply ring-yellow-500;
}

.severity-low.clickable:focus {
  @apply ring-blue-500;
}

.severity-warning.clickable:focus {
  @apply ring-yellow-500;
}

.severity-success.clickable:focus,
.severity-resolved.clickable:focus {
  @apply ring-green-500;
}

.severity-info.clickable:focus {
  @apply ring-gray-500;
}

/* Dark Mode Adjustments */
.dark .alert-count {
  @apply border-gray-800;
}

.dark .status-dot {
  @apply border-gray-800;
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .animated,
  .pulsing {
    animation: none;
  }
  
  .clickable:hover {
    transform: none;
  }
  
  .alert-icon-container {
    transition: none;
  }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .severity-critical {
    @apply text-red-700;
  }
  
  .severity-high {
    @apply text-orange-700;
  }
  
  .severity-medium {
    @apply text-yellow-700;
  }
  
  .severity-low {
    @apply text-blue-700;
  }
  
  .severity-warning {
    @apply text-yellow-700;
  }
  
  .severity-success,
  .severity-resolved {
    @apply text-green-700;
  }
  
  .severity-info {
    @apply text-gray-700;
  }
}
</style>