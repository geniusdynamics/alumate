<!-- ABOUTME: KPI (Key Performance Indicator) cards component for monitoring dashboard -->
<!-- ABOUTME: Displays important metrics in a grid layout with trend indicators and responsive design -->
<template>
  <div class="kpi-cards-container">
    <div class="kpi-grid">
      <div
        v-for="kpi in kpis"
        :key="kpi.id"
        :class="[
          'kpi-card',
          kpi.status && `kpi-${kpi.status}`,
          { 'kpi-clickable': kpi.clickable }
        ]"
        @click="handleKpiClick(kpi)"
      >
        <!-- KPI Header -->
        <div class="kpi-header">
          <div class="kpi-icon-wrapper">
            <component 
              v-if="kpi.icon" 
              :is="kpi.icon" 
              :class="['kpi-icon', kpi.iconColor || 'text-gray-600']"
            />
            <div v-else :class="['kpi-icon-placeholder', kpi.iconColor || 'bg-gray-400']"></div>
          </div>
          
          <div class="kpi-trend" v-if="kpi.trend">
            <svg 
              v-if="kpi.trend.direction === 'up'" 
              class="trend-icon trend-up" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
            </svg>
            <svg 
              v-else-if="kpi.trend.direction === 'down'" 
              class="trend-icon trend-down" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>
            </svg>
            <svg 
              v-else 
              class="trend-icon trend-neutral" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
            </svg>
            <span class="trend-value">{{ kpi.trend.value }}%</span>
          </div>
        </div>

        <!-- KPI Content -->
        <div class="kpi-content">
          <div class="kpi-value">
            {{ formatValue(kpi.value, kpi.format) }}
          </div>
          <div class="kpi-label">
            {{ kpi.label }}
          </div>
          <div v-if="kpi.subtitle" class="kpi-subtitle">
            {{ kpi.subtitle }}
          </div>
        </div>

        <!-- KPI Footer -->
        <div v-if="kpi.lastUpdated || kpi.target" class="kpi-footer">
          <div v-if="kpi.target" class="kpi-target">
            Target: {{ formatValue(kpi.target, kpi.format) }}
          </div>
          <div v-if="kpi.lastUpdated" class="kpi-updated">
            {{ formatLastUpdated(kpi.lastUpdated) }}
          </div>
        </div>

        <!-- Progress Bar (if applicable) -->
        <div v-if="kpi.progress" class="kpi-progress">
          <div class="progress-bar">
            <div 
              class="progress-fill"
              :style="{ width: `${Math.min(kpi.progress.percentage, 100)}%` }"
              :class="getProgressColor(kpi.progress.percentage)"
            ></div>
          </div>
          <div class="progress-text">
            {{ kpi.progress.percentage }}% of target
          </div>
        </div>

        <!-- Alert Indicator -->
        <div v-if="kpi.alert" class="kpi-alert">
          <svg class="alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
          <span class="alert-text">{{ kpi.alert }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface KpiTrend {
  direction: 'up' | 'down' | 'neutral'
  value: number
}

interface KpiProgress {
  percentage: number
  current: number
  target: number
}

interface Kpi {
  id: string
  label: string
  value: number | string
  format?: 'number' | 'currency' | 'percentage' | 'duration' | 'bytes'
  subtitle?: string
  icon?: any
  iconColor?: string
  trend?: KpiTrend
  status?: 'success' | 'warning' | 'error' | 'info'
  target?: number
  progress?: KpiProgress
  lastUpdated?: string
  alert?: string
  clickable?: boolean
  metadata?: Record<string, any>
}

interface Props {
  kpis: Kpi[]
  columns?: number
  loading?: boolean
}

interface Emits {
  'kpi-click': [kpi: Kpi]
}

const props = withDefaults(defineProps<Props>(), {
  kpis: () => [],
  columns: 4,
  loading: false
})

const emit = defineEmits<Emits>()

// Methods
const formatValue = (value: number | string, format?: string): string => {
  if (typeof value === 'string') return value
  
  switch (format) {
    case 'currency':
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
      }).format(value)
    
    case 'percentage':
      return `${value.toFixed(1)}%`
    
    case 'duration':
      if (value < 1000) return `${value}ms`
      if (value < 60000) return `${(value / 1000).toFixed(1)}s`
      return `${(value / 60000).toFixed(1)}m`
    
    case 'bytes':
      const units = ['B', 'KB', 'MB', 'GB', 'TB']
      let size = value
      let unitIndex = 0
      
      while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024
        unitIndex++
      }
      
      return `${size.toFixed(1)} ${units[unitIndex]}`
    
    case 'number':
    default:
      if (value >= 1000000) {
        return `${(value / 1000000).toFixed(1)}M`
      } else if (value >= 1000) {
        return `${(value / 1000).toFixed(1)}K`
      }
      return value.toLocaleString()
  }
}

