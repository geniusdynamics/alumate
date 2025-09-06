<template>
  <div class="conversion-chart bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <!-- Chart Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ title }}
        </h3>
        <p v-if="subtitle" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
          {{ subtitle }}
        </p>
      </div>

      <!-- Chart Type Controls -->
      <div class="flex items-center gap-2">
        <button
          v-for="type in chartTypes"
          :key="type.value"
          @click="chartType = type.value"
          :class="[
            'px-3 py-2 text-sm font-medium rounded-lg transition-colors',
            chartType === type.value
              ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300'
              : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
          ]"
          :aria-pressed="chartType === type.value"
          :aria-label="`Display as ${type.label}`"
        >
          <component :is="type.icon" class="w-4 h-4" />
          {{ type.label }}
        </button>
      </div>
    </div>

    <!-- Chart Area -->
    <div class="chart-container">
      <!-- Charts Container -->
      <template v-if="data.length > 0">
        <div v-if="chartType === 'line'" class="line-chart" role="img" :aria-label="`${title} line chart showing ${data.length} data points`">
          <svg :width="svgWidth" :height="svgHeight" class="w-full h-full">
            <!-- Grid lines -->
            <defs>
              <pattern id="grid" width="40" height="20" patternUnits="userSpaceOnUse">
                <path d="M 40 0 L 0 0 0 20" fill="none" stroke="#e5e7eb" stroke-width="1" opacity="0.3"/>
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)" />

            <!-- X-axis -->
            <line
              :x1="padding.left"
              :y1="svgHeight - padding.bottom"
              :x2="svgWidth - padding.right"
              :y2="svgHeight - padding.bottom"
              stroke="#374151"
              stroke-width="1"
            />

            <!-- Y-axis -->
            <line
              :x1="padding.left"
              :y1="padding.top"
              :x2="padding.left"
              :y2="svgHeight - padding.bottom"
              stroke="#374151"
              stroke-width="1"
            />

            <!-- Data Line -->
            <path
              :d="linePath"
              fill="none"
              stroke="#3b82f6"
              stroke-width="3"
              stroke-linecap="round"
              stroke-linejoin="round"
            />

            <!-- Data Points -->
            <circle
              v-for="(point, index) in data"
              :key="`point-${index}`"
              :cx="getXCoordinate(index)"
              :cy="getYCoordinate(point.rate)"
              r="6"
              fill="#3b82f6"
              stroke="white"
              stroke-width="2"
              class="hover:stroke-gray-400 cursor-pointer transition-colors"
              :aria-label="`Data point ${index + 1}: ${point.date}, ${point.rate}% conversion rate`"
            />

            <!-- Hover tooltips (placeholder for future enhancement) -->
            <g v-for="(point, index) in data" :key="`tooltip-${index}`" class="hidden">
              <rect
                :x="getXCoordinate(index) - 35"
                :y="getYCoordinate(point.rate) - 45"
                width="70"
                height="35"
                rx="4"
                fill="#1f2937"
                opacity="0.9"
              />
              <text
                :x="getXCoordinate(index)"
                :y="getYCoordinate(point.rate) - 25"
                text-anchor="middle"
                fill="white"
                font-size="12"
              >
                {{ point.rate }}%
              </text>
            </g>
          </svg>
        </div>

        <!-- Bar Chart -->
        <div v-else-if="chartType === 'bar'" class="bar-chart" role="img" :aria-label="`${title} bar chart showing ${data.length} data points`">
          <div class="flex items-end justify-around h-80 w-full">
            <div
              v-for="(point, index) in data"
              :key="`bar-${index}`"
              class="flex-1 flex flex-col items-center mx-1"
              :style="{ height: '100%' }"
            >
              <!-- Bar -->
              <div
                class="bar bg-blue-600 hover:bg-blue-700 rounded-t transition-colors cursor-pointer"
                :style="{
                  height: `${Math.max((point.rate / maxValue) * 100, 2)}%`,
                  width: '100%',
                  maxWidth: '40px'
                }"
                :aria-label="`${point.date}: ${point.rate}% conversion rate`"
              ></div>

              <!-- Label -->
              <div class="text-xs text-gray-600 dark:text-gray-400 mt-2 text-center">
                {{ formatDateLabel(point.date) }}
              </div>
            </div>
          </div>

          <!-- Y-axis labels -->
          <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
            <span>0%</span>
            <span>{{ maxValue.toFixed(1) }}%</span>
          </div>
        </div>

        <!-- Area Chart -->
        <div v-else-if="chartType === 'area'" class="area-chart" role="img" :aria-label="`${title} area chart showing ${data.length} data points`">
          <svg :width="svgWidth" :height="svgHeight" class="w-full h-full">
            <!-- Grid -->
            <defs>
              <pattern id="area-grid" width="40" height="20" patternUnits="userSpaceOnUse">
                <path d="M 40 0 L 0 0 0 20" fill="none" stroke="#e5e7eb" stroke-width="1" opacity="0.3"/>
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#area-grid)" />

            <!-- Area fill -->
            <path
              :d="`${linePath} L ${getXCoordinate(data.length - 1)} ${svgHeight - padding.bottom} L ${getXCoordinate(0)} ${svgHeight - padding.bottom} Z`"
              fill="rgba(59, 130, 246, 0.1)"
            />

            <!-- Area line -->
            <path
              :d="linePath"
              fill="none"
              stroke="#3b82f6"
              stroke-width="2"
            />

            <!-- Data points -->
            <circle
              v-for="(point, index) in data"
              :key="`area-point-${index}`"
              :cx="getXCoordinate(index)"
              :cy="getYCoordinate(point.rate)"
              r="4"
              fill="#3b82f6"
              stroke="white"
              stroke-width="2"
            />
          </svg>
        </div>

        <!-- Combined Chart -->
        <div v-else class="combined-chart" role="img" :aria-label="`${title} combined chart showing conversions and views`">
          <svg :width="svgWidth" :height="svgHeight" class="w-full h-full">
            <!-- Grid -->
            <defs>
              <pattern id="combined-grid" width="40" height="20" patternUnits="userSpaceOnUse">
                <path d="M 40 0 L 0 0 0 20" fill="none" stroke="#e5e7eb" stroke-width="1" opacity="0.3"/>
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#combined-grid)" />

            <!-- Bars for conversions -->
            <rect
              v-for="(point, index) in data"
              :key="`combined-bar-${index}`"
              :x="getXCoordinate(index) - 8"
              :y="getYCoordinate(point.conversions / maxConversions * maxValue)"
              :width="16"
              :height="getBarHeight(point.conversions / maxConversions * maxValue)"
              fill="#10b981"
              opacity="0.8"
            />

            <!-- Line for conversion rate -->
            <path
              :d="rateLinePath"
              fill="none"
              stroke="#3b82f6"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />

            <!-- Axes -->
            <line
              :x1="padding.left"
              :y1="svgHeight - padding.bottom"
              :x2="svgWidth - padding.right"
              :y2="svgHeight - padding.bottom"
              stroke="#374151"
              stroke-width="1"
            />
            <line
              :x1="padding.left"
              :y1="padding.top"
              :x2="padding.left"
              :y2="svgHeight - padding.bottom"
              stroke="#374151"
              stroke-width="1"
            />
          </svg>

          <!-- Legend -->
          <div class="flex justify-center gap-6 mt-4">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 bg-green-500 rounded"></div>
              <span class="text-sm text-gray-600 dark:text-gray-400">Conversions</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
              <span class="text-sm text-gray-600 dark:text-gray-400">Rate</span>
            </div>
          </div>
        </div>
      </template>
      
      <!-- Empty State -->
      <div v-else class="empty-state flex flex-col items-center justify-center h-80 text-gray-500 dark:text-gray-400">
        <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
        <p class="text-lg font-medium">No data available</p>
        <p class="text-sm">Conversion metrics will appear here once data is collected</p>
      </div>
    </div>
  </div>

    <!-- Chart Footer with Summary Stats -->
    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ formatNumber(averageRate) }}%
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-400">
            Average Rate
          </div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ formatNumber(totalConversions) }}
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-400">
            Total Conversions
          </div>
        </div>
        <div class="text-center">
          <div :class="trendClass" class="text-2xl font-bold flex items-center justify-center gap-1">
            <svg v-if="trendDirection === 'up'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
            </svg>
            <svg v-else-if="trendDirection === 'down'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>
            </svg>
            {{ formatNumber(Math.abs(trendValue)) }}%
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-400">
            7-Day Trend
          </div>
        </div>
      </div>
    </div>

    <!-- Accessibility description -->
    <div id="chart-description" class="sr-only">
      This chart displays conversion rate data over time. The x-axis represents dates and the y-axis represents conversion percentages.
      Current average conversion rate is {{ averageRate }}% with a {{ trendDirection }}ward trend of {{ Math.abs(trendValue) }}%.
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