const formatLastUpdated = (timestamp: string): string => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  
  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`
  return date.toLocaleDateString()
}

const getProgressColor = (percentage: number): string => {
  if (percentage >= 90) return 'bg-green-500'
  if (percentage >= 70) return 'bg-yellow-500'
  if (percentage >= 50) return 'bg-orange-500'
  return 'bg-red-500'
}

const handleKpiClick = (kpi: Kpi) => {
  if (kpi.clickable) {
    emit('kpi-click', kpi)
  }
}
</script>

<style scoped>
.kpi-cards-container {
  @apply w-full;
}

.kpi-grid {
  @apply grid gap-6;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

.kpi-card {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700;
  @apply p-6 transition-all duration-200 ease-in-out;
  @apply relative overflow-hidden;
}

.kpi-clickable {
  @apply cursor-pointer hover:shadow-md hover:scale-105;
}

.kpi-card:hover {
  @apply border-gray-300 dark:border-gray-600;
}

.kpi-success {
  @apply border-l-4 border-l-green-500;
}

.kpi-warning {
  @apply border-l-4 border-l-yellow-500;
}

.kpi-error {
  @apply border-l-4 border-l-red-500;
}

.kpi-info {
  @apply border-l-4 border-l-blue-500;
}

.kpi-header {
  @apply flex items-start justify-between mb-4;
}

.kpi-icon-wrapper {
  @apply flex-shrink-0;
}

.kpi-icon {
  @apply w-8 h-8;
}

.kpi-icon-placeholder {
  @apply w-8 h-8 rounded-full;
}

.kpi-trend {
  @apply flex items-center gap-1 text-sm font-medium;
}

.trend-icon {
  @apply w-4 h-4;
}

.trend-up {
  @apply text-green-600 dark:text-green-400;
}

.trend-down {
  @apply text-red-600 dark:text-red-400;
}

.trend-neutral {
  @apply text-gray-600 dark:text-gray-400;
}

.trend-value {
  @apply text-xs;
}

.kpi-content {
  @apply mb-4;
}

.kpi-value {
  @apply text-3xl font-bold text-gray-900 dark:text-white mb-2;
}

.kpi-label {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.kpi-subtitle {
  @apply text-xs text-gray-500 dark:text-gray-400 mt-1;
}

.kpi-footer {
  @apply flex items-center justify-between text-xs text-gray-500 dark:text-gray-400;
  @apply pt-4 border-t border-gray-100 dark:border-gray-700;
}

.kpi-target {
  @apply font-medium;
}

.kpi-updated {
  @apply text-right;
}

.kpi-progress {
  @apply mt-4;
}

.progress-bar {
  @apply w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-2;
}

.progress-fill {
  @apply h-2 rounded-full transition-all duration-300 ease-in-out;
}

.progress-text {
  @apply text-xs text-gray-600 dark:text-gray-400 text-center;
}

.kpi-alert {
  @apply absolute top-2 right-2 flex items-center gap-1;
  @apply bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400;
  @apply px-2 py-1 rounded-full text-xs;
}

.alert-icon {
  @apply w-3 h-3;
}

.alert-text {
  @apply font-medium;
}

/* Loading State */
.kpi-card.loading {
  @apply animate-pulse;
}

.kpi-card.loading .kpi-value,
.kpi-card.loading .kpi-label {
  @apply bg-gray-200 dark:bg-gray-700 rounded;
  @apply text-transparent;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .kpi-grid {
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    @apply gap-4;
  }
  
  .kpi-card {
    @apply p-4;
  }
  
  .kpi-value {
    @apply text-2xl;
  }
}

@media (max-width: 768px) {
  .kpi-grid {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    @apply gap-3;
  }
  
  .kpi-card {
    @apply p-3;
  }
  
  .kpi-value {
    @apply text-xl;
  }
  
  .kpi-header {
    @apply mb-3;
  }
}

@media (max-width: 640px) {
  .kpi-grid {
    grid-template-columns: 1fr;
  }
}

/* Dark Mode Enhancements */
.dark .kpi-card {
  @apply bg-gray-800 border-gray-700;
}

.dark .kpi-clickable:hover {
  @apply bg-gray-700 border-gray-600;
}

/* Animation for value changes */
.kpi-value {
  @apply transition-all duration-300 ease-in-out;
}

/* Focus styles for accessibility */
.kpi-clickable:focus {
  @apply outline-none ring-2 ring-blue-500 ring-offset-2;
  @apply ring-offset-white dark:ring-offset-gray-800;
}
</style>