// Icon components
const LineChartIcon = () => import('@heroicons/vue/24/outline/ChartBarIcon').then(m => m.default)
const BarChartIcon = () => import('@heroicons/vue/24/outline/Bars3Icon').then(m => m.default)
const AreaChartIcon = () => import('@heroicons/vue/24/outline/ChartBarSquareIcon').then(m => m.default)
const CombinedChartIcon = () => import('@heroicons/vue/24/outline/Squares2X2Icon').then(m => m.default)

// Types
interface ConversionDataPoint {
  date: string
  conversions: number
  views: number
  rate: number
}

interface Props {
  data: ConversionDataPoint[]
  title: string
  subtitle?: string
  height?: number
}

const props = withDefaults(defineProps<Props>(), {
  subtitle: '',
  height: 400
})

// Reactive data
const chartType = ref<'line' | 'bar' | 'area' | 'combined'>('line')
const svgWidth = ref(800)
const svgHeight = ref(props.height)

const padding = {
  top: 20,
  right: 20,
  bottom: 40,
  left: 50
}

// Chart types configuration
const chartTypes = [
  {
    value: 'line' as const,
    label: 'Line',
    icon: LineChartIcon
  },
  {
    value: 'bar' as const,
    label: 'Bar',
    icon: BarChartIcon
  },
  {
    value: 'area' as const,
    label: 'Area',
    icon: AreaChartIcon
  },
  {
    value: 'combined' as const,
    label: 'Combined',
    icon: CombinedChartIcon
  }
]

// Computed properties
const maxValue = computed(() => {
  if (props.data.length === 0) return 10
  return Math.max(...props.data.map(d => d.rate)) * 1.1 // Add 10% padding
})

const maxConversions = computed(() => {
  if (props.data.length === 0) return 1
  return Math.max(...props.data.map(d => d.conversions))
})

const averageRate = computed(() => {
  if (props.data.length === 0) return 0
  const sum = props.data.reduce((acc, point) => acc + point.rate, 0)
  return sum / props.data.length
})

const totalConversions = computed(() => {
  return props.data.reduce((acc, point) => acc + point.conversions, 0)
})

const trendValue = computed(() => {
  if (props.data.length < 2) return 0

  const recent = props.data.slice(-7) // Last 7 days
  if (recent.length < 2) return 0

  const currentAvg = recent.slice(-3).reduce((acc, point) => acc + point.rate, 0) / 3
  const previousAvg = recent.slice(0, 3).reduce((acc, point) => acc + point.rate, 0) / 3

  return currentAvg - previousAvg
})

const trendDirection = computed<'up' | 'down' | 'neutral'>(() => {
  if (trendValue.value > 0.1) return 'up'
  if (trendValue.value < -0.1) return 'down'
  return 'neutral'
})

const trendClass = computed(() => {
  switch (trendDirection.value) {
    case 'up':
      return 'text-green-600 dark:text-green-400'
    case 'down':
      return 'text-red-600 dark:text-red-400'
    default:
      return 'text-gray-600 dark:text-gray-400'
  }
})

// Path generators for SVG charts
const linePath = computed(() => {
  if (props.data.length === 0) return ''

  let path = ''
  props.data.forEach((point, index) => {
    const x = getXCoordinate(index)
    const y = getYCoordinate(point.rate)
    if (index === 0) {
      path += `M ${x} ${y}`
    } else {
      path += ` L ${x} ${y}`
    }
  })
  return path
})

const rateLinePath = computed(() => {
  if (props.data.length === 0) return ''

  let path = ''
  props.data.forEach((point, index) => {
    const x = getXCoordinate(index)
    const y = getYCoordinate(point.rate)
    if (index === 0) {
      path += `M ${x} ${y}`
    } else {
      path += ` L ${x} ${y}`
    }
  })
  return path
})

// Methods
const getXCoordinate = (index: number): number => {
  const availableWidth = svgWidth.value - padding.left - padding.right
  const sectionWidth = availableWidth / Math.max(props.data.length - 1, 1)
  return padding.left + (index * sectionWidth)
}

const getYCoordinate = (value: number): number => {
  const availableHeight = svgHeight.value - padding.top - padding.bottom
  const ratio = (maxValue.value - value) / maxValue.value
  return padding.top + (ratio * availableHeight)
}

const getBarHeight = (value: number): number => {
  const availableHeight = svgHeight.value - padding.top - padding.bottom
  return (value / maxValue.value) * availableHeight
}

const formatDateLabel = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric'
  })
}

const formatNumber = (num: number): string => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toLocaleString()
}

// Update dimensions on mount and resize
onMounted(() => {
  const updateDimensions = () => {
    const container = document.querySelector('.conversion-chart')
    if (container) {
      const rect = container.getBoundingClientRect()
      svgWidth.value = rect.width || 800
    }
  }

  updateDimensions()
  window.addEventListener('resize', updateDimensions)
})

// Cleanup
onActivated(() => {
  const updateDimensions = () => {
    const container = document.querySelector('.conversion-chart')
    if (container) {
      const rect = container.getBoundingClientRect()
      svgWidth.value = rect.width || 800
    }
  }
  window.addEventListener('resize', updateDimensions)
})

onDeactivated(() => {
  const updateDimensions = () => {
    const container = document.querySelector('.conversion-chart')
    if (container) {
      const rect = container.getBoundingClientRect()
      svgWidth.value = rect.width || 800
    }
  }
  window.removeEventListener('resize', updateDimensions)
})
</script>

<style scoped>
.conversion-chart {
  min-height: 500px;
}

.chart-container {
  min-height: 350px;
}

/* Bar chart styles */
.bar {
  transition: all 0.3s ease;
}

.bar:hover {
  transform: translateY(-2px);
}

/* Line chart interactive elements */
.line-chart circle:hover {
  r: 8;
  stroke-width: 3;
}

/* Custom focus states for accessibility */
.bar:focus {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .bar {
    transition: none;
  }

  .line-chart circle {
    transition: none;
  }
}

/* Dark mode support */
.dark .bar:hover {
  background-color: rgb(59, 130, 246);
}
</style>